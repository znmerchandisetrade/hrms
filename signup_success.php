<?php
// Check if this is a valid success redirect
if (!isset($_GET['success']) || $_GET['success'] !== 'true') {
    header("Location: index.php");
    exit();
}

$full_name = htmlspecialchars($_GET['full_name'] ?? '');
$username = htmlspecialchars($_GET['username'] ?? '');
$timestamp = $_GET['timestamp'] ?? time();

// Format date and time
$date = date('F j, Y', $timestamp);
$time = date('g:i A', $timestamp);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Successful - Leave Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .success-card {
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.95);
        }
        .pulse-animation {
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
    </style>
</head>
<body class="gradient-bg min-h-screen flex items-center justify-center p-4">
    <div class="success-card rounded-2xl shadow-2xl p-8 w-full max-w-2xl border border-white border-opacity-20">
        <div class="text-center mb-8">
            <div class="pulse-animation inline-block">
                <i class="fas fa-check-circle text-green-500 text-6xl mb-4"></i>
            </div>
            <h1 class="text-4xl font-bold text-gray-800">Registration Successful!</h1>
            <p class="text-gray-600 text-xl mt-2">Welcome to Leave Management System</p>
        </div>

        <div class="bg-green-50 border border-green-200 rounded-xl p-6 mb-6">
            <div class="text-center">
                <h2 class="text-2xl font-semibold text-green-800 mb-4">
                    <i class="fas fa-user-check mr-2"></i>Account Created Successfully
                </h2>
                <p class="text-green-700 text-lg">
                    Your account has been created and is ready to use.
                </p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <div class="bg-white border border-gray-200 rounded-xl p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-user-circle text-blue-500 mr-2"></i>
                    Account Information
                </h3>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-gray-600 font-medium">Full Name:</span>
                        <span class="text-gray-800 font-semibold"><?php echo $full_name; ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600 font-medium">Username:</span>
                        <span class="text-gray-800 font-semibold"><?php echo $username; ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600 font-medium">Status:</span>
                        <span class="bg-green-100 text-green-800 px-2 py-1 rounded-full text-sm font-medium">
                            <i class="fas fa-check mr-1"></i>Active
                        </span>
                    </div>
                </div>
            </div>

            <div class="bg-white border border-gray-200 rounded-xl p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-calendar-alt text-purple-500 mr-2"></i>
                    Registration Details
                </h3>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-gray-600 font-medium">Date Registered:</span>
                        <span class="text-gray-800 font-semibold"><?php echo $date; ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600 font-medium">Time:</span>
                        <span class="text-gray-800 font-semibold"><?php echo $time; ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600 font-medium">Account Type:</span>
                        <span class="text-gray-800 font-semibold">Employee</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-blue-50 border border-blue-200 rounded-xl p-6 mb-8">
            <div class="flex items-start">
                <i class="fas fa-info-circle text-blue-500 text-xl mt-1 mr-3"></i>
                <div>
                    <h4 class="text-lg font-semibold text-blue-800 mb-2">What's Next?</h4>
                    <p class="text-blue-700">
                        You can now login to the Leave Management System using your username and password. 
                        Once logged in, you'll be able to apply for leaves, view your leave history, and manage your profile.
                    </p>
                </div>
            </div>
        </div>

        <div class="text-center">
            <a href="index.php" 
               class="inline-flex items-center justify-center px-8 py-4 bg-green-600 text-white font-semibold rounded-xl hover:bg-green-700 transition duration-300 transform hover:scale-105 shadow-lg text-lg">
                <i class="fas fa-sign-in-alt mr-3"></i>
                Back to Login Page
            </a>
            
            <p class="text-gray-600 mt-4">
                Ready to access your account? Click the button above to proceed to login.
            </p>
        </div>

        <div class="mt-8 pt-6 border-t border-gray-200 text-center">
            <p class="text-gray-500 text-sm">
                <i class="fas fa-shield-alt mr-1"></i>
                Your account information is secure and protected.
            </p>
        </div>
    </div>

    <script>
        // Add some interactive effects
        document.addEventListener('DOMContentLoaded', function() {
            const successIcon = document.querySelector('.fa-check-circle');
            successIcon.addEventListener('mouseover', function() {
                this.style.transform = 'scale(1.1)';
            });
            successIcon.addEventListener('mouseout', function() {
                this.style.transform = 'scale(1)';
            });
        });
    </script>
</body>
</html>