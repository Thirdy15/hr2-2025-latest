<?php
session_start();
include '../../db/db_conn.php';

if (!isset($_SESSION['e_id'])) {
    header("Location: ../../login.php");
    exit();
}
// Fetch employee's leave data
$employee_id = $_SESSION['e_id']; // Assuming the employee ID is stored in session

// Query to fetch employee's info from the employee_register table
$query_employee = "SELECT * FROM employee_register WHERE e_id = ?";
$stmt_employee = $conn->prepare($query_employee);
$stmt_employee->bind_param("i", $employee_id);
$stmt_employee->execute();
$result_employee = $stmt_employee->get_result();

$employee_data = $result_employee->fetch_assoc();

// Fetch the employee's gender from the employee_register table
$employee_gender = $employee_data['gender']; // Assuming gender is stored in employee_register

// Fetch employee's leave data
$query_leave = "SELECT * FROM employee_leaves WHERE employee_id = ?";
$stmt_leave = $conn->prepare($query_leave);
$stmt_leave->bind_param("i", $employee_id);
$stmt_leave->execute();
$result_leave = $stmt_leave->get_result();

$leave_data = $result_leave->fetch_assoc();

// Fetch the leave requests that are approved for the employee
$query_approved_leave = "SELECT * FROM leave_requests WHERE e_id = ? AND status = 'approved' ORDER BY start_date DESC LIMIT 1"; // Only get the most recent approved leave
$stmt_approved_leave = $conn->prepare($query_approved_leave);
$stmt_approved_leave->bind_param("i", $employee_id);
$stmt_approved_leave->execute();
$result_approved_leave = $stmt_approved_leave->get_result();

if ($result_approved_leave->num_rows > 0) {
    $approved_leave_data = $result_approved_leave->fetch_assoc(); // Fetch the most recent approved leave

    // Get the start and end date of the leave
    $leave_start_date = new DateTime($approved_leave_data['start_date']);
    $leave_end_date = new DateTime($approved_leave_data['end_date']);
    $current_date = new DateTime(); // Current date and time

    // Calculate the total duration of the leave (in days)
    $leave_duration = $leave_start_date->diff($leave_end_date)->days + 1;

    // Calculate how many days have passed since the start date
    $days_passed = $leave_start_date->diff($current_date)->days;

    if ($current_date < $leave_start_date) {
        // If current date is before the leave starts, no progress has been made
        $days_passed = 0;
    } elseif ($current_date > $leave_end_date) {
        // If current date is after the leave ends, progress is complete
        $days_passed = $leave_duration;
    }

    // Calculate the percentage of progress
    $progress_percentage = ($days_passed / $leave_duration) * 100;
} else {
    // If no approved leave, set leave data to null
    $approved_leave_data = null;
}

