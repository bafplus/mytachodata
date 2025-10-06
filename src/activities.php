<?php
require_once __DIR__ . '/inc/db.php'; // main DB connection

// Start session and check login
if (!isset($_SESSION)) session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = intval($_SESSION['user_id']);
$userDbName = "mytacho_user_" . $userId;

// DB credentials
$dbHost = getenv('DB_HOST') ?: '127.0.0.1';
$dbUser = getenv('DB_USER') ?: 'mytacho_user';
$dbPass = getenv('DB_PASS') ?: 'mytacho_pass';
$pdoOptions = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
];

// Connect to per-user DB
try {
    $userPdo = new PDO(
        "mysql:host={$dbHost};dbname={$userDbName};charset=utf8mb4",
        $dbUser,
        $dbPass,
        $pdoOptions
    );
} catch (PDOException $e) {
    die("Could not connect to user database: " . htmlspecialchars($e->getMessage()));
}

// Handle selected date
$selectedDate = $_GET['date'] ?? null;

// Fetch all activity rows
$activityRows = [];
try {
    $stmt = $userPdo->query("SELECT raw, timestamp FROM card_driver_activity_1 ORDER BY timestamp DESC");
    $activityRows = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Error fetching activities: " . htmlspecialchars($e->getMessage()));
}

// Flatten all activity segments
$activities = [];
$dates = [];
foreach ($activityRows as $row) {
    $raw = json_decode($row['raw'], true);
    if (!$raw || !isset($raw['activity_change_info'])) continue;

    $activityDate = substr($raw['activity_record_date'], 0, 10);
    $dates[$activityDate] = $activityDate;

    if ($selectedDate && $activityDate !== $selectedDate) continue;

    $segments = $raw['activity_change_info'];
    if (count($segments) <= 1) continue;

    $segments = array_slice($segments, 1);

    $previousMinutes = 0;
    $currentType = null;
    $startMinutes = 0;

    foreach ($segments as $segment) {
        $type = $segment['work_type'];
        $endMinutes = $segment['minutes'];

        if ($currentType === null) {
            $currentType = $type;
            $startMinutes = $previousMinutes;
        } elseif ($type !== $currentType) {
            $activities[] = [
                'date' => $activityDate,
                'start_time' => sprintf('%02d:%02d', intdiv($startMinutes, 60), $startMinutes % 60),
                'end_time' => sprintf('%02d:%02d', intdiv($previousMinutes, 60), $previousMinutes % 60),
                'activity_type' => $currentType,
                'duration' => $previousMinutes - $startMinutes,
                'start_min' => $startMinutes,
                'end_min' => $previousMinutes
            ];
            $currentType = $type;
            $startMinutes = $previousMinutes;
        }

        $previousMinutes = $endMinutes;
    }

    $activities[] = [
        'date' => $activityDate,
        'start_time' => sprintf('%02d:%02d', intdiv($startMinutes, 60), $startMinutes % 60),
        'end_time' => sprintf('%02d:%02d', intdiv($previousMinutes, 60), $previousMinutes % 60),
        'activity_type' => $currentType,
        'duration' => $previousMinutes - $startMinutes,
        'start_min' => $startMinutes,
        'end_min' => $previousMinutes
    ];
}

// Correct activity labels and colors
$activityLabels = [
    0 => 'Drive',
    1 => 'Rest',
    2 => 'Work'
    3 => 'Other Work'
];

$activityColors = [
    0 => '#ff9800',  // orange
    1 => '#0000ff',  // blue
    2 => '#ff0000',  // red
    3 => '#add8e6'   // light blue
];

