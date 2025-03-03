<?php
session_start();
include '../../db/db_conn.php';

if (!isset($_SESSION['e_id'])) {
    header("Location: ../../employee/login.php");
    exit();
}

// Fetch user info
$employeeId = $_SESSION['e_id'];
$sql = "SELECT e_id, firstname, middlename, lastname, birthdate, gender, email, available_leaves, role, position, department, phone_number, address, pfp FROM employee_register WHERE e_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $employeeId);
$stmt->execute();
$result = $stmt->get_result();
$employeeInfo = $result->fetch_assoc();

if (!$employeeInfo) {
    die("Error: Employee information not found.");
}

$gender = $employeeInfo['gender']; // Fetch gender

// Check if there are any status messages to display
$status_message = isset($_SESSION['status_message']) ? $_SESSION['status_message'] : '';
unset($_SESSION['status_message']); // Clear the status message after displaying it

// Fetch the used leave by summing up approved leave days based on leave_start_date and leave_end_date
$usedLeaveQuery = "SELECT leave_type, SUM(DATEDIFF(end_date, start_date) + 1) AS used_days FROM leave_requests WHERE e_id = ? AND status = 'approved' GROUP BY leave_type";
$usedLeaveStmt = $conn->prepare($usedLeaveQuery);
$usedLeaveStmt->bind_param("i", $employeeId);
$usedLeaveStmt->execute();
$usedLeaveResult = $usedLeaveStmt->get_result();
$usedLeaveDays = [];
while ($row = $usedLeaveResult->fetch_assoc()) {
    $usedLeaveDays[$row['leave_type']] = $row['used_days'];
}

// Fetch the number of leave requests for each leave type
$leaveRequestsQuery = "SELECT leave_type, COUNT(*) AS request_count FROM leave_requests WHERE e_id = ? AND status = 'approved' GROUP BY leave_type";
$leaveRequestsStmt = $conn->prepare($leaveRequestsQuery);
$leaveRequestsStmt->bind_param("i", $employeeId);
$leaveRequestsStmt->execute();
$leaveRequestsResult = $leaveRequestsStmt->get_result();
$leaveRequests = [];
while ($row = $leaveRequestsResult->fetch_assoc()) {
    $leaveRequests[$row['leave_type']] = $row['request_count'];
}

// Function to send notification
function sendNotification($conn, $employeeId, $message) {
    $sql = "INSERT INTO leave_notifications (user_id, message) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $employeeId, $message);
    $stmt->execute();
    $stmt->close();
}

