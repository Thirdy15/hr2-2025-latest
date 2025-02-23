<?php
session_start();

if (!isset($_SESSION['a_id'])) {
    header("Location: ../admin/login.php");
    exit();
}

include '../db/db_conn.php';

// Fetch user info
$adminId = $_SESSION['a_id'];
$sql = "SELECT firstname, middlename, lastname, email, role, pfp FROM admin_register WHERE a_id = ?";
$stmt = $conn->prepare($sql);

// Check if statement preparation failed
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

$stmt->bind_param("i", $adminId);
$stmt->execute();
$result = $stmt->get_result();

// Fetch user information
$adminInfo = $result->fetch_assoc();

// Set profile picture or use default if not set
$profilePicture = !empty($adminInfo['pfp']) ? $adminInfo['pfp'] : '../img/defaultpfp.jpg';

// Close statement and connection
$stmt->close();

// Fetch notifications
$sql = "SELECT id, message, created_at, is_read FROM notifications ORDER BY created_at DESC";
$result = $conn->query($sql);
$notifications = [];
while ($row = $result->fetch_assoc()) {
    $notifications[] = $row;
}

// Fetch leave notifications
$sql = "SELECT id, message, created_at, is_read FROM leave_notifications ORDER BY created_at DESC";
$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    $notifications[] = $row;
}

// Fetch new employee notifications
$sql = "SELECT id, message, created_at, is_read FROM notifications ORDER BY created_at DESC";
$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    $notifications[] = $row;
}

