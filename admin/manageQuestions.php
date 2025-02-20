<?php
session_start();

if (!isset($_SESSION['a_id'])) {
    header("Location: ../admin/adminlogin.php");
    exit();
}

include '../db/db_conn.php';

// Handle adding, editing, or deleting questions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_question'])) {
        $category = $_POST['category'];
        $question = $_POST['question'];

        $sql = "INSERT INTO evaluation_questions (category, question) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $category, $question);
        $stmt->execute();
        $stmt->close();
    }

    if (isset($_POST['edit_question'])) {
        $id = $_POST['id'];
        $new_question = $_POST['new_question'];

        $sql = "UPDATE evaluation_questions SET question = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $new_question, $id);
        $stmt->execute();
        $stmt->close();
    }

    if (isset($_POST['delete_question'])) {
        $id = $_POST['id'];

        $sql = "DELETE FROM evaluation_questions WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
    }
}

// Fetch all questions and categories
$sql = "SELECT * FROM evaluation_questions ORDER BY category, id";
$result = $conn->query($sql);

$questions = [];
while ($row = $result->fetch_assoc()) {
    $questions[] = $row;
}

// Close the database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Evaluation Questions</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="../css/styles.css" rel="stylesheet">
</head>
<body class="bg-dark text-light">
    <div class="container mt-5">
        <h2 class="text-center text-primary mb-4">Manage Evaluation Questions</h2>

        <!-- Add New Question Form -->
        <div class="mb-4">
            <h4>Add New Question</h4>
            <form method="POST" action="../admin/manageQuestions.php">
                <div class="form-group">
                    <label for="category">Category:</label>
                    <select name="category" class="form-control  form-select" required>
                        <option value="Quality of Work">Quality of Work</option>
                        <option value="Communication Skills">Communication Skills</option>
                        <option value="Teamwork">Teamwork</option>
                        <option value="Punctuality">Punctuality</option>
                        <option value="Initiative">Initiative</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="question">Question:</label>
                    <textarea name="question" class="form-control" rows="3" required></textarea>
                </div>
                <button type="submit" name="add_question" class="btn btn-primary">Add Question</button>
            </form>
        </div>

        <!-- Questions Table -->
        <h4>Current Questions</h4>
        <table class="table table-hover text-light">
            <thead class="thead-dark">
                <tr>
                    <th>Category</th>
                    <th>Question</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($questions)): ?>
                    <?php foreach ($questions as $question): ?>
                        <tr>
                            <td class="text-light"><?php echo htmlspecialchars($question['category']); ?></td>
                            <td class="text-light"><?php echo htmlspecialchars($question['question']); ?></td>
                            <td>
                                <!-- Edit Question -->
                                <button class="btn btn-warning" data-toggle="modal" data-target="#editQuestionModal" 
                                    data-qid="<?php echo $question['id']; ?>"
                                    data-question="<?php echo htmlspecialchars($question['question']); ?>">Edit</button>
                                
                                <!-- Delete Question -->
                                <form method="POST" action="../admin/manageQuestions.php" class="d-inline">
                                    <input type="hidden" name="id" value="<?php echo $question['id']; ?>">
                                    <button type="submit" name="delete_question" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this question?')">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td class="text-light text-center" colspan="3">No questions found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Edit Question Modal -->
    <div class="modal fade" id="editQuestionModal" tabindex="-1" role="dialog" aria-labelledby="editQuestionModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="editQuestionModalLabel">Edit Question</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="manage_questions.php">
                        <input type="hidden" name="id" id="editQId">
                        <div class="form-group">
                            <label for="new_question">New Question:</label>
                            <textarea name="new_question" id="editNewQuestion" class="form-control" rows="3" required></textarea>
                        </div>
                        <button type="submit" name="edit_question" class="btn btn-primary">Save Changes</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Populate the edit modal with question data
        $('#editQuestionModal').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget);
            var qid = button.data('qid');
            var question = button.data('question');
            
            var modal = $(this);
            modal.find('#editQId').val(qid);
            modal.find('#editNewQuestion').val(question);
        });
    </script>
</body>
</html>