require_once __DIR__ . '/inc/header.php';
require_once __DIR__ . '/inc/sidebar.php';
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <h1 class="m-0">Driver Activities</h1>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid">

            <!-- Day selector -->
            <?php if (!empty($dates)): ?>
                <form method="get" class="mb-3">
                    <label for="date">Select Day:</label>
                    <select name="date" id="date" onchange="this.form.submit()">
                        <option value="">-- All Days --</option>
                        <?php foreach ($dates as $dateOption): ?>
                            <option value="<?= htmlspecialchars($dateOption) ?>" <?= $selectedDate === $dateOption ? 'selected' : '' ?>>
                                <?= htmlspecialchars($dateOption) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            <?php endif; ?>

            <!-- Timeline Graph -->
            <?php if ($selectedDate && !empty($activities)): ?>
            <div class="card card-info mb-4">
                <div class="card-header">Timeline for <?= htmlspecialchars($selectedDate) ?></div>
                <div class="card-body">
                    <canvas id="activityTimeline" height="50"></canvas>
                </div>
            </div>
            <?php endif; ?>

            <?php if (empty($activities)): ?>
                <div class="alert alert-info">No activities found.</div>
            <?php else: ?>
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Start</th>
                            <th>End</th>
                            <th>Activity Type</th>
                            <th>Duration (min)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($activities as $act): ?>
                            <tr>
                                <td><?= htmlspecialchars($act['date']) ?></td>
                                <td><?= htmlspecialchars($act['start_time']) ?></td>
                                <td><?= htmlspecialchars($act['end_time']) ?></td>
                                <td><?= htmlspecialchars($activityLabels[$act['activity_type']] ?? 'Unknown') ?></td>
                                <td><?= htmlspecialchars($act['duration']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-zoom@2.2.0/dist/chartjs-plugin-zoom.min.js"></script>

<?php if ($selectedDate && !empty($activities)): ?>
<script>
const activitiesData = <?php
    $merged = [];
    $last = null;
    foreach ($activities as $act) {
        $seg = ['type' => $act['activity_type'], 'start' => $act['start_min'], 'end' => $act['end_min']];
        if ($last && $last['type'] === $seg['type'] && $last['end'] === $seg['start']) {
            $last['end'] = $seg['end'];
        } else {
            if ($last) $merged[] = $last;
            $last = $seg;
        }
    }
    if ($last) $merged[] = $last;
    echo json_encode($merged);
?>;

document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('activityTimeline').getContext('2d');

    const activityTypes = {0: [], 1: [], 2: [], 3: []};
    activitiesData.forEach(a => activityTypes[a.type].push({x: [a.start, a.end], y: ''}));

    const activityLabels = {0: 'Drive', 1: 'Rest', 2: 'Work', 3: 'Other Work'};
    const activityColors = {0: '#ff9800', 1: '#0000ff', 2: '#ff0000', 3: '#add8e6'};

    const datasets = [];
    for (const [type, data] of Object.entries(activityTypes)) {
        if (data.length > 0) {
            datasets.push({
                label: activityLabels[type] || 'Unknown',
                data: data,
                backgroundColor: activityColors[type] || '#999',
                borderSkipped: false,
                barPercentage: 1.0,
                categoryPercentage: 1.0
            });
        }
    }

    function formatHHMM(minutes) {
        const h = Math.floor(minutes / 60).toString().padStart(2,'0');
        const m = (minutes % 60).toString().padStart(2,'0');
        return `${h}:${m}`;
    }

    function formatDuration(minutes) {
        const h = Math.floor(minutes / 60).toString().padStart(2,'0');
        const m = (minutes % 60).toString().padStart(2,'0');
        return `${h}:${m}`;
    }

    new Chart(ctx, {
        type: 'bar',
        data: {labels: [''], datasets: datasets},
        options: {
            indexAxis: 'y',
            plugins: {
                legend: { display: true },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const range = context.raw.x;
                            const startTime = formatHHMM(range[0]);
                            const endTime = formatHHMM(range[1]);
                            const duration = formatDuration(range[1] - range[0]);
                            return `${context.dataset.label}: ${startTime} → ${endTime} (${duration})`;
                        }
                    }
                },
                zoom: {
                    zoom: {
                        wheel: { enabled: true },
                        pinch: { enabled: true },
                        mode: 'x'
                    },
                    pan: {
                        enabled: true,
                        mode: 'x',
                        modifierKey: 'ctrl'
                    },
                    limits: { x: { min: 0, max: 1440 } }
                }
            },
            responsive: true,
            scales: {
                x: {
                    min: 0,
                    max: 1440,
                    title: { display: true, text: 'Time of Day' },
                    ticks: { stepSize: 120, callback: function(value){ return formatHHMM(value); } }
                },
                y: { display: false }
            }
        }
    });
});
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/inc/footer.php'; ?>

