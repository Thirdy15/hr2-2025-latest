<?php
session_start();

if (!isset($_SESSION['a_id'])) {
    header("Location: ../admin/login.php");
    exit();
}

include '../db/db_conn.php';

// Fetch user info
$adminId = $_SESSION['a_id'];
$sql = "SELECT firstname, middlename, lastname, email, role, pfp FROM admin_register WHERE a_id = ?";
$stmt = $conn->prepare($sql);

// Check if statement preparation failed
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

$stmt->bind_param("i", $adminId);
$stmt->execute();
$result = $stmt->get_result();

// Fetch user information
$adminInfo = $result->fetch_assoc();

// Set profile picture or use default if not set
$profilePicture = !empty($adminInfo['pfp']) ? $adminInfo['pfp'] : '../img/defaultpfp.jpg';

// Close statement and connection
$stmt->close();

// Fetch notifications
$sql = "SELECT id, message, created_at, is_read FROM notifications ORDER BY created_at DESC";
$result = $conn->query($sql);
$notifications = [];
while ($row = $result->fetch_assoc()) {
    $notifications[] = $row;
}

// Fetch leave notifications
$sql = "SELECT id, message, created_at, is_read FROM leave_notifications ORDER BY created_at DESC";
$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    $notifications[] = $row;
}

// Sort notifications by created_at
usort($notifications, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});

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
    <title>Admin Dashboard | HR2</title>
    <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet">
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />
    <link href="../css/styles.css" rel="stylesheet" />
    <link href="../css/calendar.css" rel="stylesheet"/>
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    <style>
    /* Dark Mode Styles */
    .custom-modal {
        background: #121212; /* Dark background */
        color: #ffffff; /* White font */
        border-radius: 10px;
        box-shadow: 0px 8px 16px rgba(255, 255, 255, 0.05);
    }

    /* Dark Border for Separation */
    .border-dark {
        border-color: #333 !important;
    }

    /* Make Notifications More Interactive */
    .custom-body {
        max-height: 400px;
        overflow-y: auto;
        padding: 15px;
    }

    .custom-item {
        background: rgba(255, 255, 255, 0.1);
        color: #ffffff;
        border-radius: 8px;
        padding: 12px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        transition: all 0.3s ease-in-out;
        border: 1px solid #333; /* Dark border for separation */
    }

    .custom-item.unread {
        font-weight: bold;
        border-left: 4px solid #ff4757;
    }

    .custom-item.read {
        opacity: 0.8;
    }

    .custom-item:hover {
        background: rgba(255, 255, 255, 0.15);
        transform: scale(1.02);
    }

    .notif-icon {
        margin-right: 10px;
        font-size: 18px;
    }

    .time-stamp {
        font-size: 12px;
        opacity: 0.7;
    }

    /* Button Outline Light */
    .btn-outline-light:hover {
        background: rgba(255, 255, 255, 0.2);
    }
</style>
</head>
        
