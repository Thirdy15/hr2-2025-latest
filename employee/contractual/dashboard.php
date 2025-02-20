<?php
session_start();
if (!isset($_SESSION['e_id'])) {
    header("Location: ../../login.php"); // Redirect to login if not logged in
    exit();
}

include '../../db/db_conn.php';

$employeeId = $_SESSION['e_id'];


// Fetch notifications
function fetchNotifications($conn, $userId) {
    $sql = "SELECT ln.message, er.firstname, er.lastname 
            FROM leave_notifications ln
            JOIN employee_register er ON ln.user_id = er.e_id
            WHERE ln.user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    $notifications = [];
    while ($row = $result->fetch_assoc()) {
        $notifications[] = $row;
    }

    $stmt->close();
    return $notifications;
}

$notifications = fetchNotifications($conn, $employeeId);

// Fetch the average of the employee's evaluations
$sql = "SELECT 
            AVG(quality) AS avg_quality, 
            AVG(communication_skills) AS avg_communication_skills, 
            AVG(teamwork) AS avg_teamwork, 
            AVG(punctuality) AS avg_punctuality, 
            AVG(initiative) AS avg_initiative,
            COUNT(*) AS total_evaluations 
        FROM admin_evaluations 
        WHERE e_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $employeeId);
$stmt->execute();
$result = $stmt->get_result();

// Check if evaluations exist
if ($result->num_rows > 0) {
    $evaluation = $result->fetch_assoc();

    // Calculate the total average
    $totalAverage = (
        $evaluation['avg_quality'] +
        $evaluation['avg_communication_skills'] +
        $evaluation['avg_teamwork'] +
        $evaluation['avg_punctuality'] +
        $evaluation['avg_initiative']
    ) / 5;
} else {
    echo "No evaluations found.";
    exit;
}

// Fetch user info
$sql = "SELECT firstname, middlename, lastname, email, role, position, pfp FROM employee_register WHERE e_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $employeeId);
$stmt->execute();
$result = $stmt->get_result();
$employeeInfo = $result->fetch_assoc();
$stmt->close();
$conn->close();

// Set the profile picture, default if not provided
$profilePicture = !empty($employeeInfo['pfp']) ? $employeeInfo['pfp'] : '../../img/defaultpfp.png';
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="" />
    <meta name="author" content="" />
    <title>Employee Dashboard | HR2</title>
    <link href="../../css/styles.css" rel="stylesheet" />
    <link href="../../css/calendar.css" rel="stylesheet"/>
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet'/>
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>

    <style>
        .collapse {
            transition: width 3s ease;
        }

        #searchInput.collapsing {
            width: 0;
        }

        #searchInput.collapse.show {
            width: 250px; /* Adjust the width as needed */
        }

        .search-bar {
            position: relative;
            width: 100%;
            max-width: 400px;
            margin: 0 auto;
        }

        #search-results {
            position: absolute;
            width: 100%;
            z-index: 1000;
            display: none; /* Hidden by default */
        }

        #search-results a {
            text-decoration: none;
        }

        .form-control:focus + #search-results {
            display: block; /* Show the results when typing */
        }
        

          /* CSS for background blur */
  .blur-background {
    filter: blur(8px); /* You can adjust the blur strength */
    transition: filter 0.3s ease;
  }
    </style>


</head>

