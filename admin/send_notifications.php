<?php
include '../db/db_conn.php';

$data = json_decode(file_get_contents('php://input'), true);
$message = $data['message'];

$sql = "INSERT INTO notifications (message, created_at, is_read) VALUES (?, NOW(), 0)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $message);

$response = [];
if ($stmt->execute()) {
    $response['success'] = true;
} else {
    $response['success'] = false;
    $response['message'] = 'Failed to send notification.';
}

$stmt->close();
$conn->close();

echo json_encode($response);
?>
