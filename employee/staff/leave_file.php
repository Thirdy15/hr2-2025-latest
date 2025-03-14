<?php
session_start();
include '../../db/db_conn.php';

if (!isset($_SESSION['e_id'])) {
    header("Location: ../../employee/login.php");
    exit();
}

// Fetch user info
$employeeId = $_SESSION['e_id'];
$sql = "SELECT e_id, firstname, middlename, lastname, birthdate, email, available_leaves, role, position, department, phone_number, address, pfp FROM employee_register WHERE e_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $employeeId);
$stmt->execute();
$result = $stmt->get_result();
$employeeInfo = $result->fetch_assoc();

if (!$employeeInfo) {
    die("Error: Employee information not found.");
}

// Check if there are any status messages to display
$status_message = isset($_SESSION['status_message']) ? $_SESSION['status_message'] : '';
unset($_SESSION['status_message']); // Clear the status message after displaying it

// Fetch the used leave by summing up approved leave days based on leave_start_date and leave_end_date
$usedLeaveQuery = "SELECT SUM(DATEDIFF(end_date, start_date) + 1) AS used_leaves FROM leave_requests WHERE e_id = ? AND status = 'approved'";
$usedLeaveStmt = $conn->prepare($usedLeaveQuery);
$usedLeaveStmt->bind_param("i", $employeeId);
$usedLeaveStmt->execute();
$usedLeaveResult = $usedLeaveStmt->get_result();
$usedLeaveRow = $usedLeaveResult->fetch_assoc();
$usedLeave = $usedLeaveRow['used_leaves'] ?? 0; // Default to 0 if no leave has been used

// Calculate remaining available leaves (optional, if needed for display or logic)
$availableLeaves = $employeeInfo['available_leaves'];

