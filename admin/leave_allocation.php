<?php
session_start();
include '../db/db_conn.php';

// Ensure the admin is logged in
if (!isset($_SESSION['a_id'])) {
    die("Error: You must be logged in as admin.");
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get leave allocations from the form
    $employee_leaves = $_POST['employee_leaves'];
    $employeeId = $_POST['employee_id']; // Get specific employee ID

    // Function to get current leave balance for a specific employee
    function get_current_leave_balance($conn, $employeeId) {
        $sql = "SELECT available_leaves FROM employee_register WHERE e_id = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            die("Error preparing statement: " . $conn->error);
        }
        $stmt->bind_param('i', $employeeId);
        $stmt->execute();
        $stmt->bind_result($available_leaves);
        $stmt->fetch();  
        $stmt->close();
        return $available_leaves;
    }

    // Update leave allocation for either all employees or a specific employee
    if ($employeeId == 'all') {
        // Update for all employees
        $update_sql = "UPDATE employee_register SET available_leaves = available_leaves + ? WHERE role = 'Employee'";
        $update_stmt = $conn->prepare($update_sql);
        if ($update_stmt === false) {
            die("Error preparing update statement: " . $conn->error);
        }
        $update_stmt->bind_param('i', $employee_leaves);
        if (!$update_stmt->execute()) {
            die("Error executing update statement: " . $update_stmt->error);
        }
    } else {
        // Update for a specific employee
        $current_leaves = get_current_leave_balance($conn, $employeeId);
        $new_leave_total = $current_leaves + $employee_leaves;

        $update_sql = "UPDATE employee_register SET available_leaves = ? WHERE e_id = ?";
        $update_stmt = $conn->prepare($update_sql);
        if ($update_stmt === false) {
            die("Error preparing update statement: " . $conn->error);
        }
        $update_stmt->bind_param('ii', $new_leave_total, $employeeId);
        if (!$update_stmt->execute()) {
            die("Error executing update statement: " . $update_stmt->error);
        }
    }

    echo "<div class='alert alert-success'>Leave allocations updated successfully!</div>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set Leave Allocations</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body class="bg-black">
    <div class="container">
        <h2 class="text-center mt-5 text-light">Set Leave Allocations</h2>
        
        <form method="POST" class="mt-4">
            <div class="form-group">
                <label class="text-light mt-3 mb-1" for="employee_leaves">Leave Days for Employees:</label>
                <input type="number" name="employee_leaves" id="employee_leaves" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="text-light mt-3 mb-1" for="employee_id">Select Employee:</label>
                <select name="employee_id" id="employee_id" class="form-control">
                    <option value="all">All Employees</option>
                    <?php
                    // Fetch employees from the database for the dropdown
                    $employees_sql = "SELECT e_id, firstname, lastname FROM employee_register";
                    $employees_result = $conn->query($employees_sql);
                    while ($employee = $employees_result->fetch_assoc()) {
                        echo "<option value='" . $employee['e_id'] . "'>" . $employee['firstname'] . " " . $employee['lastname'] . "</option>";
                    }
                    ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary mt-3">Set Allocations</button>
        </form>
        <div class="text-center mb-5">
            <a href="../admin/dashboard.php" class="btn btn-secondary mt-4">Back to Dashboard</a>
        </div>
    </div>
</body>
</html>

<?php
// Close the database connection
$conn->close();
?>