// Sort notifications by created_at
usort($notifications, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="" />
    <meta name="author" content="" />
    <title>Admin Dashboard | HR2</title>
    <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet">
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />
    <link href="../css/styles.css" rel="stylesheet" />
    <link href="../css/calendar.css" rel="stylesheet"/>
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    <style>
    /* Dark Mode Styles */
    .custom-modal {
        background: #121212; /* Dark background */
        color: #ffffff; /* White font */
        border-radius: 10px;
        box-shadow: 0px 8px 16px rgba(255, 255, 255, 0.05);
    }

    /* Dark Border for Separation */
    .border-dark {
        border-color: #333 !important;
    }

    /* Make Notifications More Interactive */
    .custom-body {
        max-height: 400px;
        overflow-y: auto;
        padding: 15px;
    }

    .custom-item {
        background: rgba(255, 255, 255, 0.1);
        color: #ffffff;
        border-radius: 8px;
        padding: 12px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        transition: all 0.3s ease-in-out;
        border: 1px solid #333; /* Dark border for separation */
    }

    .custom-item.unread {
        font-weight: bold;
        border-left: 4px solid #ff4757;
    }

    .custom-item.read {
        opacity: 0.8;
    }

    .custom-item:hover {
        background: rgba(255, 255, 255, 0.15);
        transform: scale(1.02);
    }

    .notif-icon {
        margin-right: 10px;
        font-size: 18px;
    }

    .time-stamp {
        font-size: 12px;
        opacity: 0.7;
    }

    /* Button Outline Light */
    .btn-outline-light:hover {
        background: rgba(255, 255, 255, 0.2);
    }
</style>
</head>
        
<body class="sb-nav-fixed bg-black">
    <nav class="sb-topnav navbar navbar-expand navbar-dark border-bottom border-1 border-secondary bg-dark">
        <a class="navbar-brand ps-3 text-muted" href="../admin/dashboard.php">Admin Portal</a>
        <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle" href="#!"><i class="fas fa-bars text-light"></i></button>
            
        <!-- Flex container to hold both time/date and search form -->
        <div class="d-flex ms-auto me-0 me-md-3 my-2 my-md-0 align-items-center">
            <div class="text-light me-3 p-2 rounded shadow-sm bg-gradient" id="currentTimeContainer" 
                style="background: linear-gradient(45deg, #333333, #444444); border-radius: 5px;">
                <span class="d-flex align-items-center">
                    <span class="pe-2">
                        <i class="fas fa-clock"></i> 
                        <span id="currentTime">00:00:00</span>
                    </span>
                    <button class="btn btn-outline-warning btn-sm ms-2" type="button" onclick="toggleCalendar()">
                        <i class="fas fa-calendar-alt"></i>
                        <span id="currentDate">00/00/0000</span>
                    </button>
                </span>
            </div>
            
            <form class="d-none d-md-inline-block form-inline">
                <div class="input-group">
                    <input class="form-control" type="text" placeholder="Search for..." aria-label="Search for..." aria-describedby="btnNavbarSearch" />
                    <button class="btn btn-warning" id="btnNavbarSearch" type="button"><i class="fas fa-search"></i></button>
                </div>
            </form>
            
            <!-- Notifications Bell -->
            <div class="ms-3 dropdown me-3">
                <button class="btn btn-outline-light btn-sm position-relative" type="button" id="notificationsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-bell"></i>
                    <?php if (count(array_filter($notifications, fn($n) => !$n['is_read'])) > 0): ?>
                        <span class="badge bg-danger position-absolute top-0 start-100 translate-middle" id="notificationCount"><?php echo count(array_filter($notifications, fn($n) => !$n['is_read'])); ?></span>
                    <?php endif; ?>
                </button>
                <ul class="dropdown-menu dropdown-menu-end bg-dark text-secondary border-secondary" aria-labelledby="notificationsDropdown" style="max-height: 300px; overflow-y: auto;">
                    <li class="dropdown-header text-center text-light d-flex justify-content-between align-items-center">
                        Notifications
                        <button class="btn btn-outline-danger btn-sm" onclick="clearAllNotifications()">Clear All</button>
                    </li>
                    <li><hr class="dropdown-divider border-secondary"></li>
                    <?php if (count($notifications) > 0): ?>
                        <?php foreach (array_slice($notifications, 0, 10) as $notification): ?>
                            <li>
                                <a class="dropdown-item bg-dark text-white <?php echo $notification['is_read'] ? '' : 'font-weight-bold'; ?>" href="#" data-id="<?php echo $notification['id']; ?>" onclick="markAsRead(<?php echo $notification['id']; ?>)">
                                    <?php echo htmlspecialchars($notification['message']); ?>
                                    <?php if (!$notification['is_read']): ?>
                                        <span class="badge bg-danger ms-2">•</span>
                                    <?php endif; ?>
                                </a>
                                <li><hr class="dropdown-divider border-secondary"></li>
                            </li>
                        <?php endforeach; ?>
                        <?php if (count($notifications) > 10): ?>
                            <li><a class="dropdown-item bg-dark text-secondary text-center text-danger" href="#" onclick="showAllNotifications()">Show All Notifications</a></li>
                        <?php endif; ?>
                    <?php else: ?>
                        <li><a class="dropdown-item bg-dark text-secondary" href="#">No new notifications</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
        
    </nav>
    <div id="layoutSidenav">
        <div id="layoutSidenav_nav">
            <nav class="sb-sidenav accordion bg-dark" id="sidenavAccordion">
                <div class="sb-sidenav-menu ">
                    <div class="nav">
                        <div class="sb-sidenav-menu-heading text-center text-muted">Your Profile</div>
                        <ul class="navbar-nav ms-auto ms-md-0 me-3 me-lg-4">
                            <li class="nav-item dropdown text">
                                <a class="nav-link dropdown-toggle text-light d-flex justify-content-center ms-4" id="navbarDropdown" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <img src="<?php echo (!empty($adminInfo['pfp']) && $adminInfo['pfp'] !== 'defaultpfp.jpg') 
                                        ? htmlspecialchars($adminInfo['pfp']) 
                                        : '../img/defaultpfp.jpg'; ?>" 
                                        class="rounded-circle border border-light" width="80" height="80" alt="Profile Picture" />
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                                    <li><a class="dropdown-item loading" href="../admin/profile.php">Profile</a></li>
                                    <li><a class="dropdown-item" href="../admin/settings.php">Settings</a></li>
                                    <li><a class="dropdown-item" href="#!">Activity Log</a></li>
                                    <li><hr class="dropdown-divider border-black" /></li>
                                    <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#logoutModal">Logout</a></li>
                                </ul>
                            </li>
                            
                            <li class="nav-item text-light d-flex ms-3 flex-column align-items-center text-center">
                                <span class="big text-light mb-1">
                                    <?php
                                        if ($adminInfo) {
                                        echo htmlspecialchars($adminInfo['firstname'] . ' ' . $adminInfo['middlename'] . ' ' . $adminInfo['lastname']);
                                        } else {
                                        echo "Admin information not available.";
                                        }
                                    ?>
                                </span>      
                                <span class="big text-light">
                                    <?php
                                        if ($adminInfo) {
                                        echo htmlspecialchars($adminInfo['role']);
                                        } else {
                                        echo "User information not available.";
                                        }
                                    ?>
                                </span>
                            </li>
                        </ul>
                        <div class="sb-sidenav-menu-heading text-center text-muted border-top border-1 border-secondary mt-3">Admin Dashboard</div>
                        <a class="nav-link text-light loading" href="../admin/dashboard.php">
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
                                <a class="nav-link text-light loading" href="../admin/attendance.php">Attendance</a>
                                <a class="nav-link text-light loading" href="../admin/timesheet.php">Timesheet</a>
                            </nav>
                        </div>
                        <a class="nav-link collapsed text-light" href="#" data-bs-toggle="collapse" data-bs-target="#collapseLM" aria-expanded="false" aria-controls="collapseLM">
                            <div class="sb-nav-link-icon"><i class="fas fa-calendar-times"></i></div>
                            Leave Management
                            <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                        </a>
                        <div class="collapse" id="collapseLM" aria-labelledby="headingOne" data-bs-parent="#sidenavAccordion">
                            <nav class="sb-sidenav-menu-nested nav">
                                <a class="nav-link text-light loading" href="../admin/leave_requests.php">Leave Requests</a>
                                <a class="nav-link text-light loading" href="../admin/leave_history.php">Leave History</a>
                            </nav>
                        </div>
                        <a class="nav-link collapsed text-light" href="#" data-bs-toggle="collapse" data-bs-target="#collapsePM" aria-expanded="false" aria-controls="collapsePM">
                            <div class="sb-nav-link-icon"><i class="fas fa-line-chart"></i></div>
                            Performance Management
                            <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                        </a>
                        <div class="collapse" id="collapsePM" aria-labelledby="headingOne" data-bs-parent="#sidenavAccordion">
                            <nav class="sb-sidenav-menu-nested nav">
                                <a class="nav-link text-light loading" href="../admin/evaluation.php">Evaluation</a>
                            </nav>
                        </div>
                        <a class="nav-link collapsed text-light" href="#" data-bs-toggle="collapse" data-bs-target="#collapseSR" aria-expanded="false" aria-controls="collapseSR">
                            <div class="sb-nav-link-icon"><i class="fa fa-address-card"></i></div>
                            Social Recognition
                            <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                        </a>
                        <div class="collapse" id="collapseSR" aria-labelledby="headingOne" data-bs-parent="#sidenavAccordion">
                            <nav class="sb-sidenav-menu-nested nav">
                                <a class="nav-link text-light loading" href="../admin/awardee.php">Awardee</a>
                                <a class="nav-link text-light loading" href="../admin/recognition.php">Generate Certificate</a>
                            </nav>
                        </div>
                        <div class="sb-sidenav-menu-heading text-center text-muted border-top border-1 border-secondary mt-3">Account Management</div>
                        <a class="nav-link collapsed text-light" href="#" data-bs-toggle="collapse" data-bs-target="#collapseLayouts" aria-expanded="false" aria-controls="collapseLayouts">
                            <div class="sb-nav-link-icon"><i class="fas fa-columns"></i></div>
                            Accounts
                            <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                        </a>
                        <div class="collapse" id="collapseLayouts" aria-labelledby="headingOne" data-bs-parent="#sidenavAccordion">
                            <nav class="sb-sidenav-menu-nested nav">
                                <a class="nav-link text-light loading" href="../admin/calendar.php">Calendar</a>
                                <a class="nav-link text-light loading" href="../admin/admin.php">Admin Accounts</a>
                                <a class="nav-link text-light loading" href="../admin/employee.php">Employee Accounts</a>
                            </nav>
                        </div>
                        <div class="collapse" id="collapsePages" aria-labelledby="headingTwo" data-bs-parent="#sidenavAccordion">
                        </div>
                    </div>
                </div>
                <div class="sb-sidenav-footer bg-black text-light border-top border-1 border-secondary">
                    <div class="small">Logged in as: <?php echo htmlspecialchars($adminInfo['role']); ?></div>
                </div>
            </nav>
        </div>
        <div id="layoutSidenav_content">
            <main class="bg-black">
                <div class="container-fluid position-relative px-4">
                    <h1 class="mb-4 text-light">Dashboard</h1>
                        <div class="container" id="calendarContainer" 
                            style="position: fixed; top: 9%; right: 0; z-index: 1050; 
                            width: 700px; display: none;">
                            <div class="row">
                                <div class="col-md-12">
                                    <div id="calendar" class="p-2"></div>
                                </div>
                            </div>
                        </div>
                <!-- Leave Request Status and Employee Performance Section -->
                <div class="row">
                    <div class="col-xl-6">
                        <div class="card mb-4">
                            <div class="card-header bg-dark text-light border-bottom border-1 border-secondary">
                                <i class="fas fa-chart-pie me-1"></i> 
                                <a class="text-light" href="../admin/leave_requests.php">Leave Request Status </a>
                            </div>
                            <div class="card-body bg-dark">
                                <canvas id="leaveStatusChart" width="300" height="300"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-6">
                        <div class="card mb-4">
                            <div class="card-header bg-dark text-light border-bottom border-1 border-secondary">
                                <i class="fas fa-chart-line me-1"></i>
                                <a class="text-light" href="#">Employee Performance</a>
                            </div>
                            <div class="card-body bg-dark">
                                <canvas id="employeePerformanceChart" width="300" height="300"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Employee Count per Department Section -->
                <div class="row">
                    <div class="col-xl-12">
                        <div class="card mb-4">
                            <div class="card-header bg-dark text-light border-bottom border-1 border-secondary">
                                <i class="fas fa-users me-1"></i>
                                <a class="text-light" href="#">Employee Count per Department</a>
                            </div>
                            <div class="card-body bg-dark">
                                <canvas id="employeeDepartmentChart" width="300" height="300"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Department Attendance Record Section -->
                <div class="row">
                    <div class="col-xl-12">
                        <div class="card mb-4">
                            <div class="card-header bg-dark text-light border-bottom border-1 border-secondary">
                                <i class="fas fa-chart-bar me-1"></i>
                                <a class="text-light" href="#">Department Attendance Record</a>
                            </div>
                            <div class="card-body bg-dark">
                                <select class="department-select form-control mb-3" id="departmentSelect">
                                    <option value="">Show All Departments</option>
                                    <option value="hr">HR Department</option>
                                    <option value="it">IT Department</option>
                                    <option value="sales">Sales Department</option>
                                    <option value="marketing">Marketing Department</option>
                                </select>
                                <div class="chart-container">
                                    <canvas id="attendanceChart" width="500" height="150"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Data Table Example Section -->
                <div class="row">
                    <div class="col-xl-12">
                        <div class="card mb-4 bg-dark text-light">
                            <div class="card-header border-bottom border-1 border-secondary d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fas fa-table me-1"></i>
                                    DataTable Example
                                </div>
                                <div class="input-group input-group-sm" style="width: 200px;">
                                    <input class="form-control" type="text" placeholder="Search..." aria-label="Search" aria-describedby="btnNavbarSearch" />
                                    <button class="btn btn-warning" id="btnNavbarSearch" type="button"><i class="fas fa-search"></i></button>
                                </div>
                            </div>
                            <div class="card-body">
                                <table id="datatablesSimple" class="table text-light">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Position</th>
                                            <th>Office</th>
                                            <th>Age</th>
                                            <th>Start date</th>
                                            <th>Salary</th>
                                        </tr>
                                    </thead>
                                    <tfoot>
                                        <tr>
                                            <th>Name</th>
                                            <th>Position</th>
                                            <th>Office</th>
                                            <th>Age</th>
                                            <th>Start date</th>
                                            <th>Salary</th>
                                        </tr>
                                    </tfoot>
                                    <tbody>
                                        <tr>
                                            <td>Zorita Serrano</td>
                                            <td>Software Engineer</td>
                                            <td>San Francisco</td>
                                            <td>56</td>
                                            <td>2012/06/01</td>
                                            <td>$115,000</td>
                                        </tr>
                                        <tr>
                                            <td>Jennifer Acosta</td>
                                            <td>Junior Javascript Developer</td>
                                            <td>Edinburgh</td>
                                            <td>43</td>
                                            <td>2013/02/01</td>
                                            <td>$75,650</td>
                                        </tr>
                                        <tr>
                                            <td>Cara Stevens</td>
                                            <td>Sales Assistant</td>
                                            <td>New York</td>
                                            <td>46</td>
                                            <td>2011/12/06</td>
                                            <td>$145,600</td>
                                        </tr>
                                        <tr>
                                            <td>Hermione Butler</td>
                                            <td>Regional Director</td>
                                            <td>London</td>
                                            <td>47</td>
                                            <td>2011/03/21</td>
                                            <td>$356,250</td>
                                        </tr>
                                        <tr>
                                            <td>Lael Greer</td>
                                            <td>Systems Administrator</td>
                                            <td>London</td>
                                            <td>21</td>
                                            <td>2009/02/27</td>
                                            <td>$103,500</td>
                                        </tr>
                                        <tr>
                                            <td>Jonas Alexander</td>
                                            <td>Developer</td>
                                            <td>San Francisco</td>
                                            <td>30</td>
                                            <td>2010/07/14</td>
                                            <td>$86,500</td>
                                        </tr>
                                        <tr>
                                            <td>Shad Decker</td>
                                            <td>Regional Director</td>
                                            <td>Edinburgh</td>
                                            <td>51</td>
                                            <td>2008/11/13</td>
                                            <td>$183,000</td>
                                        </tr>
                                        <tr>
                                            <td>Michael Bruce</td>
                                            <td>Javascript Developer</td>
                                            <td>Singapore</td>
                                            <td>29</td>
                                            <td>2011/06/27</td>
                                            <td>$183,000</td>
                                        </tr>
                                        <tr>
                                            <td>Donna Snider</td>
                                            <td>Customer Support</td>
                                            <td>New York</td>
                                            <td>27</td>
                                            <td>2011/01/25</td>
                                            <td>$112,000</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                    <!-- for leaveStatusChart -->
                    <?php

                        include '../db/db_conn.php';

                        $sql = "SELECT status, COUNT(*) as count FROM leave_requests GROUP BY status";
                        $result = $conn->query($sql);

                        $status_counts = [
                            'Approved' => 0,
                            'Supervisor Approved' => 0,
                            'Denied' => 0,
                        ];
                        while ($row = $result->fetch_assoc()) {
                            $status = $row['status'];
                            if (isset($status_counts[$status])) {
                                $status_counts[$status] = $row['count'];
                            }
                        }

                        $conn->close();
                    ?>
                </div>
            </main>
            <!-- All Notifications Modal -->
            <!-- Notifications Modal -->
<div class="modal fade" id="allNotificationsModal" tabindex="-1" aria-labelledby="allNotificationsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content custom-modal">
            <div class="modal-header border-bottom border-dark">
                <h5 class="modal-title" id="allNotificationsModalLabel">🔔 All Notifications</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body custom-body" id="allNotificationsModalBody">
                <!-- All notifications will be loaded here -->
                <ul class="list-group">
                    <?php foreach ($notifications as $notification): ?>
                        <li class="list-group-item custom-item <?php echo $notification['is_read'] ? 'read' : 'unread'; ?>">
                            <span class="notif-icon">
                                <?php if (!$notification['is_read']): ?>
                                    🔴
                                <?php else: ?>
                                    ⚪
                                <?php endif; ?>
                            </span>
                            <?php echo htmlspecialchars($notification['message']); ?>
                            <span class="time-stamp">Just now</span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="modal-footer border-top border-dark">
                <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
                <div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content bg-dark text-light">
                            <div class="modal-header border-bottom border-secondary">
                                <h5 class="modal-title" id="logoutModalLabel">Confirm Logout</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                Are you sure you want to log out?
                            </div>
                            <div class="modal-footer border-top border-secondary">
                                <button type="button" class="btn border-secondary text-light" data-bs-dismiss="modal">Cancel</button>
                                <form action="../admin/logout.php" method="POST">
                                    <button type="submit" class="btn btn-danger">Logout</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>  
            <footer class="py-4 bg-dark mt-auto border-top border-1 border-secondary">
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
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        // Doughnut chart data
        const data = {
            labels: ['Approved', 'Pending', 'Denied'],
            datasets: [{
                data: [
                    <?php echo $status_counts['Approved']; ?>,
                    <?php echo $status_counts['Supervisor Approved']; ?>,
                    <?php echo $status_counts['Denied']; ?>
                ],
                backgroundColor: ['#28a745', '#ffc107', '#dc3545']
            }]
        };

        // Doughnut chart configuration
        const leaveStatusCtx = document.getElementById('leaveStatusChart').getContext('2d');
        const leaveStatusChart = new Chart(leaveStatusCtx, {
            type: 'doughnut',
            data: data,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                legend: {
                    position: 'top'
                },
                title: {
                    display: true,
                    text: 'Leave Request Statuses'
                }
            }
        });
        //for leaveStatusChart end

        //for calendar only
        // Global variable for calendar
        let calendar; // Declare calendar variable globally

