<?php
require_once 'includes/session_check.php';
checkSession();

include_once 'config/Database.php';

if ($_POST) {
    $database = new Database();
    $db = $database->getConnection();

    try {
        // Validate required fields
        $required_fields = ['late_date', 'arrival_time', 'reason'];
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("Please fill in all required fields.");
            }
        }

        // Validate late date (cannot be in the future)
        $late_date = new DateTime($_POST['late_date']);
        $today = new DateTime();
        $today->setTime(0, 0, 0);
        
        if ($late_date > $today) {
            throw new Exception("Late date cannot be in the future.");
        }

        // Check if late date is not too far in the past (within 7 days)
        $seven_days_ago = clone $today;
        $seven_days_ago->modify('-7 days');
        
        if ($late_date < $seven_days_ago) {
            throw new Exception("Late date cannot be more than 7 days in the past.");
        }

        // Validate arrival time format
        if (!preg_match('/^([0-1]?[0-9]|2[0-3]):[0-5][0-9]$/', $_POST['arrival_time'])) {
            throw new Exception("Please enter a valid arrival time.");
        }

        // Check reason length
        if (strlen(trim($_POST['reason'])) < 10) {
            throw new Exception("Please provide a more detailed reason (at least 10 characters).");
        }

        // Check if a late letter already exists for this date
        $check_query = "SELECT id FROM late_letter_applications 
                       WHERE user_id = :user_id AND late_date = :late_date AND status != 'rejected'";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->bindParam(":user_id", $_SESSION['user_id']);
        $check_stmt->bindParam(":late_date", $_POST['late_date']);
        $check_stmt->execute();

        if ($check_stmt->rowCount() > 0) {
            throw new Exception("You have already submitted a late letter for this date.");
        }

        // Get user details for logging
        $user_query = "SELECT full_name, company_id, department_id FROM users WHERE id = :user_id";
        $user_stmt = $db->prepare($user_query);
        $user_stmt->bindParam(":user_id", $_SESSION['user_id']);
        $user_stmt->execute();
        $user = $user_stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            throw new Exception("User not found.");
        }

        // Insert into database
        $query = "INSERT INTO late_letter_applications 
                  (user_id, late_date, arrival_time, reason, status, created_at, updated_at) 
                  VALUES (:user_id, :late_date, :arrival_time, :reason, 'pending', NOW(), NOW())";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(":user_id", $_SESSION['user_id']);
        $stmt->bindParam(":late_date", $_POST['late_date']);
        $stmt->bindParam(":arrival_time", $_POST['arrival_time']);
        $stmt->bindParam(":reason", $_POST['reason']);

        if ($stmt->execute()) {
            $application_id = $db->lastInsertId();
            
            // Log the submission
            error_log("Late Letter Submitted - ID: $application_id, User: {$user['full_name']}, Date: {$_POST['late_date']}, Time: {$_POST['arrival_time']}");
            
            $_SESSION['success'] = "Late letter submitted successfully! Your application ID is LL-" . str_pad($application_id, 6, '0', STR_PAD_LEFT);
        } else {
            throw new Exception("Failed to submit late letter. Please try again.");
        }

    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        error_log("Late Letter Submission Error: " . $e->getMessage());
        
        // Store form data in session to repopulate form
        $_SESSION['form_data'] = [
            'late_date' => $_POST['late_date'] ?? '',
            'arrival_time' => $_POST['arrival_time'] ?? '',
            'reason' => $_POST['reason'] ?? ''
        ];
    }
} else {
    $_SESSION['error'] = "Invalid request method.";
}

header("Location: dashboard.php");
exit();
?>