// Close the database connection
$stmt->close();
$usedLeaveStmt->close();
$conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave Request Form</title>
    <link href="../../css/styles.css" rel="stylesheet" />
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />
    <link href="../../css/calendar.css" rel="stylesheet"/>
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="sb-nav-fixed bg-black">
    <?php include 'navbar.php'; ?>
    <div id="layoutSidenav">
        <?php include 'sidebar.php'; ?>
        <div id="layoutSidenav_content">
            <main class="bg-black">
                <div class="container" id="calendarContainer" 
                style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 1050; 
                        width: 80%; height: 80%; display: none;">
                    <div class="row">
                        <div class="col-md-12">
                            <div id="calendar" class="p-2"></div>
                        </div>
                    </div>
                </div>        
                <div class="container mt-5">
                        <!-- Leave Balance Section -->
                        <div class="row mb-4">
                            <div class="col-md-12">
                                <div class="card leave-balance-card bg-dark text-light">
                                    <div class="card-body text-center">
                                        <h4 class="card-title">Leave Information</h4>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="p-3">
                                                    <h5>Overall Available Leave</h5>
                                                    <p class="fs-4 text-success"><?php echo htmlspecialchars($employeeInfo['available_leaves']); ?> days</p>
                                                    <a class="btn btn-success" href="#"> View leave details</a>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="p-3">
                                                    <h5>Used Leave</h5>
                                                    <p class="fs-4 text-danger"><?php echo htmlspecialchars($usedLeave); ?> days</p>
                                                    <a class="btn btn-danger" href="#"> View leave history</a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <form id="leave-request-form" action="../../employee_db/staff/leave_conn.php" method="POST" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="card leave-form text bg-dark text-light">
                                    <div class="card-body">
                                        <h5 class="card-title text-center mb-4">Request Leave</h5>
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label for="name" class="text-light">Name:</label>
                                                <input type="text" class="form-control text-dark" id="name" name="name" value="<?php echo htmlspecialchars($employeeInfo['firstname'] . ' ' . $employeeInfo['lastname']); ?>" readonly>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="department" class="text-light">Department:</label>
                                                <input type="text" class="form-control text-dark" id="department" name="department" value="<?php echo htmlspecialchars($employeeInfo['department']); ?>" readonly>
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label for="leave_type" class="form-label">Leave Type</label>
                                                <select id="leave_type" name="leave_type" class="form-control" required>
                                                    <option value="" disabled selected>Select leave type</option>
                                                    <option value="Sick Leave">Sick Leave</option>
                                                    <option value="Vacation Leave">Vacation Leave</option>
                                                    <option value="Emergency Leave">Emergency Leave</option>
                                                    <option value="Maternity Leave">Maternity Leave</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="leave_days" class="form-label">Number of Days</label>
                                                <input type="number" name="leave_days" id="leave_days" class="form-control" min="1" max="30" placeholder="" required readonly>
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label for="start_date" class="form-label">Start Date</label>
                                                <input type="date" id="start_date" name="start_date" class="form-control" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="end_date" class="form-label">End Date</label>
                                                <input type="date" id="end_date" name="end_date" class="form-control" required>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label for="proof" class="form-label">Attach Proof</label>
                                            <input type="file" id="proof" name="proof[]" class="form-control" accept="*/*" multiple>
                                            <small class="form-text text-warning">Note: Upload multiple files (images or PDFs) as proof for your leave.</small>
                                        </div>
                                        <div class="d-grid">
                                            <button type="submit" class="btn btn-primary">Submit Leave Request</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div> 
            </main>
                <div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content bg-dark text-light">
                            <div class="modal-header border-bottom border-warning">
                                <h5 class="modal-title" id="logoutModalLabel">Confirm Logout</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                Are you sure you want to log out?
                            </div>
                            <div class="modal-footer border-top border-warning">
                                <button type="button" class="btn border-secondary text-light" data-bs-dismiss="modal">Cancel</button>
                                <form action="../../employee/logout.php" method="POST">
                                    <button type="submit" class="btn btn-danger">Logout</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>  
          <?php include 'footer.php'; ?>
        </div>
    </div>
    <script>
        //CALENDAR 
        let calendar;
            function toggleCalendar() {
                const calendarContainer = document.getElementById('calendarContainer');
                    if (calendarContainer.style.display === 'none' || calendarContainer.style.display === '') {
                        calendarContainer.style.display = 'block';
                        if (!calendar) {
                            initializeCalendar();
                         }
                        } else {
                            calendarContainer.style.display = 'none';
                        }
            }

            function initializeCalendar() {
                const calendarEl = document.getElementById('calendar');
                    calendar = new FullCalendar.Calendar(calendarEl, {
                        initialView: 'dayGridMonth',
                        headerToolbar: {
                        left: 'prev,next today',
                        center: 'title',
                        right: 'dayGridMonth,timeGridWeek,timeGridDay'
                        },
                        height: 440,  
                        events: {
                        url: '../../db/holiday.php',  
                        method: 'GET',
                        failure: function() {
                        alert('There was an error fetching events!');
                        }
                        }
                    });

                    calendar.render();
            }

            document.addEventListener('DOMContentLoaded', function () {
                const currentDateElement = document.getElementById('currentDate');
                const currentDate = new Date().toLocaleDateString(); 
                currentDateElement.textContent = currentDate; 
            });

            document.addEventListener('click', function(event) {
                const calendarContainer = document.getElementById('calendarContainer');
                const calendarButton = document.querySelector('button[onclick="toggleCalendar()"]');

                    if (!calendarContainer.contains(event.target) && !calendarButton.contains(event.target)) {
                        calendarContainer.style.display = 'none';
                        }
            });
        //CALENDAR END

        //TIME 
        function setCurrentTime() {
            const currentTimeElement = document.getElementById('currentTime');
            const currentDateElement = document.getElementById('currentDate');

            const currentDate = new Date();
    
            currentDate.setHours(currentDate.getHours() + 0);
                const hours = currentDate.getHours();
                const minutes = currentDate.getMinutes();
                const seconds = currentDate.getSeconds();
                const formattedHours = hours < 10 ? '0' + hours : hours;
                const formattedMinutes = minutes < 10 ? '0' + minutes : minutes;
                const formattedSeconds = seconds < 10 ? '0' + seconds : seconds;

            currentTimeElement.textContent = `${formattedHours}:${formattedMinutes}:${formattedSeconds}`;
            currentDateElement.textContent = currentDate.toLocaleDateString();
        }
        setCurrentTime();
        setInterval(setCurrentTime, 1000);
        //TIME END

        //LEAVE DAYS
        document.getElementById('start_date').addEventListener('change', calculateLeaveDays);
        document.getElementById('end_date').addEventListener('change', calculateLeaveDays);

        function calculateLeaveDays() {
            const start_date = document.getElementById('start_date').value;
            const end_date = document.getElementById('end_date').value;
            
            if (start_date && end_date) {
                const start = new Date(start_date);
                const end = new Date(end_date);
                let totalDays = 0;

                // Loop through the dates between start and end dates
                for (let date = new Date(start); date <= end; date.setDate(date.getDate() + 1)) {
                    // Exclude Sundays (0 is Sunday)
                    if (date.getDay() !== 0) {
                        totalDays++;
                    }
                }

                // Update the number of days in the input field
                document.getElementById('leave_days').value = totalDays;
            }
        }
        //LEAVE DAYS END
</script>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'> </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="../../js/employee.js"></script>
</body>
</html>