<body class="sb-nav-fixed bg-black">
    <nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark border-bottom border-1 border-secondary">
        <a class="navbar-brand ps-3 text-muted" href="../../employee/contractual/dashboard.php">Employee Portal</a>
        <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle" href="#!"><i class="fas fa-bars text-light"></i></button>
        <div class="d-flex ms-auto me-0 me-md-3 my-2 my-md-0 align-items-center">
            <div class="text-light me-3 p-2 rounded shadow-sm bg-gradient" id="currentTimeContainer" 
            style="background: linear-gradient(45deg, #333333, #444444); border-radius: 5px;">
                <span class="d-flex align-items-center">
                    <span class="pe-2">
                        <i class="fas fa-clock"></i> 
                        <span id="currentTime">00:00:00</span>
                    </span>
                    <button class="btn btn-outline-secondary btn-sm ms-2 text-light" title="Calendar" type="button" onclick="toggleCalendar()">
                        <i class="fas fa-calendar-alt"></i>
                        <span id="currentDate">00/00/0000</span>
                    </button>
                </span>
            </div>
            <div class="dropdown search-container" style="position: relative;">
                <form class="d-none d-md-inline-block form-inline">
                    <div class="input-group">
                        <!-- Search Input -->
                        <input class="form-control collapse" id="searchInput" type="text" placeholder="Search for..." aria-label="Search for..." aria-describedby="btnNavbarSearch" data-bs-toggle="dropdown" aria-expanded="false" />
                        <button class="btn btn-outline-secondary rounded" id="btnNavbarSearch" type="button" data-bs-toggle="collapse" data-bs-target="#searchInput" aria-expanded="false" aria-controls="searchInput">
                            <i id="searchIcon" class="fas fa-search"></i> <!-- Initial Icon -->
                        </button>
                    </div>
                    <ul id="searchResults" class="dropdown-menu list-group mt-2 bg-transparent" style="width: 100%;"></ul>
                </form>
            </div>
             <!-- Notification Bell -->
             <div class="ms-3 dropdown">
                <button class="btn btn-outline-secondary btn-sm text-light" type="button" id="notificationDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-bell"></i>
                    <span class="badge bg-danger" id="notificationCount">0</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="notificationDropdown" id="notificationList">
                    <!-- Notifications will be dynamically added here -->
                </ul>
            </div>
        </div>
    </nav>
    <div id="layoutSidenav">
        <div id="layoutSidenav_nav">
            <nav class="sb-sidenav accordion sb-sidenav-dark" id="sidenavAccordion">
                <div class="sb-sidenav-menu">
                    <div class="nav">
                         <div class="sb-sidenav-menu-heading text-center text-muted">Profile</div>  
                        <ul class="navbar-nav ms-auto ms-md-0 me-3 me-lg-4">
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle text-light d-flex justify-content-center ms-4" id="navbarDropdown" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <img src="<?php echo (!empty($employeeInfo['pfp']) && $employeeInfo['pfp'] !== 'defaultpfp.png') 
                                        ? htmlspecialchars($employeeInfo['pfp']) 
                                        : '../../img/defaultpfp.jpg'; ?>" 
                                        class="rounded-circle border border-light" width="120" height="120" alt="" />
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                                    <li><a class="dropdown-item loading" href="../../employee/contractual/profile.php">Profile</a></li>
                                    <li><a class="dropdown-item" href="#!">Settings</a></li>
                                    <li><a class="dropdown-item" href="#!">Activity Log</a></li>
                                    <li><hr class="dropdown-divider" /></li>
                                    <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#logoutModal">Logout</a></li>
                                </ul>
                            </li>
                            <li class="nav-item text-light d-flex ms-3 flex-column align-items-center text-center">
                                <span class="big text-light mb-1">
                                    <p><?php echo htmlspecialchars($employeeInfo['firstname'] . ' ' . $employeeInfo['middlename'] . ' ' . $employeeInfo['lastname']); ?></p>
                                </span>
                                <span class="big text-light">
                                    <p><?php echo htmlspecialchars($employeeInfo['position']); ?></p>
                                </span>
                            </li>
                        </ul>
                        <div class="sb-sidenav-menu-heading text-center text-muted border-top border-1 border-secondary mt-3">Employee Dashboard</div>
                        <a class="nav-link text-light loading" href="../../employee/contractual/dashboard.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>
                            Dashboard
                        </a>           
                        <a class="nav-link collapsed text-light" href="#" data-bs-toggle="collapse" data-bs-target="#collapseTAD" aria-expanded="false" aria-controls="collapseTAD">
                            <div class="sb-nav-link-icon"><i class="fa fa-address-card"></i></div>
                            Time and Attendance
                            <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                        </a>
                        <div class="collapse" id="collapseTAD" aria-labelledby="headingOne" data-bs-parent="#sidenavAccordion">
                            <nav class="sb-sidenav-menu-nested nav">
                                <a class="nav-link text-light loading" href="../../employee/contractual/attendance.php">Attendance Scanner</a>
                                <a class="nav-link text-light loading" href="">View Attendance Record</a>
                            </nav>
                        </div>
                        
                        <a class="nav-link collapsed text-light" href="#" data-bs-toggle="collapse" data-bs-target="#collapsePM" aria-expanded="false" aria-controls="collapsePM">
                            <div class="sb-nav-link-icon"><i class="fas fa-line-chart"></i></div>
                            Performance Management
                            <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                        </a>
                        <div class="collapse" id="collapsePM" aria-labelledby="headingOne" data-bs-parent="#sidenavAccordion">
                            <nav class="sb-sidenav-menu-nested nav">
                                <a class="nav-link text-light loading" href="../../employee/contractual/kpi.php">Performance</a>
                                <a class="nav-link text-light loading" href="../../employee/contractual/evaluation.php">Evaluation Ratings</a>
                            </nav>
                        </div>
                        <a class="nav-link collapsed text-light" href="#" data-bs-toggle="collapse" data-bs-target="#collapseSR" aria-expanded="false" aria-controls="collapseSR">
                            <div class="sb-nav-link-icon"><i class="fa fa-address-card"></i></div>
                            Social Recognition
                            <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                        </a>
                        <div class="collapse" id="collapseSR" aria-labelledby="headingOne" data-bs-parent="#sidenavAccordion">
                            <nav class="sb-sidenav-menu-nested nav">
                                <a class="nav-link text-light loading" href="../../employee/contractual/awardee.php">Awardee</a>
                            </nav>
                        </div> 
                        <div class="sb-sidenav-menu-heading text-center text-muted border-top border-1 border-secondary mt-3">Feedback</div> 
                        <a class="nav-link collapsed text-light" href="#" data-bs-toggle="collapse" data-bs-target="#collapseFB" aria-expanded="false" aria-controls="collapseFB">
                            <div class="sb-nav-link-icon"><i class="fas fa-exclamation-circle"></i></div>
                            Report Issue
                            <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                        </a>
                        <div class="collapse" id="collapseFB" aria-labelledby="headingOne" data-bs-parent="#sidenavAccordion">
                            <nav class="sb-sidenav-menu-nested nav">
                                <a class="nav-link text-light" href="">Report Issue</a>
                            </nav>
                        </div> 
                    </div>
                </div>
                <div class="sb-sidenav-footer bg-black border-top border-1 border-secondary">
                    <div class="small text-light">Logged in as: <?php echo htmlspecialchars($employeeInfo['role']); ?></div>
                </div>
            </nav>
        </div>
        <div id="layoutSidenav_content">
            <main id="main-content">
                <div class="container-fluid position-relative px-4">
                    <div class="">
                        <div class="row align-items-center">
                            <div class="col">
                                <h1 class="mb-4 text-light">Dashboard</h1>
                            </div>
                            <div class="col-auto">
                                <button type="button" class="btn btn-light" data-bs-toggle="modal" data-bs-target="#todoModal" title="To-Do List" style="font-size: 20px; width: 40px; height: 40px;">
                                    <i class="fas fa-tasks"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="container" id="calendarContainer" 
                         style="position: fixed; top: 9%; right: 0; z-index: 1050; 
                        width: 700px; height: 300px; display: none;">
                        <div class="row">
                            <div class="col-md-12">
                                <div id="calendar" class="p-2"></div>
                            </div>
                        </div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-6 mt-2 mb-2">
                            <div class="card bg-dark text-light" style="height: 500px;">
                                <div class="card-header text-light border-bottom border-1 border-secondary">
                                    <h3>Attendance</h3> <!-- Month and Year display -->
                                </div>
                                <div class="card-body overflow-auto" style="max-height: 400px;">
                                    <div class="d-flex justify-content-between align-items-start mb-4">
                                        <div>
                                            <h5 class="fw-bold">Today's Date:</h5>
                                            <a href="../../employee/contractual/dashboard.php" id="todaysDate" class="cursor-pointer">
                                                <span id="todaysDateContent"></span>
                                            </a>
                                        </div>
                                        <div>
                                            <h5 class="fw-bold">Time in:</h5>
                                            <p class="text-warning">08:11 AM</p>
                                        </div>
                                    </div>
                                    <hr>
                                    <div class="mb-0">
                                        <h3 class="mb-0" id="monthYearDisplay"></h3>
                                        <div class="row text-center fw-bold">
                                            <div class="col">Sun</div>
                                            <div class="col">Mon</div>
                                            <div class="col">Tue</div>
                                            <div class="col">Wed</div>
                                            <div class="col">Thu</div>
                                            <div class="col">Fri</div>
                                            <div class="col">Sat</div>
                                        </div>

                                        <!-- Calendar rows with attendance status -->
                                        <div id="ATTENDANCEcalendar" class="pt-3 text-light bg-black"></div>
                                    </div>
                                </div>
                                <div class="card-footer text-center d-flex justify-content-around">
                                    <!-- Footer with Next and Previous buttons -->
                                    <button class="btn btn-primary" id="prevMonthBtn">&lt; Prev</button>
                                    <button class="btn btn-primary" id="nextMonthBtn">Next &gt;</button>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mt-2">
                            <div class="card bg-dark">
                                <div class="card-header text-light border-bottom border-1 border-secondary">
                                    <h3>Performance Ratings | Graph</h3>
                                </div>
                                <div class="card-body">
                                    <div class="mt-2">
                                        <div class="row">
                                            <div class="col-xl-6">
                                                <h5 class="text-light">Quality of Work</h5>
                                                <div class="d-flex justify-content-between">
                                                    <span class="text-warning">
                                                        <?php 
                                                            // Display rating label based on avg_quality value
                                                            if ($evaluation['avg_quality'] == 6) {
                                                                echo "Excellent";
                                                                $progressBarClass = "bg-success"; // Green for excellent
                                                            } elseif ($evaluation['avg_quality'] <= 5.99 && $evaluation['avg_quality'] >= 5 ) {
                                                                echo "Good";
                                                                $progressBarClass = "bg-primary"; // Blue for good
                                                            } elseif ($evaluation['avg_quality'] <= 4.99 && $evaluation['avg_quality'] >= 3) {
                                                                echo "Average";
                                                                $progressBarClass = "bg-warning"; // Yellow for average
                                                            } elseif ($evaluation['avg_quality'] <= 2.99 && $evaluation['avg_quality'] >= 0.01) {
                                                                echo "Need Improvements";
                                                                $progressBarClass = "bg-danger"; // Red for needs improvement
                                                            } else {
                                                                echo "Not Yet Evaluated";
                                                                $progressBarClass = "bg-light";
                                                            }                                                                                                    
                                                        ?>
                                                    </span>
                                                </div>
                                                <div class="progress">
                                                    <div 
                                                        class="progress-bar <?php echo $progressBarClass; ?>" 
                                                        role="progressbar" 
                                                        style="width: <?php echo min(100, ($evaluation['avg_quality'] / 6) * 100); ?>%;" 
                                                        aria-valuenow="<?php echo htmlspecialchars($evaluation['avg_quality']); ?>" 
                                                        aria-valuemin="0" 
                                                        aria-valuemax="6">
                                                        <?php echo htmlspecialchars(number_format($evaluation['avg_quality'], 2)); ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-xl-6">
                                                <h5 class="text-light">Communication Skills</h5>
                                                    <div class="d-flex justify-content-between">
                                                        <span class="text-warning">
                                                            <?php 
                                                                // Display rating label based on avg_quality value
                                                                if ($evaluation['avg_communication_skills'] == 6) {
                                                                    echo "Excellent";
                                                                    $progressBarClass = "bg-success"; // Green for excellent
                                                                } elseif ($evaluation['avg_communication_skills'] <= 5.99 && $evaluation['avg_communication_skills'] >= 5) {
                                                                    echo "Good";
                                                                    $progressBarClass = "bg-primary"; // Blue for good
                                                                } elseif ($evaluation['avg_communication_skills'] <= 4.99 && $evaluation['avg_communication_skills'] >= 3) {
                                                                    echo "Average";
                                                                    $progressBarClass = "bg-warning"; // Yellow for average
                                                                } elseif ($evaluation['avg_communication_skills'] <= 2.99 && $evaluation['avg_communication_skills'] >= 0.01) {
                                                                    echo "Need Improvements";
                                                                    $progressBarClass = "bg-danger";
                                                                } else {
                                                                    echo "Not Yet Evaluated";
                                                                    $progressBarClass = "bg-light"; // Red for needs improvement
                                                                }
                                                            ?>
                                                        </span>
                                                    </div>
                                                <div class="progress">
                                                    <div 
                                                        class="progress-bar <?php echo $progressBarClass; ?>" 
                                                        role="progressbar" 
                                                        style="width: <?php echo min(100, ($evaluation['avg_communication_skills'] / 6) * 100); ?>%;" 
                                                        aria-valuenow="<?php echo htmlspecialchars($evaluation['avg_communication_skills']); ?>" 
                                                        aria-valuemin="0" 
                                                        aria-valuemax="6">
                                                        <?php echo htmlspecialchars(number_format($evaluation['avg_communication_skills'], 2)); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>   
                                    <div class="mt-4">
                                        <div class="row">
                                            <div class="col-xl-6">
                                                <h5 class="text-light">Teamwork</h5>
                                                <div class="d-flex justify-content-between">
                                                    <span class="text-warning">
                                                        <?php 
                                                            // Display rating label based on avg_quality value
                                                            if ($evaluation['avg_teamwork'] == 6) {
                                                                echo "Excellent";
                                                                $progressBarClass = "bg-success"; // Green for excellent
                                                            } elseif ($evaluation['avg_teamwork'] <= 5.99 && $evaluation['avg_teamwork'] >= 5) {
                                                                echo "Good";
                                                                $progressBarClass = "bg-primary"; // Blue for good
                                                            } elseif ($evaluation['avg_teamwork'] <= 4.99 && $evaluation['avg_teamwork'] >= 3) {
                                                                echo "Average";
                                                                $progressBarClass = "bg-warning"; // Yellow for average
                                                            } elseif ($evaluation['avg_teamwork'] <= 2.99 && $evaluation['avg_teamwork'] >= 0.01) {
                                                                echo "Need Improvements";
                                                                $progressBarClass = "bg-danger";
                                                            } else {
                                                                echo "Not Yet Evaluated";
                                                                $progressBarClass = "bg-light"; // Light gray for not yet evaluated
                                                            }
                                                        ?>
                                                    </span>
                                                </div>
                                                <div class="progress">
                                                    <div 
                                                        class="progress-bar <?php echo $progressBarClass; ?>" 
                                                        role="progressbar" 
                                                        style="width: <?php echo min(100, ($evaluation['avg_teamwork'] / 6) * 100); ?>%;" 
                                                        aria-valuenow="<?php echo htmlspecialchars($evaluation['avg_teamwork']); ?>" 
                                                        aria-valuemin="0" 
                                                        aria-valuemax="6">
                                                        <?php echo htmlspecialchars(number_format($evaluation['avg_teamwork'], 2)); ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-xl-6">
                                                <h5 class="text-light">Punctuality</h5>
                                                <div class="d-flex justify-content-between">
                                                    <span class="text-warning">
                                                        <?php 
                                                            // Display rating label based on avg_quality value
                                                            if ($evaluation['avg_punctuality'] == 6) {
                                                                echo "Excellent";
                                                                $progressBarClass = "bg-success"; // Green for excellent
                                                            } elseif ($evaluation['avg_punctuality'] <= 5.99 && $evaluation['avg_punctuality'] >= 5) {
                                                                echo "Good";
                                                                $progressBarClass = "bg-primary"; // Blue for good
                                                            } elseif ($evaluation['avg_punctuality'] <= 4.99 && $evaluation['avg_punctuality'] >= 3) {
                                                                echo "Average";
                                                                $progressBarClass = "bg-warning"; // Yellow for average
                                                            } elseif ($evaluation['avg_punctuality'] <= 2.99 && $evaluation['avg_punctuality'] >= 0.01) {
                                                                echo "Need Improvements";
                                                                $progressBarClass = "bg-danger";
                                                            } else {
                                                                echo "Not Yet Evaluated";
                                                                $progressBarClass = "bg-light"; // Red for needs improvement
                                                            }
                                                        ?>
                                                    </span>
                                                </div>
                                                <div class="progress">  
                                                    <div 
                                                        class="progress-bar <?php echo $progressBarClass; ?>" 
                                                        role="progressbar" 
                                                        style="width: <?php echo min(100, ($evaluation['avg_punctuality'] / 6) * 100); ?>%;" 
                                                        aria-valuenow="<?php echo htmlspecialchars($evaluation['avg_punctuality']); ?>" 
                                                        aria-valuemin="0" 
                                                        aria-valuemax="6">
                                                        <?php echo htmlspecialchars(number_format($evaluation['avg_punctuality'], 2)); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- Rating 5: Initiative -->
                                    <div class="mt-4">
                                        <div class="row">
                                            <div class="col-xl-6">
                                                <h5 class="text-light">Initiative</h5>
                                                <div class="d-flex justify-content-between">
                                                    <span class="text-warning">
                                                        <?php 
                                                            // Display rating label based on avg_quality value
                                                            if ($evaluation['avg_initiative'] == 6) {
                                                                echo "Excellent";
                                                                $progressBarClass = "bg-success"; // Green for excellent
                                                            } elseif ($evaluation['avg_initiative'] <= 5.99 && $evaluation['avg_initiative'] >= 5) {
                                                                echo "Good";
                                                                $progressBarClass = "bg-primary"; // Blue for good
                                                            } elseif ($evaluation['avg_initiative'] <= 4.99 && $evaluation['avg_initiative'] >= 3) {
                                                                echo "Average";
                                                                $progressBarClass = "bg-warning"; // Yellow for average
                                                            } elseif ($evaluation['avg_initiative'] <= 2.99 && $evaluation['avg_initiative'] >= 0.01) {
                                                                echo "Need Improvements";
                                                                $progressBarClass = "bg-danger";
                                                            } else {
                                                                echo "Not Yet Evaluated";
                                                                $progressBarClass = "bg-light"; // Red for needs improvement
                                                            }
                                                        ?>
                                                    </span>
                                                </div>
                                                <div class="progress">
                                                    <div 
                                                        class="progress-bar <?php echo $progressBarClass; ?>" 
                                                        role="progressbar" 
                                                        style="width: <?php echo min(100, ($evaluation['avg_initiative'] / 6) * 100); ?>%;" 
                                                        aria-valuenow="<?php echo htmlspecialchars($evaluation['avg_initiative']); ?>" 
                                                        aria-valuemin="0" 
                                                        aria-valuemax="6">
                                                        <?php echo htmlspecialchars(number_format($evaluation['avg_initiative'], 2)); ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-xl-6">
                                                <h5 class="text-light">Overall Rating</h5>
                                                <div class="d-flex justify-content-between">
                                                    <span class="text-warning">
                                                        <?php
                                                        // Display rating label based on totalAverage value
                                                        if ($totalAverage == 6) {
                                                            echo "Excellent";
                                                            $progressBarClass = "bg-success"; // Green for excellent
                                                        } elseif ($totalAverage <= 5.99 && $totalAverage >= 5) {
                                                            echo "Good";
                                                            $progressBarClass = "bg-primary"; // Blue for good
                                                        } elseif ($totalAverage <= 4.99 && $totalAverage >= 3) {
                                                            echo "Average";
                                                            $progressBarClass = "bg-warning"; // Yellow for average
                                                        } elseif ($totalAverage <= 2.99 && $totalAverage >= 0.01) {
                                                            echo "Need Improvements";
                                                            $progressBarClass = "bg-danger"; // Red for needs improvement
                                                        } else {
                                                            echo "Not Yet Evaluated";
                                                            $progressBarClass = "bg-light";
                                                        }
                                                        ?>
                                                    </span>
                                                </div>
                                                <div class="progress">
                                                    <div
                                                        class="progress-bar <?php echo $progressBarClass; ?>"
                                                        role="progressbar"
                                                        style="width: <?php echo min(100, ($totalAverage / 6) * 100); ?>%;"
                                                        aria-valuenow="<?php echo htmlspecialchars($totalAverage); ?>"
                                                        aria-valuemin="0"
                                                        aria-valuemax="6">
                                                        <?php echo htmlspecialchars(number_format($totalAverage, 2)); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>               
                                    </div>
                                </div>
                            </div>
                        </div>
                    <div class="row mb-4">
                        <div class="col-md-12 mt-2 mb-2">
                            <div class="card bg-dark text-light border-0">
                                <div class="card-header border-bottom border-1 border-secondary">
                                    <h3 class="mb-0">Top Performers | Graph</h3>
                                </div>
                                <div class="card-body">
                                    <ul class="list-group list-group-flush">
                                        <!-- Performer 1 -->
                                        <li class="list-group-item bg-dark text-light d-flex align-items-center justify-content-between border-0">
                                            <div class="d-flex align-items-center">
                                                <img src="../../uploads/profile_pictures/try.jpg" alt="Performer 1" class="rounded-circle me-3" style="width: 50px; height: 50px; object-fit: cover;">
                                                <div>
                                                    <h5 class="mb-0">John Doe</h5>
                                                    <small class="text-warning">Sales Manager</small>
                                                </div>
                                            </div>
                                            <div class="progress" style="width: 30%; height: 8px;">
                                                <div class="progress-bar bg-success" role="progressbar" style="width: 90%;" aria-valuenow="90" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                        </li>
                                        <!-- Performer 2 -->
                                        <li class="list-group-item bg-dark text-light d-flex align-items-center justify-content-between border-0">
                                            <div class="d-flex align-items-center">
                                                <img src="../../uploads/profile_pictures/pfp3.jpg" alt="Performer 2" class="rounded-circle me-3" style="width: 50px; height: 50px; object-fit: cover;">
                                                <div>
                                                    <h5 class="mb-0">Jane Smith</h5>
                                                    <small class="text-warning">Marketing Specialist</small>
                                                </div>
                                            </div>
                                            <div class="progress" style="width: 30%; height: 8px;">
                                                <div class="progress-bar bg-success" role="progressbar" style="width: 85%;" aria-valuenow="85" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                        </li>
                                        <!-- Performer 3 -->
                                        <li class="list-group-item bg-dark text-light d-flex align-items-center justify-content-between border-0">
                                            <div class="d-flex align-items-center">
                                                <img src="../../uploads/profile_pictures/logo.jpg" alt="Performer 3" class="rounded-circle me-3" style="width: 50px; height: 50px; object-fit: cover;">
                                                <div>
                                                    <h5 class="mb-0">Michael Johnson</h5>
                                                    <small class="text-warning">HR Manager</small>
                                                </div>
                                            </div>
                                            <div class="progress" style="width: 30%; height: 8px;">
                                                <div class="progress-bar bg-success" role="progressbar" style="width: 80%;" aria-valuenow="80" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
                <div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content bg-dark text-light">
                            <div class="modal-header">
                                <h5 class="modal-title" id="logoutModalLabel">Confirm Logout</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                Are you sure you want to log out?
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn border-secondary text-light" data-bs-dismiss="modal">Cancel</button>
                                <form action="../../employee/logout.php" method="POST">
                                    <button type="submit" class="btn btn-danger">Logout</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal fade" id="attendanceModal" tabindex="-1" aria-labelledby="timeInfoModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content bg-dark text-light">
                            <div class="modal-header">
                                <h5 class="modal-title" id="timeInfoModalLabel">Attendance Info</h5>
                                <button type="button" class="btn-close bg-light" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="d-flex justify-content-around">
                                    <div>
                                        <h6 class="fw-bold">Time In:</h6>
                                        <p class="text-info fw-bold" id="timeIn"></p> <!-- Time will be dynamically filled -->
                                    </div>
                                    <div>
                                        <h6 class="fw-bold">Time Out:</h6>
                                        <p class="text-info fw-bold" id="timeOut"></p> <!-- Time will be dynamically filled -->
                                    </div>
                                </div>
                                <!-- New Section for Work Status -->
                                <div class="d-flex justify-content-around mt-3">
                                    <div>
                                        <h6 class="fw-bold">Work Status:</h6>
                                        <p class="text-warning fw-bold" id="workStatus"></p> <!-- Status will be dynamically filled -->
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal fade" id="todoModal" tabindex="-1" aria-labelledby="todoModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content bg-dark text-light">
                            <div class="modal-header">
                                <h5 class="modal-title text-info" id="todoModalLabel">To Do</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="d-flex justify-content-end">
                                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addTaskModal">
                                        <i class="fas fa-plus me-2"></i>Add To Do List
                                    </button>
                                </div>
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item bg-dark text-light fs-4 border-0 d-flex justify-content-between align-items-center">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" value="" id="task1">
                                            <label class="form-check-label" for="task1">
                                                <i class="bi bi-check-circle text-warning me-2"></i>Facial Recognition
                                            </label>
                                        </div>
                                    </li>
                                    <li class="list-group-item bg-dark text-light fs-4 border-0 d-flex justify-content-between align-items-center">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" value="" id="task2">
                                            <label class="form-check-label" for="task2">
                                                <i class="bi bi-check-circle text-warning me-2"></i>Attendance Record
                                            </label>
                                        </div>
                                    </li>
                                    
                                    <li class="list-group-item bg-dark text-light fs-4 border-0 d-flex justify-content-between align-items-center">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" value="" id="task4">
                                            <label class="form-check-label" for="task4">
                                                <i class="bi bi-check-circle text-warning me-2"></i>Performance Processing
                                            </label>
                                        </div>
                                    </li>
                                    <li class="list-group-item bg-dark text-light fs-4 border-0 d-flex justify-content-between align-items-center">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" value="" id="task5">
                                            <label class="form-check-label" for="task5">
                                                <i class="bi bi-check-circle text-warning me-2"></i>Payroll Processing
                                            </label>
                                        </div>
                                    </li>
                                    <li class="list-group-item bg-dark text-light fs-4 border-0 d-flex justify-content-between align-items-center">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" value="" id="task6">
                                            <label class="form-check-label" for="task6">
                                                <i class="bi bi-check-circle text-warning me-2"></i>Social Recognition
                                            </label>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            <footer class="py-4 bg-light mt-auto bg-dark border-top border-1 border-secondary">
                <div class="container-fluid px-4">
                    <div class="d-flex align-items-center justify-content-between small">
                        <div class="text-muted">Copyright &copy; Your Website 2023</div>
                        <div>
                            <a href="#">Privacy Policy</a>
                            &middot;
                            <a href="#">Terms &amp; Conditions</a>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    </div>
    <div class="modal fade" id="loadingModal" tabindex="-1" aria-labelledby="loadingModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content bg-transparent border-0">
                    <div class="modal-body d-flex flex-column align-items-center justify-content-center">
                            <!-- Bouncing coin spinner -->
                            <div class="coin-spinner"></div>
                            <div class="mt-3 text-light fw-bold">Please wait...</div>
                        </div>
                    </div>
                </div>
           </div>

