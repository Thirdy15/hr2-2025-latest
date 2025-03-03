<?php
session_start();

if (!isset($_SESSION['a_id'])) {
    header("Location: ../admin/login.php");
    exit();
}

include '../db/db_conn.php';

// Fetch user info
$adminId = $_SESSION['a_id'];
$sql = "SELECT a_id, firstname, middlename, lastname, birthdate, email, role, department, phone_number, address, pfp FROM admin_register WHERE a_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $adminId);
$stmt->execute();
$result = $stmt->get_result();
$adminInfo = $result->fetch_assoc();

// Fetch employee data
$sql = "SELECT e_id, firstname, lastname, face_image, gender, email, department, position, phone_number, address FROM employee_register WHERE role='Employee'";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Management</title>
    <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet">
    <link href="../css/styles.css" rel="stylesheet" />
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />
    <link href="../css/calendar.css" rel="stylesheet"/>
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    <style>
        .btn {
            transition: transform 0.3s ease;
            border-radius: 50px;
        }

        .btn:hover {
            transform: translateY(-4px); /* Raise effect on hover */
        }
    </style>
</head>
<body class="sb-nav-fixed bg-black">
    
    <div id="layoutSidenav">
        <?php include 'sidebar.php'; ?>
        <div id="layoutSidenav_content">
            <main class="bg-black">
                <div class="container-fluid position-relative px-4">
                    <h1 class="mb-4 text-light">Employees' Account Management</h1>
                    <div class="container" id="calendarContainer" 
                    style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 1050; 
                            width: 80%; height: 80%; display: none;">
                        <div class="row">
                            <div class="col-md-12">
                                <div id="calendar" class="p-2"></div>
                            </div>
                        </div>
                    </div>               
                    <div class=""></div>
                    <div class="card mb-4 bg-dark text-light">
                        <div class="card-header border-bottom border-1 border-secondary d-flex justify-content-between align-items-center">
                            <span>
                                <i class="fas fa-table me-1"></i>
                                Employee Accounts
                            </span>
                            <a class="btn btn-primary text-light" href="../admin/create_employee.php">Create Employee</a>
                        </div>
                        <div class="card-body">
                            <table id="datatablesSimple" class="table text-light text-center">
                                <thead class="thead-light">
                                    <tr class="text-center text-light">
                                        <th>Employee ID</th>
                                        <th>Name</th>
                                        <th>Gender</th>
                                        <th>Email</th>
                                        <th>Department</th>
                                        <th>Role</th>
                                        <th>Phone Number</th>
                                        <th>Address</th>
                                        <th>Registered Face</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($result->num_rows > 0): ?>
                                        <?php while ($row = $result->fetch_assoc()): ?>
                                            <tr class="text-center text-light align-items-center">
                                                <td><?php echo htmlspecialchars(trim($row['e_id'] ?? 'N/A')); ?></td>
                                                <td><?php echo htmlspecialchars(trim($row['firstname'] . ' ' . $row['lastname'] ?? 'N/A')); ?></td>
                                                <td><?php echo htmlspecialchars(trim($row['gender'] ?? 'N/A')); ?></td>
                                                <td><?php echo htmlspecialchars(trim($row['email'] ?? 'N/A')); ?></td>
                                                <td><?php echo htmlspecialchars(trim($row['department'] ?? 'N/A')); ?></td>
                                                <td><?php echo htmlspecialchars(trim($row['position'] ?? 'N/A')); ?></td>
                                                <td><?php echo htmlspecialchars(trim($row['phone_number'] ?? 'N/A')) ?: 'N/A'; ?></td>
                                                <td><?php echo htmlspecialchars(trim($row['address'] ?? 'N/A')) ?: 'N/A'; ?></td>
                                                <td>
                                                    <?php if (!empty($row['face_image'])): ?>
                                                        <img src="/HR2/face/<?php echo htmlspecialchars(trim(basename($row['face_image']))); ?>" style="width: 100px; height: 100px;">
                                                    <?php else: ?>
                                                        N/A
                                                    <?php endif; ?>
                                                </td>
                                                <td class='d-flex justify-content-around '>
                                                    <button class="btn btn-success btn-sm me-2" 
                                                        onclick="fillUpdateForm(<?php echo $row['e_id']; ?>, '<?php echo htmlspecialchars($row['firstname']); ?>', '<?php echo htmlspecialchars($row['lastname']); ?>', '<?php echo htmlspecialchars($row['email']); ?>',
                                                        '<?php echo htmlspecialchars($row['department']); ?>', '<?php echo htmlspecialchars($row['position']); ?>', '<?php echo htmlspecialchars($row['phone_number']); ?>', '<?php echo htmlspecialchars($row['address']); ?>')">Update</button>
                                                    <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $row['e_id']; ?>">Delete</button>
                                                </td>
                                            </tr>
                                            <!-- Delete Modal -->
                                            <div class="modal fade" id="deleteModal<?php echo $row['e_id']; ?>" tabindex="-1" aria-labelledby="deleteModalLabel<?php echo $row['e_id']; ?>" aria-hidden="true">
                                                <div class="modal-dialog modal-dialog-centered">
                                                    <div class="modal-content bg-dark text-light">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="deleteModalLabel<?php echo $row['e_id']; ?>">
                                                                <i class="fa fa-info-circle text-light me-2 fs-4"></i> Delete Employee
                                                            </h5>
                                                            <button type="button" class="btn-close text-light" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            Are you sure you want to delete this employee?
                                                            <div class="d-flex justify-content-center mt-3">
                                                                <button type="button" class="btn btn-danger me-2" onclick="confirmDelete(<?php echo $row['e_id']; ?>)">Yes</button>
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No</button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr><td colspan="10" class="text-center">No records found.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
            <!-- Update Employee Modal -->
            <div class="modal fade" id="updateEmployeeModal" tabindex="-1" aria-labelledby="updateEmployeeModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content bg-dark text-light">
                        <div class="modal-header">
                            <h5 class="modal-title text-center" id="updateEmployeeModalLabel">Update Employee Account</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form id="updateForm">
                                <input type="hidden" name="e_id" id="updateId">      
                                    <div class="">                     
                                        <div class="form-group mb-3 row">
                                            <div class="col-sm-6 bg-dark form-floating mb-3">
                                                <input type="text" class="form-control fw-bold" name="firstname" required>
                                                <label class="text-dark fw-bold" for="firstname">First Name</label>
                                            </div>
                                            <div class="col-sm-6 bg-dark form-floating mb-3">                                 
                                                <input type="text" class="form-control fw-bold" name="lastname" required>
                                                <label class="text-dark fw-bold" for="lastname">Last Name</label>
                                            </div>
                                        </div>
                                    </div>  
                                    <div class="">
                                        <div class="form-group mb-3 row">
                                            <div class="col-sm-6 bg-dark form-floating mb-3">
                                                <input type="email" class="form-control fw-bold" name="email" placeholder="Email" required>
                                                <label class="text-dark fw-bold" for="email">Email</label>
                                            </div> 
                                            <div class="col-sm-6 bg-dark form-floating mb-3">
                                            <input type="text" class="form-control fw-bold" name="phone_number" pattern="^\d{11}$" maxlength="11" required>
                                            <label class="text-dark fw-bold" for="phone_number">Phone Number</label>
                                        </div>
                                        </div>
                                    </div>  
                                    <div class="">                          
                                        <div class="form-group mb-3 row">
                                            <div class="col-sm-6 bg-dark form-floating mb-3">
                                                <select class="form-control fw-bold form-select" name="department" required>
                                                    <option value="" disabled selected>Select a Department</option>
                                                    <option value="Finance Department">Finance Department</option>
                                                    <option value="Administration Department">Administration Department</option>
                                                    <option value="Sales Department">Sales Department</option>
                                                    <option value="Credit Department">Credit Department</option>
                                                    <option value="Human Resource Department">Human Resource Department</option>
                                                    <option value="IT Department">IT Department</option>
                                                </select>
                                                <label class="text-dark fw-bold" for="department">Department</label>
                                            </div>
                                            <div class="col-sm-6 bg-dark form-floating mb-3">
                                                <select class="form-control fw-bold form-select" name="position" required>
                                                    <option value="" disabled selected>Select Role</option>
                                                    <option value="Contractual">Contractual</option>
                                                    <option value="Field Worker">Field Worker</option>
                                                    <option value="Staff">Staff</option>
                                                    <option value="Supervisor">Supervisor</option>
                                                </select>
                                                <label class="text-dark fw-bold" for="position">Role</label>
                                            </div>
                                        </div>   
                                    </div>  
                                    <div class="">  
                                        <div class="form-group mb-3 row">
                                            <div class="col-sm-12 bg-dark form-floating mb-3">
                                                <input type="text" class="form-control fw-bold" name="address" placeholder="Address" required>
                                                <label class="text-dark fw-bold" for="address">Address</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-end">
                                        <button type="button" class="btn btn-secondary me-2" onclick="closeModal()">Close</button>
                                        <button type="submit" class="btn btn-primary">Update</button>
                                    </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Status Modal -->
            <div class="modal fade" id="statusModal" tabindex="-1" aria-labelledby="statusModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content bg-dark text-light">
                        <div class="modal-header">
                            <h5 class="modal-title" id="statusModalLabel">
                                <i class="fa fa-info-circle text-light me-2 fs-4"></i> Message
                            </h5>
                            <button type="button" class="btn-close text-light" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body align-items-center" id="statusModalBody">
                            <!-- Status message will be inserted here -->
                            <div class="d-flex justify-content-center mt-3">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Ok</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
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
            url: '../db/holiday.php',  
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

