<?php
session_start();
include '../../db/db_conn.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Ensure user is logged in
    if (!isset($_SESSION['e_id'])) {
        $_SESSION['status_message'] = "User not logged in.";
        header("Location: ../employee/login.php");
        exit();
    }

    $employeeId = $_SESSION['e_id'];
    $startDate = $_POST['start_date'];
    $endDate = $_POST['end_date'];
    $leaveType = $_POST['leave_type'];

    // Validate required fields
    if (empty($startDate) || empty($endDate) || empty($leaveType)) {
        $_SESSION['status_message'] = "Please fill in all required fields.";
        header("Location: ../../employee/staff/leave_request.php");
        exit();
    }

    // Ensure the end date is not earlier than the start date
    if (strtotime($endDate) < strtotime($startDate)) {
        $_SESSION['status_message'] = "End date cannot be earlier than start date.";
        header("Location: ../../employee/staff/leave_request.php");
        exit();
    }

    // Check available leaves for the employee
    $sql = "SELECT available_leaves FROM employee_register WHERE e_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $employeeId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $employee = $result->fetch_assoc();
        $availableLeaves = $employee['available_leaves'];

        // Calculate the number of requested leave days
        $startDateObj = new DateTime($startDate);
        $endDateObj = new DateTime($endDate);
        $leaveDaysRequested = $endDateObj->diff($startDateObj)->days + 1;

        if ($leaveDaysRequested > $availableLeaves) {
            $_SESSION['status_message'] = "You don't have enough leave days.";
            header("Location: ../../employee/staff/leave_request.php");
            exit();
        }
    } else {
        $_SESSION['status_message'] = "Employee not found.";
        header("Location: ../../employee/staff/leave_request.php");
        exit();
    }

    // Handle multiple file uploads and store file paths
    $proofFiles = [];  // Initialize an array to hold the file paths
    $uploadDir = '../../proof/';  // Directory to store uploaded files
    $baseURL = 'http://localhost/HR2/proof/';  // Full URL to access the files from the web

    if (isset($_FILES['proof']) && !empty($_FILES['proof']['name'][0])) {
        $files = $_FILES['proof'];

        // Loop through each uploaded file
        foreach ($files['tmp_name'] as $key => $tmpName) {
            if ($files['error'][$key] === UPLOAD_ERR_OK) {
                $fileTmpPath = $tmpName;
                $fileName = $files['name'][$key];
                $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);

                // Generate a unique name for the file to avoid overwriting
                $newFileName = uniqid('proof_', true) . '.' . $fileExtension;

                // Check if the file is a valid document (image, PDF, or common documents)
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'txt', 'xlsx', 'pptx'];
                if (!in_array(strtolower($fileExtension), $allowedExtensions)) {
                    $_SESSION['status_message'] = "Invalid file type. Only images, PDFs, and documents are allowed.";
                    header("Location: ../../employee/staff/leave_request.php");
                    exit();
                }

                // Move the file to the uploads directory
                $destFilePath = $uploadDir . $newFileName;
                if (move_uploaded_file($fileTmpPath, $destFilePath)) {
                    $proofFiles[] = $newFileName;  // Store only the file name
                } else {
                    $_SESSION['status_message'] = "Error: Could not move the uploaded file.";
                    header("Location: ../../employee/staff/leave_request.php");
                    exit();
                }
            } else {
                $_SESSION['status_message'] = "Error: File upload failed.";
                header("Location: ../../employee/staff/leave_request.php");
                exit();
            }
        }
    }

    // Insert the leave request into the database, including the proof file paths
    $sql = "INSERT INTO leave_requests (e_id, start_date, end_date, leave_type, proof, status) 
            VALUES (?, ?, ?, ?, ?, 'pending')";
    $stmt = $conn->prepare($sql);

    // Prepare the file paths to store in the database (comma-separated if multiple files)
    $proofFilePaths = implode(',', $proofFiles);  // Store only the file names (relative to 'proof' folder)
    
    // Bind parameters
    $stmt->bind_param('issss', $employeeId, $startDate, $endDate, $leaveType, $proofFilePaths);

    // Execute the query
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            $_SESSION['status_message'] = "Leave request submitted successfully.";
        } else {
            $_SESSION['status_message'] = "Error: Leave request was not inserted.";
        }
    } else {
        $_SESSION['status_message'] = "Error executing query: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();

    header("Location: ../../employee/staff/leave_request.php");
    exit();
}
?>
