<?php
session_start();

// Check authentication and authorization
if (!isset($_SESSION['user_id']) || ($_SESSION['role_name'] != 'HR Manager' && $_SESSION['role_name'] != 'Store Manager')) {
    header("Location: index.php");
    exit();
}

include_once 'config/Database.php';
include_once 'models/User.php';

/**
 * Handle profile image upload with security checks
 */
function handleProfileImageUpload($file) {
    $result = ['success' => false, 'filename' => null, 'error' => null];
    
    $target_dir = "uploads/profiles/";
    $max_file_size = 2 * 1024 * 1024; // 2MB
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
    $allowed_mime_types = ['image/jpeg', 'image/png', 'image/gif'];
    
    // Create directory if it doesn't exist
    if (!file_exists($target_dir)) {
        if (!mkdir($target_dir, 0755, true)) {
            $result['error'] = "Failed to create upload directory";
            return $result;
        }
    }
    
    // Check file size
    if ($file['size'] > $max_file_size) {
        $result['error'] = "File size must be less than 2MB";
        return $result;
    }
    
    // Get file extension
    $file_extension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    
    // Validate extension
    if (!in_array($file_extension, $allowed_extensions)) {
        $result['error'] = "Only JPG, JPEG, PNG & GIF files are allowed";
        return $result;
    }
    
    // Validate MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mime_type, $allowed_mime_types)) {
        $result['error'] = "Invalid file type";
        return $result;
    }
    
    // Check if file is actually an image
    $image_info = getimagesize($file['tmp_name']);
    if (!$image_info) {
        $result['error'] = "File is not a valid image";
        return $result;
    }
    
    // Generate secure filename
    $filename = 'user_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $file_extension;
    $target_file = $target_dir . $filename;
    
    // Move uploaded file
    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        // Set proper permissions
        chmod($target_file, 0644);
        
        $result['success'] = true;
        $result['filename'] = $filename;
    } else {
        $result['error'] = "Failed to upload file";
    }
    
    return $result;
}

/**
 * Delete profile image file
 */
function deleteProfileImage($filename) {
    if (!empty($filename) && $filename != 'default.png') {
        $file_path = "uploads/profiles/" . $filename;
        if (file_exists($file_path) && is_file($file_path)) {
            unlink($file_path);
        }
    }
}

/**
 * Validate date format
 */
function validateDate($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

/**
 * Log user update action
 */
function logUserUpdate($updater_id, $user_id) {
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        $query = "INSERT INTO audit_logs (user_id, action, target_user_id, ip_address, user_agent) 
                  VALUES (:user_id, 'update_user', :target_user_id, :ip_address, :user_agent)";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(":user_id", $updater_id);
        $stmt->bindParam(":target_user_id", $user_id);
        $stmt->bindParam(":ip_address", $_SERVER['REMOTE_ADDR']);
        $stmt->bindParam(":user_agent", $_SERVER['HTTP_USER_AGENT']);
        $stmt->execute();
    } catch (Exception $e) {
        error_log("Audit log error: " . $e->getMessage());
    }
}

// Only process POST requests
if ($_POST) {
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        $user = new User($db);
        
        // Validate and sanitize input
        $user->id = filter_var($_POST['id'], FILTER_VALIDATE_INT);
        if (!$user->id || $user->id <= 0) {
            throw new Exception("Invalid user ID");
        }

        // Verify user exists and is not deleted
        $existing_user = $user->getById($user->id);
        if (!$existing_user) {
            throw new Exception("User not found or has been deleted");
        }

        $user->username = trim(filter_var($_POST['username'], FILTER_SANITIZE_STRING));
        $user->full_name = trim(filter_var($_POST['full_name'], FILTER_SANITIZE_STRING));
        $user->company_id = filter_var($_POST['company_id'], FILTER_VALIDATE_INT);
        $user->brand_id = filter_var($_POST['brand_id'], FILTER_VALIDATE_INT);
        $user->department_id = filter_var($_POST['department_id'], FILTER_VALIDATE_INT);
        $user->store_id = filter_var($_POST['store_id'], FILTER_VALIDATE_INT);
        $user->role_id = filter_var($_POST['role_id'], FILTER_VALIDATE_INT);
        $user->date_hired = trim(filter_var($_POST['date_hired'], FILTER_SANITIZE_STRING));

        // Validate required fields
        if (empty($user->username) || empty($user->full_name)) {
            throw new Exception("Username and full name are required");
        }

        // Validate username length
        if (strlen($user->username) < 3) {
            throw new Exception("Username must be at least 3 characters long");
        }

        // Check if username already exists (excluding current user)
        if ($user->usernameExists($user->username) && $existing_user['username'] !== $user->username) {
            throw new Exception("Username already exists. Please choose a different username.");
        }

        // Validate date format
        if (!empty($user->date_hired) && !validateDate($user->date_hired)) {
            throw new Exception("Invalid date format for hire date");
        }

        // Handle profile image upload
        if (!empty($_FILES['profile_image']['name']) && $_FILES['profile_image']['error'] == 0) {
            $upload_result = handleProfileImageUpload($_FILES['profile_image']);
            if ($upload_result['success']) {
                $user->profile_image = $upload_result['filename'];
                
                // Delete old profile image if it exists and is not the default
                if (!empty($existing_user['profile_image']) && $existing_user['profile_image'] != 'default.png') {
                    deleteProfileImage($existing_user['profile_image']);
                }
            } else {
                throw new Exception($upload_result['error']);
            }
        } else {
            // Keep existing image if no new image uploaded
            $user->profile_image = !empty($_POST['existing_profile_image']) ? 
                filter_var($_POST['existing_profile_image'], FILTER_SANITIZE_STRING) : null;
        }
        
        // Update user
        if ($user->update()) {
            $_SESSION['success'] = "User updated successfully!";
            
            // Log the action
            logUserUpdate($_SESSION['user_id'], $user->id);
        } else {
            throw new Exception("Failed to update user in database");
        }
        
    } catch (Exception $e) {
        error_log("User update error: " . $e->getMessage());
        $_SESSION['error'] = $e->getMessage();
    } catch (PDOException $e) {
        error_log("Database error in edit_user.php: " . $e->getMessage());
        $_SESSION['error'] = "A database error occurred. Please try again.";
    }
} else {
    $_SESSION['error'] = "Invalid request method.";
}

header("Location: users.php");
exit();
?>