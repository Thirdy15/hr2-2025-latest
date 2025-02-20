<?php

date_default_timezone_set('Asia/Manila'); // Set the time zone to Philippine time

include 'db/db_conn.php'; // Ensure correct file path

// Get today's date in the format 'Y-m-d'
$today = date('Y-m-d');

// Fetch attendance data for the current day along with employee details
$query = "
    SELECT 
        a.e_id, 
        er.firstname, 
        er.lastname, 
        a.time_in, 
        a.time_out, 
        a.attendance_date, 
        a.status 
    FROM attendance_logs AS a
    LEFT JOIN employee_register AS er ON a.e_id = er.e_id
    WHERE a.attendance_date = ?
";

// Prepare the statement to bind the date parameter
$stmt = $conn->prepare($query);
$stmt->bind_param('s', $today); // Bind today's date to the query
$stmt->execute();
$result = $stmt->get_result();

// Store data in an array for the table
$attendanceLogs = [];
while ($row = $result->fetch_assoc()) {
    $attendanceLogs[] = $row;
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Facial Recognition Attendance</title>
  <script src="/HR2/face-api.js-master/dist/face-api.min.js"></script>
  <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet">
  <link href="css/styles.css" rel="stylesheet"/>
  <style>
    #videoInput {
        border-radius: 8px;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
    }
    .start-button {
        margin-bottom: 20px;
    }
  </style>
</head>
<body class="bg-light">
    <div class="container-fluid">
        <div class="row">
            <div class="card bg-light mt-5">
                <div class="col-xl-12 text-dark">
                    <h1 class="mb-4 text-center">Attendance Log</h1>
                    <div class="d-flex justify-content-around">
                        <div class="row">
                            <div class="col-md-6 mb-4 d-flex justify-content-start mt-5">
                                <div class="d-flex flex-column align-items-center">
                                    <button id="startBtn" class="btn btn-primary start-button">Start Facial Recognition</button>
                                    <div style="width:540px; height:380px;">
                                        <video id="videoInput" class="img-fluid bg-dark" width="640" height="480" autoplay muted></video>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card bg-dark text-light">
                            <div class="card-header border-bottom border-1 border-warning">
                                <i class="fas fa-table me-1"></i>
                                Employee Accounts
                            </div>
                            <div class="card-body">
                                <table id="datatablesSimple" class="table text-light text-center">
                                    <thead class="thead-dark">
                                        <tr>
                                            <th>Employee ID</th>
                                            <th>Name</th>
                                            <th>Time-In</th>
                                            <th>Time-Out</th>
                                            <th>Attendance Date</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($attendanceLogs)): ?>
                                            <tr>
                                                <td colspan="6" class="text-center">No records available for today</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($attendanceLogs as $row): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars(trim($row['e_id'] ?? 'N/A')); ?></td>
                                                <td><?php echo htmlspecialchars(trim($row['firstname'] . ' ' . $row['lastname'] ?? 'N/A')); ?></td>
                                                <td><?php echo !empty($row['time_in']) ? date('h:i A', strtotime($row['time_in'])) : 'N/A'; ?></td>
                                                <td><?php echo !empty($row['time_out']) ? date('h:i A', strtotime($row['time_out'])) : 'N/A'; ?></td>
                                                <td><?php echo htmlspecialchars(trim(date("F j, Y", strtotime($row['attendance_date'])))); ?></td>
                                                <td><?php echo htmlspecialchars(trim($row['status'] ?? 'N/A')); ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="attendanceModal" tabindex="-1" aria-labelledby="attendanceModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="attendanceModalLabel">Attendance Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="modalMessage">
                    <!-- Success or error message will be displayed here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
<script>
let employeeData = []; // To store employee data with face descriptors

// Initialize when window is loaded
window.onload = async function() {
  try {
    await loadModels();
    await fetchEmployeeData(); // Fetch employee data from the database
  } catch (error) {
    console.error("Error during onload:", error);
    alert("Error during initial setup. Check console for details.");
  }
};