<script>
      document.addEventListener('DOMContentLoaded', function () {
                const buttons = document.querySelectorAll('.loading');
                const loadingModal = new bootstrap.Modal(document.getElementById('loadingModal'));

                // Loop through each button and add a click event listener
                buttons.forEach(button => {
                    button.addEventListener('click', function (event) {
                        // Show the loading modal
                        loadingModal.show();

                        // Disable the button to prevent multiple clicks
                        this.classList.add('disabled');

                        // Handle form submission buttons
                        if (this.closest('form')) {
                            event.preventDefault(); // Prevent the default form submit

                            // Submit the form after a short delay
                            setTimeout(() => {
                                this.closest('form').submit();
                            }, 1500);
                        }
                        // Handle links
                        else if (this.tagName.toLowerCase() === 'a') {
                            event.preventDefault(); // Prevent the default link behavior

                            // Redirect after a short delay
                            setTimeout(() => {
                                window.location.href = this.href;
                            }, 1500);
                        }
                    });
                });

                // Hide the loading modal when navigating back and enable buttons again
                window.addEventListener('pageshow', function (event) {
                    if (event.persisted) { // Check if the page was loaded from cache (back button)
                        loadingModal.hide();

                        // Re-enable all buttons when coming back
                        buttons.forEach(button => {
                            button.classList.remove('disabled');
                        });
                        
                    }
                });
            });
    // for calendar only
    let calendar; // Declare calendar variable globally

    function toggleCalendar() {
        const calendarContainer = document.getElementById('calendarContainer');
        if (calendarContainer.style.display === 'none' || calendarContainer.style.display === '') {
            calendarContainer.style.display = 'block';

            // Initialize the calendar if it hasn't been initialized yet
            if (!calendar) {
                initializeCalendar();
            }
        } else {
            calendarContainer.style.display = 'none';
        }
    }

    function initializeCalendar() {
        const calendarEl = document.getElementById('calendar');
        calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay'
            },
            height: 440,  // Set the height of the calendar to make it small
            events: {
                url: '../../db/holiday.php',  // Endpoint for fetching events
                method: 'GET',
                failure: function() {
                    alert('There was an error fetching events!');
                }
            }
        });

        calendar.render();
    }

    document.addEventListener('DOMContentLoaded', function () {
        const currentDateElement = document.getElementById('currentDate');
        const currentDate = new Date().toLocaleDateString(); // Get the current date
        currentDateElement.textContent = currentDate; // Set the date text
    });

    document.addEventListener('click', function(event) {
        const calendarContainer = document.getElementById('calendarContainer');
        const calendarButton = document.querySelector('button[onclick="toggleCalendar()"]');

        if (!calendarContainer.contains(event.target) && !calendarButton.contains(event.target)) {
            calendarContainer.style.display = 'none';
        }
    });
    // for calendar only end

    function setCurrentTime() {
        const currentTimeElement = document.getElementById('currentTime');
        const currentDateElement = document.getElementById('currentDate');

        const currentDate = new Date();

        // Convert to 12-hour format with AM/PM
        let hours = currentDate.getHours();
        const minutes = currentDate.getMinutes();
        const seconds = currentDate.getSeconds();
        const ampm = hours >= 12 ? 'PM' : 'AM';

        hours = hours % 12;
        hours = hours ? hours : 12; // If hour is 0, set to 12

        const formattedHours = hours < 10 ? '0' + hours : hours;
        const formattedMinutes = minutes < 10 ? '0' + minutes : minutes;
        const formattedSeconds = seconds < 10 ? '0' + seconds : seconds;

        currentTimeElement.textContent = `${formattedHours}:${formattedMinutes}:${formattedSeconds} ${ampm}`;

        // Format the date in text form (e.g., "January 12, 2025")
        const options = { year: 'numeric', month: 'long', day: 'numeric' };
        currentDateElement.textContent = currentDate.toLocaleDateString('en-US', options);
    }

    setCurrentTime();
    setInterval(setCurrentTime, 1000);


