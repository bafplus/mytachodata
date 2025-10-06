<?php
// Example: Fetch available dates from DB
// In real usage, replace this with your query:
// SELECT DISTINCT DATE(timestamp) as activity_date FROM card_driver_activity_1
$dates = [
    "2025-10-01",
    "2025-10-02",
    "2025-10-03",
    "2025-10-05",
];

// Which date is currently selected
$selectedDate = $_GET['date'] ?? date("Y-m-d");

// Example: load raw JSON for that day (in real code, fetch from DB)
$rawJson = '{
  "activity_record_date": "2025-10-03T00:00:00Z",
  "activity_change_info": [
    {"work_type":0,"minutes":0},
    {"work_type":2,"minutes":195},
    {"work_type":3,"minutes":199},
    {"work_type":0,"minutes":221},
    {"work_type":3,"minutes":226},
    {"work_type":2,"minutes":251},
    {"work_type":0,"minutes":255},
    {"work_type":3,"minutes":257},
    {"work_type":2,"minutes":259},
    {"work_type":3,"minutes":265}
  ]
}';
$data = json_decode($rawJson, true);
$records = $data['activity_change_info'] ?? [];

// Remove the very first "0" record
if (count($records) > 0 && $records[0]['minutes'] === 0) {
    array_shift($records);
}

// Build table rows
$tableRows = [];
for ($i = 0; $i < count($records) - 1; $i++) {
    $start = $records[$i]['minutes'];
    $end   = $records[$i+1]['minutes'];
    $duration = $end - $start;

    $tableRows[] = [
        'Work Type' => $records[$i]['work_type'],
        'Start (min)' => $start,
        'End (min)' => $end,
        'Total (min)' => $duration
    ];
}
?>

<!-- Calendar -->
<div class="card card-primary mb-4">
  <div class="card-header">Activity Calendar</div>
  <div class="card-body">
    <div id="calendar"></div>
  </div>
</div>

<!-- Table -->
<div class="card card-secondary">
  <div class="card-header">Activity for <?= htmlspecialchars($selectedDate) ?></div>
  <div class="card-body">
    <table class="table table-bordered table-striped">
      <thead>
        <tr>
          <th>Work Type</th>
          <th>Start (min)</th>
          <th>End (min)</th>
          <th>Total (min)</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($tableRows as $row): ?>
        <tr>
          <td><?= $row['Work Type'] ?></td>
          <td><?= $row['Start (min)'] ?></td>
          <td><?= $row['End (min)'] ?></td>
          <td><?= $row['Total (min)'] ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- FullCalendar -->
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css" rel="stylesheet"/>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>

<style>
  /* Dot style instead of blocks */
  .fc-daygrid-event {
    display: flex;
    justify-content: center;
    align-items: center;
    font-size: 8px;
    line-height: 8px;
    height: 8px;
    width: 8px;
    margin: 0 auto;
    border-radius: 50%;
    background-color: green !important;
  }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
  var calendarEl = document.getElementById('calendar');
  var calendar = new FullCalendar.Calendar(calendarEl, {
    initialView: 'dayGridMonth',
    height: 'auto',
    headerToolbar: {
      left: 'prev,next today',
      center: 'title',
      right: ''
    },
    events: [
      <?php foreach ($dates as $date): ?>
      {
        start: '<?= $date ?>',
        display: 'list-item', // shows as small marker
        url: '?date=<?= $date ?>'
      },
      <?php endforeach; ?>
    ]
  });
  calendar.render();
});
</script>