// Close the database connections
$stmt_employee->close();
$stmt_leave->close();
$stmt_approved_leave->close();
$conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave Balance</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="../../css/styles.css"> <!-- If you have custom styles -->
</head>
<body class="bg-black">
    <div class="container mt-5 text-light">
        <h2 class="text-center mb-4">Leave Details</h2>
        <div class="card text-light bg-dark">
            <div class="card-header border-bottom border-secondary">
                <h5 class="card-title mb-0">Employee Information</h5>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <p><strong>Name:</strong> <?php echo $employee_data['firstname'] . ' ' . $employee_data['lastname']; ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>ID No.:</strong> <?php echo $employee_data['e_id']; ?></p>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <p><strong>Position:</strong> <?php echo $employee_data['position']; ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Department:</strong> <?php echo $employee_data['department']; ?></p>
                    </div>
                </div>
            </div>
        </div>
        <div class="bg-dark text-light">
            <div class="mt-4">
                <table class="table table-bordered mt-3">
                    <thead>
                        <tr>
                            <th>Leave Type</th>
                            <th>Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($employee_gender == 'Female') { ?>
                            <tr>
                                <td>Bereavement Leave</td>
                                <td>
                                    <?php 
                                        if (isset($leave_data['bereavement_leave'])) {
                                            echo $leave_data['bereavement_leave'];
                                        } else {
                                            echo '0';
                                        }
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <td>Emergency Leave</td>
                                <td>
                                    <?php 
                                        if (isset($leave_data['emergency_leave'])) {
                                            echo $leave_data['emergency_leave'];
                                        } else {
                                            echo '0';
                                        }
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <td>Maternity Leave</td>
                                <td>
                                    <?php 
                                        if (isset($leave_data['maternity_leave'])) {
                                            echo $leave_data['maternity_leave'];
                                        } else {
                                            echo '0';
                                        }
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <td>MCW Special Leave</td>
                                <td>
                                    <?php 
                                        if (isset($leave_data['mcw_special_leave'])) {
                                            echo $leave_data['mcw_special_leave'];
                                        } else {
                                            echo '0';
                                        }
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <td>Parental Leave</td>
                                <td>
                                    <?php 
                                        if (isset($leave_data['parental_leave'])) {
                                            echo $leave_data['parental_leave'];
                                        } else {
                                            echo '0';
                                        }
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <td>Service Incentive Leave</td>
                                <td>
                                    <?php 
                                        if (isset($leave_data['service_incentive_leave'])) {
                                            echo $leave_data['service_incentive_leave'];
                                        } else {
                                            echo '0';
                                        }
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <td>Sick Leave</td>
                                <td>
                                    <?php 
                                        if (isset($leave_data['sick_leave'])) {
                                            echo $leave_data['sick_leave'];
                                        } else {
                                            echo '0';
                                        }
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <td>Vacation Leave</td>
                                <td>
                                    <?php 
                                        if (isset($leave_data['vacation_leave'])) {
                                            echo $leave_data['vacation_leave'];
                                        } else {
                                            echo '0';
                                        }
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <td>VAWC Leave</td>
                                <td>
                                    <?php 
                                        if (isset($leave_data['vawc_leave'])) {
                                            echo $leave_data['vawc_leave'];
                                        } else {
                                            echo '0';
                                        }
                                    ?>
                                </td>
                            </tr>
                        <?php } elseif ($employee_gender == 'Male') { ?>
                            <tr>
                                <td>Bereavement Leave</td>
                                <td>
                                    <?php 
                                        if (isset($leave_data['bereavement_leave_male'])) {
                                            echo $leave_data['bereavement_leave_male'];
                                        } else {
                                            echo '0';
                                        }
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <td>Emergency Leave</td>
                                <td>
                                    <?php 
                                        if (isset($leave_data['emergency_leave_male'])) {
                                            echo $leave_data['emergency_leave_male'];
                                        } else {
                                            echo '0';
                                        }
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <td>Parental Leave</td>
                                <td>
                                    <?php 
                                        if (isset($leave_data['parental_leave_male'])) {
                                            echo $leave_data['parental_leave_male'];
                                        } else {
                                            echo '0';
                                        }
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <td>Paternity Leave</td>
                                <td>
                                    <?php 
                                        if (isset($leave_data['paternity_leave_male'])) {
                                            echo $leave_data['paternity_leave_male'];
                                        } else {
                                            echo '0';
                                        }
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <td>Service Incentive Leave</td>
                                <td>
                                    <?php 
                                        if (isset($leave_data['service_incentive_leave_male'])) {
                                            echo $leave_data['service_incentive_leave_male'];
                                        } else {
                                            echo '0';
                                        }
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <td>Sick Leave</td>
                                <td>
                                    <?php 
                                        if (isset($leave_data['sick_leave_male'])) {
                                            echo $leave_data['sick_leave_male'];
                                        } else {
                                            echo '0';
                                        }
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <td>Vacation Leave</td>
                                <td>
                                    <?php 
                                        if (isset($leave_data['vacation_leave_male'])) {
                                            echo $leave_data['vacation_leave_male'];
                                        } else {
                                            echo '0';
                                        }
                                    ?>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php if ($approved_leave_data): ?>
            <div class="container">
                <h3>Ongoing Leave Schedule</h3>
                <p><strong>Leave Start Date:</strong> <?php echo $approved_leave_data['start_date']; ?></p>
                <p><strong>Leave End Date:</strong> <?php echo $approved_leave_data['end_date']; ?></p>
                <p><strong>Total Leave Duration:</strong> <?php echo $leave_duration; ?> days</p>
                <p><strong>Days Passed:</strong> <?php echo $days_passed; ?> days</p>
            </div>
        <?php endif; ?>
    </div>
    <a href="../../employee/supervisor/leave_file.php" class="btn btn-primary mt-4">Back to Settings</a>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