// ATTENDANCE
let currentMonth = new Date().getMonth(); // January is 0, December is 11
let currentYear = new Date().getFullYear();
let employeeId = <?php echo $employeeId; ?>; // Employee ID from PHP session

const monthNames = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];

// Define operation hours: Start at 8:00 AM and End at 4:00 PM
const operationStartTime = new Date();
operationStartTime.setHours(8, 0, 0, 0); // 8:00 AM

const operationEndTime = new Date();
operationEndTime.setHours(16, 0, 0, 0); // 4:00 PM

// Function to format the time in 12-hour format (with AM/PM)
function formatTimeWithAmPm(time24) {
    if (!time24 || time24 === 'N/A') {
        return 'No data';  // Handle cases where there's no data
    }
    
    // Split time into hours and minutes
    let [hour, minute] = time24.split(':');
    hour = parseInt(hour); // Convert hour to an integer

    // Determine AM or PM suffix
    const amPm = hour >= 12 ? 'PM' : 'AM';
    
    // Convert 24-hour time to 12-hour time
    hour = hour % 12 || 12; // Convert 0 to 12 for midnight (12 AM)

    // Return formatted time with AM/PM
    return `${hour}:${minute} ${amPm}`;
}

// Function to calculate status (Late or Overtime)
function calculateAttendanceStatus(timeIn, timeOut) {
    let status = '';

    if (timeIn && timeIn !== 'Absent') {
        const timeInDate = new Date(`1970-01-01T${timeIn}:00`);
        if (timeInDate > operationStartTime) {
            status += 'Late';
        }
    }

    if (timeOut && timeOut !== 'Absent') {
        const timeOutDate = new Date(`1970-01-01T${timeOut}:00`);
        if (timeOutDate > operationEndTime) {
            if (status) {
                status += ' & Overtime';
            } else {
                status = 'Overtime';
            }
        }
    }

    return status || 'On Time'; // Default to "On Time" if no issues
}

