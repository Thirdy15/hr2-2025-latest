<?php
include '../config.php';

$query = "SELECT message FROM notifications ORDER BY created_at DESC";
$result = mysqli_query($conn, $query);

$notifications = [];
while ($row = mysqli_fetch_assoc($result)) {
    $notifications[] = ['message' => $row['message']];
}

$response = [
    'notifications' => $notifications,
    'count' => count($notifications)
];

echo json_encode($response);
?>