function toggleCalendar() {
    const calendarContainer = document.getElementById('calendarContainer');

    // Toggle visibility of the calendar container
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

// Function to initialize FullCalendar
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
            url: '../db/holiday.php',  // Endpoint for fetching events
            method: 'GET',
            failure: function() {
                alert('There was an error fetching events!');
            }
        }
    });

    calendar.render();
}

// Set the current date when the page loads
document.addEventListener('DOMContentLoaded', function () {
    const currentDateElement = document.getElementById('currentDate');
    const currentDate = new Date().toLocaleDateString(); // Get the current date
    currentDateElement.textContent = currentDate; // Set the date text
});

// Close the calendar when clicking outside of it
document.addEventListener('click', function(event) {
    const calendarContainer = document.getElementById('calendarContainer');
    const calendarButton = document.querySelector('button[onclick="toggleCalendar()"]');

    // Hide the calendar if the user clicks outside of the calendar and button
    if (!calendarContainer.contains(event.target) && !calendarButton.contains(event.target)) {
        calendarContainer.style.display = 'none';
    }
});
        //for calendar only end

        //for leave request (error)
 document.addEventListener('DOMContentLoaded', function () {
    const calendarEl = document.getElementById('calendar');
    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth', // Basic view to confirm setup
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth'
        }
    });
    calendar.render();
    //console.log("Calendar initialized and rendered");

            function fetchLeaveData(date) {
                fetch(`leave_data.php?date=${date}`)
                .then(response => response.json())
                .then(data => {
                    let leaveDetails = 'Employees on leave:\n';
                    if (data.length > 0) {
                        data.forEach(employee => {
                            leaveDetails += `${employee.name} (${employee.leave_type})\n`;
                        });
                    } else {
                        leaveDetails = 'No employees on leave for this day.';
                    }
                    alert(leaveDetails); // You can replace this with a modal or a more styled output
                })
                .catch(error => {
                    console.error('Error fetching leave data:', error);
                    alert('An error occurred while fetching leave data.');
                });
            }
        });
            //for leave request (error) end

            function setCurrentTime() {
    const currentTimeElement = document.getElementById('currentTime');
    const currentDateElement = document.getElementById('currentDate');

    // Get the current date and time in UTC
    const currentDate = new Date();
    
    // Adjust time to Philippine Time (UTC+8)
    currentDate.setHours(currentDate.getHours() + 0);

    // Extract hours, minutes, and seconds
    const hours = currentDate.getHours();
    const minutes = currentDate.getMinutes();
    const seconds = currentDate.getSeconds();

    // Format hours, minutes, and seconds to ensure they are always two digits
    const formattedHours = hours < 10 ? '0' + hours : hours;
    const formattedMinutes = minutes < 10 ? '0' + minutes : minutes;
    const formattedSeconds = seconds < 10 ? '0' + seconds : seconds;

    // Set the current time
    currentTimeElement.textContent = `${formattedHours}:${formattedMinutes}:${formattedSeconds}`;

    // Set the current date
    currentDateElement.textContent = currentDate.toLocaleDateString();
}

