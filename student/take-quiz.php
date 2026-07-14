<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/db.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    die("Access Denied.");
}

$student_id = intval($_SESSION['user_id']);
$quiz_id = isset($_GET['quiz_id']) ? intval($_GET['quiz_id']) : 0;

// Verify if the quiz exists
$quiz_query = $conn->query("SELECT * FROM quizzes WHERE id = $quiz_id");
if (!$quiz_query || $quiz_query->num_rows === 0) {
    die("Quiz not found.");
}
$quiz = $quiz_query->fetch_assoc();

// Check if already attempted to prevent multiple submissions
$check = $conn->query("SELECT * FROM quiz_results WHERE student_id = $student_id AND quiz_id = $quiz_id");
if ($check && $check->num_rows > 0) {
    die("You have already attempted this quiz.");
}

// Handle Quiz Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_quiz'])) {
    $total_questions = intval($quiz['total_questions']);
    
    // Yahan hum simulation ke taur par default score auto-calculate kar rahe hain
    // Aap isko customizable questions ke mutabik badal sakte hain
    $random_correct = rand(min(2, $total_questions), $total_questions); 
    
    $insert = $conn->query("INSERT INTO quiz_results (student_id, quiz_id, score, total_score) 
                            VALUES ($student_id, $quiz_id, $random_correct, $total_questions)");
    
    if ($insert) {
        echo "<script>alert('Quiz submitted successfully! Your Score: $random_correct/$total_questions'); window.location.href='dashboard.php';</script>";
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Take Quiz | School SMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-5">
    <div class="card shadow-sm border-0 max-width-600 mx-auto p-4" style="max-width: 600px;">
        <span class="badge bg-warning text-dark mb-2"><?php echo htmlspecialchars($quiz['subject_name']); ?></span>
        <h3 class="fw-bold"><?php echo htmlspecialchars($quiz['quiz_title']); ?></h3>
        <p class="text-muted">Total Questions: <?php echo $quiz['total_questions']; ?> MCQs | Time Allowed: 15 Mins</p>
        <hr>
        
        <form method="POST">
            <!-- Mock Questions for interface structure -->
            <?php for($i = 1; $i <= $quiz['total_questions']; $i++): ?>
                <div class="mb-4">
                    <p class="fw-semibold mb-2">Q<?php echo $i; ?>: Identify the correct statement or system logic query?</p>
                    <div class="form-check mb-1">
                        <input class="form-check-input" type="radio" name="q<?php echo $i; ?>" id="q<?php echo $i; ?>a" required>
                        <label class="form-check-input-label" for="q<?php echo $i; ?>a">Option Statement Alpha</label>
                    </div>
                    <div class="form-check mb-1">
                        <input class="form-check-input" type="radio" name="q<?php echo $i; ?>" id="q<?php echo $i; ?>b">
                        <label class="form-check-input-label" for="q<?php echo $i; ?>b">Option Statement Beta</label>
                    </div>
                </div>
            <?php endfor; ?>
            
            <button type="submit" name="submit_quiz" class="btn btn-primary w-100 fw-bold py-2">Submit Quiz Answers</button>
        </form>
    </div>
</div>

</body>
</html>