<body class="sb-nav-fixed bg-black">>
    <?php include 'navbar.php'; ?>
    </nav>
    <div id="layoutSidenav">
        <?php include 'sidebar.php'; ?>
        <div id="layoutSidenav_content">
            <main class="bg-black">
                <div class="container-fluid position-relative px-4">
                    <h1 class="mb-4 text-light">Dashboard</h1>
                        <div class="container" id="calendarContainer" 
                            style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 1050; 
                            width: 80%; height: 80%; display: none;">
                            <div class="row">
                                <div class="col-md-12">
                                    <div id="calendar" class="p-2"></div>
                                </div>
                            </div>
                        </div>
                <!-- Leave Request Status and Employee Performance Section -->
                <div class="row">
                    <div class="col-xl-6">
                        <div class="card mb-4">
                            <div class="card-header bg-dark text-light border-bottom border-1 border-secondary">
                                <i class="fas fa-chart-pie me-1"></i> 
                                <a class="text-light" href="../admin/leave_requests.php">Leave Request Status </a>
                            </div>
                            <div class="card-body bg-dark">
                                <canvas id="leaveStatusChart" width="300" height="300"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-6">
                        <div class="card mb-4">
                            <div class="card-header bg-dark text-light border-bottom border-1 border-secondary">
                                <i class="fas fa-chart-line me-1"></i>
                                <a class="text-light" href="#">Employee Performance</a>
                            </div>
                            <div class="card-body bg-dark">
                                <canvas id="employeePerformanceChart" width="300" height="300"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Employee Count per Department Section -->
                <div class="row">
                    <div class="col-xl-12">
                        <div class="card mb-4">
                            <div class="card-header bg-dark text-light border-bottom border-1 border-secondary">
                                <i class="fas fa-users me-1"></i>
                                <a class="text-light" href="#">Employee Count per Department</a>
                            </div>
                            <div class="card-body bg-dark">
                                <canvas id="employeeDepartmentChart" width="300" height="300"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Department Attendance Record Section -->
                <div class="row">
                    <div class="col-xl-12">
                        <div class="card mb-4">
                            <div class="card-header bg-dark text-light border-bottom border-1 border-secondary">
                                <i class="fas fa-chart-bar me-1"></i>
                                <a class="text-light" href="#">Department Attendance Record</a>
                            </div>
                            <div class="card-body bg-dark">
                                <select class="department-select form-control mb-3" id="departmentSelect">
                                    <option value="">Show All Departments</option>
                                    <option value="hr">HR Department</option>
                                    <option value="it">IT Department</option>
                                    <option value="sales">Sales Department</option>
                                    <option value="marketing">Marketing Department</option>
                                </select>
                                <div class="chart-container">
                                    <canvas id="attendanceChart" width="500" height="150"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Data Table Example Section -->
                <div class="row">
                    <div class="col-xl-12">
                        <div class="card mb-4 bg-dark text-light">
                            <div class="card-header border-bottom border-1 border-secondary d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fas fa-table me-1"></i>
                                    DataTable Example
                                </div>
                                <div class="input-group input-group-sm" style="width: 200px;">
                                    <input class="form-control" type="text" placeholder="Search..." aria-label="Search" aria-describedby="btnNavbarSearch" />
                                    <button class="btn btn-warning" id="btnNavbarSearch" type="button"><i class="fas fa-search"></i></button>
                                </div>
                            </div>
                            <div class="card-body">
                                <table id="datatablesSimple" class="table text-light">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Position</th>
                                            <th>Office</th>
                                            <th>Age</th>
                                            <th>Start date</th>
                                            <th>Salary</th>
                                        </tr>
                                    </thead>
                                    <tfoot>
                                        <tr>
                                            <th>Name</th>
                                            <th>Position</th>
                                            <th>Office</th>
                                            <th>Age</th>
                                            <th>Start date</th>
                                            <th>Salary</th>
                                        </tr>
                                    </tfoot>
                                    <tbody>
                                        <tr>
                                            <td>Zorita Serrano</td>
                                            <td>Software Engineer</td>
                                            <td>San Francisco</td>
                                            <td>56</td>
                                            <td>2012/06/01</td>
                                            <td>$115,000</td>
                                        </tr>
                                        <tr>
                                            <td>Jennifer Acosta</td>
                                            <td>Junior Javascript Developer</td>
                                            <td>Edinburgh</td>
                                            <td>43</td>
                                            <td>2013/02/01</td>
                                            <td>$75,650</td>
                                        </tr>
                                        <tr>
                                            <td>Cara Stevens</td>
                                            <td>Sales Assistant</td>
                                            <td>New York</td>
                                            <td>46</td>
                                            <td>2011/12/06</td>
                                            <td>$145,600</td>
                                        </tr>
                                        <tr>
                                            <td>Hermione Butler</td>
                                            <td>Regional Director</td>
                                            <td>London</td>
                                            <td>47</td>
                                            <td>2011/03/21</td>
                                            <td>$356,250</td>
                                        </tr>
                                        <tr>
                                            <td>Lael Greer</td>
                                            <td>Systems Administrator</td>
                                            <td>London</td>
                                            <td>21</td>
                                            <td>2009/02/27</td>
                                            <td>$103,500</td>
                                        </tr>
                                        <tr>
                                            <td>Jonas Alexander</td>
                                            <td>Developer</td>
                                            <td>San Francisco</td>
                                            <td>30</td>
                                            <td>2010/07/14</td>
                                            <td>$86,500</td>
                                        </tr>
                                        <tr>
                                            <td>Shad Decker</td>
                                            <td>Regional Director</td>
                                            <td>Edinburgh</td>
                                            <td>51</td>
                                            <td>2008/11/13</td>
                                            <td>$183,000</td>
                                        </tr>
                                        <tr>
                                            <td>Michael Bruce</td>
                                            <td>Javascript Developer</td>
                                            <td>Singapore</td>
                                            <td>29</td>
                                            <td>2011/06/27</td>
                                            <td>$183,000</td>
                                        </tr>
                                        <tr>
                                            <td>Donna Snider</td>
                                            <td>Customer Support</td>
                                            <td>New York</td>
                                            <td>27</td>
                                            <td>2011/01/25</td>
                                            <td>$112,000</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                    <!-- for leaveStatusChart -->
                    <?php

                        include '../db/db_conn.php';

                        $sql = "SELECT status, COUNT(*) as count FROM leave_requests GROUP BY status";
                        $result = $conn->query($sql);

                        $status_counts = [
                            'Approved' => 0,
                            'Supervisor Approved' => 0,
                            'Denied' => 0,
                        ];
                        while ($row = $result->fetch_assoc()) {
                            $status = $row['status'];
                            if (isset($status_counts[$status])) {
                                $status_counts[$status] = $row['count'];
                            }
                        }

                        $conn->close();
                    ?>
                </div>
            </main>
         <div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content bg-dark text-light">
                            <div class="modal-header border-bottom border-secondary">
                                <h5 class="modal-title" id="logoutModalLabel">Confirm Logout</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                Are you sure you want to log out?
                            </div>
                            <div class="modal-footer border-top border-secondary">
                                <button type="button" class="btn border-secondary text-light" data-bs-dismiss="modal">Cancel</button>
                                <form action="../admin/logout.php" method="POST">
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
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        // Doughnut chart data
        const data = {
            labels: ['Approved', 'Pending', 'Denied'],
            datasets: [{
                data: [
                    <?php echo $status_counts['Approved']; ?>,
                    <?php echo $status_counts['Supervisor Approved']; ?>,
                    <?php echo $status_counts['Denied']; ?>
                ],
                backgroundColor: ['#28a745', '#ffc107', '#dc3545']
            }]
        };

        // Doughnut chart configuration
        const leaveStatusCtx = document.getElementById('leaveStatusChart').getContext('2d');
        const leaveStatusChart = new Chart(leaveStatusCtx, {
            type: 'doughnut',
            data: data,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                legend: {
                    position: 'top'
                },
                title: {
                    display: true,
                    text: 'Leave Request Statuses'
                }
            }
        });
        //for leaveStatusChart end

        //for calendar only
        // Global variable for calendar
        let calendar; // Declare calendar variable globally