//UPDATE MODAL
let modalInstance;

function fillUpdateForm(id, firstname, lastname, email, department, position, phone_number, address) {
    document.getElementById('updateId').value = id;
    document.querySelector('input[name="firstname"]').value = firstname.trim() === '' ? 'N/A' : firstname;
    document.querySelector('input[name="lastname"]').value = lastname.trim() === '' ? 'N/A' : lastname;
    document.querySelector('input[name="email"]').value = email.trim() === '' ? 'N/A' : email;
    document.querySelector('select[name="department"]').value = department.trim() === '' ? 'N/A' : department;
    document.querySelector('select[name="position"]').value = position.trim() === '' ? 'N/A' : position;
    document.querySelector('input[name="phone_number"]').value = phone_number.trim() === '' ? 'N/A' : phone_number;
    document.querySelector('input[name="address"]').value = address.trim() === '' ? 'N/A' : address;

    modalInstance = new bootstrap.Modal(document.getElementById('updateEmployeeModal'));
    modalInstance.show();
}

function closeModal() {
    if (modalInstance) {
        modalInstance.hide();
    }
}

function confirmDelete(id) {
    const formData = new FormData();
    formData.append('e_id', id);

    fetch('../db/delete_employee.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        showStatusModal(data.success || data.error);
        if (data.success) {
            location.reload();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showStatusModal('An error occurred while deleting the employee.');
    });
}

document.getElementById('updateForm').onsubmit = function(e) {
    e.preventDefault();
    const formData = new FormData(this);

    fetch('../db/update_employee.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        showStatusModal(data.success || data.error);
        if (data.success) {
            closeModal();
            location.reload();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showStatusModal('An error occurred while updating the employee.');
    });
};

function showStatusModal(message) {
    document.getElementById('statusModalBody').insertAdjacentHTML('afterbegin', `<p>${message}</p>`);
    var statusModal = new bootstrap.Modal(document.getElementById('statusModal'));
    statusModal.show();
}

// Show status modal if there's a status message
<?php if (isset($_SESSION['status_message'])): ?>
    showStatusModal('<?php echo $_SESSION['status_message']; ?>');
    <?php unset($_SESSION['status_message']); ?>
<?php endif; ?>
</script>
<script src="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/umd/simple-datatables.min.js" crossorigin="anonymous"></script>
<script src="../js/datatables-simple-demo.js"></script>
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'> </script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script src="../js/admin.js"></script>
</body>
</html>