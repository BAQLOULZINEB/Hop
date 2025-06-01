<?php
require_once '../../backend/auth/session_handler.php';
checkRole('medecin');

$doctor_name = htmlspecialchars($_SESSION['user']['nom']);
$medecin_id = $_SESSION['user']['id'];

// Get current view and date from GET params
$view = isset($_GET['view']) ? $_GET['view'] : 'month';
$year = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));
$month = isset($_GET['month']) ? intval($_GET['month']) : intval(date('n'));
$day = isset($_GET['day']) ? intval($_GET['day']) : intval(date('j'));

// Fetch all confirmed appointments for this doctor (past and future)
try {
    $db = new PDO("mysql:host=localhost;dbname=medical_system", "root", "");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->exec("SET NAMES utf8mb4");
    $sql = "SELECT u.id as patient_id, u.nom, u.email, r.date_rendezvous, r.statut
            FROM rendez_vous r
            JOIN patient p ON r.patient_id = p.id
            JOIN utilisateur u ON p.id = u.id
            WHERE r.medecin_id = :medecin_id 
            AND r.statut = 'confirmé'
            ORDER BY r.date_rendezvous ASC";
    $stmt = $db->prepare($sql);
    $stmt->execute([
        ':medecin_id' => $medecin_id
    ]);
    $all_appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $all_appointments = [];
}

// Group appointments by date
$appointments_by_date = [];
foreach ($all_appointments as $apt) {
    $date = substr($apt['date_rendezvous'], 0, 10);
    if (!isset($appointments_by_date[$date])) $appointments_by_date[$date] = [];
    $appointments_by_date[$date][] = $apt;
}

// Color palette
$colors = [
    '#2ecc71', '#3498db', '#e67e22', '#e74c3c', '#9b59b6', '#f1c40f', '#16a085', '#34495e'
];

// Helper for navigation
function build_url($params) {
    $base = strtok($_SERVER["REQUEST_URI"], '?');
    $query = array_merge($_GET, $params);
    return $base . '?' . http_build_query($query);
}

