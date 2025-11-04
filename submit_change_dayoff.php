<?php
require_once 'includes/session_check.php';
checkSession();

include_once 'config/Database.php';

if ($_POST) {
    $database = new Database();
    $db = $database->getConnection();

    try {
        // Validate required fields
        $required_fields = ['current_dayoff', 'requested_dayoff', 'effective_date', 'reason'];
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("Please fill in all required fields.");
            }
        }

        // Validate effective date (must be at least tomorrow)
        $effective_date = new DateTime($_POST['effective_date']);
        $today = new DateTime();
        $today->setTime(0, 0, 0);
        
        if ($effective_date <= $today) {
            throw new Exception("Effective date must be at least 1 day from today.");
        }

        // Validate that current and requested dayoffs are different
        if ($_POST['current_dayoff'] === $_POST['requested_dayoff']) {
            throw new Exception("Requested dayoff must be different from current dayoff.");
        }

        // Check reason length
        if (strlen(trim($_POST['reason'])) < 10) {
            throw new Exception("Please provide a more detailed reason (at least 10 characters).");
        }

        // Insert into database
        $query = "INSERT INTO change_dayoff_applications 
                  (user_id, current_dayoff, requested_dayoff, effective_date, reason, status, created_at, updated_at) 
                  VALUES (:user_id, :current_dayoff, :requested_dayoff, :effective_date, :reason, 'pending', NOW(), NOW())";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(":user_id", $_SESSION['user_id']);
        $stmt->bindParam(":current_dayoff", $_POST['current_dayoff']);
        $stmt->bindParam(":requested_dayoff", $_POST['requested_dayoff']);
        $stmt->bindParam(":effective_date", $_POST['effective_date']);
        $stmt->bindParam(":reason", $_POST['reason']);

        if ($stmt->execute()) {
            $_SESSION['success'] = "Change of dayoff request submitted successfully!";
        } else {
            throw new Exception("Failed to submit change of dayoff request. Please try again.");
        }

    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        error_log("Change Dayoff Submission Error: " . $e->getMessage());
    }
} else {
    $_SESSION['error'] = "Invalid request method.";
}

header("Location: dashboard.php");
exit();
?>