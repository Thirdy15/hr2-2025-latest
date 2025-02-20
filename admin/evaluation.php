<?php
session_start();

if (!isset($_SESSION['a_id'])) {
    header("Location: ../admin/login.php");
    exit();
}

include '../db/db_conn.php';

// Fetch the admin's ID
$adminId = $_SESSION['a_id'];

// Fetch the admin's info
$sql = "SELECT a_id, firstname, middlename, lastname, birthdate, email, role, department, phone_number, address, pfp FROM admin_register WHERE a_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $adminId);
$stmt->execute();
$result = $stmt->get_result();
$adminInfo = $result->fetch_assoc();

// Function to get evaluation progress by department for a specific admin
function getAdminEvaluationProgress($conn, $department, $adminId) {
    // Get total employees in the department
    $employeeQuery = "SELECT COUNT(*) as total FROM employee_register WHERE department = '$department'";
    $employeeResult = $conn->query($employeeQuery);
    $totalEmployees = $employeeResult->fetch_assoc()['total'];

    // Get total employees evaluated by the admin in the department
    $evaluatedQuery = "SELECT COUNT(*) as evaluated FROM admin_evaluations WHERE department = '$department' AND a_id = '$adminId'";
    $evaluatedResult = $conn->query($evaluatedQuery);
    $evaluated = $evaluatedResult->fetch_assoc()['evaluated'];

    $pendingEmployees = $totalEmployees - $evaluated;

    return array('total' => $totalEmployees, 'evaluated' => $evaluated, 'pending' => $pendingEmployees);
}

