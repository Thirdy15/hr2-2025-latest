<nav class="sb-topnav navbar navbar-expand navbar-dark border-bottom border-1 border-secondary bg-dark">
    <a class="navbar-brand ps-3 text-muted" href="../admin/dashboard.php">Admin Portal</a>
    <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle" href="#!"><i class="fas fa-bars text-light"></i></button>

    <!-- Flex container to hold both time/date and search form -->
    <div class="d-flex ms-auto me-0 me-md-3 my-2 my-md-0 align-items-center">
        <div class="text-light me-3 p-2 rounded shadow-sm bg-gradient" id="currentTimeContainer" 
        style="background: linear-gradient(45deg, #333333, #444444); border-radius: 5px;">
            <span class="d-flex align-items-center">
                <span class="pe-2">
                    <i class="fas fa-clock"></i> 
                    <span id="currentTime">00:00:00</span>
                </span>
                <button class="btn btn-outline-warning btn-sm ms-2" type="button" onclick="toggleCalendar()">
                    <i class="fas fa-calendar-alt"></i>
                    <span id="currentDate">00/00/0000</span>
                </button>
            </span>
        </div>
        <form class="d-none d-md-inline-block form-inline me-3">
            <div class="input-group">
                <input class="form-control" type="text" id="searchInput" placeholder="Search for..." aria-label="Search for..." aria-describedby="btnNavbarSearch" />
                <button class="btn btn-warning" id="btnNavbarSearch" type="button"><i class="fas fa-search"></i></button>
            </div>
            <div id="searchResults" class="list-group position-absolute w-100"></div>
        </form>
<!-- Notifications Bell -->
        
</nav>
<script>
    
    // GENERAL SEARCH
    const features = [
        { name: "Dashboard", link: "../admin/dashboard.php", path: "Employee Dashboard" },
        { name: "Profile", link: "../admin/profile.php", path: "Profile" },
        { name: "Attendance Scanner", link: "../admin/attendance.php", path: "Time and Attendance/Attendance Scanner" },
        { name: "Timesheet", link: "../admin/timesheet.php", path: "Time and Attendance/Timesheet" },
        { name: "Leave Request", link: "../admin/leave_request.php", path: "Leave Management/Leave Request" },
        { name: "Evaluation Ratings", link: "../admin/evaluation.php", path: "Performance Management/Evaluation Ratings" },
        { name: "Leave History", link: "../admin/leave_history.php", path: "Leave Management/Leave History" },
        { name: "Awardee", link: "../admin/awardee.php", path: "Social Recognition/Awardee" },
        { name: "Calendar", link: "../admin/calendar.php", path: "Accounts/Calendar" },
        { name: "Admin Account", link: "../admin/admin.php", path: "Accounts/admin" },
        { name: "Employee Account", link: "../admin/employee.php", path: "Accounts/employee" }
    ];
    
    document.getElementById('searchInput').addEventListener('input', function () {
        let input = this.value.toLowerCase();
        let results = '';
    
        if (input) {
            // Filter the features based on the search input
            const filteredFeatures = features.filter(feature => 
                feature.name.toLowerCase().includes(input)
            );
    
            if (filteredFeatures.length > 0) {
                // Generate the HTML for the filtered results
                filteredFeatures.forEach(feature => {
                    results += `                   
                        <a href="${feature.link}" class="list-group-item list-group-item-action">
                            ${feature.name}
                            <br>
                            <small class="text-muted">${feature.path}</small>
                        </a>`;
                });
            } else {
                // If no matches found, show "No result found"
                results = `<li class="list-group-item list-group-item-action">No result found</li>`;
            }
        }
    
        // Update the search results with the filtered features
        document.getElementById('searchResults').innerHTML = results;
        
        if (!input) {
            document.getElementById('searchResults').innerHTML = ''; // Clears the dropdown if input is empty
        }
    });

    document.getElementById('btnNavbarSearch').addEventListener('click', function () {
        const input = document.getElementById('searchInput').value.toLowerCase();
        const feature = features.find(feature => feature.name.toLowerCase() === input);

        if (feature) {
            window.location.href = feature.link;
        } else {
            alert('No result found');
        }
    });
    
    const searchInputElement = document.getElementById('searchInput');
    searchInputElement.addEventListener('hidden.bs.collapse', function () {
        searchInputElement.value = '';
        document.getElementById('searchResults').innerHTML = ''; 
    });
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
            .catch(error => {
                console.error('Error marking notification as read:', error);
                alert('An error occurred while marking notification as read.');
            });
        }

        // Function to clear all notifications
        function clearAllNotifications() {
            fetch('../admin/clear_notifications.php', {
                method: 'POST'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload(); // Reload the page to update the notifications
                } else {
                    alert('Failed to clear notifications.');
                }
            })
            .catch(error => {
                console.error('Error clearing notifications:', error);
                alert('An error occurred while clearing notifications.');
            });
        }

        // Function to show all notifications in modal
        function showAllNotifications() {
            const allNotificationsModalBody = document.getElementById('allNotificationsModalBody');
            allNotificationsModalBody.innerHTML = `
                <ul class="list-group">
                    <?php foreach ($notifications as $notification): ?>
                        <li class="list-group-item bg-dark text-white <?php echo $notification['is_read'] ? '' : 'font-weight-bold'; ?>">
                            <?php echo htmlspecialchars($notification['message']); ?>
                            <?php if (!$notification['is_read']): ?>
                                <span class="badge bg-danger ms-2">•</span>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            `;
            const allNotificationsModal = new bootstrap.Modal(document.getElementById('allNotificationsModal'));
            allNotificationsModal.show();
        }
</script>
