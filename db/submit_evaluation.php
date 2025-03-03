<?php
session_start();

// Include database connection
include '../db/db_conn.php';

if (!isset($_SESSION['a_id'])) {
    echo 'You are not AUTHORIZED.';
    exit();
}

// Get the admin ID from the session
$adminId = $_SESSION['a_id'];

// Fetch admin details using the session's a_id
$adminSql = "SELECT firstname, lastname FROM admin_register WHERE a_id = ?";
$adminStmt = $conn->prepare($adminSql);
$adminStmt->bind_param('i', $adminId);
$adminStmt->execute();
$adminStmt->bind_result($adminFirstName, $adminLastName);

if ($adminStmt->fetch()) {
    $adminName = $adminFirstName . ' ' . $adminLastName;
} else {
    echo 'Error: Admin not found or failed to fetch name for ID: ' . $adminId;
    exit();
}

$adminStmt->close();

// Get data from the POST request
$employeeId = $_POST['e_id'];
$categoryAverages = json_decode($_POST['categoryAverages'], true);
$department = $_POST['department'];

// Fetch the employee's first and last name
$employeeSql = "SELECT firstname, lastname FROM employee_register WHERE e_id = ?";
$employeeStmt = $conn->prepare($employeeSql);
$employeeStmt->bind_param('i', $employeeId);
$employeeStmt->execute();
$employeeStmt->bind_result($employeeFirstName, $employeeLastName);

if ($employeeStmt->fetch()) {
    $employeeName = $employeeFirstName . ' ' . $employeeLastName;
} else {
    echo 'Error: Employee not found or unable to fetch the name.';
    exit();
}
$employeeStmt->close();

// Check if the current admin has already evaluated this employee
$checkSql = "SELECT * FROM admin_evaluations WHERE a_id = ? AND e_id = ?";
$checkStmt = $conn->prepare($checkSql);
$checkStmt->bind_param('ii', $adminId, $employeeId);
$checkStmt->execute();
$checkStmt->store_result();

if ($checkStmt->num_rows > 0) {
    echo 'You have already evaluated this employee.';
} else {
    // Prepare the SQL to insert the evaluation into the database
    $sql = "INSERT INTO admin_evaluations (
                a_id, admin_name, e_id, employee_name, department, quality, communication_skills, teamwork, punctuality, initiative
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        'isissddddd',
        $adminId,
        $adminName,
        $employeeId,
        $employeeName,
        $department,
        $categoryAverages['QualityOfWork'],
        $categoryAverages['CommunicationSkills'],
        $categoryAverages['Teamwork'],
        $categoryAverages['Punctuality'],
        $categoryAverages['Initiative']
    );

    if ($stmt->execute()) {
        // Log this activity
        $actionType = "Employee Evaluation";
        $affectedFeature = "Evaluation";
        $details = "Admin ($adminName) evaluated employee Name: $employeeName in $department.";
        $ipAddress = $_SERVER['REMOTE_ADDR'];

        $logQuery = "INSERT INTO activity_logs (admin_id, admin_name, action_type, affected_feature, details, ip_address)
                     VALUES (?, ?, ?, ?, ?, ?)";
        $logStmt = $conn->prepare($logQuery);
        $logStmt->bind_param("isssss", $adminId, $adminName, $actionType, $affectedFeature, $details, $ipAddress);

        if ($logStmt->execute()) {
            echo 'Evaluation saved and activity logged successfully.';
        } else {
            echo 'Error logging the activity: ' . $logStmt->error;
        }

        $logStmt->close();
    } else {
        echo 'Error: ' . $stmt->error;
    }
    $stmt->close();
}

$checkStmt->close();
$conn->close();
?>