// Dummy event data for modal (for demonstration)
$dummy_event = [
    'title' => 'Consultation',
    'date' => date('Y-m-d'),
    'start' => '09:00',
    'end' => '09:30',
    'patient' => 'John Doe'
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Schedule - Doctor Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Bootstrap 5 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css_files/all.min.css">
    <style>
        .calendar-header-bar {
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 1.5rem; flex-wrap: wrap;
        }
        .calendar-header-bar .btn-group { margin-right: 1rem; }
        .calendar-header-bar .form-select, .calendar-header-bar .form-control { width: auto; display: inline-block; }
        .calendar-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 1px; background: #e9ecef; border-radius: 8px; }
        .calendar-day-header, .calendar-cell { background: #fff; min-height: 110px; border-radius: 6px; padding: 8px; }
        .calendar-day-header { font-weight: bold; text-align: center; background: #f8f9fa; color: #0e2f44; }
        .calendar-cell.today { border: 2px solid #2ecc71; background: #eafaf1; }
        .calendar-cell .date-num { font-weight: bold; color: #0e2f44; }
        .event-preview {
            display: block; margin: 4px 0; padding: 4px 8px; border-radius: 4px; color: #fff; font-size: 0.95em;
            text-decoration: none; font-weight: 500; cursor: pointer; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .calendar-week-grid { display: grid; grid-template-columns: 60px repeat(7, 1fr); }
        .calendar-week-hour { background: #f8f9fa; text-align: right; padding: 2px 6px; font-size: 0.9em; color: #888; }
        .calendar-week-cell { min-height: 48px; border: 1px solid #e9ecef; position: relative; }
        .calendar-week-cell .event-block {
            position: absolute; left: 2px; right: 2px; top: 2px; min-height: 20px; border-radius: 4px; color: #fff; font-size: 0.95em; padding: 2px 6px;
            z-index: 2; font-weight: 500; cursor: pointer;
        }
        .calendar-day-timeline { border-left: 3px solid #2ecc71; margin-left: 30px; padding-left: 20px; }
        .calendar-day-timeline .event-block { margin-bottom: 16px; }
        .sidebar-events { max-height: 80vh; overflow-y: auto; background: #f8f9fa; border-radius: 8px; padding: 1rem; }
        .sidebar-events .event-preview { margin-bottom: 8px; }
        @media (max-width: 900px) {
            .calendar-header-bar { flex-direction: column; align-items: flex-start; }
            .calendar-header-bar .btn-group { margin-bottom: 1rem; }
            .calendar-grid, .calendar-week-grid { font-size: 0.95em; }
        }
    </style>
</head>
<body class="bg-light">
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-lg-9">
            <!-- Calendar Header -->
            <div class="calendar-header-bar mb-3">
                <div>
                    <form class="d-inline-flex align-items-center" method="get" action="">
                        <input type="hidden" name="view" value="<?= htmlspecialchars($view) ?>">
                        <select name="month" class="form-select me-2" onchange="this.form.submit()">
                            <?php for ($m=1; $m<=12; $m++): ?>
                                <option value="<?= $m ?>" <?= $m==$month?'selected':'' ?>><?= date('F', mktime(0,0,0,$m,1)) ?></option>
                            <?php endfor; ?>
                        </select>
                        <select name="year" class="form-select me-2" onchange="this.form.submit()">
                            <?php for ($y=date('Y')-2; $y<=date('Y')+2; $y++): ?>
                                <option value="<?= $y ?>" <?= $y==$year?'selected':'' ?>><?= $y ?></option>
                            <?php endfor; ?>
                        </select>
                        <button type="submit" class="btn btn-outline-secondary me-2">Go</button>
                    </form>
                    <div class="btn-group ms-2" role="group">
                        <a href="<?= build_url(['view'=>'month']) ?>" class="btn btn-outline-primary<?= $view=='month'?' active':'' ?>">Month</a>
                        <a href="<?= build_url(['view'=>'week','year'=>$year,'month'=>$month,'day'=>$day]) ?>" class="btn btn-outline-primary<?= $view=='week'?' active':'' ?>">Week</a>
                        <a href="<?= build_url(['view'=>'day','year'=>$year,'month'=>$month,'day'=>$day]) ?>" class="btn btn-outline-primary<?= $view=='day'?' active':'' ?>">Day</a>
                    </div>
                </div>
                <div>
                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addEventModal"><i class="fa fa-plus"></i> Add Event</button>
                </div>
            </div>

            <!-- Calendar Views -->
            <?php
            // Month View
            if ($view == 'month') {
                $first_day = mktime(0,0,0,$month,1,$year);
                $days_in_month = date('t', $first_day);
                $start_weekday = date('w', $first_day); // 0=Sunday
                $today_str = date('Y-m-d');
                echo '<div class="calendar-grid mb-4">';
                // Header
                foreach (['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $d) {
                    echo '<div class="calendar-day-header">'.$d.'</div>';
                }
                // Empty cells before first day
                for ($i=0; $i<$start_weekday; $i++) echo '<div class="calendar-cell"></div>';
                // Days
                for ($d=1; $d<=$days_in_month; $d++) {
                    $date_str = sprintf('%04d-%02d-%02d', $year, $month, $d);
                    $is_today = ($date_str == $today_str);
                    echo '<div class="calendar-cell'.($is_today?' today':'').'">';
                    echo '<div class="date-num">'.$d.'</div>';
                    if (isset($appointments_by_date[$date_str])) {
                        foreach ($appointments_by_date[$date_str] as $idx=>$apt) {
                            $color = $colors[$apt['patient_id'] % count($colors)];
                            $time = substr($apt['date_rendezvous'],11,5);
                            echo '<div class="event-preview" style="background:'.$color.'">';
                            echo '<b>'.$time.'</b> - '.htmlspecialchars($apt['nom']);
                            echo '</div>';
                        }
                    }
                    echo '</div>';
                }
                // Fill out last week
                $cells = $start_weekday + $days_in_month;
                for ($i=0; $i<(7-($cells%7))%7; $i++) echo '<div class="calendar-cell"></div>';
                echo '</div>';
            }
            // Week View
            elseif ($view == 'week') {
                $current = mktime(0,0,0,$month,$day,$year);
                $start = strtotime('last sunday', $current);
                $today_str = date('Y-m-d');
                echo '<div class="calendar-week-grid mb-4">';
                // Header row
                echo '<div></div>';
                for ($i=0; $i<7; $i++) {
                    $date = strtotime("+$i day", $start);
                    $date_str = date('Y-m-d', $date);
                    $is_today = ($date_str == $today_str);
                    echo '<div class="calendar-day-header'.($is_today?' today':'').'">'.date('D j', $date).'</div>';
                }
                // Hour rows
                for ($h=6; $h<=20; $h++) { // 6am to 8pm
                    echo '<div class="calendar-week-hour">'.sprintf('%02d:00', $h).'</div>';
                    for ($i=0; $i<7; $i++) {
                        $date = strtotime("+$i day", $start);
                        $date_str = date('Y-m-d', $date);
                        echo '<div class="calendar-week-cell">';
                        if (isset($appointments_by_date[$date_str])) {
                            foreach ($appointments_by_date[$date_str] as $idx=>$apt) {
                                $event_start = intval(substr($apt['date_rendezvous'],11,2));
                                if ($event_start == $h) {
                                    $color = $colors[$apt['patient_id'] % count($colors)];
                                    $time = substr($apt['date_rendezvous'],11,5);
                                    $end = substr($apt['date_rendezvous'],11,5); // For demo, not using real end time
                                    echo '<div class="event-block" style="background:'.$color.';top:2px;">';
                                    echo '<b>'.$time.'</b> - '.htmlspecialchars($apt['nom']);
                                    echo '</div>';
                                }
                            }
                        }
                        echo '</div>';
                    }
                }
                echo '</div>';
            }
            // Day View
            else {
                $date_str = sprintf('%04d-%02d-%02d', $year, $month, $day);
                $is_today = ($date_str == date('Y-m-d'));
                echo '<div class="calendar-day-timeline">';
                if (isset($appointments_by_date[$date_str])) {
                    foreach ($appointments_by_date[$date_str] as $idx=>$apt) {
                        $color = $colors[$apt['patient_id'] % count($colors)];
                        $time = substr($apt['date_rendezvous'],11,5);
                        echo '<div class="event-block mb-2" style="background:'.$color.'">';
                        echo '<b>'.$time.'</b> - '.htmlspecialchars($apt['nom']);
                        echo '</div>';
                    }
                } else {
                    echo '<div style="margin:20px 0;color:#888;">No appointments for this day.</div>';
                }
                echo '</div>';
            }
            ?>
        </div>
        <!-- Sidebar: Upcoming Events -->
        <div class="col-lg-3">
            <div class="sidebar-events shadow-sm">
                <h5 class="mb-3">Upcoming Appointments</h5>
                <?php
                $now = date('Y-m-d H:i:s');
                $future_events = array_filter($all_appointments, function($apt) use ($now) {
                    return $apt['date_rendezvous'] >= $now;
                });
                if (count($future_events) == 0) {
                    echo '<div class="text-muted">No upcoming appointments.</div>';
                } else {
                    foreach (array_slice($future_events,0,10) as $apt) {
                        $color = $colors[$apt['patient_id'] % count($colors)];
                        $date = date('D, M j', strtotime($apt['date_rendezvous']));
                        $time = substr($apt['date_rendezvous'],11,5);
                        echo '<div class="event-preview" style="background:'.$color.'">';
                        echo '<b>'.$date.' '.$time.'</b> - '.htmlspecialchars($apt['nom']);
                        echo '</div>';
                    }
                }
                ?>
            </div>
        </div>
    </div>
</div>

<!-- Add Event Modal (dummy logic for now) -->
<div class="modal fade" id="addEventModal" tabindex="-1" aria-labelledby="addEventModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="addEventModalLabel">Add Appointment</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Patient Name</label>
          <input type="text" class="form-control" value="<?= htmlspecialchars($dummy_event['patient']) ?>" disabled>
        </div>
        <div class="mb-3">
          <label class="form-label">Date</label>
          <input type="date" class="form-control" value="<?= htmlspecialchars($dummy_event['date']) ?>">
        </div>
        <div class="mb-3 row">
          <div class="col">
            <label class="form-label">Start Time</label>
            <input type="time" class="form-control" value="<?= htmlspecialchars($dummy_event['start']) ?>">
          </div>
          <div class="col">
            <label class="form-label">End Time</label>
            <input type="time" class="form-control" value="<?= htmlspecialchars($dummy_event['end']) ?>">
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label">Title</label>
          <input type="text" class="form-control" value="<?= htmlspecialchars($dummy_event['title']) ?>">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-success" data-bs-dismiss="modal">Add (Demo)</button>
      </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>