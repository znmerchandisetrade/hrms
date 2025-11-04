<?php
// cancel_leave.php
require_once 'includes/session_check.php';
checkAdminAccess(); // Only HR Manager can cancel leaves

include_once 'config/Database.php';
include_once 'models/LeaveHistory.php';

$database = new Database();
$db = $database->getConnection();

$leaveHistory = new LeaveHistory($db);

// Get leave ID from URL
$leave_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$leave_id) {
    $_SESSION['error'] = "Invalid leave application ID.";
    header("Location: leave_history.php");
    exit();
}

// Get leave details for confirmation
$query = "SELECT lh.*, u.full_name as employee_name 
          FROM leave_history lh
          LEFT JOIN users u ON lh.user_id = u.id
          WHERE lh.id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(":id", $leave_id);
$stmt->execute();
$leave = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$leave) {
    $_SESSION['error'] = "Leave application not found.";
    header("Location: leave_history.php");
    exit();
}

// Process cancellation
if ($_POST && isset($_POST['confirm'])) {
    // Delete from leave_history (or mark as cancelled)
    $delete_query = "DELETE FROM leave_history WHERE id = :id";
    $delete_stmt = $db->prepare($delete_query);
    $delete_stmt->bindParam(":id", $leave_id);
    
    if ($delete_stmt->execute()) {
        $_SESSION['success'] = "Leave application cancelled successfully!";
    } else {
        $_SESSION['error'] = "Failed to cancel leave application.";
    }
    
    header("Location: leave_history.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cancel Leave Application - Leave Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <?php include 'includes/header.php'; ?>

    <main class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-gray-800">Cancel Leave Application</h1>
                <a href="leave_history.php" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition duration-300 text-sm font-medium">
                    <i class="fas fa-arrow-left mr-2"></i>Back to History
                </a>
            </div>

            <!-- Confirmation Message -->
            <div class="bg-red-50 border border-red-200 rounded-lg p-6 mb-6">
                <div class="flex items-center mb-4">
                    <i class="fas fa-exclamation-triangle text-red-600 text-2xl mr-3"></i>
                    <h3 class="text-lg font-semibold text-red-800">Warning: This action cannot be undone</h3>
                </div>
                <p class="text-red-700 mb-4">
                    You are about to permanently cancel this leave application. This will remove it from the system history.
                </p>
            </div>

            <!-- Leave Details -->
            <div class="bg-gray-50 rounded-lg p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Leave Application Details</h3>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Employee:</span>
                        <span class="font-semibold"><?php echo htmlspecialchars($leave['employee_name']); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Period:</span>
                        <span><?php echo date('M j, Y', strtotime($leave['start_date'])); ?> - <?php echo date('M j, Y', strtotime($leave['end_date'])); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Reason:</span>
                        <span><?php echo htmlspecialchars($leave['reason']); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Status:</span>
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium <?php echo $leave['status'] == 'approved' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                            <?php echo ucfirst($leave['status']); ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Confirmation Form -->
            <form method="POST">
                <div class="flex space-x-3">
                    <a href="leave_history.php" class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition duration-300 text-center font-medium">
                        <i class="fas fa-times mr-2"></i>Keep Leave
                    </a>
                    <button type="submit" name="confirm" value="1" class="flex-1 bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition duration-300 font-medium">
                        <i class="fas fa-trash mr-2"></i>Confirm Cancellation
                    </button>
                </div>
            </form>
        </div>
    </main>
</body>
</html>