function toggleCalendar() {
    const calendarContainer = document.getElementById('calendarContainer');

    // Toggle visibility of the calendar container
    if (calendarContainer.style.display === 'none' || calendarContainer.style.display === '') {
        calendarContainer.style.display = 'block';

        // Initialize the calendar if it hasn't been initialized yet
        if (!calendar) {
            initializeCalendar();
        }
    } else {
        calendarContainer.style.display = 'none';
    }
}

// Function to initialize FullCalendar
function initializeCalendar() {
    const calendarEl = document.getElementById('calendar');
    calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        height: 440,  // Set the height of the calendar to make it small
        events: {
            url: '../db/holiday.php',  // Endpoint for fetching events
            method: 'GET',
            failure: function() {
                alert('There was an error fetching events!');
            }
        }
    });

    calendar.render();
}

// Set the current date when the page loads
document.addEventListener('DOMContentLoaded', function () {
    const currentDateElement = document.getElementById('currentDate');
    const currentDate = new Date().toLocaleDateString(); // Get the current date
    currentDateElement.textContent = currentDate; // Set the date text
});

// Close the calendar when clicking outside of it
document.addEventListener('click', function(event) {
    const calendarContainer = document.getElementById('calendarContainer');
    const calendarButton = document.querySelector('button[onclick="toggleCalendar()"]');

    // Hide the calendar if the user clicks outside of the calendar and button
    if (!calendarContainer.contains(event.target) && !calendarButton.contains(event.target)) {
        calendarContainer.style.display = 'none';
    }
});
        //for calendar only end

        //for leave request (error)
 document.addEventListener('DOMContentLoaded', function () {
    const calendarEl = document.getElementById('calendar');
    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth', // Basic view to confirm setup
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth'
        }
    });
    calendar.render();
    //console.log("Calendar initialized and rendered");

            function fetchLeaveData(date) {
                fetch(`leave_data.php?date=${date}`)
                .then(response => response.json())
                .then(data => {
                    let leaveDetails = 'Employees on leave:\n';
                    if (data.length > 0) {
                        data.forEach(employee => {
                            leaveDetails += `${employee.name} (${employee.leave_type})\n`;
                        });
                    } else {
                        leaveDetails = 'No employees on leave for this day.';
                    }
                    alert(leaveDetails); // You can replace this with a modal or a more styled output
                })
                .catch(error => {
                    console.error('Error fetching leave data:', error);
                    alert('An error occurred while fetching leave data.');
                });
            }
        });
            //for leave request (error) end

            function setCurrentTime() {
    const currentTimeElement = document.getElementById('currentTime');
    const currentDateElement = document.getElementById('currentDate');

    // Get the current date and time in UTC
    const currentDate = new Date();
    
    // Adjust time to Philippine Time (UTC+8)
    currentDate.setHours(currentDate.getHours() + 0);

    // Extract hours, minutes, and seconds
    const hours = currentDate.getHours();
    const minutes = currentDate.getMinutes();
    const seconds = currentDate.getSeconds();

    // Format hours, minutes, and seconds to ensure they are always two digits
    const formattedHours = hours < 10 ? '0' + hours : hours;
    const formattedMinutes = minutes < 10 ? '0' + minutes : minutes;
    const formattedSeconds = seconds < 10 ? '0' + seconds : seconds;

    // Set the current time
    currentTimeElement.textContent = `${formattedHours}:${formattedMinutes}:${formattedSeconds}`;

    // Set the current date
    currentDateElement.textContent = currentDate.toLocaleDateString();
}

