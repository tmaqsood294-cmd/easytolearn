<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once '../config/db.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../login.php");
    exit;
}

$quiz_id = isset($_GET['quiz_id']) ? (int)$_GET['quiz_id'] : 0;
$message = "";

// Database Query to fetch quiz, class details with JOIN
$quiz_stmt = $conn->prepare("
    SELECT q.*, c.class_name, c.section 
    FROM quizzes q 
    LEFT JOIN classes c ON q.class_id = c.id 
    WHERE q.id = ?
");
$quiz_stmt->bind_param("i", $quiz_id);
$quiz_stmt->execute();
$quiz_result = $quiz_stmt->get_result();
$quiz = $quiz_result->fetch_assoc();

if (!$quiz) {
    die("Quiz not found!");
}

// Handle Question Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_question'])) {
    $question_text = trim($_POST['question_text']);
    $option_a = trim($_POST['option_a']);
    $option_b = trim($_POST['option_b']);
    $option_c = trim($_POST['option_c']);
    $option_d = trim($_POST['option_d']);
    $correct_option = $_POST['correct_option'];

    if (!empty($question_text) && !empty($option_a) && !empty($option_b) && !empty($option_c) && !empty($option_d) && !empty($correct_option)) {
        $ins = $conn->prepare("INSERT INTO quiz_questions (quiz_id, question_text, option_a, option_b, option_c, option_d, correct_option) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $ins->bind_param("issssss", $quiz_id, $question_text, $option_a, $option_b, $option_c, $option_d, $correct_option);
        
        if ($ins->execute()) {
            $message = "<div class='alert alert-success'>Question added successfully!</div>";
        } else {
            $message = "<div class='alert alert-danger'>Error adding question.</div>";
        }
        $ins->close();
    }
}

// Fetch already added questions
$q_stmt = $conn->prepare("SELECT * FROM quiz_questions WHERE quiz_id = ?");
$q_stmt->bind_param("i", $quiz_id);
$q_stmt->execute();
$questions_list = $q_stmt->get_result();

// Clean class name logic: Agar string mein pehle se "Class " word ho toh use dynamically remove kar dein
$clean_class = $quiz['class_name'];
if (stripos($clean_class, 'Class ') === 0) {
    $clean_class = trim(substr($clean_class, 6)); // "Class 9" ban jayega sirf "9"
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Questions - <?php echo htmlspecialchars($quiz['quiz_title']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container my-5">
    <a href="manage-quizzes.php" class="btn btn-secondary mb-3">← Back to Quizzes</a>
    <h2>Add Questions for: <span class="text-success"><?php echo htmlspecialchars($quiz['quiz_title']); ?></span></h2>
    
    <p class="text-muted">
        Subject: <?php echo htmlspecialchars($quiz['subject_name']); ?> | 
        Class: <?php echo htmlspecialchars($clean_class) . "(" . htmlspecialchars($quiz['section']) . ")"; ?>
    </p>

    <?php echo $message; ?>

    <div class="row">
        <div class="col-md-5">
            <div class="card p-4 border-0 shadow-sm">
                <h5 class="fw-bold mb-3">New Question</h5>
                <form action="" method="POST">
                    <div class="mb-3">
                        <label class="form-label">Question Text</label>
                        <textarea name="question_text" class="form-control" rows="2" required></textarea>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Option A</label>
                        <input type="text" name="option_a" class="form-control" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Option B</label>
                        <input type="text" name="option_b" class="form-control" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Option C</label>
                        <input type="text" name="option_c" class="form-control" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Option D</label>
                        <input type="text" name="option_d" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Correct Option</label>
                        <select name="correct_option" class="form-select" required>
                            <option value="A">A</option>
                            <option value="B">B</option>
                            <option value="C">C</option>
                            <option value="D">D</option>
                        </select>
                    </div>
                    <button type="submit" name="submit_question" class="btn btn-success w-100">Save Question</button>
                </form>
            </div>
        </div>

        <div class="col-md-7">
            <div class="card p-4 border-0 shadow-sm">
                <h5 class="fw-bold mb-3">Questions Added So Far</h5>
                <?php if($questions_list->num_rows > 0): $i=1; while($q = $questions_list->fetch_assoc()): ?>
                    <div class="p-3 mb-3 bg-white border rounded">
                        <strong>Q<?php echo $i++; ?>: <?php echo htmlspecialchars($q['question_text']); ?></strong>
                        <div class="small text-muted mt-1">
                            A: <?php echo htmlspecialchars($q['option_a']); ?> | 
                            B: <?php echo htmlspecialchars($q['option_b']); ?> | 
                            C: <?php echo htmlspecialchars($q['option_c']); ?> | 
                            D: <?php echo htmlspecialchars($q['option_d']); ?>
                        </div>
                        <div class="text-success small fw-bold mt-1">Correct: Option <?php echo $q['correct_option']; ?></div>
                    </div>
                <?php endwhile; else: ?>
                    <p class="text-muted">No questions added yet for this quiz.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
</body>
</html>