<?php
session_start();
if (!isset($_SESSION['e_id'])) {
    header("Location: ../HR2/login.php");
    exit();
}

include '../../db/db_conn.php';

$employeeId = $_SESSION['e_id'];

// Fetch all notifications for the logged-in user
$sql = "SELECT message, is_read, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $employeeId);
$stmt->execute();
$result = $stmt->get_result();
$notifications = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
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
    <title>Notifications | HR2</title>
    <link href="../../css/styles.css" rel="stylesheet" />
</head>
<body class="sb-nav-fixed bg-black">
    <div id="layoutSidenav">
        <div id="layoutSidenav_nav">
            <!-- ...existing code... -->
        </div>
        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4">
                    <h1 class="mt-4 text-light">Notifications</h1>
                    <div class="card mb-4 bg-dark text-light">
                        <div class="card-header">
                            <i class="fas fa-bell me-1"></i>
                            All Notifications
                        </div>
                        <div class="card-body">
                            <ul class="list-group list-group-flush">
                                <?php foreach ($notifications as $notification): ?>
                                    <li class="list-group-item bg-dark text-light">
                                        <?php echo htmlspecialchars($notification['message']); ?>
                                        <br>
                                        <small class="text-muted"><?php echo htmlspecialchars($notification['created_at']); ?></small>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </main>
            <!-- ...existing code... -->
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../js/scripts.js"></script>
</body>
</html>
