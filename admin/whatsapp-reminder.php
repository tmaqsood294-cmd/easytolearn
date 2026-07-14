<?php
// Include database connection (path check kar lein apne structure ke mutabik)
include(__DIR__ . '/../config/db.php');

/**
 * WhatsApp Gateway Settings (Twilio Example)
 * Agar aap koi aur gateway use kar rahe hain to uske mutabik curl request modify kar lein.
 */
define('TWILIO_SID', 'YOUR_TWILIO_ACCOUNT_SID'); 
define('TWILIO_AUTH_TOKEN', 'YOUR_TWILIO_AUTH_TOKEN');
define('TWILIO_WHATSAPP_NUMBER', 'whatsapp:+14155238886'); // Twilio sandbox number ya aapka approved number

function sendWhatsAppMessage($to_phone, $message) {
    // Phone number format ensure karein (+92 ya aapka country code lazmi ho)
    // Agar database me basic number hai to prefix add karein
    if (substr($to_phone, 0, 1) === '0') {
        $to_phone = '+92' . substr($to_phone, 1); // For Pakistan
    }
    
    if (substr($to_phone, 0, 1) !== '+') {
        $to_phone = '+' . $to_phone;
    }

    $url = "https://api.twilio.com/2010-04-01/Accounts/" . TWILIO_SID . "/Messages.json";
    
    $data = [
        'From' => TWILIO_WHATSAPP_NUMBER,
        'To' => 'whatsapp:' . $to_phone,
        'Body' => $message
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_USERPWD, TWILIO_SID . ':' . TWILIO_AUTH_TOKEN);
    
    $response = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);

    if ($err) {
        return false;
    } else {
        return json_decode($response, true);
    }
}

// -------------------------------------------------------------
// CORE LOGIC: Fetch Unpaid Challans (Latest only per student)
// -------------------------------------------------------------
$current_month_year = date('Y-m');

$unpaid_query = "SELECT f1.*, 
                    users.name AS student_name, 
                    students.parent_name, students.parent_phone,
                    classes.class_name 
                 FROM fees f1
                 INNER JOIN (
                     SELECT student_id, MAX(id) as max_id 
                     FROM fees 
                     GROUP BY student_id
                 ) f2 ON f1.id = f2.max_id
                 LEFT JOIN students ON f1.student_id = students.id 
                 LEFT JOIN users ON students.user_id = users.id 
                 LEFT JOIN classes ON students.class_id = classes.id 
                 WHERE f1.status = 'Unpaid' AND f1.due_date LIKE '$current_month_year-%'";

$result = $conn->query($unpaid_query);

if ($result && $result->num_rows > 0) {
    echo "Processing reminders for " . $result->num_rows . " students...\n";
    
    while ($row = $result->fetch_assoc()) {
        $parent_phone = trim($row['parent_phone']); // Make sure your students table has this column
        $parent_name = $row['parent_name'];
        $student_name = $row['student_name'];
        $class_name = $row['class_name'];
        $challan_no = $row['challan_number'];
        
        $base_amount = $row['amount'];
        $fine = $row['fine_amount'];
        $total_payable = $base_amount + $fine;
        $due_date = date('d-M-Y', strtotime($row['due_date']));

        if (empty($parent_phone)) {
            echo "Skipped: Student {$student_name} has no parent phone number.\n";
            continue;
        }

        // Professional Message Template
        $message = "Dear Parent ({$parent_name}),\n\n";
        $message .= "This is a daily reminder regarding the school fee of your child *{$student_name}* (Class: {$class_name}).\n\n";
        $message .= "*Challan No:* {$challan_no}\n";
        $message .= "*Total Payable Amount:* Rs. " . number_format($total_payable) . " (Includes Fine: Rs. " . number_format($fine) . ")\n";
        $message .= "*Due Date:* {$due_date}\n\n";
        $message .= "Kindly ignore this message if already paid. Please submit the fee at the earliest to avoid further fine increases.\n\n";
        $message .= "Regards,\nSchool Management Account Office.";

        // Send Message
        $status = sendWhatsAppMessage($parent_phone, $message);
        
        if ($status && !isset($status['error_code'])) {
            echo "Successfully sent reminder to {$parent_name} for student {$student_name}.\n";
        } else {
            $error_msg = isset($status['message']) ? $status['message'] : 'Unknown Error';
            echo "Failed to send to {$parent_phone}. Error: " . $error_msg . "\n";
        }
        
        // Anti-spam delay (1 to 2 seconds sleep) taake WhatsApp numbers block na hon
        usleep(150000); 
    }
} else {
    echo "No unpaid challans found for this month.\n";
}
?>