// Function to render the calendar for a specific month and year
function renderCalendar(month, year, attendanceRecords = {}) {
    const daysInMonth = new Date(year, month + 1, 0).getDate(); // Get total days in the current month
    const firstDay = new Date(year, month, 1).getDay(); // Get the starting day (0 = Sunday, 1 = Monday, etc.)

    let calendarHTML = '<div class="row text-center pt-3">';

    // Add empty columns before the first day of the month
    for (let i = 0; i < firstDay; i++) {
        calendarHTML += '<div class="col"></div>';
    }

    // Fill in the days of the month
    let dayCounter = 1;
    for (let i = firstDay; i < 7; i++) {
        const status = (i === 0) ? 'Day Off' : attendanceRecords[dayCounter] || ''; // Set "Day Off" for Sundays (day 0)
        
        calendarHTML += `
            <div class="col">
                <button class="btn text-light p-0" data-bs-toggle="modal" data-bs-target="#attendanceModal" onclick="showAttendanceDetails(${dayCounter})">
                    <span class="fw-bold ${status === 'Present' ? 'text-success' : status === 'Absent' ? 'text-danger' : status === 'Late' ? 'text-warning' : status === 'Day Off' ? 'text-muted' : ''}">
                        ${dayCounter}
                    </span>
                </button>
            </div>
        `;
        dayCounter++;
    }
    calendarHTML += '</div>';

    // Continue filling rows for the remaining days
    while (dayCounter <= daysInMonth) {
        calendarHTML += '<div class="row text-center pt-3">';
        let dayOfWeek = 0; // Reset for each row

        for (let i = 0; i < 7 && dayCounter <= daysInMonth; i++) {
            const status = (dayOfWeek === 0) ? 'Day Off' : attendanceRecords[dayCounter] || ''; 
            
            calendarHTML += `
                <div class="col">
                    <button class="btn text-light p-0" data-bs-toggle="modal" data-bs-target="#attendanceModal" onclick="showAttendanceDetails(${dayCounter})">
                        <span class="fw-bold ${status === 'Present' ? 'text-success' : status === 'Absent' ? 'text-danger' : status === 'Late' ? 'text-warning' : status === 'Day Off' ? 'text-muted' : ''}">
                            ${dayCounter}
                        </span>
                    </button>
                </div>
            `;
            dayCounter++;
            dayOfWeek++;
        }

        if (dayOfWeek < 7) {
            for (let j = dayOfWeek; j < 7; j++) {
                calendarHTML += '<div class="col"></div>';
            }
        }

        calendarHTML += '</div>';
    }

    document.getElementById('ATTENDANCEcalendar').innerHTML = calendarHTML;
    document.getElementById('monthYearDisplay').textContent = `${monthNames[month]} ${year}`;
    document.getElementById('todaysDate').textContent = `${monthNames[new Date().getMonth()]} ${new Date().getDate()}, ${new Date().getFullYear()}`;
}