// Initial call to set the current time and date
setCurrentTime();

// Update the current time every second
setInterval(setCurrentTime, 1000);

        // Function to show notification details in modal
        function showNotificationDetails(message) {
            const modalBody = document.getElementById('notificationModalBody');
            modalBody.textContent = message;
            const notificationModal = new bootstrap.Modal(document.getElementById('notificationModal'));
            notificationModal.show();
        }

        // Add event listeners to notification items
        document.addEventListener('DOMContentLoaded', function () {
            const notificationItems = document.querySelectorAll('.dropdown-item');
            notificationItems.forEach(item => {
                item.addEventListener('click', function (event) {
                    event.preventDefault();
                    const message = this.textContent;
                    showNotificationDetails(message);
                });
            });
        });

        

        // Function to hide reminder
        function hideReminder() {
            const reminderElement = document.getElementById('reminder');
            if (reminderElement) {
                reminderElement.style.display = 'none';
            }
        }

        // Function to show reminder
        function showReminder() {
            const reminderElement = document.getElementById('reminder');
            if (reminderElement) {
                reminderElement.style.display = 'block';
            }
        }


        // Dummy Data for Employee Performance
        const employees = [
            { name: "John Doe", loansDisbursed: 50, recoveryRate: 95, customerRating: 4.5 },
            { name: "Jane Smith", loansDisbursed: 45, recoveryRate: 85, customerRating: 4.2 },
            { name: "Alice Johnson", loansDisbursed: 60, recoveryRate: 90, customerRating: 4.7 },
        ];

        // Employee Performance Chart
        const employeePerformanceCtx = document.getElementById("employeePerformanceChart").getContext("2d");
        new Chart(employeePerformanceCtx, {
            type: "bar",
            data: {
                labels: employees.map(emp => emp.name),
                datasets: [{
                    label: "Loans Disbursed",
                    data: employees.map(emp => emp.loansDisbursed),
                    backgroundColor: "#1abc9c", // Highlight color
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: true },
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: "#4a5f78", // Grid color
                        },
                        ticks: {
                            color: "#fff", // Y-axis text color
                        },
                    },
                    x: {
                        grid: {
                            color: "#4a5f78", // Grid color
                        },
                        ticks: {
                            color: "#fff", // X-axis text color
                        },
                    },
                },
            },
        });

        // Dummy Data for Employee Count per Department
        const departments = [
            { name: "Sales", count: 25 },
            { name: "Marketing", count: 15 },
            { name: "Finance", count: 10 },
            { name: "HR", count: 8 },
            { name: "IT", count: 20 },
            { name: "Credit", count: 12 }, // Added Credit Department
        ];

        // Employee Count per Department Bar Chart
        const employeeDepartmentCtx = document.getElementById("employeeDepartmentChart").getContext("2d");
        new Chart(employeeDepartmentCtx, {
            type: "bar",
            data: {
                labels: departments.map(dept => dept.name),
                datasets: [{
                    label: "Employee Count",
                    data: departments.map(dept => dept.count),
                    backgroundColor: "#1abc9c", // Bar color
                    borderColor: "#34495e", // Border color
                    borderWidth: 1,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false, // Hide legend for bar chart
                    },
                    tooltip: {
                        enabled: true,
                        callbacks: {
                            label: function (context) {
                                const label = context.label || "";
                                const value = context.raw || 0;
                                return `${label}: ${value} employees`;
                            },
                        },
                    },
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: "#4a5f78", // Grid color
                        },
                        ticks: {
                            color: "#fff", // Y-axis text color
                        },
                    },
                    x: {
                        grid: {
                            color: "#4a5f78", // Grid color
                        },
                        ticks: {
                            color: "#fff", // X-axis text color
                        },
                    },
                },
            },
        });

        document.addEventListener('DOMContentLoaded', function () {
            const ctx = document.getElementById('attendanceChart').getContext('2d');
            const departmentSelect = document.getElementById('departmentSelect');

            // Temporary data for departments and their employees' attendance
            const departmentData = {
                hr: {
                    labels: ['John Doe', 'Jane Smith', 'Alice Johnson', 'Bob Brown'],
                    attendance: ['Present', 'Absent', 'Present', 'Present'] // Attendance status
                },
                it: {
                    labels: ['Mike Ross', 'Harvey Specter', 'Rachel Zane', 'Louis Litt'],
                    attendance: ['Present', 'Present', 'Absent', 'Present']
                },
                sales: {
                    labels: ['Tom Cruise', 'Emma Watson', 'Chris Evans', 'Scarlett Johansson'],
                    attendance: ['Absent', 'Present', 'Present', 'Absent']
                },
                marketing: {
                    labels: ['Tony Stark', 'Steve Rogers', 'Natasha Romanoff', 'Bruce Banner'],
                    attendance: ['Present', 'Present', 'Present', 'Absent']
                }
            };

            // Calculate total present and absent employees for each department
            const departmentSummary = {
                labels: ['HR', 'IT', 'Sales', 'Marketing'],
                present: [
                    departmentData.hr.attendance.filter(status => status === 'Present').length,
                    departmentData.it.attendance.filter(status => status === 'Present').length,
                    departmentData.sales.attendance.filter(status => status === 'Present').length,
                    departmentData.marketing.attendance.filter(status => status === 'Present').length
                ],
                absent: [
                    departmentData.hr.attendance.filter(status => status === 'Absent').length,
                    departmentData.it.attendance.filter(status => status === 'Absent').length,
                    departmentData.sales.attendance.filter(status => status === 'Absent').length,
                    departmentData.marketing.attendance.filter(status => status === 'Absent').length
                ]
            };

            let attendanceChart;

            // Function to create or update the chart
            function updateChart(labels, presentData, absentData = null, label = 'Department Attendance') {
                if (attendanceChart) {
                    attendanceChart.destroy(); // Destroy the existing chart
                }

                const datasets = [
                    {
                        label: 'Present',
                        data: presentData,
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 1
                    }
                ];

                if (absentData) {
                    datasets.push({
                        label: 'Absent',
                        data: absentData,
                        backgroundColor: 'rgba(255, 99, 132, 0.2)',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 1
                    });
                }

                attendanceChart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: datasets
                    },
                    options: {
                        scales: {
                            y: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: 'Number of Employees'
                                }
                            },
                            x: {
                                title: {
                                    display: true,
                                    text: label === 'Department Attendance' ? 'Departments' : 'Employees'
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                display: true,
                                position: 'top',
                            },
                            tooltip: {
                                enabled: true
                            }
                        }
                    }
                });
            }

            // Initial chart showing present and absent employees for all departments
            updateChart(departmentSummary.labels, departmentSummary.present, departmentSummary.absent, 'Department Attendance');

            // Event listener for department selection
            departmentSelect.addEventListener('change', function () {
                const selectedDepartment = departmentSelect.value;

                if (selectedDepartment && departmentData[selectedDepartment]) {
                    const { labels, attendance } = departmentData[selectedDepartment];
                    const presentData = attendance.map(status => status === 'Present' ? 1 : 0);
                    const absentData = attendance.map(status => status === 'Absent' ? 1 : 0);
                    updateChart(labels, presentData, absentData, 'Employee Attendance');
                } else {
                    // Show present and absent employees for all departments if no specific department is selected
                    updateChart(departmentSummary.labels, departmentSummary.present, departmentSummary.absent, 'Department Attendance');
                }
            });
        });
    </script>

    <!-- Notification Details Modal -->
    <!-- <div class="modal fade" id="notificationModal" tabindex="-1" aria-labelledby="notificationModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content bg-dark text-light">
                <div class="modal-header border-bottom border-secondary">
                    <h5 class="modal-title" id="notificationModalLabel">Notification Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="notificationModalBody">
                    Notification details will be loaded here -->
                <!-- </div>
                <div class="modal-footer border-top border-secondary">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    Error Modal -->
    <div class="modal fade" id="errorModal" tabindex="-1" aria-labelledby="errorModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content bg-dark text-light">
                <div class="modal-header border-bottom border-secondary">
                    <h5 class="modal-title" id="errorModalLabel">Error</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p id="errorMessage"></p>
                </div>
                <div class="modal-footer border-top border-secondary">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    <!-- Logout Modal -->
    <div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content bg-dark text-light">
                <div class="modal-header border-bottom border-secondary">
                    <h5 class="modal-title" id="logoutModalLabel">Confirm Logout</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to log out?
                </div>
                <div class="modal-footer border-top border-secondary">
                    <button type="button" class="btn border-secondary text-light" data-bs-dismiss="modal">Cancel</button>
                    <form action="../admin/logout.php" method="POST">
                        <button type="submit" class="btn btn-danger">Logout</button>
                    </form>
                </div>
            </div>
        </div>
    </div>      
                                        
                                        

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="../js/admin.js"></script>
    <script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.querySelector('.input-group-sm input');
    const table = document.getElementById('datatablesSimple');
    const rows = table.querySelectorAll('tbody tr');

    searchInput.addEventListener('input', function () {
        const searchTerm = searchInput.value.toLowerCase();

        rows.forEach(row => {
            const cells = row.querySelectorAll('td');
            const nameCell = cells[0].textContent.toLowerCase();

            if (nameCell.includes(searchTerm)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });
});
</script>

<script>
        // Function to show notification details in modal
        function showNotificationDetails(message) {
            const modalBody = document.getElementById('notificationModalBody');
            modalBody.textContent = message;
            const notificationModal = new bootstrap.Modal(document.getElementById('notificationModal'));
            notificationModal.show();
        }

        // Add event listeners to notification items
        document.addEventListener('DOMContentLoaded', function () {
            const notificationItems = document.querySelectorAll('.dropdown-item');
            notificationItems.forEach(item => {
                item.addEventListener('click', function (event) {
                    event.preventDefault();
                    const message = this.textContent;
                    showNotificationDetails(message);
                });
            });
        });

        // Function to mark a notification as read
        function markAsRead(notificationId) {
            fetch('../admin/mark_notification_read.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ id: notificationId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Reduce the notification count
                    const notificationCountElement = document.getElementById('notificationCount');
                    let notificationCount = parseInt(notificationCountElement.textContent);
                    notificationCount -= 1;
                    if (notificationCount > 0) {
                        notificationCountElement.textContent = notificationCount;
                    } else {
                        notificationCountElement.remove();
                    }
                    // Mark the notification as read visually
                    const notificationItem = document.querySelector(`a[data-id="${notificationId}"]`);
                    notificationItem.classList.remove('font-weight-bold');
                    notificationItem.querySelector('.badge').remove();
                } else {
                    alert('Failed to mark notification as read.');
                }
            })
        }
    </script>