// Fetch data for different departments for the logged-in admin
$financeData = getAdminEvaluationProgress($conn, 'Finance Department', $adminId);
$hrData = getAdminEvaluationProgress($conn, 'Human Resource Department', $adminId);
$administrationData = getAdminEvaluationProgress($conn, 'Administration Department', $adminId);
$salesData = getAdminEvaluationProgress($conn, 'Sales Department', $adminId);
$creditData = getAdminEvaluationProgress($conn, 'Credit Department', $adminId);
$itData = getAdminEvaluationProgress($conn, 'IT Department', $adminId);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Evaluation Dashboard | HR2</title>
    <link href="../css/styles.css" rel="stylesheet" />
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />
    <link href="../css/calendar.css" rel="stylesheet"/>
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="sb-nav-fixed bg-black">
    <nav class="sb-topnav navbar navbar-expand navbar-dark border-bottom border-1 border-secondary bg-dark">
        <a class="navbar-brand ps-3 text-muted" href="../admin/dashboard.php">Microfinance</a>
        <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle" href="#!"><i class="fas fa-bars text-light"></i></button>
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
                                    <img src="<?php echo (!empty($adminInfo['pfp']) && $adminInfo['pfp'] !== 'defaultpfp.png') 
                                        ? htmlspecialchars($adminInfo['pfp']) 
                                        : '../img/defaultpfp.jpg'; ?>" 
                                        class="rounded-circle border border-light" width="120" height="120" alt="Profile Picture" />
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                                    <li><a class="dropdown-item loading" href="../admin/profile.php">Profile</a></li>
                                    <li><a class="dropdown-item" href="#!">Settings</a></li>
                                    <li><a class="dropdown-item" href="#!">Activity Log</a></li>
                                    <li><hr class="dropdown-divider" /></li>
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
                                <a class="nav-link text-light loading"  href="../admin/leave_allocation.php">Set Leave</a>
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
                        <div class="sb-sidenav-menu-heading text-center text-muted border-top border-1 border-secondary">Account Management</div>
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
                    <h1 class="mb-4 text-light">Evaluation</h1>
                </div>
                <div class="container" id="calendarContainer" 
                    style="position: fixed; top: 9%; right: 0; z-index: 1050; 
                    width: 700px; display: none;">
                    <div class="row">
                        <div class="col-md-12">
                            <div id="calendar" class="p-2"></div>
                        </div>
                    </div>
                </div>     
                <div class="container-fluid px-4">
                    <div class="row justify-content-center">
                        <div class="col-xl-4 col-md-6 mt-5">
                            <div class="card mb-4">
                                <div class="card-body bg-secondary text-center">
                                    <a href="../admin/finance.php" class="btn card-button text-light font-weight-bold bg-dark border border-dark w-100">Finance Department</a>
                                </div>
                                <div class="card-footer d-flex align-items-center justify-content-between bg-dark border-bottom border-light department-toggle">
                                    <div class="small text-warning">Details</div>
                                </div>
                                <div id="financeInfo" class=" bg-dark text-dark">
                                    <div class="card-body">
                                        <h5 class="text-center mb-4 text-light">Finance Department Evaluation Status</h5>
                                        <div class="text-center mb-3">
                                            <span class="badge badge-primary mx-1">Total Employees: <?php echo $financeData['total']; ?></span>
                                            <span class="badge badge-success mx-1">Evaluated: <?php echo $financeData['evaluated']; ?></span>
                                            <span class="badge badge-warning mx-1">Pending: <?php echo $financeData['pending']; ?></span>
                                        </div>
                                        <div class="progress mb-2">
                                        <?php if ($financeData['total'] > 0): ?>
                                    <div class="progress-bar bg-success font-weight-bold" role="progressbar" 
                                        style="width: <?php echo ($financeData['evaluated'] / $financeData['total']) * 100; ?>%;" 
                                        aria-valuenow="<?php echo $financeData['evaluated']; ?>" 
                                        aria-valuemin="0" 
                                        aria-valuemax="<?php echo $financeData['total']; ?>">
                                        Evaluated (<?php echo $financeData['evaluated']; ?>)
                                    </div>
                                    <div class="progress-bar bg-warning text-dark font-weight-bold" role="progressbar" 
                                        style="width: <?php echo ($financeData['pending'] / $financeData['total']) * 100; ?>%;" 
                                        aria-valuenow="<?php echo $financeData['pending']; ?>" 
                                        aria-valuemin="0" 
                                        aria-valuemax="<?php echo $financeData['total']; ?>">
                                        Pending (<?php echo $financeData['pending']; ?>)
                                    </div>
                                <?php else: ?>
                                    <div class="progress-bar bg-secondary font-weight-bold w-100" role="progressbar" 
                                        aria-valuenow="0" 
                                        aria-valuemin="0" 
                                        aria-valuemax="100">
                                        No employees available
                                    </div>
                                <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-4 col-md-6 mt-5">
                            <div class="card mb-4">
                                <div class="card-body bg-secondary text-center">
                                    <a href="../admin/hr.php" class="btn card-button text-light font-weight-bold bg-dark border border-dark w-100">Human Resource Department</a>
                                </div>
                                <div class="card-footer d-flex align-items-center justify-content-between bg-dark border-bottom border-light department-toggle">
                                    <div class="small text-warning">Details</div>
                                </div>
                                <div id="hrInfo" class="bg-dark text-dark">
                                    <div class="card-body">
                                        <h5 class="text-center mb-4 text-light">Human Resource Evaluation Status</h5>
                                        <div class="text-center mb-3">
                                            <span class="badge badge-primary mx-1">Total Employees: <?php echo $hrData['total']; ?></span>
                                            <span class="badge badge-success mx-1">Evaluated: <?php echo $hrData['evaluated']; ?></span>
                                            <span class="badge badge-warning mx-1">Pending: <?php echo $hrData['pending']; ?></span>
                                        </div>
                                        <div class="progress mb-2">
                                        <?php if ($hrData['total'] > 0): ?>
                                    <div class="progress-bar bg-success font-weight-bold" role="progressbar" 
                                        style="width: <?php echo ($hrData['evaluated'] / $hrData['total']) * 100; ?>%;" 
                                        aria-valuenow="<?php echo $hrData['evaluated']; ?>" 
                                        aria-valuemin="0" 
                                        aria-valuemax="<?php echo $hrData['total']; ?>">
                                        Evaluated (<?php echo $hrData['evaluated']; ?>)
                                    </div>
                                    <div class="progress-bar bg-warning text-dark font-weight-bold" role="progressbar" 
                                        style="width: <?php echo ($hrData['pending'] / $hrData['total']) * 100; ?>%;" 
                                        aria-valuenow="<?php echo $hrData['pending']; ?>" 
                                        aria-valuemin="0" 
                                        aria-valuemax="<?php echo $hrData['total']; ?>">
                                        Pending (<?php echo $hrData['pending']; ?>)
                                    </div>
                                <?php else: ?>
                                    <div class="progress-bar bg-secondary font-weight-bold w-100" role="progressbar" 
                                        aria-valuenow="0" 
                                        aria-valuemin="0" 
                                        aria-valuemax="100">
                                        No employees available
                                    </div>
                                <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-4 col-md-6 mt-5">
                            <div class="card mb-4">
                                <div class="card-body bg-secondary text-center">
                                    <a href="../admin/administration.php" class="btn card-button text-light font-weight-bold bg-dark border border-dark w-100">Administration Department</a>
                                </div>
                                <div class="card-footer d-flex align-items-center justify-content-between bg-dark border-bottom border-light department-toggle">
                                    <div class="small text-warning">Details</div>
                                </div>
                                <div id="administrationInfo" class="bg-dark text-dark">
                                    <div class="card-body">
                                        <h5 class="text-center mb-4 text-light">Administration Evaluation Status</h5>
                                        <div class="text-center mb-3">
                                            <span class="badge badge-primary mx-1">Total Employees: <?php echo $administrationData['total']; ?></span>
                                            <span class="badge badge-success mx-1">Evaluated: <?php echo $administrationData['evaluated']; ?></span>
                                            <span class="badge badge-warning mx-1">Pending: <?php echo $administrationData['pending']; ?></span>
                                        </div>
                                        <div class="progress mb-2">
                                        <?php if ($administrationData['total'] > 0): ?>
                                    <div class="progress-bar bg-success font-weight-bold" role="progressbar" 
                                        style="width: <?php echo ($administrationData['evaluated'] / $administrationData['total']) * 100; ?>%;" 
                                        aria-valuenow="<?php echo $administrationData['evaluated']; ?>" 
                                        aria-valuemin="0" 
                                        aria-valuemax="<?php echo $administrationData['total']; ?>">
                                        Evaluated (<?php echo $administrationData['evaluated']; ?>)
                                    </div>
                                    <div class="progress-bar bg-warning text-dark font-weight-bold" role="progressbar" 
                                        style="width: <?php echo ($administrationData['pending'] / $administrationData['total']) * 100; ?>%;" 
                                        aria-valuenow="<?php echo $administrationData['pending']; ?>" 
                                        aria-valuemin="0" 
                                        aria-valuemax="<?php echo $administrationData['total']; ?>">
                                        Pending (<?php echo $administrationData['pending']; ?>)
                                    </div>
                                <?php else: ?>
                                    <div class="progress-bar bg-secondary font-weight-bold w-100" role="progressbar" 
                                        aria-valuenow="0" 
                                        aria-valuemin="0" 
                                        aria-valuemax="100">
                                        No employees available
                                    </div>
                                <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-4 col-md-6 mt-5">
                            <div class="card mb-4">
                                <div class="card-body bg-secondary text-center">
                                    <a href="../admin/sales.php" class="btn card-button text-light font-weight-bold bg-dark border border-dark w-100">Sales Department</a>
                                </div>
                                <div class="card-footer d-flex align-items-center justify-content-between bg-dark border-bottom border-light department-toggle">
                                    <div class="small text-warning">Details</div>
                                </div>
                                <div id="salesInfo" class="bg-dark text-dark">
                                    <div class="card-body">
                                        <h5 class="text-center mb-4 text-light">Sales Evaluation Status</h5>
                                        <div class="text-center mb-3">
                                            <span class="badge badge-primary mx-1">Total Employees: <?php echo $salesData['total']; ?></span>
                                            <span class="badge badge-success mx-1">Evaluated: <?php echo $salesData['evaluated']; ?></span>
                                            <span class="badge badge-warning mx-1">Pending: <?php echo $salesData['pending']; ?></span>
                                        </div>
                                        <div class="progress mb-2">
                                        <?php if ($salesData['total'] > 0): ?>
                                    <div class="progress-bar bg-success font-weight-bold" role="progressbar" 
                                        style="width: <?php echo ($salesData['evaluated'] / $salesData['total']) * 100; ?>%;" 
                                        aria-valuenow="<?php echo $salesData['evaluated']; ?>" 
                                        aria-valuemin="0" 
                                        aria-valuemax="<?php echo $salesData['total']; ?>">
                                        Evaluated (<?php echo $salesData['evaluated']; ?>)
                                    </div>
                                    <div class="progress-bar bg-warning text-dark font-weight-bold" role="progressbar" 
                                        style="width: <?php echo ($salesData['pending'] / $salesData['total']) * 100; ?>%;" 
                                        aria-valuenow="<?php echo $salesData['pending']; ?>" 
                                        aria-valuemin="0" 
                                        aria-valuemax="<?php echo $salesData['total']; ?>">
                                        Pending (<?php echo $salesData['pending']; ?>)
                                    </div>
                                <?php else: ?>
                                    <div class="progress-bar bg-secondary font-weight-bold w-100" role="progressbar" 
                                        aria-valuenow="0" 
                                        aria-valuemin="0" 
                                        aria-valuemax="100">
                                        No employees available
                                    </div>
                                <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-4 col-md-6 mt-5">
                            <div class="card mb-4">
                                <div class="card-body bg-secondary text-center">
                                    <a href="../admin/credit.php" class="btn card-button text-light font-weight-bold bg-dark border border-dark w-100">Credit Department</a>
                                </div>
                                <div class="card-footer d-flex align-items-center justify-content-between bg-dark border-bottom border-light department-toggle">
                                    <div class="small text-warning">Details</div>
                                </div>
                                <div id="creditInfo" class="bg-dark text-dark">
                                    <div class="card-body">
                                        <h5 class="text-center mb-4 text-light">Credit Evaluation Status</h5>
                                        <div class="text-center mb-3">
                                            <span class="badge badge-primary mx-1">Total Employees: <?php echo $creditData['total']; ?></span>
                                            <span class="badge badge-success mx-1">Evaluated: <?php echo $creditData['evaluated']; ?></span>
                                            <span class="badge badge-warning mx-1">Pending: <?php echo $creditData['pending']; ?></span>
                                        </div>
                                        <div class="progress mb-2">
                                        <?php if ($creditData['total'] > 0): ?>
                                    <div class="progress-bar bg-success font-weight-bold" role="progressbar" 
                                        style="width: <?php echo ($creditData['evaluated'] / $creditData['total']) * 100; ?>%;" 
                                        aria-valuenow="<?php echo $creditData['evaluated']; ?>" 
                                        aria-valuemin="0" 
                                        aria-valuemax="<?php echo $creditData['total']; ?>">
                                        Evaluated (<?php echo $creditData['evaluated']; ?>)
                                    </div>
                                    <div class="progress-bar bg-warning text-dark font-weight-bold" role="progressbar" 
                                        style="width: <?php echo ($creditData['pending'] / $creditData['total']) * 100; ?>%;" 
                                        aria-valuenow="<?php echo $creditData['pending']; ?>" 
                                        aria-valuemin="0" 
                                        aria-valuemax="<?php echo $creditData['total']; ?>">
                                        Pending (<?php echo $creditData['pending']; ?>)
                                    </div>
                                <?php else: ?>
                                    <div class="progress-bar bg-secondary font-weight-bold w-100" role="progressbar" 
                                        aria-valuenow="0" 
                                        aria-valuemin="0" 
                                        aria-valuemax="100">
                                        No employees available
                                    </div>
                                <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-4 col-md-6 mt-5">
                            <div class="card mb-4">
                                <div class="card-body bg-secondary text-center">
                                    <a href="../admin/it.php" class="btn card-button text-light font-weight-bold bg-dark border border-dark w-100">IT Department</a>
                                </div>
                                <div class="card-footer d-flex align-items-center justify-content-between bg-dark border-bottom border-light department-toggle">
                                    <div class="small text-warning">Details</div>
                                </div>
                                <div id="itInfo" class="bg-dark text-dark">
                                    <div class="card-body">
                                        <h5 class="text-center mb-4 text-light">It Evaluation Status</h5>
                                        <div class="text-center mb-3">
                                            <span class="badge badge-primary mx-1">Total Employees: <?php echo $itData['total']; ?></span>
                                            <span class="badge badge-success mx-1">Evaluated: <?php echo $itData['evaluated']; ?></span>
                                            <span class="badge badge-warning mx-1">Pending: <?php echo $itData['pending']; ?></span>
                                        </div>
                                        <div class="progress mb-2">
                                            <?php if ($itData['total'] > 0): ?>
                                                <div class="progress-bar bg-success font-weight-bold" role="progressbar" 
                                                    style="width: <?php echo ($itData['evaluated'] / $itData['total']) * 100; ?>%;" 
                                                    aria-valuenow="<?php echo $itData['evaluated']; ?>" 
                                                    aria-valuemin="0" 
                                                    aria-valuemax="<?php echo $itData['total']; ?>">
                                                    Evaluated (<?php echo $itData['evaluated']; ?>)
                                                </div>
                                                <div class="progress-bar bg-warning text-dark font-weight-bold" role="progressbar" 
                                                    style="width: <?php echo ($itData['pending'] / $itData['total']) * 100; ?>%;" 
                                                    aria-valuenow="<?php echo $itData['pending']; ?>" 
                                                    aria-valuemin="0" 
                                                    aria-valuemax="<?php echo $itData['total']; ?>">
                                                    Pending (<?php echo $itData['pending']; ?>)
                                                </div>
                                            <?php else: ?>
                                                <div class="progress-bar bg-secondary font-weight-bold w-100" role="progressbar" 
                                                    aria-valuenow="0" 
                                                    aria-valuemin="0" 
                                                    aria-valuemax="100">
                                                    No employees available
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
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
            <footer class="py-4 bg-dark text-light mt-auto border-top border-secondary">
                <div class="container-fluid px-4">
                    <div class="d-flex align-items-center justify-content-between small">
                        <div class="text-muted">Copyright &copy; Your Website 2024</div>
                        <div>
                            <a href="#">Privacy Policy</a>
                            &middot;
                            <a href="#">Terms & Conditions</a>
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
        //CALENDAR 
        let calendar;
            function toggleCalendar() {
                const calendarContainer = document.getElementById('calendarContainer');
                    if (calendarContainer.style.display === 'none' || calendarContainer.style.display === '') {
                        calendarContainer.style.display = 'block';
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
                        height: 440,  
                        events: {
                        url: '../db/holiday.php',  
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
                const currentDate = new Date().toLocaleDateString(); 
                currentDateElement.textContent = currentDate; 
            });

            document.addEventListener('click', function(event) {
                const calendarContainer = document.getElementById('calendarContainer');
                const calendarButton = document.querySelector('button[onclick="toggleCalendar()"]');

                    if (!calendarContainer.contains(event.target) && !calendarButton.contains(event.target)) {
                        calendarContainer.style.display = 'none';
                        }
            });
        //CALENDAR END

        //TIME 
        function setCurrentTime() {
            const currentTimeElement = document.getElementById('currentTime');
            const currentDateElement = document.getElementById('currentDate');

            const currentDate = new Date();
    
            currentDate.setHours(currentDate.getHours() + 0);
                const hours = currentDate.getHours();
                const minutes = currentDate.getMinutes();
                const seconds = currentDate.getSeconds();
                const formattedHours = hours < 10 ? '0' + hours : hours;
                const formattedMinutes = minutes < 10 ? '0' + minutes : minutes;
                const formattedSeconds = seconds < 10 ? '0' + seconds : seconds;

            currentTimeElement.textContent = `${formattedHours}:${formattedMinutes}:${formattedSeconds}`;
            currentDateElement.textContent = currentDate.toLocaleDateString();
        }
        setCurrentTime();
        setInterval(setCurrentTime, 1000);
        //TIME END

        //EVALUATION TOGGLE
            // Add event listener to all elements with class "department-toggle"
        document.querySelectorAll('.department-toggle').forEach(function (toggle) {
            toggle.addEventListener('click', function () {
                const target = this.getAttribute('data-target');
                const icon = this.querySelector('i');

                // Toggle the collapse
                $(target).collapse('toggle');

                // Toggle the icon classes between angle-down and angle-up
                icon.classList.toggle('fa-angle-down');
                icon.classList.toggle('fa-angle-up');
            });
        });
        //EVALUATION TOGGLE END
</script>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'> </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="../js/admin.js"></script>
</body>
</html>
