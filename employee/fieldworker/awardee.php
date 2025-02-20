<?php
// Start session and check admin login
session_start();
if (!isset($_SESSION['e_id'])) {
    header("Location: ../../employee/login.php");
    exit();
}

// Include database connection
include '../../db/db_conn.php';

// Fetch user info
$employeeId = $_SESSION['e_id'];
$sql = "SELECT e_id, firstname, middlename, lastname, birthdate, email, role, position, department, phone_number, address, pfp FROM employee_register WHERE e_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $employeeId);
$stmt->execute();
$result = $stmt->get_result();
$employeeInfo = $result->fetch_assoc();

function calculateProgressCircle($averageScore) {
    return ($averageScore / 10) * 100;
}

function getTopEmployeesByCriterion($conn, $criterion, $criterionLabel, $index) {
    // Whitelist allowed criteria to avoid SQL injection
    $allowedCriteria = ['quality', 'communication_skills', 'teamwork', 'punctuality', 'initiative'];
    if (!in_array($criterion, $allowedCriteria)) {
        echo "<p>Invalid criterion selected</p>";
        return;
    }

    // SQL query to fetch the highest average score for each employee
    $sql = "SELECT e.e_id, e.firstname, e.lastname, e.department, e.pfp, e.email, 
                   AVG(ae.$criterion) AS avg_score,
                   AVG(ae.quality) AS avg_quality,
                   AVG(ae.communication_skills) AS avg_communication,
                   AVG(ae.teamwork) AS avg_teamwork,
                   AVG(ae.punctuality) AS avg_punctuality,
                   AVG(ae.initiative) AS avg_initiative
            FROM employee_register e
            JOIN admin_evaluations ae ON e.e_id = ae.e_id
            GROUP BY e.e_id
            ORDER BY avg_score DESC
            LIMIT 1";

    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();

    // Path to the default profile picture
    $defaultPfpPath = '../../img/defaultpfp.jpg';
    $defaultPfp = base64_encode(file_get_contents($defaultPfpPath));

    // Output the awardee's information for each criterion
    echo "<div class='category' id='category-$index' style='display: none;'>";
    echo "<h3 class='text-center mt-4'>$criterionLabel</h3>";

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            // Check if profile picture exists, else use the default picture
            if (!empty($row['pfp'])) {
                $pfp = base64_encode($row['pfp']);  // Assuming pfp is a BLOB
            } else {
                $pfp = $defaultPfp;
            }

            // Calculate percentage for the progress circle
            $scorePercentage = calculateProgressCircle($row['avg_score']);
            
            echo "<div class='employee-card'>";
            echo "<div class='metrics-container'>";

            // Left metrics
            echo "<div class='metrics-column'>";
            echo "<div class='metric-box fade-in'>";
            echo "<span class='metric-label'>Quality of Work</span>";
            echo "<span class='metric-value'>" . round($row['avg_quality'], 2) . "</span>";
            echo "<div class='star-rating' style='margin-top: 5px;'>";
            
            // Get the exact quality score
            $qualityScore = $row['avg_quality'];
            
            for ($i = 1; $i <= 6; $i++) {
                if ($i <= floor($qualityScore)) {
                    echo "<i class='bi bi-star-fill' style='color: gold;'></i>"; 
                } elseif ($i == ceil($qualityScore)) {
                    $decimalPart = $qualityScore - floor($qualityScore);
                    
                    if ($decimalPart >= 0.2 && $decimalPart <= 0.9) {
                        echo "<i class='bi bi-star-half' style='color: gold;'></i>";
                    } else {
                        echo "<i class='bi bi-star' style='color: lightgray;'></i>";
                    }
                } else {
                    echo "<i class='bi bi-star' style='color: lightgray;'></i>"; 
                }
            }
            
            echo "</div>";
            echo "</div>";
            
            
            echo "<div class='metric-box fade-in' style='animation-delay: 0.2s;'>";
            echo "<span class='metric-label'>Communication Skills</span>";
            echo "<span class='metric-value'>" . round($row['avg_communication'], 2) . "</span>";
           
           
            echo "</div>";
            echo "</div>";

            // Center profile section
            echo "<div class='profile-section'>";
            echo "<div class='progress-circle-container'>";
            echo "<div class='progress-circle' data-progress='" . $scorePercentage . "'>";
            echo "<div class='profile-image-container'>";
            if (!empty($pfp)) {
                echo "<img src='data:image/jpeg;base64,$pfp' alt='Profile Picture' class='profile-image'>";
            }
            echo "</div>";
            echo "</div>";
            echo "</div>";
            
            echo "<div class='profile-info'>";
            echo "<h2 class='employee-name'>" . htmlspecialchars($row['firstname'] . ' ' . $row['lastname']) . "</h2>";
            echo "<p class='department-name'>" . htmlspecialchars($row['department']) . "</p>";
            echo "</div>";
            echo "<div class='employee-id fade-in' style='animation-delay: 0.8s;'>";
            echo "Employee ID: " . htmlspecialchars($row['e_id']);
            echo "</div>";

            // New metric box below employee ID
            echo "<div class='metric-box fade-in' style='animation-delay: 0.8s;'>";
            echo "<span class='metric-label'>Initiative</span>";
            echo "<span class='metric-value'>" . round($row['avg_initiative'], 2) . "</span>";
            echo "<div class='star-rating' style='margin-top: 5px;'>";
            
            // Get the exact initiative score
            $initiativeScore = $row['avg_initiative'];
            
            for ($i = 1; $i <= 6; $i++) {
                if ($i <= floor($initiativeScore)) {
                    echo "<i class='bi bi-star-fill' style='color: gold;'></i>"; 
                } elseif ($i == ceil($initiativeScore)) {
                    $decimalPart = $initiativeScore - floor($initiativeScore);
                    
                    if ($decimalPart >= 0.2 && $decimalPart <= 0.9) {
                        echo "<i class='bi bi-star-half' style='color: gold;'></i>";
                    } else {
                        echo "<i class='bi bi-star' style='color: lightgray;'></i>";
                    }
                } else {
                    echo "<i class='bi bi-star' style='color: lightgray;'></i>"; 
                }
            }
            
            echo "</div>";
            echo "</div>";
            

            // Add buttons for comments and reactions
            echo "<div class='comment-reaction-buttons'>";
            echo "  <button class='btn btn-primary' onclick='openCommentModal()'>Write a Comment</button>";
            echo "  <div class='comment-input-container'>";
            echo "      <button class='btn btn-primary react-btn' onclick='showReactions(this)'>React</button>";
            echo "      <div id='reaction-menu' class='reaction-dropdown'>";
            echo "          <button onclick='selectReaction(\"👍 Like\")'>👍</button>";
            echo "          <button onclick='selectReaction(\"😂 Haha\")'>😂</button>";
            echo "          <button onclick='selectReaction(\"❤️ Heart\")'>❤️</button>";
            echo "          <button onclick='selectReaction(\"😡 Angry\")'>😡</button>";
            echo "          <button onclick='selectReaction(\"😢 Sad\")'>😢</button>";
            echo "      </div>";
            echo "  </div>";
            echo "</div>";

            echo "</div>"; // End profile-section

            // Right metrics
            echo "<div class='metrics-column'>";
            echo "<div class='metric-box fade-in' style='animation-delay: 0.4s;'>";
            echo "<span class='metric-label'>Teamwork</span>";
            echo "<span class='metric-value'>" . round($row['avg_teamwork'], 2) . "</span>";
            echo "<div class='star-rating' style='margin-top: 5px;'>";
            
            // Get the exact teamwork score
            $teamworkScore = $row['avg_teamwork'];
            
            for ($i = 1; $i <= 6; $i++) {
                if ($i <= floor($teamworkScore)) {
                    echo "<i class='bi bi-star-fill' style='color: gold;'></i>"; 
                } elseif ($i == ceil($teamworkScore)) {
                    $decimalPart = $teamworkScore - floor($teamworkScore);
                    
                    if ($decimalPart >= 0.2 && $decimalPart <= 0.9) {
                        echo "<i class='bi bi-star-half' style='color: gold;'></i>";
                    } else {
                        echo "<i class='bi bi-star' style='color: lightgray;'></i>";
                    }
                } else {
                    echo "<i class='bi bi-star' style='color: lightgray;'></i>"; 
                }
            }
            
            echo "</div>"; 
            echo "</div>";
            
            
            echo "<div class='metric-box fade-in' style='animation-delay: 0.6s;'>";
            echo "<span class='metric-label'>Punctuality</span>";
            echo "<span class='metric-value'>" . round($row['avg_punctuality'], 2) . "</span>";
            echo "<div class='star-rating' style='margin-top: 5px;'>";
            
            // Get the exact punctuality score
            $punctualityScore = $row['avg_punctuality'];
            
            for ($i = 1; $i <= 6; $i++) {
                if ($i <= floor($punctualityScore)) {
                    echo "<i class='bi bi-star-fill' style='color: gold;'></i>"; 
                } elseif ($i == ceil($punctualityScore)) {
                    $decimalPart = $punctualityScore - floor($punctualityScore);
                    
                    if ($decimalPart >= 0.2 && $decimalPart <= 0.9) {
                        echo "<i class='bi bi-star-half' style='color: gold;'></i>";
                    } else {
                        echo "<i class='bi bi-star' style='color: lightgray;'></i>";
                    }
                } else {
                    echo "<i class='bi bi-star' style='color: lightgray;'></i>"; 
                }
            }
            
            echo "</div>";
            echo "</div>";

            echo "</div>"; // End metrics-container
            echo "</div>"; // End employee-card
        }
    } else {
        echo "<p class='text-center'>No outstanding employees found for $criterionLabel.</p>";
    }

    echo "</div>"; // End of category
    $stmt->close();
}


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Outstanding Employees</title>
    <link href="../../css/styles.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../../css/styles.css" rel="stylesheet" />
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />
    <link href="../../css/calendar.css" rel="stylesheet"/>
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    <style>
        .card {
            border: 2px solid #ddd; 
            border-radius: 10px; 
            box-shadow: 0 2px 4px rgba(0,0,0,0.1); 
            padding: 10px; 
            background-color: #f9f9f9;
        }
        .card-img {
            border-radius: 10px;
        }
        .card-body {
            padding-left: 10px;
        }
        .card-title {
            font-size: 20px; 
            font-weight: bold; 
            color: #333;
        }
        .card-text {
            font-size: 14px;
        }
        .category {
            display: none;
        }
        .btn {
            transition: transform 0.3s, background-color 0.3s; /* Smooth transition */
            border-radius: 20px;
            padding: 5px 10px;
            font-size: 14px;
        }

        .btn:hover {
            transform: translateY(-2px); /* Raise the button up */
        }
        
        .emoji-container {
            display: none;
            gap: 15px;
            cursor: pointer;
            margin-top: 15px;
            flex-wrap: wrap;
        }
        .emoji {
            font-size: 30px;
            transition: transform 0.2s ease;
            padding: 10px;
        }
        .emoji:hover {
            transform: scale(1.2);
        }
        .reaction {
            margin-top: 15px;
            font-size: 18px;
            color: #333;
        }
        .saved-reaction {
            margin-top: 10px;
            color: #007bff;
        }
        .open-btn {
            font-size: 16px;
            padding: 8px 16px;
            background-color: #007bff;
            color: white;
            border: none;
            cursor: pointer;
            border-radius: 5px;
            transition: background-color 0.3s;
        }
        .open-btn:hover {
            background-color: #0056b3;
        }
        .reaction-count {
            font-size: 18px;
            color: #333;
            margin-top: 10px;
            display: flex;
            gap: 15px;
        }
        .reaction-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .selected-emoji {
            font-size: 50px;
            margin-top: 20px;
        }
        .employee-card {
            background: rgb(33, 37, 41);
            border-radius: 20px;
            padding: 2rem;
            max-width: 1200px;
            margin: 2rem auto;
            color: rgba(248, 249, 250);
        }

        .dashboard-title {
            text-align: center;
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 2rem;
            color: white;
        }

        .metrics-container {
            display: grid;
            grid-template-columns: 1fr 1.5fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .metrics-column {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .metric-box {
            background:rgb(16, 17, 18);
            border-radius: 15px;
            padding: 1rem;
            display: flex;
            flex-direction: column;
            margin-top: 2rem; /* Add margin to the top */
            margin-bottom: 2rem; /* Add margin to the bottom */
            width: 100%; /* Ensure all metric boxes have the same width */
        }

        .metric-label {
            color: rgba(248, 249, 250);
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
        }

        .metric-value {
            font-size: 1.875rem;
            font-weight: bold;
        }

        .profile-section {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .progress-circle-container {
            position: relative;
            width: 200px;
            height: 200px;
        }

        .progress-circle {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            position: relative;
            background: #1e3a8a;
        }

        .profile-image-container {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 100px;
            height: 100px;
            border-radius: 50%;
            border: 4px solid #22d3ee;
            overflow: hidden;
        }

        .profile-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .profile-info {
            text-align: center;
            margin-top: 1rem;
        }

        .employee-name {
            font-size: 1.25rem;
            font-weight: bold;
            margin-bottom: 0.25rem;
        }

        .department-name {
            color: #22d3ee;
            font-size: 0.875rem;
        }

        .employee-id {
            text-align: center;
            color: rgba(255, 255, 255, 0.6);
            margin-top: 1rem;
        }

        /* Animations */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .fade-in {
            animation: fadeIn 0.5s ease-out forwards;
            opacity: 0;
        }

        /* Progress Circle Animation */
        @keyframes progressCircle {
            from {
                stroke-dashoffset: 628;
            }
            to {
                stroke-dashoffset: var(--progress);
            }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .metrics-container {
                grid-template-columns: 1fr;
            }
            
            .employee-card {
                margin: 1rem;
                padding: 1rem;
            }
        }

        /* Additional styles for progress circle */
        .progress-ring {
            position: absolute;
            top: 0;
            left: 0;
            transform: rotate(-90deg);
        }

        .progress-ring__circle {
            transition: stroke-dashoffset 0.5s ease-out;
        }

        .comment-reaction-buttons {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }

        .modal-right {
            position: fixed;
            top: 10%;
            right: 0;
            width: 300px;
            height: 80%;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            padding: 20px;
            display: none;
            z-index: 1000;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
        }

        .modal-body {
            margin-top: 20px;
            overflow-y: auto;
            max-height: 70%;
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }

        .close-btn {
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
        }

        .comment-button {
            background-color: #1877F2;
            color: white;
            padding: 5px 10px;
            border: none;
            cursor: pointer;
            border-radius: 5px;
            margin-top: 10px;
        }

        .comment-display {
            background: #f1f1f1;
            padding: 10px;
            border-radius: 5px;
            margin-top: 10px;
        }

        .reaction-container {
            position: relative;
            display: inline-block;
        }

        .like-button {
            background-color: #1877F2;
            color: white;
            padding: 5px 10px;
            border: none;
            cursor: pointer;
            border-radius: 5px;
        }

        .reaction-menu {
            display: none;
            position: absolute;
            top: -40px;
            left: 0;
            background: white;
            border-radius: 10px;
            padding: 5px;
            box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
            z-index: 10;
            flex-direction: row;
            gap: 5px;
        }

        .reaction {
            cursor: pointer;
            font-size: 20px;
            transition: transform 0.2s;
        }

        .reaction:hover {
            transform: scale(1.3);
        }

        .comment-section {
            margin-top: 10px;
        }

        .comment-input {
            width: 100%;
            padding: 5px;
            border: 1px solid #ccc;
            border-radius: 5px;
            color: black; /* Change text color to black */
        }

        .comment-list {
            margin-top: 5px;
            max-height: 100px;
            overflow-y: auto;
        }

        .comment {
            background: transparent;
            padding: 5px;
            margin-top: 3px;
            border-radius: 5px;
            color: white;
        }
        .reaction-dropdown {
            display: none;
            position: absolute;
            background-color: white;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 5px;
            box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
            z-index: 1;
        }
        .reaction-dropdown.show {
            display: block;
        }
        .reaction-dropdown button {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 20px;
            margin: 5px;
        }
        .comment-input-container {
            display: flex;
            align-items: center;
            margin-top: 10px;
        }
        .comment-input {
            width: 100%;
            padding: 5px;
            border: 1px solid #ccc;
            border-radius: 5px;
            color: black;
        }
        .comment-list {
            margin-top: 5px;
            max-height: 100px;
            overflow-y: auto;
        }
        .comment {
            background: transparent;
            padding: 5px;
            margin-top: 3px;
            border-radius: 5px;
            color: white;
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.5);
        }
        .modal-content {
            background-color: rgba(51, 51, 51, 0.9);
            color: white;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 500px;
            border-radius: 10px;
        }
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }
        .close:hover,
        .close:focus {
            color: white;
            text-decoration: none;
            cursor: pointer;
        }
        .modal-label {
            display: block;
            margin-bottom: 10px;
            font-size: 18px;
        }


        .star-rating {
            display: inline-flex;
            align-items: center;
        }

        .star {
            font-size: 24px;
            color: gold;  /* Gold for filled stars */
            margin-right: 5px;
        }

        .star:not(.filled) {
            color: #ccc;  /* Light gray for empty stars */
        }

    </style>
</head>
<body class="sb-nav-fixed bg-black">
    <nav class="sb-topnav navbar navbar-expand navbar-dark border-bottom border-1 border-secondary bg-dark">
        <a class="navbar-brand ps-3 text-muted" href="../../employee/fieldworker/dashboard.php">Employee's Portal</a>
        <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle" href="#!"><i class="fas fa-bars text-light"></i></button>
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
                <form class="d-none d-md-inline-block form-inline">
                    <div class="input-group">
                        <input class="form-control" type="text" placeholder="Search for..." aria-label="Search for..." aria-describedby="btnNavbarSearch" />
                        <button class="btn btn-warning" id="btnNavbarSearch" type="button"><i class="fas fa-search"></i></button>
                    </div>
                </form>
            </div>
            
    </nav>
    <div id="layoutSidenav">
        <div id="layoutSidenav_nav">
            <nav class="sb-sidenav accordion bg-dark" id="sidenavAccordion">
                <div class="sb-sidenav-menu ">
                    <div class="nav">
                        <div class="sb-sidenav-menu-heading text-center text-muted">Your Profile</div>
                        <ul class="navbar-nav ms-auto ms-md-0 me-3 me-lg-4">
                            <li class="nav-item dropdown text">
                                <a class="nav-link dropdown-toggle text-light d-flex justify-content-center ms-4" id="navbarDropdown" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <img src="<?php echo (!empty($employeeInfo['pfp']) && $employeeInfo['pfp'] !== 'defaultpfp.png') 
                                        ? htmlspecialchars($employeeInfo['pfp']) 
                                        : '../../img/defaultpfp.jpg'; ?>" 
                                        class="rounded-circle border border-light" width="120" height="120" alt="Profile Picture" />
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                                    <li><a class="dropdown-item loading" href="../../employee/fieldworker/profile.php">Profile</a></li>
                                    <li><a class="dropdown-item" href="#!">Settings</a></li>
                                    <li><a class="dropdown-item" href="#!">Activity Log</a></li>
                                    <li><hr class="dropdown-divider" /></li>
                                    <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#logoutModal">Logout</a></li>
                                </ul>
                            </li>
                            <li class="nav-item text-light d-flex ms-3 flex-column align-items-center text-center">
                                <span class="big text-light mb-1">
                                    <?php
                                        if ($employeeInfo) {
                                        echo htmlspecialchars($employeeInfo['firstname'] . ' ' . $employeeInfo['middlename'] . ' ' . $employeeInfo['lastname']);
                                        } else {
                                        echo "User information not available.";
                                        }
                                    ?>
                                </span>      
                                <span class="big text-light">
                                    <?php
                                        if ($employeeInfo) {
                                        echo htmlspecialchars($employeeInfo['role']);
                                        } else {
                                        echo "User information not available.";
                                        }
                                    ?>
                                </span>
                            </li>
                        </ul>
                        <div class="sb-sidenav-menu-heading text-center text-muted border-top border-1 border-secondary mt-3">Employee Dashboard</div>
                        <a class="nav-link text-light loading" href="../../employee/fieldworker/dashboard.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>
                            Dashboard
                        </a>
                        <a class="nav-link collapsed text-light" href="#" data-bs-toggle="collapse" data-bs-target="#collapseTAD" aria-expanded="false" aria-controls="collapseTAD">
                            <div class="sb-nav-link-icon"><i class="fa fa-address-card"></i></div>
                            Time and Attendance
                            <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                        </a>
                        <div class="collapse" id="collapseTAD" aria-labelledby="headingOne" data-bs-parent="#sidenavAccordion">
                            <nav class="sb-sidenav-menu-nested nav">
                                <a class="nav-link text-light loading" href="../../employee/fieldworker/attendance.php">Attendance</a>
                            </nav>
                        </div>
                        <a class="nav-link collapsed text-light" href="#" data-bs-toggle="collapse" data-bs-target="#collapseLM" aria-expanded="false" aria-controls="collapseLM">
                            <div class="sb-nav-link-icon"><i class="fas fa-calendar-times"></i></div>
                            Leave Management
                            <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                        </a>
                        <div class="collapse" id="collapseLM" aria-labelledby="headingOne" data-bs-parent="#sidenavAccordion">
                            <nav class="sb-sidenav-menu-nested nav">
                            <a class="nav-link text-light loading" href="../../employee/fieldworker/leave_file.php">File Leave</a>
                            </nav>
                        </div>
                        <a class="nav-link collapsed text-light" href="#" data-bs-toggle="collapse" data-bs-target="#collapsePM" aria-expanded="false" aria-controls="collapsePM">
                            <div class="sb-nav-link-icon"><i class="fas fa-line-chart"></i></div>
                            Performance Management
                            <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                        </a>
                        <div class="collapse" id="collapsePM" aria-labelledby="headingOne" data-bs-parent="#sidenavAccordion">
                            <nav class="sb-sidenav-menu-nested nav">
                                <a class="nav-link text-light loading" href="../../employee/fieldworker/evaluation.php">Evaluation</a>
                            </nav>
                        </div>
                        <a class="nav-link collapsed text-light" href="#" data-bs-toggle="collapse" data-bs-target="#collapseSR" aria-expanded="false" aria-controls="collapseSR">
                            <div class="sb-nav-link-icon"><i class="fa fa-address-card"></i></div>
                            Social Recognition
                            <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                        </a>
                        <div class="collapse" id="collapseSR" aria-labelledby="headingOne" data-bs-parent="#sidenavAccordion">
                            <nav class="sb-sidenav-menu-nested nav">
                                <a class="nav-link text-light loading" href="../../employee/fieldworker/awardee.php">Awardee</a>
                            </nav>
                        </div>
                        <div class="sb-sidenav-menu-heading text-center text-muted border-top border-1 border-secondary">Account Management</div>
                    </div>
                </div>
                <div class="sb-sidenav-footer bg-black text-light border-top border-1 border-secondary">
                    <div class="small">Logged in as: <?php echo htmlspecialchars($employeeInfo['role']); ?></div>
                </div>
            </nav>
        </div>
    <div id="layoutSidenav_content">
        <main class="container-fluid position-relative bg-black">
            <div class="container" id="calendarContainer" 
                style="position: fixed; top: 9%; right: 0; z-index: 1050; 
                width: 700px; display: none;">
                <div class="row">
                    <div class="col-md-12">
                        <div id="calendar" class="p-2"></div>
                    </div>
                </div>
            </div>   
            <h1 class="mb-2 text-light ms-2">Outstanding Employees by Evaluation Criteria</h1>            
            <div class="container text-light">
                <!-- Top Employees for Different Criteria -->
                <?php getTopEmployeesByCriterion($conn, 'quality', 'Quality of Work', 1); ?>
                <?php getTopEmployeesByCriterion($conn, 'communication_skills', 'Communication Skills', 2); ?>
                <?php getTopEmployeesByCriterion($conn, 'teamwork', 'Teamwork', 3); ?>
                <?php getTopEmployeesByCriterion($conn, 'punctuality', 'Punctuality', 4); ?>
                <?php getTopEmployeesByCriterion($conn, 'initiative', 'Initiative', 5); ?>

                <!-- Navigation buttons for manually controlling the categories -->
                <div class="text-center">
                    <button class="btn btn-primary" onclick="showPreviousCategory()">Previous</button>
                    <button class="btn btn-primary" onclick="showNextCategory()">Next</button>
                </div>
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
                            <form action="../employee/logout.php" method="POST">
                                <button type="submit" class="btn btn-danger">Logout</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>  
        <footer class="py-4 bg-dark text-light mt-auto border-top border-secondary">
                <div class="container-fluid px-4">
                    <div class="d-flex align-items-center justify-content-between small">
                        <div class="text-muted">Copyright &copy; Your Website 2024</div>
                        <div>
                            <a href="#">Privacy Policy</a>
                            &middot;
                            <a href="#">Terms & Conditions</a>
                        </div>
                    </div>
                </div>
        </footer>
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

        let currentCategoryIndex = 1;
        const totalCategories = 5; // Total number of categories

        function showNextCategory() {
            // Hide the current category
            document.getElementById(`category-${currentCategoryIndex}`).style.display = 'none';

            // Update to the next category index, loop back to 1 if at the end
            currentCategoryIndex = (currentCategoryIndex % totalCategories) + 1;

            // Show the next category
            document.getElementById(`category-${currentCategoryIndex}`).style.display = 'block';
        }

        function showPreviousCategory() {
            // Hide the current category
            document.getElementById(`category-${currentCategoryIndex}`).style.display = 'none';

            // Update to the previous category index, loop back to totalCategories if at the start
            currentCategoryIndex = (currentCategoryIndex - 1) || totalCategories;

            // Show the previous category
            document.getElementById(`category-${currentCategoryIndex}`).style.display = 'block';
        }

        // Start the slideshow, show the first category immediately
        window.onload = function() {
            // Show the first category immediately
            document.getElementById(`category-1`).style.display = 'block';
            
            // Start the slideshow after showing the first category
            setInterval(showNextCategory, 30000); // Change every 8 seconds
        };

        document.addEventListener('DOMContentLoaded', function() {
            // Initialize progress circles
            const circles = document.querySelectorAll('.progress-circle');
            circles.forEach(circle => {
                const progress = circle.getAttribute('data-progress');
                const circumference = 2 * Math.PI * 90; // for r=90
                const strokeDashoffset = circumference - (progress / 100) * circumference;
                
                // Create SVG element
                const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
                svg.setAttribute('class', 'progress-ring');
                svg.setAttribute('width', '200');
                svg.setAttribute('height', '200');

                // Create a circle for the background with the color rgb(16, 17, 18)
                const backgroundCircle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
                backgroundCircle.setAttribute('cx', '100');  // Center X coordinate
                backgroundCircle.setAttribute('cy', '100');  // Center Y coordinate
                backgroundCircle.setAttribute('r', '90');    // Radius
                backgroundCircle.setAttribute('fill', 'rgb(16, 17, 18)');  // Background color

                // Append the background circle to the SVG
                svg.appendChild(backgroundCircle);

                // Now you can add other elements (e.g., a progress circle) on top of the background
                // Create a progress circle (example)
                const progressCircle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
                progressCircle.setAttribute('cx', '100');
                progressCircle.setAttribute('cy', '100');
                progressCircle.setAttribute('r', '90');
                progressCircle.setAttribute('fill', 'none');
                progressCircle.setAttribute('stroke', 'rgb(16, 17, 18)');
                progressCircle.setAttribute('stroke-width', '20');

                // Append the progress circle
                svg.appendChild(progressCircle);

                // Add the SVG to the document (e.g., append it to the body or a specific element)
                document.body.appendChild(svg);

                
                const circleElement = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
                circleElement.setAttribute('class', 'progress-ring__circle');
                circleElement.setAttribute('stroke', '#22d3ee');
                circleElement.setAttribute('stroke-width', '4');
                circleElement.setAttribute('fill', 'transparent');
                circleElement.setAttribute('r', '90');
                circleElement.setAttribute('cx', '100');
                circleElement.setAttribute('cy', '100');
                circleElement.style.strokeDasharray = `${circumference} ${circumference}`;
                circleElement.style.strokeDashoffset = strokeDashoffset;
                
                svg.appendChild(circleElement);
                circle.insertBefore(svg, circle.firstChild);
            });
        });

        function showReactions(button) {
            const menu = button.nextElementSibling;
            menu.classList.toggle('show');
        }

        function selectReaction(reaction) {
            const reactionModal = document.getElementById('reactionModal');
            reactionModal.querySelector('.modal-body').textContent = "You reacted with: " + reaction;
            reactionModal.style.display = 'block';
            document.getElementById("reaction-menu").classList.remove("show");
        }

        // Close the reaction menu when clicking outside
        window.onclick = function(event) {
            if (!event.target.matches('.react-btn')) {
                var dropdowns = document.getElementsByClassName("reaction-dropdown");
                for (var i = 0; i < dropdowns.length; i++) {
                    var openDropdown = dropdowns[i];
                    if (openDropdown.classList.contains('show')) {
                        openDropdown.classList.remove('show');
                    }
                }
            }
        }

        function postComment(event, input) {
            if (event.key === 'Enter' && input.value.trim() !== '') {
                let commentList = document.querySelector('.modal-body .comment-list');
                let newComment = document.createElement('p');
                newComment.textContent = input.value;
                newComment.classList.add('comment');
                commentList.appendChild(newComment);

                input.value = '';

                // Optional: Send comment to the backend using AJAX
                // fetch('save_comment.php', {
                //     method: 'POST',
                //     body: JSON.stringify({ comment: input.value }),
                //     headers: { 'Content-Type': 'application/json' }
                // });
            }
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        function openCommentModal() {
            document.getElementById('commentModal').style.display = 'block';
        }


        function getStarRating($scorePercentage) {
            // Max stars are 6, calculate the number of stars based on percentage
            return round(($scorePercentage / 100) * 6);
        }

    </script>

    <!-- Comment Modal -->
    <div id="commentModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('commentModal')">&times;</span>
            <input type="text" class="comment-input" placeholder="Write your comment..." onkeypress="postComment(event, this)">
            <div class="modal-body">
                <div class="comment-list"></div>
            </div>
        </div>
    </div>

    <!-- Reaction Modal -->
    <div id="reactionModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('reactionModal')">&times;</span>
            <div class="modal-body"></div>
        </div>
    </div>

    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../js/employee.js"></script>
</body>
</html>

<?php
// Close the database connection
$conn->close();
?>