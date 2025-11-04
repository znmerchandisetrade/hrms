<?php
require_once 'includes/session_check.php';
checkSession();

include_once 'config/Database.php';

if ($_POST) {
    $database = new Database();
    $db = $database->getConnection();

    $application_id = $_POST['application_id'] ?? 0;

    if (!$application_id) {
        echo json_encode(['success' => false, 'message' => 'Invalid application ID.']);
        exit();
    }

    try {
        // Check if the application belongs to the user and is pending
        $check_query = "SELECT id FROM change_dayoff_applications 
                       WHERE id = :id AND user_id = :user_id AND status = 'pending'";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->bindParam(":id", $application_id);
        $check_stmt->bindParam(":user_id", $_SESSION['user_id']);
        $check_stmt->execute();

        if ($check_stmt->rowCount() === 0) {
            echo json_encode(['success' => false, 'message' => 'Application not found or cannot be cancelled.']);
            exit();
        }

        // Delete the application
        $delete_query = "DELETE FROM change_dayoff_applications WHERE id = :id";
        $delete_stmt = $db->prepare($delete_query);
        $delete_stmt->bindParam(":id", $application_id);

        if ($delete_stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Change of dayoff application cancelled successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to cancel application.']);
        }

    } catch (Exception $e) {
        error_log("Cancel Change Dayoff Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error cancelling application.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>