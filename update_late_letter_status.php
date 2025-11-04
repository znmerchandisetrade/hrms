<?php
require_once 'includes/session_check.php';
checkSession();

include_once 'config/Database.php';

if ($_POST) {
    $database = new Database();
    $db = $database->getConnection();

    $application_id = $_POST['application_id'] ?? 0;
    $status = $_POST['status'] ?? '';
    $manager_notes = $_POST['manager_notes'] ?? '';

    if (!$application_id || !in_array($status, ['approved', 'rejected'])) {
        $_SESSION['error'] = "Invalid request parameters.";
        header("Location: dashboard.php");
        exit();
    }

    try {
        $db->beginTransaction();

        $update_query = "UPDATE late_letter_applications 
                        SET status = :status, 
                            approved_by = :approved_by,
                            approved_at = NOW(),
                            manager_notes = :manager_notes,
                            updated_at = NOW()
                        WHERE id = :id AND status = 'pending'";
        
        $stmt = $db->prepare($update_query);
        $stmt->bindParam(":status", $status);
        $stmt->bindParam(":approved_by", $_SESSION['user_id']);
        $stmt->bindParam(":manager_notes", $manager_notes);
        $stmt->bindParam(":id", $application_id);
        
        if ($stmt->execute()) {
            $history_query = "INSERT INTO late_letter_history 
                            (late_letter_application_id, user_id, late_date, arrival_time, 
                             reason, status, approved_by, manager_notes)
                            SELECT id, user_id, late_date, arrival_time, reason, 
                                   :status, :approved_by, :manager_notes
                            FROM late_letter_applications 
                            WHERE id = :id";
            
            $history_stmt = $db->prepare($history_query);
            $history_stmt->bindParam(":status", $status);
            $history_stmt->bindParam(":approved_by", $_SESSION['user_id']);
            $history_stmt->bindParam(":manager_notes", $manager_notes);
            $history_stmt->bindParam(":id", $application_id);
            $history_stmt->execute();

            $db->commit();
            $_SESSION['success'] = "Late letter application " . $status . " successfully!";
        } else {
            $db->rollBack();
            $_SESSION['error'] = "Failed to update application status.";
        }
    } catch (Exception $e) {
        $db->rollBack();
        $_SESSION['error'] = "Error processing request: " . $e->getMessage();
    }
} else {
    $_SESSION['error'] = "Invalid request method.";
}

header("Location: dashboard.php");
exit();
?>