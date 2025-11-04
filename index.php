<?php
// Include the configuration autoloader
include_once 'config/autoload.php';
SessionConfig::initialize();
SessionConfig::clear();

include_once 'models/User.php';
include_once 'models/Reference.php';

// Handle login
if ($_POST && isset($_POST['username']) && isset($_POST['password'])) {
    $database = new Database();
    $db = $database->getConnection();
    
    $user = new User($db);
    $user->username = $_POST['username'];
    $user->password = $_POST['password'];
    
    if ($user->login()) {
        // Store user data using SessionConfig methods
        SessionConfig::set('user_id', $user->id);
        SessionConfig::set('username', $user->username);
        SessionConfig::set('full_name', $user->full_name);
        SessionConfig::set('role_name', $user->role_name);
        SessionConfig::set('company_name', $user->company_name);
        SessionConfig::set('brand_name', $user->brand_name);
        SessionConfig::set('department_name', $user->department_name);
        SessionConfig::set('store_name', $user->store_name);
        SessionConfig::set('date_hired', $user->date_hired);
        SessionConfig::set('profile_image', $user->profile_image);
        SessionConfig::set('logged_in', true);
        SessionConfig::set('login_time', time());
        
        // Log successful login
        if (ENABLE_ERROR_LOGGING) {
            error_log("User login successful: " . $user->username . " (ID: " . $user->id . ") at " . date('Y-m-d H:i:s'));
        }
        
        header("Location: dashboard.php");
        exit();
    } else {
        $error = "Invalid username or password.";
        
        // Log failed login attempt
        if (ENABLE_ERROR_LOGGING) {
            $username = $_POST['username'];
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            error_log("Failed login attempt - Username: " . $username . " from IP: " . $ip . " at " . date('Y-m-d H:i:s'));
        }
    }
}

// Check for URL parameters
$url_success = isset($_GET['signup']) && $_GET['signup'] === 'success';
$url_message = $_GET['message'] ?? '';
$url_username = $_GET['username'] ?? '';
$url_fullname = $_GET['full_name'] ?? '';

// Check for session-based signup messages
$session_success = SessionConfig::get('signup_success');
$session_message = SessionConfig::get('signup_message');
$session_details = SessionConfig::get('signup_details');