// Fetch attendance data for a specific month and year
function fetchAttendance(month, year) {
    fetch(`/HR2/employee_db/supervisor/fetch_attendance.php?e_id=${employeeId}&month=${month + 1}&year=${year}`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                console.error(data.error);
                return;
            }

            // Handle attendance records and render calendar
            renderCalendar(month, year, data); // Pass attendance data to render calendar
        })
        .catch(error => console.error('Error fetching attendance data:', error));
}

// Show attendance details when a specific day is clicked
function showAttendanceDetails(day) {
    fetch(`/HR2/employee_db/supervisor/fetch_attendance.php?e_id=${employeeId}&day=${day}&month=${currentMonth + 1}&year=${currentYear}`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                console.error(data.error);
                return;
            }

            // If there's no time_in or time_out, mark it as "Absent"
            const timeInFormatted = data.time_in ? formatTimeWithAmPm(data.time_in) : 'Absent';
            const timeOutFormatted = data.time_out ? formatTimeWithAmPm(data.time_out) : 'Absent';

            // Calculate the status (Late, Overtime, or On Time)
            const attendanceStatus = calculateAttendanceStatus(data.time_in, data.time_out);

            // Update the modal with the formatted time_in, time_out, and attendance status
            document.getElementById('timeIn').textContent = timeInFormatted;
            document.getElementById('timeOut').textContent = timeOutFormatted;
            document.getElementById('workStatus').textContent = attendanceStatus;
            // Set appropriate colors for the status
            const statusElement = document.getElementById('workStatus');
            if (attendanceStatus === 'Late') {
                statusElement.classList.add('text-warning');
                statusElement.classList.remove('text-success', 'text-danger', 'text-muted');
            } else if (attendanceStatus === 'Overtime') {
                statusElement.classList.add('text-info');
                statusElement.classList.remove('text-success', 'text-danger', 'text-muted');
            } else if (attendanceStatus === 'On Time') {
                statusElement.classList.add('text-success');
                statusElement.classList.remove('text-warning', 'text-danger', 'text-muted');
            } else {
                statusElement.classList.add('text-muted');
                statusElement.classList.remove('text-success', 'text-danger', 'text-warning');
            }
        })
        .catch(error => console.error('Error fetching attendance details:', error));
}

