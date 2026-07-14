<?php
require_once '../config/db.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../login.php");
    exit;
}

$teacher_user_id = intval($_SESSION['user_id']);
$message = "";

// Handle Video Upload/Link Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_video'])) {
    $video_title = $conn->real_escape_string($_POST['video_title']);
    $class_id = intval($_POST['class_id']);
    $video_url = $conn->real_escape_string($_POST['video_url']);

    if (!empty($video_title) && $class_id > 0 && !empty($video_url)) {
        // Simple validation to check if it's a URL
        if (filter_var($video_url, FILTER_VALIDATE_URL)) {
            $insert_query = "INSERT INTO video_lectures (teacher_id, class_id, video_title, video_url) VALUES ($teacher_user_id, $class_id, '$video_title', '$video_url')";
            if ($conn->query($insert_query)) {
                $message = "<div class='alert alert-success'>Video Lecture link successfully shared!</div>";
            } else {
                $message = "<div class='alert alert-danger'>Error: " . $conn->error . "</div>";
            }
        } else {
            $message = "<div class='alert alert-warning'>Please enter a valid URL link.</div>";
        }
    } else {
        $message = "<div class='alert alert-warning'>Please fill all fields.</div>";
    }
}

// Fetch Teacher's Assigned Classes for the Dropdown (Resetting object pointer by running query again)
$classes_query = "SELECT DISTINCT c.id, c.class_name, c.section 
                  FROM teacher_assignments ta 
                  INNER JOIN classes c ON ta.class_id = c.id 
                  WHERE ta.teacher_id = $teacher_user_id";
$classes_result = $conn->query($classes_query);

// Fetch Existing Videos
$videos_query = "SELECT vl.*, c.class_name, c.section 
                 FROM video_lectures vl 
                 INNER JOIN classes c ON vl.class_id = c.id 
                 WHERE vl.teacher_id = $teacher_user_id 
                 ORDER BY vl.id DESC";
$videos_result = $conn->query($videos_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Video Lectures | Teacher Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-success">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold" href="dashboard.php"><i class="fas fa-chalkboard-teacher me-2"></i>Teacher Portal</a>
    <div class="collapse navbar-collapse">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link text-white" href="dashboard.php">Dashboard</a></li>
        <li class="nav-item"><a class="nav-link text-white" href="manage-quizzes.php">Manage Quizzes</a></li>
        <li class="nav-item"><a class="nav-link text-white active" href="video-lectures.php">Video Lectures</a></li>
      </ul>
    </div>
  </div>
</nav>

<div class="container my-5">
    <h2><i class="fas fa-video text-danger me-2"></i>Share Video Lectures</h2>
    <p class="text-muted">Upload or link external video lectures (YouTube, Drive, etc.) for your students.</p>
    
    <?php echo $message; ?>

    <div class="row g-4">
        <!-- Add Video Form -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-4">
                <h5 class="fw-bold text-danger mb-3">Add Video Link</h5>
                <form action="" method="POST">
                    <div class="mb-3">
                        <label class="form-label">Lecture Title</label>
                        <input type="text" name="video_title" class="form-control" placeholder="e.g., Introduction to Algebra" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Select Class</label>
                        <select name="class_id" class="form-select" required>
                            <option value="">-- Choose Class --</option>
                            <?php while($row = $classes_result->fetch_assoc()): ?>
                                <option value="<?php echo $row['id']; ?>"><?php echo htmlspecialchars($row['class_name'] . " (" . $row['section'] . ")"); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Video URL (YouTube/Drive)</label>
                        <input type="url" name="video_url" class="form-control" placeholder="https://www.youtube.com/watch?v=..." required>
                    </div>
                    <button type="submit" name="add_video" class="btn btn-danger w-100"><i class="fas fa-cloud-upload-alt me-2"></i>Share Lecture</button>
                </form>
            </div>
        </div>

        <!-- Videos List -->
        <div class="col-md-8">
            <div class="card border-0 shadow-sm p-4">
                <h5 class="fw-bold text-dark mb-3">Shared Lectures</h5>
                <div class="table-responsive">
                    <table class="table table-striped align-middle text-center">
                        <thead class="table-dark">
                            <tr>
                                <th>Lecture Title</th>
                                <th>Class</th>
                                <th>Link</th>
                                <th>Date Shared</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($videos_result && $videos_result->num_rows > 0): while($row = $videos_result->fetch_assoc()): ?>
                                <tr>
                                    <td class="text-start fw-semibold"><?php echo htmlspecialchars($row['video_title']); ?></td>
                                    <td><span class="badge bg-secondary"><?php echo htmlspecialchars($row['class_name'] . " (" . $row['section'] . ")"); ?></span></td>
                                    <td>
                                        <a href="<?php echo htmlspecialchars($row['video_url']); ?>" target="_blank" class="btn btn-sm btn-outline-danger">
                                            <i class="fas fa-play me-1"></i> Watch Video
                                        </a>
                                    </td>
                                    <td class="small text-muted"><?php echo date('d M, Y', strtotime($row['created_at'])); ?></td>
                                </tr>
                            <?php endwhile; else: ?>
                                <tr>
                                    <td colspan="4" class="text-muted py-3">No video lectures shared yet.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>