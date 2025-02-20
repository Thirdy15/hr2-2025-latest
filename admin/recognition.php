<?php
session_start();

include '../db/db_conn.php';

// Add employee to database
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $name = $_POST['name'];
    $recognition_type = $_POST['recognition_type'];

    // Insert employee data into the database
    $stmt = $conn->prepare("INSERT INTO awardee (name, recognition_type) VALUES (?, ?)");
    $stmt->bind_param("ss", $name, $recognition_type);

    if ($stmt->execute()) {
        // Redirect after successful submission to avoid resubmission on page refresh
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
}

// Fetch employee recognition data from the database
function fetchEmployeeData($conn) {
    $sql = "SELECT id, name, recognition_type FROM awardee";
    $result = $conn->query($sql);

    $employees = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $employees[] = $row;
        }
    }

    return $employees;
}

$employees = fetchEmployeeData($conn);

// Close connection
$conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../img/logo.png">
    <title>Social Recognition</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body class="d-flex flex-column min-vh-100 bg-black">

    <!-- Header Section -->
    <header class="text-white text-center py-3">
        <h1>Social Recognition</h1>
    </header>

    <div id="layoutSidenav_content" class="flex-grow-1">
        <main>
            <div class="page-wrapper container mt-4 mb-4 bg-dark">
                <div class="row justify-content-center">
                    <div class="col-md-8">
                        <h2 class="text-center mb-4">Add Employee Recognition</h2>

                        <!-- Employee Form -->
                        <form id="employee-form" class="mb-4" action="" method="POST">
                            <div class="form-group">
                                <label for="employee-name" class="text-light mb-2">Name:</label>
                                <input type="text" id="employee-name" name="name" class="form-control" required>
                            </div>

                            <div class="form-group mt-4">
                                <label for="recognition-type" class="text-light mb-2">Recognition Type:</label>
                                <select id="recognition-type" name="recognition_type" class="form-control" required>
                                    <option value="">Select Recognition</option>
                                    <option value="Quality of Work">Quality of Work</option>
                                    <option value="Communication Skills">Communication Skills</option>
                                    <option value="Teamwork">Teamwork</option>
                                    <option value="Punctuality">Punctuality</option>
                                    <option value="Initiative">Initiative</option>
                                    <option value="Most Helpful">Most Helpful</option>
                                </select>
                            </div>
                            
                            <button type="submit" class="btn btn-primary btn-block mt-4">Add Employee</button>
                        </form>

                        <!-- Employee Cards Slideshow -->
                        <div id="employee-container" class="employee-container">
                            <div id="employee-cards-wrapper" class="d-flex flex-wrap justify-content-center">
                                <?php if (!empty($employees)): ?>
                                    <?php foreach ($employees as $employee): ?>
                                        <div class="card mb-4 mx-2" style="width: 18rem;">
                                            <div class="card-body text-center bg-black border border-light text-light">
                                                <h5 class="card-title"><?php echo htmlspecialchars($employee['name']); ?></h5>
                                                <p class="card-text">Recognition Type: <strong><?php echo htmlspecialchars($employee['recognition_type']); ?></strong></p>
                                                <button class="btn btn-info download-pdf" data-id="<?php echo $employee['id']; ?>" data-name="<?php echo htmlspecialchars($employee['name']); ?>" data-recognition="<?php echo htmlspecialchars($employee['recognition_type']); ?>">Download as PDF</button>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p class="text-center text-light">No employees to display</p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Navigation buttons for sliding -->
                        <div class="text-center mt-4">
                            <button id="prev-btn" class="btn btn-primary mx-5">Previous</button>
                            <button id="next-btn" class="btn btn-primary mx-5">Next</button>
                        </div>

                        <!-- Downloaded Certificate Counter -->
                        <div class="text-center mt-4 text-light">
                            <p id="downloaded-counter">Employees who downloaded their certificate: <span id="download-count">0</span></p>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white text-center py-3">
        <p>© 2024 Social Recognition System</p>
    </footer>

    <!-- jsPDF for PDF generation -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const { jsPDF } = window.jspdf;
            const employeeCardsWrapper = document.getElementById('employee-cards-wrapper');
            const employeeCards = document.querySelectorAll('.employee-card');
            const totalEmployees = employeeCards.length;
            const cardsPerPage = 3; // Show 3 cards at a time
            let currentIndex = 0;
            let downloadCount = 0;

            // Function to generate PDF
            function generatePDF(name, recognitionType) {
                const pdf = new jsPDF('landscape', 'mm', 'a4');
                pdf.setFont('arial');
                pdf.setDrawColor(0, 0, 0);
                pdf.setLineWidth(2);
                pdf.rect(15, 15, 270, 180);

                pdf.setFont('arial', 'bold');
                pdf.setFontSize(30);
                pdf.text('Certificate of Achievement', 145, 40, { align: 'center' });

                pdf.setFontSize(18);
                pdf.setFont('arial', 'normal');
                pdf.text('This certificate is proudly presented to', 145, 60, { align: 'center' });

                pdf.setFontSize(30); 
                pdf.text(name, 145, 80, { align: 'center' });

                pdf.setFontSize(16);
                pdf.text(`For Outstanding Achievement in ${recognitionType}`, 145, 100, { align: 'center' });

                const customComment = "Your hard work and dedication are truly appreciated. Thank you for your contributions!";
                pdf.setFontSize(12);
                const splitComment = pdf.splitTextToSize(customComment, 250);
                pdf.text(splitComment, 145, 120, { align: 'center' });

                pdf.setFontSize(12);
                pdf.text('__________________', 45, 160);
                pdf.text('__________________', 220, 160);
                pdf.text('Signature', 45, 165);
                pdf.text('Signature', 220, 165);

                const currentDate = new Date().toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
                pdf.text(`Date: ${currentDate}`, 145, 175, { align: 'center' });

                pdf.save(`${name}_certificate.pdf`);
            }

            // Add event listener to download PDF buttons
            document.querySelectorAll('.download-pdf').forEach((button) => {
                button.addEventListener('click', () => {
                    const name = button.getAttribute('data-name');
                    const recognitionType = button.getAttribute('data-recognition');
                    generatePDF(name, recognitionType);

                    downloadCount++;
                    document.getElementById('download-count').textContent = downloadCount;
                });
            });

            // Function to show the current set of employee cards
            function showCards() {
                const offset = -currentIndex * (employeeCards[0].offsetWidth + 20) * cardsPerPage;
                employeeCardsWrapper.style.transform = `translateX(${offset}px)`;
            }

            // Event listeners for "Previous" and "Next" buttons
            document.getElementById('next-btn').addEventListener('click', () => {
                if (currentIndex < Math.ceil(totalEmployees / cardsPerPage) - 1) {
                    currentIndex++;
                } else {
                    currentIndex = 0; // Loop back to the start
                }
                showCards();
            });

            document.getElementById('prev-btn').addEventListener('click', () => {
                if (currentIndex > 0) {
                    currentIndex--;
                } else {
                    currentIndex = Math.ceil(totalEmployees / cardsPerPage) - 1; // Loop back to the last set
                }
                showCards();
            });

            // Initial call to show the first set of employee cards
            showCards();
        });
    </script>
</body>
</html>