// Event listeners for next and previous month buttons
document.getElementById('nextMonthBtn').addEventListener('click', function() {
    currentMonth++;
    if (currentMonth > 11) {
        currentMonth = 0;
        currentYear++;
    }
    fetchAttendance(currentMonth, currentYear);
});

document.getElementById('prevMonthBtn').addEventListener('click', function() {
    currentMonth--;
    if (currentMonth < 0) {
        currentMonth = 11;
        currentYear--;
    }
    fetchAttendance(currentMonth, currentYear);
});

// Fetch the initial calendar for the current month and year
fetchAttendance(currentMonth, currentYear);



// GENERAL SEARCH
const features = [
    { name: "Dashboard", link: "../../employee/supervisor/dashboard.php", path: "Employee Dashboard" },
    { name: "Attendance Scanner", link: "../../employee/supervisor/attendance.php", path: "Time and Attendance/Attendance Scanner" },
    { name: "Leave Request", link: "../../employee/supervisor/leave_request.php", path: "Leave Management/Leave Request" },
    { name: "Evaluation Ratings", link: "../../employee/supervisor/evaluation.php", path: "Performance Management/Evaluation Ratings" },
    { name: "File Leave", link: "../../employee/supervisor/leave_file.php", path: "Leave Management/File Leave" },
    { name: "View Your Rating", link: "../../employee/supervisor/social_recognition.php", path: "Social Recognition/View Your Rating" },
    { name: "Report Issue", link: "../../employee/supervisor/report_issue.php", path: "Feedback/Report Issue" }
];

