<?php
require_once '../config/db.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Strict security check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo "<p class='text-danger text-center'>Unauthorized access.</p>";
    exit;
}

if (isset($_GET['teacher_id'])) {
    $t_id = intval($_GET['teacher_id']);
    
    // Query to get all history sorted by recent dates & lectures
    $query = "SELECT attendance_date, lecture_no, status FROM teacher_attendance 
              WHERE teacher_id = $t_id 
              ORDER BY attendance_date DESC, lecture_no ASC";
              
    $result = $conn->query($query);
    
    if ($result && $result->num_rows > 0) {
        echo '<table class="table table-bordered table-striped table-sm text-center align-middle">';
        echo '<thead class="table-secondary">
                <tr>
                    <th>Date</th>
                    <th>Lecture</th>
                    <th>Status</th>
                </tr>
              </thead>
              <tbody>';
        while ($row = $result->fetch_assoc()) {
            $status = $row['status'];
            $badge_class = 'bg-success';
            if ($status == 'Absent') $badge_class = 'bg-danger';
            if ($status == 'Leave') $badge_class = 'bg-warning text-dark';
            
            // Format Date nicely
            $formatted_date = date("d-M-Y", strtotime($row['attendance_date']));
            
            echo "<tr>
                    <td class='fw-semibold'>{$formatted_date}</td>
                    <td>Lecture {$row['lecture_no']}</td>
                    <td><span class='badge {$badge_class} px-3 py-1 fs-6'>{$status}</span></td>
                  </tr>";
        }
        echo '</tbody></table>';
    } else {
        echo '<div class="alert alert-info text-center m-0">No attendance history logs found for this teacher yet.</div>';
    }
} else {
    echo "<p class='text-danger text-center'>Invalid Request.</p>";
}
?>