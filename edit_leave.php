<?php
// edit_leave.php
require_once 'includes/session_check.php';
checkManagerAccess();

include_once 'config/Database.php';

$database = new Database();
$db = $database->getConnection();

// Get leave ID from URL - this should be leave_application_id
$leave_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$leave_id) {
    $_SESSION['error'] = "Invalid leave application ID.";
    header("Location: leave_history.php");
    exit();
}

// Get leave details - join both tables to get all data
$query = "SELECT la.*, lh.id as history_id, u.full_name as employee_name
          FROM leave_applications la
          LEFT JOIN leave_history lh ON la.id = lh.leave_application_id
          LEFT JOIN users u ON la.user_id = u.id
          WHERE la.id = :id AND la.processed = 1";
$stmt = $db->prepare($query);
$stmt->bindParam(":id", $leave_id);
$stmt->execute();
$leave = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$leave) {
    $_SESSION['error'] = "Leave application not found or not processed.";
    header("Location: leave_history.php");
    exit();
}

// Process form submission
if ($_POST) {
    $status = $_POST['status'];
    $manager_notes = $_POST['manager_notes'] ?? '';
    
    // Validate status
    if (!in_array($status, ['approved', 'rejected'])) {
        $_SESSION['error'] = "Invalid status selected.";
        header("Location: edit_leave.php?id=" . $leave_id);
        exit();
    }
    
    error_log("Editing leave ID: " . $leave_id . " to status: " . $status);
    
    try {
        // Start transaction
        $db->beginTransaction();
        
        // 1. Update leave_applications table
        $app_query = "UPDATE leave_applications 
                     SET status = :status, 
                         manager_notes = :manager_notes,
                         approved_at = NOW()
                     WHERE id = :id";
        
        $app_stmt = $db->prepare($app_query);
        $app_stmt->bindParam(":status", $status);
        $app_stmt->bindParam(":manager_notes", $manager_notes);
        $app_stmt->bindParam(":id", $leave_id);
        $app_result = $app_stmt->execute();
        $app_rows = $app_stmt->rowCount();
        
        error_log("Leave applications updated - Rows: " . $app_rows);
        
        if (!$app_result) {
            throw new Exception("Failed to update leave application");
        }
        
        // 2. Update leave_history table if it exists
        if (!empty($leave['history_id'])) {
            $history_query = "UPDATE leave_history 
                             SET status = :status, 
                                 manager_notes = :manager_notes,
                                 created_at = NOW()
                             WHERE id = :history_id";
            
            $history_stmt = $db->prepare($history_query);
            $history_stmt->bindParam(":status", $status);
            $history_stmt->bindParam(":manager_notes", $manager_notes);
            $history_stmt->bindParam(":history_id", $leave['history_id']);
            $history_result = $history_stmt->execute();
            $history_rows = $history_stmt->rowCount();
            
            error_log("Leave history updated - Rows: " . $history_rows);
        }
        
        // Commit transaction
        $db->commit();
        
        $_SESSION['success'] = "Leave application updated successfully!";
        header("Location: leave_history.php");
        exit();
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $db->rollBack();
        error_log("Edit leave error: " . $e->getMessage());
        $_SESSION['error'] = "Failed to update leave application: " . $e->getMessage();
        header("Location: edit_leave.php?id=" . $leave_id);
        exit();
    }
}
?>

<!-- HTML FORM REMAINS THE SAME AS BEFORE -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Leave Application - Leave Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <?php include 'includes/header.php'; ?>

    <main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Edit Leave Application</h1>
                    <p class="text-gray-600">Update leave application details</p>
                </div>
                <a href="leave_history.php" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition duration-300 text-sm font-medium">
                    <i class="fas fa-arrow-left mr-2"></i>Back to History
                </a>
            </div>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6" role="alert">
                    <span class="block sm:inline"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></span>
                </div>
            <?php endif; ?>

            <div class="bg-gray-50 rounded-lg p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Current Leave Details</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm font-medium text-gray-600">Employee</label>
                        <p class="text-gray-800 font-semibold"><?php echo htmlspecialchars($leave['employee_name']); ?></p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-600">Period</label>
                        <p class="text-gray-800"><?php echo date('M j, Y', strtotime($leave['start_date'])); ?> - <?php echo date('M j, Y', strtotime($leave['end_date'])); ?></p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-600">Reason</label>
                        <p class="text-gray-800"><?php echo htmlspecialchars($leave['reason']); ?></p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-600">Days</label>
                        <p class="text-gray-800"><?php echo $leave['day_off_count']; ?> days</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-600">Current Status</label>
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium <?php echo $leave['status'] == 'approved' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                            <?php echo ucfirst($leave['status']); ?>
                        </span>
                    </div>
                </div>
            </div>

            <form method="POST" class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">New Status *</label>
                    <select name="status" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="approved" <?php echo $leave['status'] == 'approved' ? 'selected' : ''; ?>>Approved</option>
                        <option value="rejected" <?php echo $leave['status'] == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Manager's Notes</label>
                    <textarea name="manager_notes" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Update manager's notes..."><?php echo htmlspecialchars($leave['manager_notes'] ?? ''); ?></textarea>
                </div>

                <div class="flex space-x-3 pt-4">
                    <a href="leave_history.php" class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition duration-300 text-center font-medium">
                        Cancel
                    </a>
                    <button type="submit" class="flex-1 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition duration-300 font-medium">
                        <i class="fas fa-save mr-2"></i>Update Leave
                    </button>
                </div>
            </form>
        </div>
    </main>
</body>
</html>