document.getElementById('searchInput').addEventListener('input', function () {
    let input = this.value.toLowerCase();
    let results = '';

    if (input) {
        // Filter the features based on the search input
        const filteredFeatures = features.filter(feature => 
            feature.name.toLowerCase().includes(input)
        );

        if (filteredFeatures.length > 0) {
            // Generate the HTML for the filtered results
            filteredFeatures.forEach(feature => {
                results += `                   
                    <a href="${feature.link}" class="list-group-item list-group-item-action">
                        ${feature.name}
                        <br>
                        <small class="text-muted">${feature.path}</small>
                    </a>`;
            });
        } else {
            // If no matches found, show "No result found"
            results = `<li class="list-group-item list-group-item-action">No result found</li>`;
        }
    }

    // Update the search results with the filtered features
    document.getElementById('searchResults').innerHTML = results;
    
    if (!input) {
        document.getElementById('searchResults').innerHTML = ''; // Clears the dropdown if input is empty
    }
});


const searchInputElement = document.getElementById('searchInput');
searchInputElement.addEventListener('hidden.bs.collapse', function () {
    searchInputElement.value = '';
    document.getElementById('searchResults').innerHTML = ''; 
});



// BLUR MODAL
const todoModal = document.getElementById('todoModal');
  const mainContent = document.getElementById('main-content');

  todoModal.addEventListener('show.bs.modal', function () {
    mainContent.classList.add('blur-background');
  });

  todoModal.addEventListener('hidden.bs.modal', function () {
    mainContent.classList.remove('blur-background');
  });
  // Fetch notifications using JavaScript and fetch API
function fetchNotifications() {
        const notifications = <?php echo json_encode($notifications); ?>;
        const notificationList = document.getElementById('notificationList');
        const notificationCount = document.getElementById('notificationCount');
        notificationList.innerHTML = ''; // Clear existing notifications

        if (notifications.length > 0) {
            notifications.forEach(notification => {
                const listItem = document.createElement('li');
                listItem.classList.add('dropdown-item');
                listItem.innerHTML = `<strong>${notification.firstname} ${notification.lastname}:</strong> ${notification.message}`;
                notificationList.appendChild(listItem);
            });
            notificationCount.textContent = notifications.length;
        } else {
            const listItem = document.createElement('li');
            listItem.classList.add('dropdown-item');
            listItem.textContent = 'No notifications found';
            notificationList.appendChild(listItem);
            notificationCount.textContent = 0;
        }
    }

    // Call fetchNotifications on page load
    document.addEventListener('DOMContentLoaded', fetchNotifications);

</script>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'> </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../js/employee.js"></script>



</body>

</html>