// Clear session messages after reading
if ($session_success !== null) {
    SessionConfig::remove('signup_success');
    SessionConfig::remove('signup_message');
    SessionConfig::remove('signup_details');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HR Management System - Login</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="assets/css/login.css" rel="stylesheet">
    <link href="assets/css/styles.css" rel="stylesheet">
    
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .login-card {
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .pulse-animation {
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        .signup-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
    </style>
</head>
<body class="gradient-bg">
    <div class="container-fluid min-vh-100 d-flex align-items-center justify-content-center p-3">
        <div class="login-card rounded-3 p-4 text-white" style="max-width: 400px; width: 100%;">
            <!-- Header -->
            <div class="text-center mb-4">
                <i class="fas fa-calendar-alt fa-3x mb-3 pulse-animation"></i>
                <h2 class="fw-bold">HR Management System</h2>
                <p class="mb-0 opacity-75">Sign in to your account</p>
            </div>

            <!-- Error Message -->
            <?php if (isset($error)): ?>
                <div class="alert alert-danger d-flex align-items-center">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <!-- Login Form -->
            <form method="POST" action="">
                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <div class="input-group">
                        <span class="input-group-text bg-transparent text-white border-end-0">
                            <i class="fas fa-user"></i>
                        </span>
                        <input type="text" name="username" class="form-control border-start-0 bg-transparent text-white" 
                               placeholder="Enter your username" required
                               value="<?php echo htmlspecialchars($url_username); ?>">
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text bg-transparent text-white border-end-0">
                            <i class="fas fa-lock"></i>
                        </span>
                        <input type="password" name="password" class="form-control border-start-0 bg-transparent text-white" 
                               placeholder="Enter your password" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-light w-100 fw-bold py-2">
                    <i class="fas fa-sign-in-alt me-2"></i>Sign In
                </button>
            </form>

            <!-- Signup Section -->
            <div class="text-center mt-4">
                <p class="mb-2 opacity-75">Don't have an account?</p>
                <button type="button" class="btn btn-outline-light w-100" data-bs-toggle="modal" data-bs-target="#signupModal">
                    <i class="fas fa-user-plus me-2"></i>Sign Up Here
                </button>
            </div>

            <!-- Demo Credentials (Optional) -->
            <!--
            <div class="mt-3 p-3 bg-dark bg-opacity-25 rounded">
                <small class="opacity-75">
                    <i class="fas fa-info-circle me-1"></i>Demo Credentials:<br>
                    <span class="text-white">
                        Admin: admin / password<br>
                        Manager: manager1 / password<br>
                        Employee: employee1 / password
                    </span>
                </small>
            </div>
            -->
        </div>
    </div>

    <!-- Signup Modal -->
    <div class="modal fade" id="signupModal" tabindex="-1" aria-labelledby="signupModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header signup-header text-white">
                    <h5 class="modal-title" id="signupModalLabel">
                        <i class="fas fa-user-plus me-2"></i>Create New Account
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="signupForm" action="process_signup.php" method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <!-- Message Display -->
                        <div id="signupMessage"></div>
                        
                        <!-- Personal Information -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="full_name" class="form-label">
                                        <i class="fas fa-user me-1 text-primary"></i>Full Name *
                                    </label>
                                    <input type="text" class="form-control" id="full_name" name="full_name" required 
                                           placeholder="Enter your full name"
                                           value="<?php echo htmlspecialchars($url_fullname); ?>">
                                    <div class="form-text" id="fullNameFeedback"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="username" class="form-label">
                                        <i class="fas fa-at me-1 text-primary"></i>Username *
                                    </label>
                                    <input type="text" class="form-control" id="signup_username" name="username" required 
                                           placeholder="Choose a username"
                                           value="<?php echo htmlspecialchars($url_username); ?>">
                                    <div class="form-text" id="usernameFeedback"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Password -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="password" class="form-label">
                                        <i class="fas fa-key me-1 text-primary"></i>Password *
                                    </label>
                                    <input type="password" class="form-control" id="signup_password" name="password" required minlength="6"
                                           placeholder="Enter password">
                                    <div class="form-text" id="passwordFeedback"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">
                                        <i class="fas fa-key me-1 text-primary"></i>Confirm Password *
                                    </label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required
                                           placeholder="Confirm password">
                                    <div class="form-text" id="confirmFeedback"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Company & Department -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="company_id" class="form-label">
                                        <i class="fas fa-building me-1 text-primary"></i>Company
                                    </label>
                                    <select class="form-select" id="company_id" name="company_id">
                                        <option value="">Select Company</option>
                                        <?php
                                        $database = new Database();
                                        $db = $database->getConnection();
                                        $reference = new Reference($db);
                                        $companies = $reference->getCompanies();
                                        if ($companies) {
                                            while ($row = $companies->fetch(PDO::FETCH_ASSOC)) {
                                                echo '<option value="' . $row['id'] . '">' . htmlspecialchars($row['company_name']) . '</option>';
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="department_id" class="form-label">
                                        <i class="fas fa-users me-1 text-primary"></i>Department
                                    </label>
                                    <select class="form-select" id="department_id" name="department_id">
                                        <option value="">Select Department</option>
                                        <?php
                                        $departments = $reference->getDepartments();
                                        if ($departments) {
                                            while ($row = $departments->fetch(PDO::FETCH_ASSOC)) {
                                                echo '<option value="' . $row['id'] . '">' . htmlspecialchars($row['department_name']) . '</option>';
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Brand & Store -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="brand_id" class="form-label">
                                        <i class="fas fa-tag me-1 text-primary"></i>Brand
                                    </label>
                                    <select class="form-select" id="brand_id" name="brand_id">
                                        <option value="">Select Brand</option>
                                        <?php
                                        $brands = $reference->getBrands();
                                        if ($brands) {
                                            while ($row = $brands->fetch(PDO::FETCH_ASSOC)) {
                                                echo '<option value="' . $row['id'] . '">' . htmlspecialchars($row['brand_name']) . '</option>';
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="store_id" class="form-label">
                                        <i class="fas fa-store me-1 text-primary"></i>Store
                                    </label>
                                    <select class="form-select" id="store_id" name="store_id">
                                        <option value="">Select Store</option>
                                        <?php
                                        $stores = $reference->getStores();
                                        if ($stores) {
                                            while ($row = $stores->fetch(PDO::FETCH_ASSOC)) {
                                                echo '<option value="' . $row['id'] . '">' . htmlspecialchars($row['store_name']) . '</option>';
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Role & Date Hired -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="role_id" class="form-label">
                                        <i class="fas fa-user-tie me-1 text-primary"></i>Role
                                    </label>
                                    <select class="form-select" id="role_id" name="role_id">
                                        <option value="">Select Role</option>
                                        <?php
                                        $roles = $reference->getRoles();
                                        if ($roles) {
                                            while ($row = $roles->fetch(PDO::FETCH_ASSOC)) {
                                                echo '<option value="' . $row['id'] . '">' . htmlspecialchars($row['role_name']) . '</option>';
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="date_hired" class="form-label">
                                        <i class="fas fa-calendar-day me-1 text-primary"></i>Date Hired
                                    </label>
                                    <input type="date" class="form-control" id="date_hired" name="date_hired">
                                </div>
                            </div>
                        </div>

                        <!-- Profile Image -->
                        <div class="mb-3">
                            <label for="profile_image" class="form-label">
                                <i class="fas fa-camera me-1 text-primary"></i>Profile Image
                            </label>
                            <input type="file" class="form-control" id="profile_image" name="profile_image" accept="image/*">
                            <div class="form-text">
                                <i class="fas fa-info-circle me-1"></i>Max file size: 2MB. Allowed types: JPG, PNG, GIF
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Cancel
                        </button>
                        <button type="submit" class="btn btn-primary" id="signupSubmitBtn">
                            <i class="fas fa-user-plus me-2"></i>Create Account
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap & jQuery -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <script>
        // Initialize when page loads
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Page loaded successfully');
            
            // Check for signup messages from URL
            const urlParams = new URLSearchParams(window.location.search);
            const signupParam = urlParams.get('signup');
            const messageParam = urlParams.get('message');
            
            if (signupParam === 'error' && messageParam) {
                // Show error message and open modal
                document.getElementById('signupMessage').innerHTML = 
                    '<div class="alert alert-danger alert-dismissible fade show">' +
                    '<i class="fas fa-exclamation-triangle me-2"></i>' +
                    decodeURIComponent(messageParam) +
                    '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
                    '</div>';
                
                // Auto-open modal on error
                const modal = new bootstrap.Modal(document.getElementById('signupModal'));
                modal.show();
            } else if (signupParam === 'success') {
                // Show success message
                document.getElementById('signupMessage').innerHTML = 
                    '<div class="alert alert-success alert-dismissible fade show">' +
                    '<i class="fas fa-check-circle me-2"></i>' +
                    'Account created successfully! You can now login.' +
                    '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
                    '</div>';
            }
            
            // Setup form validation
            setupSignupFormValidation();
        });

        // Setup signup form validation
        function setupSignupFormValidation() {
            const signupForm = document.getElementById('signupForm');
            const passwordInput = document.getElementById('signup_password');
            const confirmInput = document.getElementById('confirm_password');
            const usernameInput = document.getElementById('signup_username');
            const fullNameInput = document.getElementById('full_name');
            
            // Password strength check
            if (passwordInput) {
                passwordInput.addEventListener('input', checkPasswordStrength);
            }
            
            // Password confirmation check
            if (confirmInput) {
                confirmInput.addEventListener('input', checkPasswordConfirmation);
            }
            
            // Username availability check (debounced)
            if (usernameInput) {
                usernameInput.addEventListener('input', debounce(checkUsernameAvailability, 800));
            }
            
            // Full name availability check (debounced)
            if (fullNameInput) {
                fullNameInput.addEventListener('input', debounce(checkFullNameAvailability, 800));
            }
            
            // Form submission validation
            if (signupForm) {
                signupForm.addEventListener('submit', validateSignupForm);
            }
        }

        // Password strength checker
        function checkPasswordStrength() {
            const password = document.getElementById('signup_password').value;
            const feedback = document.getElementById('passwordFeedback');
            
            if (!password) {
                feedback.innerHTML = '';
                return;
            }
            
            let strength = 0;
            if (password.length >= 6) strength++;
            if (password.length >= 8) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[a-z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^A-Za-z0-9]/.test(password)) strength++;
            
            let strengthText = '', strengthClass = '', strengthIcon = '';
            
            if (strength <= 2) {
                strengthText = 'Weak';
                strengthClass = 'text-danger';
                strengthIcon = 'fa-times-circle';
            } else if (strength <= 4) {
                strengthText = 'Medium';
                strengthClass = 'text-warning';
                strengthIcon = 'fa-exclamation-triangle';
            } else {
                strengthText = 'Strong';
                strengthClass = 'text-success';
                strengthIcon = 'fa-check-circle';
            }
            
            feedback.innerHTML = `<span class="${strengthClass}"><i class="fas ${strengthIcon} me-1"></i>Password strength: ${strengthText}</span>`;
            checkPasswordConfirmation();
        }

        function checkPasswordConfirmation() {
            const password = document.getElementById('signup_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const feedback = document.getElementById('confirmFeedback');
            
            if (!confirmPassword) {
                feedback.innerHTML = '';
                return;
            }
            
            if (password === confirmPassword) {
                feedback.innerHTML = '<span class="text-success"><i class="fas fa-check-circle me-1"></i>Passwords match</span>';
            } else {
                feedback.innerHTML = '<span class="text-danger"><i class="fas fa-times-circle me-1"></i>Passwords do not match</span>';
            }
        }

        function checkUsernameAvailability() {
            const username = document.getElementById('signup_username').value.trim();
            const feedback = document.getElementById('usernameFeedback');
            
            if (username.length < 3) {
                feedback.innerHTML = '<span class="text-warning"><i class="fas fa-exclamation-triangle me-1"></i>Username must be at least 3 characters</span>';
                return;
            }
            
            feedback.innerHTML = '<span class="text-info"><i class="fas fa-spinner fa-spin me-1"></i>Checking availability...</span>';
            
            fetch('check_availability.php?type=username&value=' + encodeURIComponent(username))
                .then(response => response.json())
                .then(data => {
                    if (data.available) {
                        feedback.innerHTML = '<span class="text-success"><i class="fas fa-check-circle me-1"></i>Username available</span>';
                    } else {
                        feedback.innerHTML = '<span class="text-danger"><i class="fas fa-times-circle me-1"></i>Username already taken</span>';
                    }
                })
                .catch(error => {
                    console.error('Error checking username:', error);
                    feedback.innerHTML = '<span class="text-warning"><i class="fas fa-exclamation-triangle me-1"></i>Error checking availability</span>';
                });
        }

        function checkFullNameAvailability() {
            const fullName = document.getElementById('full_name').value.trim();
            const feedback = document.getElementById('fullNameFeedback');
            
            if (fullName.length < 2) {
                feedback.innerHTML = '';
                return;
            }
            
            feedback.innerHTML = '<span class="text-info"><i class="fas fa-spinner fa-spin me-1"></i>Checking availability...</span>';
            
            fetch('check_availability.php?type=fullname&value=' + encodeURIComponent(fullName))
                .then(response => response.json())
                .then(data => {
                    if (data.available) {
                        feedback.innerHTML = '<span class="text-success"><i class="fas fa-check-circle me-1"></i>Name available</span>';
                    } else {
                        feedback.innerHTML = '<span class="text-danger"><i class="fas fa-times-circle me-1"></i>An account with this name already exists</span>';
                    }
                })
                .catch(error => {
                    console.error('Error checking full name:', error);
                    feedback.innerHTML = '<span class="text-warning"><i class="fas fa-exclamation-triangle me-1"></i>Error checking availability</span>';
                });
        }

        function validateSignupForm(e) {
            const password = document.getElementById('signup_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const usernameFeedback = document.getElementById('usernameFeedback').textContent;
            const fullNameFeedback = document.getElementById('fullNameFeedback').textContent;
            
            document.getElementById('signupMessage').innerHTML = '';
            
            let errors = [];
            
            // Check required fields
            const requiredFields = ['full_name', 'signup_username', 'signup_password', 'confirm_password'];
            requiredFields.forEach(fieldId => {
                const field = document.getElementById(fieldId);
                if (!field.value.trim()) {
                    errors.push('Please fill in all required fields!');
                }
            });
            
            // Check password match
            if (password !== confirmPassword) {
                errors.push('Passwords do not match!');
            }
            
            // Check password length
            if (password.length < 6) {
                errors.push('Password must be at least 6 characters long!');
            }
            
            // Check username availability
            if (usernameFeedback.includes('already taken')) {
                errors.push('Username is already taken!');
            }
            
            // Check full name availability
            if (fullNameFeedback.includes('already exists')) {
                errors.push('An account with this full name already exists!');
            }
            
            // Show errors if any
            if (errors.length > 0) {
                e.preventDefault();
                const uniqueErrors = [...new Set(errors)];
                document.getElementById('signupMessage').innerHTML = 
                    '<div class="alert alert-danger">' +
                    '<strong><i class="fas fa-exclamation-triangle me-2"></i>Please fix the following errors:</strong>' +
                    '<ul class="mb-0 mt-2">' +
                    uniqueErrors.map(error => `<li>${error}</li>`).join('') +
                    '</ul>' +
                    '</div>';
            }
        }

        // Utility function for debouncing
        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }

        // Clear modal when closed
        document.getElementById('signupModal').addEventListener('hidden.bs.modal', function() {
            document.getElementById('signupMessage').innerHTML = '';
            document.getElementById('usernameFeedback').innerHTML = '';
            document.getElementById('fullNameFeedback').innerHTML = '';
            document.getElementById('passwordFeedback').innerHTML = '';
            document.getElementById('confirmFeedback').innerHTML = '';
        });
    </script>
</body>
</html>