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
<html dir="ltr" lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- main css file -->
    <link rel="preload" href="../images/background_page.jpg" as="image">
    <link rel="stylesheet" href="../css_files/master.css">
    <!-- font awesome -->
    <link rel="stylesheet" href="../css_files/all.min.css">
    <!-- fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Arabic:wght@100..900&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    <title>Schedule - Doctor Dashboard</title>
    <!-- Bootstrap 5 CDN for calendar -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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

        /* Improved navigation styles */
        .dashboard {
            width: 250px;
            background: #2c3e50;
            color: white;
            padding: 15px;
            transition: all 0.3s ease;
        }
        .dashboard.collapsed {
            width: 70px;
        }
        .dashboard .title {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        .dashboard .title img {
            width: 40px;
            margin-right: 10px;
        }
        .dashboard .title h2 {
            margin: 0;
            font-size: 1.5rem;
            white-space: nowrap;
            overflow: hidden;
        }
        .dashboard .toggle {
            margin-left: auto;
            cursor: pointer;
            font-size: 1.2rem;
        }
        .dashboard .links {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .dashboard .links li {
            margin-bottom: 5px;
        }
        .dashboard .links a {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: all 0.3s ease;
        }
        .dashboard .links a:hover {
            background: rgba(255, 255, 255, 0.1);
        }
        .dashboard .links a.active {
            background: #3498db;
        }
        .dashboard .links i {
            width: 20px;
            margin-right: 10px;
        }
        .dashboard.collapsed .links span {
            display: none;
        }
        .dashboard.collapsed .title h2 {
            display: none;
        }

        /* Dashboard Statistics */
        .details {
            margin-bottom: 2rem;
        }
        .main-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        .m-d {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
        }
        .m-d i {
            font-size: 2rem;
            margin-right: 1rem;
            color: #3498db;
        }
        .stat p {
            margin: 0;
        }
        .stat .number {
            font-size: 1.5rem;
            font-weight: bold;
            color: #2c3e50;
        }

        /* Collapsible Sidebar */
        .sidebar-toggle {
            position: fixed;
            right: 20px;
            bottom: 20px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            font-size: 1.2rem;
            cursor: pointer;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .sidebar-events {
            position: fixed;
            right: -300px;
            top: 0;
            width: 300px;
            height: 100vh;
            background: white;
            box-shadow: -2px 0 10px rgba(0,0,0,0.1);
            transition: right 0.3s ease;
            z-index: 999;
            padding: 20px;
        }
        .sidebar-events.active {
            right: 0;
        }
        .sidebar-events .close-sidebar {
            position: absolute;
            top: 10px;
            right: 10px;
            background: none;
            border: none;
            font-size: 1.2rem;
            cursor: pointer;
            color: #666;
        }
    </style>
</head>
<body style="background-image: url('../images/background_page.jpg'); background-color: rgba(12, 36, 54, 0.55); background-position: center; background-size: cover; background-repeat: no-repeat;">   
    <div class="page">
        <div class="dashboard">
            <div class="title">
                <img class="logo" src="../images/download__15__14-removebg-preview.png" alt="">
                <h2>HopCare</h2>
                <i class="fa-solid fa-bars toggle"></i>
            </div>
            <ul class="links">
                <li>
                    <a href="doctor_dashboard.php">
                        <i class="fa-solid fa-cubes fa-fw"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="my_patients.php">
                        <i class="fa-solid fa-people-arrows fa-fw"></i>
                        <span>My Patients</span>
                    </a>
                </li>
                <li>
                    <a href="appointments.php">
                        <i class="fa-solid fa-calendar-check fa-fw"></i>
                        <span>Appointments</span>
                    </a>
                </li>
                <li>
                    <a href="prescriptions.php">
                        <i class="fa-solid fa-prescription fa-fw"></i>
                        <span>Prescriptions</span>
                    </a>
                </li>
                <li>
                    <a href="medical_records.php">
                        <i class="fa-solid fa-file-medical fa-fw"></i>
                        <span>Medical Records</span>
                    </a>
                </li>
                <li>
                    <a href="schedule.php">
                        <i class="fa-solid fa-clock fa-fw"></i>
                        <span>Schedule</span>
                    </a>
                </li>
                <li>
                    <a href="messages.php">
                        <i class="fa-solid fa-message fa-fw"></i>
                        <span>Messages</span>
                    </a>
                </li>
            </ul>
            <form method="post" class="log-out">
                <button type="submit" name="logout">
                    <i class="fa-solid fa-arrow-right-from-bracket fa-fw"></i>
                    <span>Logout</span>
                </button>
            </form>
        </div>
        <div class="content">
            <div class="header pro-header">
                <div class="header-left">
                    <img src="../images/download__15__14-removebg-preview.png" alt="Logo" class="header-logo">
                    <div class="welcome">
                        <h1>Welcome Dr. <span id="doctor-name"><?php echo $doctor_name; ?></span></h1>
                        <span class="subtitle">Doctor Dashboard</span>
                    </div>
                </div>
                <div class="header-center">
                    <form action="" method="post" class="search-bar">
                        <input type="search" name="search_query" placeholder="Search patients or appointments">
                        <button type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
                    </form>
                </div>
                <div class="header-right">
                    <div class="profile-menu">
                        <img src="../images/avatar.jpg" alt="Profile" class="avatar">
                        <span class="profile-name">Dr. <?php echo $doctor_name; ?></span>
                        <i class="fa-solid fa-chevron-down"></i>
                        <div class="profile-dropdown">
                            <ul>
                                <li><a href="profile.php">My Profile</a></li>
                                <li><a href="settings.php">Settings</a></li>
                                <li>
                                    <form method="post">
                                        <button type="submit" name="logout">Logout</button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Calendar Header and Views (keep the new calendar code here) -->
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
                        <div class="sidebar-events">
                            <button class="close-sidebar"><i class="fa-solid fa-times"></i></button>
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

    <!-- Add sidebar toggle button -->
    <button class="sidebar-toggle" id="sidebarToggle">
        <i class="fa-solid fa-calendar"></i>
    </button>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle dashboard collapse
        document.querySelector('.toggle').addEventListener('click', function() {
            document.querySelector('.dashboard').classList.toggle('collapsed');
        });

        // Toggle upcoming appointments sidebar
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebar = document.querySelector('.sidebar-events');
        const closeSidebar = document.querySelector('.close-sidebar');

        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.add('active');
        });

        closeSidebar.addEventListener('click', () => {
            sidebar.classList.remove('active');
        });

        // Close sidebar when clicking outside
        document.addEventListener('click', (e) => {
            if (!sidebar.contains(e.target) && !sidebarToggle.contains(e.target)) {
                sidebar.classList.remove('active');
            }
        });

        // Set active link in navigation
        const currentPage = window.location.pathname.split('/').pop();
        document.querySelectorAll('.links a').forEach(link => {
            if (link.getAttribute('href') === currentPage) {
                link.classList.add('active');
            }
        });
    </script>
</body>
</html>