// Initial call to set the current time and date
setCurrentTime();

// Update the current time every second
setInterval(setCurrentTime, 1000);

        // Function to show notification details in modal
        function showNotificationDetails(message) {
            const modalBody = document.getElementById('notificationModalBody');
            modalBody.textContent = message;
            const notificationModal = new bootstrap.Modal(document.getElementById('notificationModal'));
            notificationModal.show();
        }

        // Add event listeners to notification items
        document.addEventListener('DOMContentLoaded', function () {
            const notificationItems = document.querySelectorAll('.dropdown-item');
            notificationItems.forEach(item => {
                item.addEventListener('click', function (event) {
                    event.preventDefault();
                    const message = this.textContent;
                    showNotificationDetails(message);
                });
            });
        });

        

        // Function to hide reminder
        function hideReminder() {
            const reminderElement = document.getElementById('reminder');
            if (reminderElement) {
                reminderElement.style.display = 'none';
            }
        }

        // Function to show reminder
        function showReminder() {
            const reminderElement = document.getElementById('reminder');
            if (reminderElement) {
                reminderElement.style.display = 'block';
            }
        }

        // Add event listeners to notification items
        document.addEventListener('DOMContentLoaded', function () {
            const notificationItems = document.querySelectorAll('.dropdown-item');
            notificationItems.forEach(item => {
                item.addEventListener('click', function (event) {
                    event.preventDefault();
                    const notificationId = this.getAttribute('data-id');
                    markAsRead(notificationId);
                });
            });

            // Show reminder if there are unread notifications
            if (document.querySelectorAll('.dropdown-item.font-weight-bold').length > 0) {
                showReminder();
            }
        });

        // Function to clear all notifications
        function clearAllNotifications() {
            fetch('../admin/clear_notifications.php', {
                method: 'POST'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload(); // Reload the page to update the notifications
                } else {
                    showErrorModal('Failed to clear notifications.');
                }
            })
            .catch(error => {
                console.error('Error clearing notifications:', error);
                showErrorModal('An error occurred while clearing notifications.');
            });
        }

        // Function to show all notifications in modal
        function showAllNotifications() {
            const allNotificationsModalBody = document.getElementById('allNotificationsModalBody');
            allNotificationsModalBody.innerHTML = `
                <ul class="list-group">
                    <?php foreach ($notifications as $notification): ?>
                        <li class="list-group-item bg-dark text-white <?php echo $notification['is_read'] ? '' : 'font-weight-bold'; ?>">
                            <?php echo htmlspecialchars($notification['message']); ?>
                            <?php if (!$notification['is_read']): ?>
                                <span class="badge bg-danger ms-2">•</span>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            `;
            const allNotificationsModal = new bootstrap.Modal(document.getElementById('allNotificationsModal'));
            allNotificationsModal.show();
        }

        function showErrorModal(message) {
            const errorModal = new bootstrap.Modal(document.getElementById('errorModal'));
            document.getElementById('errorMessage').textContent = message;
            errorModal.show();
        }

        function showNotificationDetails(message) {
            const modalBody = document.getElementById('notificationModalBody');
            modalBody.textContent = message;
            const notificationModal = new bootstrap.Modal(document.getElementById('notificationModal'));
            notificationModal.show();
        }

        document.addEventListener('DOMContentLoaded', function () {
            const notificationItems = document.querySelectorAll('.dropdown-item');
            notificationItems.forEach(item => {
                item.addEventListener('click', function (event) {
                    event.preventDefault();
                    const message = this.textContent;
                    showNotificationDetails(message);
                });
            });
        });

        // Dummy Data for Employee Performance
        const employees = [
            { name: "John Doe", loansDisbursed: 50, recoveryRate: 95, customerRating: 4.5 },
            { name: "Jane Smith", loansDisbursed: 45, recoveryRate: 85, customerRating: 4.2 },
            { name: "Alice Johnson", loansDisbursed: 60, recoveryRate: 90, customerRating: 4.7 },
        ];

        // Employee Performance Chart
        const employeePerformanceCtx = document.getElementById("employeePerformanceChart").getContext("2d");
        new Chart(employeePerformanceCtx, {
            type: "bar",
            data: {
                labels: employees.map(emp => emp.name),
                datasets: [{
                    label: "Loans Disbursed",
                    data: employees.map(emp => emp.loansDisbursed),
                    backgroundColor: "#1abc9c", // Highlight color
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: true },
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: "#4a5f78", // Grid color
                        },
                        ticks: {
                            color: "#fff", // Y-axis text color
                        },
                    },
                    x: {
                        grid: {
                            color: "#4a5f78", // Grid color
                        },
                        ticks: {
                            color: "#fff", // X-axis text color
                        },
                    },
                },
            },
        });

        // Dummy Data for Employee Count per Department
        const departments = [
            { name: "Sales", count: 25 },
            { name: "Marketing", count: 15 },
            { name: "Finance", count: 10 },
            { name: "HR", count: 8 },
            { name: "IT", count: 20 },
            { name: "Credit", count: 12 }, // Added Credit Department
        ];

        // Employee Count per Department Bar Chart
        const employeeDepartmentCtx = document.getElementById("employeeDepartmentChart").getContext("2d");
        new Chart(employeeDepartmentCtx, {
            type: "bar",
            data: {
                labels: departments.map(dept => dept.name),
                datasets: [{
                    label: "Employee Count",
                    data: departments.map(dept => dept.count),
                    backgroundColor: "#1abc9c", // Bar color
                    borderColor: "#34495e", // Border color
                    borderWidth: 1,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false, // Hide legend for bar chart
                    },
                    tooltip: {
                        enabled: true,
                        callbacks: {
                            label: function (context) {
                                const label = context.label || "";
                                const value = context.raw || 0;
                                return `${label}: ${value} employees`;
                            },
                        },
                    },
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: "#4a5f78", // Grid color
                        },
                        ticks: {
                            color: "#fff", // Y-axis text color
                        },
                    },
                    x: {
                        grid: {
                            color: "#4a5f78", // Grid color
                        },
                        ticks: {
                            color: "#fff", // X-axis text color
                        },
                    },
                },
            },
        });

        document.addEventListener('DOMContentLoaded', function () {
            const ctx = document.getElementById('attendanceChart').getContext('2d');
            const departmentSelect = document.getElementById('departmentSelect');

            // Temporary data for departments and their employees' attendance
            const departmentData = {
                hr: {
                    labels: ['John Doe', 'Jane Smith', 'Alice Johnson', 'Bob Brown'],
                    attendance: ['Present', 'Absent', 'Present', 'Present'] // Attendance status
                },
                it: {
                    labels: ['Mike Ross', 'Harvey Specter', 'Rachel Zane', 'Louis Litt'],
                    attendance: ['Present', 'Present', 'Absent', 'Present']
                },
                sales: {
                    labels: ['Tom Cruise', 'Emma Watson', 'Chris Evans', 'Scarlett Johansson'],
                    attendance: ['Absent', 'Present', 'Present', 'Absent']
                },
                marketing: {
                    labels: ['Tony Stark', 'Steve Rogers', 'Natasha Romanoff', 'Bruce Banner'],
                    attendance: ['Present', 'Present', 'Present', 'Absent']
                }
            };

            // Calculate total present and absent employees for each department
            const departmentSummary = {
                labels: ['HR', 'IT', 'Sales', 'Marketing'],
                present: [
                    departmentData.hr.attendance.filter(status => status === 'Present').length,
                    departmentData.it.attendance.filter(status => status === 'Present').length,
                    departmentData.sales.attendance.filter(status => status === 'Present').length,
                    departmentData.marketing.attendance.filter(status => status === 'Present').length
                ],
                absent: [
                    departmentData.hr.attendance.filter(status => status === 'Absent').length,
                    departmentData.it.attendance.filter(status => status === 'Absent').length,
                    departmentData.sales.attendance.filter(status => status === 'Absent').length,
                    departmentData.marketing.attendance.filter(status => status === 'Absent').length
                ]
            };

            let attendanceChart;

            // Function to create or update the chart
            function updateChart(labels, presentData, absentData = null, label = 'Department Attendance') {
                if (attendanceChart) {
                    attendanceChart.destroy(); // Destroy the existing chart
                }

                const datasets = [
                    {
                        label: 'Present',
                        data: presentData,
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 1
                    }
                ];

                if (absentData) {
                    datasets.push({
                        label: 'Absent',
                        data: absentData,
                        backgroundColor: 'rgba(255, 99, 132, 0.2)',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 1
                    });
                }

                attendanceChart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: datasets
                    },
                    options: {
                        scales: {
                            y: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: 'Number of Employees'
                                }
                            },
                            x: {
                                title: {
                                    display: true,
                                    text: label === 'Department Attendance' ? 'Departments' : 'Employees'
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                display: true,
                                position: 'top',
                            },
                            tooltip: {
                                enabled: true
                            }
                        }
                    }
                });
            }

            // Initial chart showing present and absent employees for all departments
            updateChart(departmentSummary.labels, departmentSummary.present, departmentSummary.absent, 'Department Attendance');

            // Event listener for department selection
            departmentSelect.addEventListener('change', function () {
                const selectedDepartment = departmentSelect.value;

                if (selectedDepartment && departmentData[selectedDepartment]) {
                    const { labels, attendance } = departmentData[selectedDepartment];
                    const presentData = attendance.map(status => status === 'Present' ? 1 : 0);
                    const absentData = attendance.map(status => status === 'Absent' ? 1 : 0);
                    updateChart(labels, presentData, absentData, 'Employee Attendance');
                } else {
                    // Show present and absent employees for all departments if no specific department is selected
                    updateChart(departmentSummary.labels, departmentSummary.present, departmentSummary.absent, 'Department Attendance');
                }
            });
        });
    </script>

    <!-- Notification Details Modal -->
    <!-- <div class="modal fade" id="notificationModal" tabindex="-1" aria-labelledby="notificationModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content bg-dark text-light">
                <div class="modal-header border-bottom border-secondary">
                    <h5 class="modal-title" id="notificationModalLabel">Notification Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="notificationModalBody">
                    Notification details will be loaded here -->
                <!-- </div>
                <div class="modal-footer border-top border-secondary">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    Error Modal -->
    <div class="modal fade" id="errorModal" tabindex="-1" aria-labelledby="errorModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content bg-dark text-light">
                <div class="modal-header border-bottom border-secondary">
                    <h5 class="modal-title" id="errorModalLabel">Error</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p id="errorMessage"></p>
                </div>
                <div class="modal-footer border-top border-secondary">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- All Notifications Modal -->
    <div class="modal fade" id="allNotificationsModal" tabindex="-1" aria-labelledby="allNotificationsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content custom-modal">
                <div class="modal-header border-bottom border-dark">
                    <h5 class="modal-title" id="allNotificationsModalLabel">🔔 All Notifications</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body custom-body" id="allNotificationsModalBody">
                    <!-- All notifications will be loaded here -->
                    <ul class="list-group">
                        <?php foreach ($notifications as $notification): ?>
                            <li class="list-group-item custom-item <?php echo $notification['is_read'] ? 'read' : 'unread'; ?>">
                                <span class="notif-icon">
                                    <?php if (!$notification['is_read']): ?>
                                        🔴
                                    <?php else: ?>
                                        ⚪
                                    <?php endif; ?>
                                </span>
                                <?php echo htmlspecialchars($notification['message']); ?>
                                <span class="time-stamp">Just now</span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div class="modal-footer border-top border-dark">
                    <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Logout Modal -->
    <div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content bg-dark text-light">
                <div class="modal-header border-bottom border-secondary">
                    <h5 class="modal-title" id="logoutModalLabel">Confirm Logout</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to log out?
                </div>
                <div class="modal-footer border-top border-secondary">
                    <button type="button" class="btn border-secondary text-light" data-bs-dismiss="modal">Cancel</button>
                    <form action="../admin/logout.php" method="POST">
                        <button type="submit" class="btn btn-danger">Logout</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="../js/admin.js"></script>
    <script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.querySelector('.input-group-sm input');
    const table = document.getElementById('datatablesSimple');
    const rows = table.querySelectorAll('tbody tr');

    searchInput.addEventListener('input', function () {
        const searchTerm = searchInput.value.toLowerCase();

        rows.forEach(row => {
            const cells = row.querySelectorAll('td');
            const nameCell = cells[0].textContent.toLowerCase();

            if (nameCell.includes(searchTerm)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });
});
</script>

