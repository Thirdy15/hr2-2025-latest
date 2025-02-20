<?php
session_start();

if (!isset($_SESSION['a_id'])) {
    header("Location: ../admin/adminlogin.php");
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Record</title>
    <link href="../css/Display.css" rel="stylesheet">
</head>
<body>
    <header>
        <h1>Attendance Record</h1>
    </header>

    <main>
        <section class="add-record">
            <h2>Add Attendance</h2>
            <form id="attendance-form">
                <div class="form-group">
                    <label for="name">Employee Name:</label>
                    <input type="text" id="name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="date">Date:</label>
                    <input type="date" id="date" name="date" required>
                </div>
                <div class="form-group">
                    <label for="status">Status:</label>
                    <select id="status" name="status" required>
                        <option value="">Select</option>
                        <option value="Present">Present</option>
                        <option value="Absent">Absent</option>
                        <option value="Late">Late</option>
                        <option value="Excused">Excused</option>
                    </select>
                </div>
                <button type="submit">Add Record</button>
            </form>
        </section>

        <section class="attendance-table">
            <h2>Attendance Records</h2>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Employee Name</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="record-table-body">
                    <!-- Records will be dynamically inserted here -->
                </tbody>
            </table>
        </section>
    </main>

    <footer>
    </footer>

    <script src="../js/Display.js"></script>
</body>
</html>