<?php
// Include the configuration autoloader
include_once 'config/autoload.php';

// Use the SessionConfig class instead of native session functions
SessionConfig::initialize();

// Include other necessary files
include_once 'models/User.php';

if ($_POST) {
    try {
        $database = new Database();
        $db = $database->getConnection();
        $user = new User($db);

        // Validate required fields
        $required_fields = ['full_name', 'username', 'password', 'confirm_password'];
        foreach ($required_fields as $field) {
            if (empty(trim($_POST[$field] ?? ''))) {
                throw new Exception("Please fill in all required fields.");
            }
        }

        // Check if passwords match
        if ($_POST['password'] !== $_POST['confirm_password']) {
            throw new Exception("Passwords do not match.");
        }

        // Check if username already exists
        if ($user->usernameExists($_POST['username'])) {
            throw new Exception("Username already exists. Please choose a different username.");
        }

        // Check if full name already exists
        if ($user->fullNameExists($_POST['full_name'])) {
            throw new Exception("An account with this full name already exists.");
        }

        // Validate password length
        if (strlen($_POST['password']) < 6) {
            throw new Exception("Password must be at least 6 characters long.");
        }

        // Assign user properties
        $user->full_name = trim($_POST['full_name']);
        $user->username = trim($_POST['username']);
        $user->password = $_POST['password'];
        $user->company_id = !empty($_POST['company_id']) ? (int)$_POST['company_id'] : null;
        $user->department_id = !empty($_POST['department_id']) ? (int)$_POST['department_id'] : null;
        $user->brand_id = !empty($_POST['brand_id']) ? (int)$_POST['brand_id'] : null;
        $user->store_id = !empty($_POST['store_id']) ? (int)$_POST['store_id'] : null;
        $user->role_id = !empty($_POST['role_id']) ? (int)$_POST['role_id'] : 3; // Default to Employee role
        $user->date_hired = !empty($_POST['date_hired']) ? $_POST['date_hired'] : date('Y-m-d');

        // Handle profile image upload
        if (!empty($_FILES['profile_image']['name']) && $_FILES['profile_image']['error'] == 0) {
            $target_dir = "uploads/profiles/";
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0755, true);
            }
            
            $file_extension = strtolower(pathinfo($_FILES["profile_image"]["name"], PATHINFO_EXTENSION));
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
            
            if (in_array($file_extension, $allowed_extensions)) {
                if ($_FILES["profile_image"]["size"] <= 2097152) { // 2MB
                    $filename = 'user_' . time() . '_' . uniqid() . '.' . $file_extension;
                    $target_file = $target_dir . $filename;
                    
                    if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file)) {
                        $user->profile_image = $filename;
                    } else {
                        throw new Exception("Failed to upload profile image.");
                    }
                } else {
                    throw new Exception("Profile image size must be less than 2MB.");
                }
            } else {
                throw new Exception("Only JPG, JPEG, PNG & GIF files are allowed.");
            }
        } else {
            $user->profile_image = 'default-avatar.png';
        }

        // Create user
        if ($user->create()) {
            // SUCCESS - Store success data in session
            SessionConfig::set('signup_success', true);
            SessionConfig::set('signup_message', 'Account created successfully!');
            SessionConfig::set('signup_details', [
                'full_name' => $user->full_name,
                'username' => $user->username,
                'timestamp' => time(),
                'date' => date('F j, Y'),
                'time' => date('g:i A')
            ]);
            
            // Log successful signup
            if (ENABLE_ERROR_LOGGING) {
                error_log("User signup successful: " . $user->username . " (Name: " . $user->full_name . ") at " . date('Y-m-d H:i:s'));
            }
            
            // Redirect to index for modal display
            header("Location: index.php?signup=success");
            exit();
        } else {
            throw new Exception("Failed to create account. Please try again.");
        }

    } catch (Exception $e) {
        // ERROR - Store error data in session
        SessionConfig::set('signup_success', false);
        SessionConfig::set('signup_message', $e->getMessage());
        
        // Log signup error
        if (ENABLE_ERROR_LOGGING) {
            error_log("User signup failed: " . ($_POST['username'] ?? 'unknown') . " - " . $e->getMessage());
        }
        
        // Redirect back to index with error
        header("Location: index.php?signup=error&message=" . urlencode($e->getMessage()));
        exit();
    }
} else {
    // Invalid request
    SessionConfig::set('signup_success', false);
    SessionConfig::set('signup_message', 'Invalid request method.');
    header("Location: index.php?signup=error&message=" . urlencode('Invalid request'));
    exit();
}
?>