// Fetch employee data from the database (ID and face image)
async function fetchEmployeeData() {
    try {
        console.log("Fetching employee data...");
        const response = await fetch('/HR2/fetch_employee_data.php'); // Fetch from PHP backend
        const data = await response.json();
        console.log("Employee data received:", data);
        
        // Clear the employeeData array to avoid duplicate entries on each reload
        employeeData = [];

        for (const employee of data) {
            if (employee.face_descriptor) {
                let descriptor = employee.face_descriptor;

                // Check if the descriptor is a string and needs conversion to a Float32Array
                if (typeof descriptor === 'string') {
                    descriptor = JSON.parse(descriptor);
                }

                // Check if descriptor is valid
                if (Array.isArray(descriptor) && descriptor.length === 128) {
                    employeeData.push({
                        e_id: employee.e_id,
                        descriptor: new Float32Array(descriptor) // Convert to Float32Array for matching
                    });
                } else {
                    console.error(`Invalid face descriptor for employee ${employee.e_id}`);
                }
            } else {
                console.error(`No face descriptor found for employee ${employee.e_id}`);
            }
        }

        console.log("Employee data loaded.");
    } catch (error) {
        console.error("Error fetching employee data:", error);
        alert("Error fetching employee data. Check console for details.");
    }
}

async function loadModels() {
    try {
        console.log("Loading face-api models...");
        await faceapi.nets.ssdMobilenetv1.loadFromUri('/HR2/face-api.js-master/weights/');
        await faceapi.nets.faceLandmark68Net.loadFromUri('/HR2/face-api.js-master/weights/');
        await faceapi.nets.faceRecognitionNet.loadFromUri('/HR2/face-api.js-master/weights/');
        console.log("Models loaded successfully.");
    } catch (error) {
        console.error("Error loading models:", error);
        alert("Error loading face-api models. Check console for details.");
    }
}

// Start the video and face recognition process when the Start button is clicked
document.getElementById('startBtn').addEventListener('click', async () => {
    await startVideo(); // Start the video stream
    setInterval(detectFace, 5000); // Start detecting faces every few seconds
});

async function startVideo() {
    const video = document.getElementById('videoInput');
    try {
        const stream = await navigator.mediaDevices.getUserMedia({
            video: { width: 640, height: 480, frameRate: { ideal: 10, max: 15 } }
        });
        console.log("Video stream started successfully.");
        
        video.srcObject = stream;

        video.onloadedmetadata = () => {
            video.play();
        };

        video.onerror = (error) => {
            console.error("Error with video playback:", error);
            alert("Error with video playback. Please check your camera.");
        };
    } catch (err) {
        console.error("Error accessing webcam:", err.name, err.message);
        
        if (err.name === 'NotReadableError') {
            alert("The webcam is already in use by another application.");
        } else if (err.name === 'NotAllowedError') {
            alert("Permission to access the camera has been denied.");
        } else if (err.name === 'OverconstrainedError') {
            alert("The webcam does not meet the specified constraints.");
        } else {
            alert("Error accessing webcam. Please check your browser settings.");
        }
    }
}

// Detect and match the face from the video stream with stored employee data
async function detectFace() {
    const video = document.getElementById('videoInput');
    const detections = await faceapi.detectAllFaces(video)
        .withFaceLandmarks()
        .withFaceDescriptors();

    if (detections.length === 0) {
        console.log("No face detected.");
        return;
    }

    let faceMatched = false; // Flag to track if a face is matched

    for (let detection of detections) {
        // Check for the best match based on descriptors
        const bestMatch = findBestMatch(detection.descriptor);
        if (bestMatch) {
            // If we find a match, log attendance via PHP backend
            logAttendance(bestMatch);
            faceMatched = true; // Mark that a face was matched
        }
    }

    // If no match was found, show alert for unknown person
    if (!faceMatched) {
        alert("Unknown person detected!");
    }
}

// Find the best match for the detected face
function findBestMatch(descriptor) {
    let bestMatch = null;
    let bestMatchDistance = Number.MAX_VALUE;

    for (let i = 0; i < employeeData.length; i++) {
        const distance = faceapi.euclideanDistance(descriptor, employeeData[i].descriptor);
        if (distance < bestMatchDistance) {
            bestMatch = employeeData[i];
            bestMatchDistance = distance;
        }
    }

    // If the best match is less than 0.6 distance, it is considered a match
    return bestMatchDistance < 0.6 ? bestMatch : null;
}

async function logAttendance(employee) {
    const formData = new FormData();
    formData.append('employeeId', employee.e_id);

    try {
        const response = await fetch('/HR2/attendanceLog.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();
        alert(result.message); // Display success or failure message from the PHP response
    } catch (error) {
        console.error("Error logging attendance:", error);
        alert("Error logging attendance. Check console for details.");
    }
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/umd/simple-datatables.min.js" crossorigin="anonymous"></script>
<script src="js/datatables-simple-demo.js"></script>
</body>
</html>
