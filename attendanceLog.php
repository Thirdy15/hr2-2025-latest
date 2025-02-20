<?php

date_default_timezone_set('Asia/Manila');

include 'db/db_conn.php'; // Ensure correct file path for your database connection

// Check if the employeeId is passed
if (isset($_POST['employeeId'])) {
    $employeeId = $_POST['employeeId'];

    // Get current date and time
    $currentDate = date('Y-m-d');
    $currentTime = date('Y-m-d H:i:s');

    // Check if there's already an attendance record for today for the employee
    $query = "SELECT * FROM attendance_logs WHERE e_id = ? AND attendance_date = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("is", $employeeId, $currentDate);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // If there's a record, update it with the time-out if it's not already done
        $attendance = $result->fetch_assoc();
        if ($attendance['time_out'] === null) {
            // Update time-out if it hasn't been logged
            $updateQuery = "UPDATE attendance_logs SET time_out = ?, status = 'Present' WHERE id = ?";
            $updateStmt = $conn->prepare($updateQuery);
            $updateStmt->bind_param("si", $currentTime, $attendance['id']);
            $updateStmt->execute();
            echo json_encode(["success" => true, "message" => "Attendance logged successfully. Time-out recorded."]);
        } else {
            echo json_encode(["success" => false, "message" => "Attendance already logged for today."]);
        }
    } else {
        // If no attendance record exists, create a new one for time-in
        $insertQuery = "INSERT INTO attendance_logs (e_id, time_in, attendance_date) VALUES (?, ?, ?)";
        $insertStmt = $conn->prepare($insertQuery);
        $insertStmt->bind_param("iss", $employeeId, $currentTime, $currentDate);
        if ($insertStmt->execute()) {
            echo json_encode(["success" => true, "message" => "Attendance logged successfully. Time-in recorded."]);
        } else {
            echo json_encode(["success" => false, "message" => "Failed to log attendance."]);
        }
    }

    $stmt->close();
    $conn->close();
} else {
    echo json_encode(["success" => false, "message" => "No employee ID provided."]);
}
?>
