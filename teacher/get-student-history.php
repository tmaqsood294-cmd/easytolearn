<?php
require_once '../config/db.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    echo "<div class='p-3 text-danger fw-bold'>Unauthorized Access</div>";
    exit;
}

$student_id = isset($_GET['student_id']) ? intval($_GET['student_id']) : 0;
$current_month = date('m');
$current_year = date('Y');

if ($student_id <= 0) {
    echo "<div class='p-3 text-danger'>Invalid Student ID provided.</div>";
    exit;
}

// Student ka is mahine ka mukammal attendance data nikalna
$history_query = "SELECT date, status FROM attendance 
                  WHERE student_id = $student_id 
                  AND MONTH(date) = '$current_month' 
                  AND YEAR(date) = '$current_year' 
                  ORDER BY date DESC";
$res = $conn->query($history_query);
?>

<div class="table-responsive">
    <table class="table table-bordered table-striped m-0 align-middle">
        <thead class="table-secondary text-center">
            <tr>
                <th>Date</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            if($res && $res->num_rows > 0): 
                while($row = $res->fetch_assoc()):
                    $status = $row['status'];
                    $badge_class = "bg-success";
                    if(strtolower($status) == 'absent') { $badge_class = "bg-danger"; }
                    elseif(strtolower($status) == 'leave') { $badge_class = "bg-warning text-dark"; }
            ?>
                <tr>
                    <td class="text-center fw-bold text-secondary"><?php echo date('d-M-Y', strtotime($row['date'])); ?></td>
                    <td class="text-center">
                        <span class="badge <?php echo $badge_class; ?> px-3 py-2 fs-6 w-75"><?php echo htmlspecialchars($status); ?></span>
                    </td>
                </tr>
            <?php 
                endwhile; 
            else: 
            ?>
                <tr>
                    <td colspan="2" class="text-center text-muted py-3">
                        <i class="fas fa-info-circle me-1"></i> Current month me koi attendance record mojood nahi hai.
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>