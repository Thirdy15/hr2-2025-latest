<?php
header('Content-Type: application/json');

date_default_timezone_set('Asia/Manila'); // Set the time zone to Philippine time

include '../../db/db_conn.php';

// Check if necessary parameters are provided for a specific day or entire month
if (!isset($_GET['e_id'], $_GET['month'], $_GET['year'])) {
    echo json_encode(['error' => 'Missing parameters']);
    exit;
}

$employee_id = $_GET['e_id'];
$month = $_GET['month'];
$year = $_GET['year'];

// If 'day' parameter is provided, we are fetching attendance for a specific day
if (isset($_GET['day'])) {
    $day = $_GET['day'];

    // Prepare and execute the query to get attendance details for the given day
    $sql = "SELECT time_in, time_out FROM attendance_log WHERE e_id = ? AND DAY(attendance_date) = ? AND MONTH(attendance_date) = ? AND YEAR(attendance_date) = ?";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        echo json_encode(['error' => 'Database error: ' . $conn->error]);
        exit;
    }

    $stmt->bind_param("iiii", $employee_id, $day, $month, $year);
    $stmt->execute();
    $result = $stmt->get_result();

    $attendanceDetails = [];
    if ($row = $result->fetch_assoc()) {
        $attendanceDetails = [
            'time_in' => $row['time_in'] ? $row['time_in'] : 'No data',
            'time_out' => $row['time_out'] ? $row['time_out'] : 'No data'
        ];
    }

    if (empty($attendanceDetails)) {
        echo json_encode(['message' => 'No attendance details found for the given day']);
    } else {
        echo json_encode($attendanceDetails);
    }

    $stmt->close();
} else {
    // Otherwise, we are fetching attendance for the entire month
    // Prepare and execute query for the entire month
    $sql = "SELECT DAY(attendance_date) AS day, time_in, time_out FROM attendance_log WHERE e_id = ? AND MONTH(attendance_date) = ? AND YEAR(attendance_date) = ?";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        echo json_encode(['error' => 'Database error: ' . $conn->error]);
        exit;
    }

    $stmt->bind_param("iii", $employee_id, $month, $year);
    $stmt->execute();
    $result = $stmt->get_result();

    $attendanceRecords = [];
    while ($row = $result->fetch_assoc()) {
        $day = (int)$row['day'];

        // Determine the status based on time_in and time_out
        if ($row['time_in'] === null || $row['time_out'] === null) {
            $attendanceRecords[$day] = 'Absent';
        } else {
            // Check if the employee is "Late" based on time_in (Example: 9:00 AM)
            $timeThreshold = '09:00:00';
            $status = ($row['time_in'] > $timeThreshold) ? 'Late' : 'Present';
            $attendanceRecords[$day] = $status;
        }
    }

    if (empty($attendanceRecords)) {
        echo json_encode(['message' => 'No attendance records found for the given month']);
    } else {
        echo json_encode($attendanceRecords);
    }

    $stmt->close();
}

$conn->close();
?>
