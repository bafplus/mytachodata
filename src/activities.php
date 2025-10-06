<?php
require_once __DIR__ . '/inc/db.php';

// Start session
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

// Connect to DB
try {
    $userPdo = new PDO(
        "mysql:host={$dbHost};dbname={$userDbName};charset=utf8mb4",
        $dbUser,
        $dbPass,
        $pdoOptions
    );
} catch (PDOException $e) {
    die("Could not connect: " . htmlspecialchars($e->getMessage()));
}

// Handle selected date
$selectedDate = $_GET['date'] ?? null;

// Fetch activity days for calendar
$dates = [];
try {
    $stmt = $userPdo->query("SELECT DISTINCT DATE(JSON_UNQUOTE(JSON_EXTRACT(raw,'$.activity_record_date'))) as day 
                             FROM card_driver_activity_1 
                             ORDER BY day DESC");
    while ($row = $stmt->fetch()) {
        if (!empty($row['day'])) {
            $dates[] = $row['day'];
        }
    }
} catch (PDOException $e) {
    // ignore
}

// Fetch activities (same logic as before)
$activities = [];
try {
    $stmt = $userPdo->query("SELECT raw FROM card_driver_activity_1 ORDER BY timestamp DESC");
    $activityRows = $stmt->fetchAll();

    foreach ($activityRows as $row) {
        $raw = json_decode($row['raw'], true);
        if (!$raw || !isset($raw['activity_change_info'])) continue;

        $activityDate = substr($raw['activity_record_date'], 0, 10);
        if ($selectedDate && $activityDate !== $selectedDate) continue;

        $segments = $raw['activity_change_info'];
        if (count($segments) <= 1) continue;

        $segments = array_slice($segments, 1); // skip index 0

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
                    'duration' => $previousMinutes - $startMinutes
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
            'duration' => $previousMinutes - $startMinutes
        ];
    }
} catch (PDOException $e) {
    // ignore
}

$activityLabels = [
    0 => 'Rest/Unknown',
    1 => 'Available',
    2 => 'Driving',
    3 => 'Other Work'
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

      <!-- Compact Calendar -->
      <div class="card card-primary mb-4">
        <div class="card-header">Activity Calendar</div>
        <div class="card-body p-2">
          <div id="calendar" style="max-width: 500px; margin: auto;"></div>
        </div>
      </div>

      <!-- Activities Table -->
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

<!-- FullCalendar -->
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css" rel="stylesheet"/>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>

<style>
  /* Mini calendar styling */
  #calendar {
    font-size: 0.75rem;
  }
  .fc .fc-toolbar-title {
    font-size: 1rem;
  }
  .fc .fc-daygrid-day-frame {
    padding: 2px !important;
    min-height: 50px !important;
  }
  .fc .fc-daygrid-day-number {
    font-size: 0.7rem;
    padding: 2px !important;
  }
  .fc-daygrid-event-dot {
    display: none !important;
  }
  .fc-daygrid-event {
    display: flex !important;
    justify-content: center;
    align-items: center;
    background: none !important;
  }
  .fc-daygrid-event::after {
    content: "";
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: green;
  }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');
    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        height: 'auto',
        aspectRatio: 1.2,
        headerToolbar: {
            left: 'prev,next',
            center: 'title',
            right: ''
        },
        events: [
            <?php foreach ($dates as $date): ?>
            {
                start: '<?= $date ?>',
                url: '?date=<?= $date ?>'
            },
            <?php endforeach; ?>
        ]
    });
    calendar.render();
});
</script>

<?php require_once __DIR__ . '/inc/footer.php'; ?>