// Close the database connection
$stmt->close();
$usedLeaveStmt->close();
$leaveRequestsStmt->close();
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
            <main class="container-fluid position-relative bg-black px-4">
                <div class="container" id="calendarContainer" 
                style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 1050; 
                        width: 80%; height: 80%; display: none;">
                    <div class="row">
                        <div class="col-md-12">
                            <div id="calendar" class="p-2"></div>
                        </div>
                    </div>
                </div> 
                <h1 class="mb-2 text-light">File Leave</h1>                   
                <div class="card bg-black py-4">
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="card leave-balance-card bg-dark text-light">
                                <div class="card-body text-center">
                                    <h3 class="card-title">Leave Information</h3>
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
                                                <p class="fs-4 text-danger"><?php echo htmlspecialchars(array_sum($usedLeaveDays)); ?> days</p>
                                                <a class="btn btn-danger" href="../../employee/staff/leaveHistory.php"> View leave history</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <form id="leave-request-form" action="../../employee_db/fieldworker/leave_conn.php" method="POST" enctype="multipart/form-data" onsubmit="return showConfirmationModal()">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="card leave-form text bg-dark text-light">
                                    <div class="card-body">
                                        <h3 class="card-title text-center mb-4">Request Leave</h3>
                                        <div class="row mb-3">
                                            <div class="col-md-6 mb-3">
                                                <div class="form-floating mb-3 mb-md-0">
                                                    <input type="text" class="form-control text-dark" id="name" name="name" value="<?php echo htmlspecialchars($employeeInfo['firstname'] . ' ' . $employeeInfo['lastname']); ?>" readonly>
                                                    <label for="name" class="text-dark fw-bold">Name:</label>
                                                </div>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <div class="form-floating mb-3 mb-md-0">
                                                    <input type="text" class="form-control text-dark" id="department" name="department" value="<?php echo htmlspecialchars($employeeInfo['department']); ?>" readonly>
                                                    <label for="department" class="text-dark fw-bold">Department:</label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-md-6 mb-3">
                                                <div class="form-floating mb-3 mb-md-0">
                                                    <select id="leave_type" name="leave_type" class="form-control form-select" required>
                                                        <option value="" disabled selected>Select leave type</option>
                                                        <?php
                                                        $leaveTypes = [
                                                            "Service Incentive leave" => 5,
                                                            "Vacation leave" => 14,
                                                            "Sick leave" => 15,
                                                            "Bereavement leave" => 5,
                                                            "Parental leave" => 7,
                                                            "Emergency leave" => 5,
                                                            "Maternity leave" => 105,
                                                            "Paternity leave" => 14,
                                                            "Special leave benefit for woman" => 60,
                                                            "Victims of violence against woman and their children" => 10,
                                                            "Jury duty leave" => 5
                                                        ];

                                                        foreach ($leaveTypes as $type => $limit) {
                                                            if (($employeeInfo['gender'] == 'Female' && in_array($type, ["Paternity leave"])) ||
                                                                ($employeeInfo['gender'] == 'Male' && in_array($type, ["Maternity leave", "Special leave benefit for woman", "Victims of violence against woman and their children"]))) {
                                                                continue;
                                                            }
                                                            $used = $usedLeaveDays[$type] ?? 0; // Get the number of used leave days for this type
                                                            $remaining = $limit - $used; // Calculate remaining leave days
                                                            $disabled = ($remaining <= 0) ? 'disabled' : ''; // Disable if no remaining leave days
                                                            $title = ($remaining <= 0) ? 'title="This leave type has reached its limit"' : '';
                                                            echo "<option value=\"$type\" $disabled $title>$type: $remaining days remaining</option>";
                                                        }
                                                        ?>
                                                    </select>
                                                    <label class="text-dark fw-bold" for="leave_type">Leave Type</label>
                                                </div>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <div class="form-floating mb-3 mb-md-0">
                                                    <input type="number" name="leave_days" id="leave_days" class="form-control" min="1" max="30" placeholder="" required readonly>
                                                    <label for="leave_days" class="form-label text-dark fw-bold">Number of Days</label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-md-6 mb-3">
                                                <div class="form-floating mb-3 mb-md-0">
                                                    <input type="date" id="start_date" name="start_date" class="form-control" required>
                                                    <label for="start_date" class="text-dark fw-bold">Start Date</label>
                                                </div>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <div class="form-floating mb-3 mb-md-0">
                                                    <input type="date" id="end_date" name="end_date" class="form-control" required>
                                                    <label for="end_date" class="text-dark fw-bold">End Date</label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <div class="form-floating mb-3 mb-md-0">
                                                <input type="file" id="proof" name="proof[]" class="form-control mb-2" accept="*/*" multiple>
                                                <label for="proof" class="text-dark fw-bold">Attach Proof</label>
                                                <small class="form-text text-warning">Note: Please upload the necessary proof (image or PDF) to support your leave request. You may upload multiple files,
                                                 but a single file is sufficient for your request to be considered valid.</small>
                                            </div>
                                        </div>
                                        <div class="d-flex justify-content-end">
                                            <button type="button" class="btn btn-danger me-2" onclick="resetForm()">Clear</button>
                                            <button type="button" class="btn btn-primary" onclick="showConfirmationModal()">Submit Leave Request</button>
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
                            <div class="modal-header">
                                <h5 class="modal-title" id="logoutModalLabel">Confirm Logout</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                Are you sure you want to log out?
                            </div>
                            <div class="modal-footer">
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
    <div class="modal fade" id="loadingModal" tabindex="-1" aria-labelledby="loadingModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content bg-transparent border-0">
                    <div class="modal-body d-flex flex-column align-items-center justify-content-center">
                            <!-- Bouncing coin spinner -->
                            <div class="coin-spinner"></div>
                            <div class="mt-3 text-light fw-bold">Please wait...</div>
                        </div>
                    </div>
                </div>
           </div>
    <script>
          document.addEventListener('DOMContentLoaded', function () {
                const buttons = document.querySelectorAll('.loading');
                const loadingModal = new bootstrap.Modal(document.getElementById('loadingModal'));

                // Loop through each button and add a click event listener
                buttons.forEach(button => {
                    button.addEventListener('click', function (event) {
                        // Show the loading modal
                        loadingModal.show();

                        // Disable the button to prevent multiple clicks
                        this.classList.add('disabled');

                        // Handle form submission buttons
                        if (this.closest('form')) {
                            event.preventDefault(); // Prevent the default form submit

                            // Submit the form after a short delay
                            setTimeout(() => {
                                this.closest('form').submit();
                            }, 1500);
                        }
                        // Handle links
                        else if (this.tagName.toLowerCase() === 'a') {
                            event.preventDefault(); // Prevent the default link behavior

                            // Redirect after a short delay
                            setTimeout(() => {
                                window.location.href = this.href;
                            }, 1500);
                        }
                    });
                });

                // Hide the loading modal when navigating back and enable buttons again
                window.addEventListener('pageshow', function (event) {
                    if (event.persisted) { // Check if the page was loaded from cache (back button)
                        loadingModal.hide();

                        // Re-enable all buttons when coming back
                        buttons.forEach(button => {
                            button.classList.remove('disabled');
                        });
                        
                    }
                });
            });
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
        document.getElementById('leave_type').addEventListener('change', calculateLeaveDays);

        function calculateLeaveDays() {
            const start_date = document.getElementById('start_date').value;
            const end_date = document.getElementById('end_date').value;
            const leave_type = document.getElementById('leave_type').value;
            
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

                // Check if leave type is sick leave and limit to 15 days
                if (leave_type === 'Sick leave' && totalDays > 15) {
                    totalDays = 15;
                    alert('Sick leave cannot exceed 15 days.');
                }

                // Update the number of days in the input field
                document.getElementById('leave_days').value = totalDays;
            }
        }
        //LEAVE DAYS END

        function validateLeaveRequest() {
            const leave_type = document.getElementById('leave_type').value;
            const leave_days = parseInt(document.getElementById('leave_days').value);
            const leaveLimits = {
                "Service Incentive leave": 5,
                "Vacation leave": 14,
                "Sick leave": 15,
                "Bereavement leave": 5,
                "Parental leave": 7,
                "Emergency leave": 5,
                "Maternity leave": 105,
                "Paternity leave": 14,
                "Special leave benefit for woman": 60,
                "Victims of violence against woman and their children": 10,
                "Jury duty leave": 5
            };

            if (leave_type && leave_days) {
                const usedLeaves = <?php echo json_encode($leaveRequests); ?>;
                const limit = leaveLimits[leave_type];
                const used = usedLeaves[leave_type] || 0;

                if (used + leave_days > limit) {
                    alert(`You have exceeded the leave limit for ${leave_type}.`);
                    return false;
                }
            }
            return true;
        }

        // Get the gender from PHP
        const gender = "<?php echo $gender; ?>";

        const maternityLeaveOption = document.querySelector('.maternity-leave');
        const paternityLeaveOption = document.querySelector('.paternity-leave');

        if (gender === 'Female') {
            maternityLeaveOption.style.display = 'block';  // Show Maternity Leave
        } else if (gender === 'Male') {
            paternityLeaveOption.style.display = 'block';  // Show Paternity Leave
        }

        function resetForm() {
            document.getElementById('leave-request-form').reset();  // Reset the form
        }

        function showConfirmationModal() {
            const confirmationModal = new bootstrap.Modal(document.getElementById('confirmationModal'));
            confirmationModal.show();
            return false; // Prevent form submission
        }

        function submitLeaveRequest() {
            document.getElementById('leave-request-form').submit();
        }

        document.getElementById('leave-request-form').addEventListener('submit', function(event) {
            event.preventDefault(); // Prevent the form from submitting
            showConfirmationModal(); // Show the confirmation modal
        });

    </script>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'> </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="../../js/employee.js"></script>

    <!-- Confirmation Modal -->
    <div class="modal fade" id="confirmationModal" tabindex="-1" aria-labelledby="confirmationModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content bg-dark text-light">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmationModalLabel">Confirm Leave Request</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to submit this leave request?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn border-secondary text-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="submitLeaveRequest()">Confirm</button>
                </div>
            </div>
        </div>
    </div>
</body>
</html>