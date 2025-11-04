<?php
require_once 'includes/session_check.php';
checkAdminManagerAccess();

include_once 'config/Database.php';
include_once 'models/User.php';

if ($_POST) {
    $database = new Database();
    $db = $database->getConnection();
    
    $user = new User($db);
    
    $user->username = $_POST['username'];
    $user->full_name = $_POST['full_name'];
    $user->password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $user->company_id = $_POST['company_id'];
    $user->brand_id = $_POST['brand_id'];
    $user->department_id = $_POST['department_id'];
    $user->store_id = $_POST['store_id'];
    $user->role_id = $_POST['role_id'];
    $user->date_hired = $_POST['date_hired'];
    
    // Handle profile image upload
    $user->profile_image = 'default-avatar.png';
    if (!empty($_FILES['profile_image']['name']) && $_FILES['profile_image']['error'] == 0) {
        $target_dir = "uploads/profiles/";
        
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES["profile_image"]["name"], PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (in_array($file_extension, $allowed_extensions)) {
            $filename = 'user_' . time() . '_' . uniqid() . '.' . $file_extension;
            $target_file = $target_dir . $filename;
            
            if ($_FILES["profile_image"]["size"] <= 2097152) {
                if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file)) {
                    $user->profile_image = $filename;
                }
            }
        }
    }
    
    if ($user->create()) {
        $_SESSION['success'] = "User created successfully!";
    } else {
        $_SESSION['error'] = "Failed to create user.";
    }
} else {
    $_SESSION['error'] = "Invalid request method.";
}

header("Location: dashboard.php");
exit();
?>