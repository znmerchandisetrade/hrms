<?php
require_once 'includes/session_check.php';
checkAdminManagerAccess();

include_once 'config/Database.php';
include_once 'models/User.php';

if (isset($_GET['id'])) {
    $database = new Database();
    $db = $database->getConnection();
    
    $user = new User($db);
    $user->id = $_GET['id'];
    
    // Check if user is trying to delete themselves
    if ($_GET['id'] == $_SESSION['user_id']) {
        $_SESSION['error'] = "You cannot delete your own account!";
    } else {
        if ($user->delete()) {
            $_SESSION['success'] = "User deleted successfully!";
        } else {
            $_SESSION['error'] = "Failed to delete user.";
        }
    }
} else {
    $_SESSION['error'] = "No user ID provided.";
}

// Redirect back to the previous page or users.php
$redirect_url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'users.php';
header("Location: " . $redirect_url);
exit();
?>