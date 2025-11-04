<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role_name'] == 'Employee')) {
    echo '<div class="text-center py-8"><p class="text-red-500">Access denied.</p></div>';
    exit();
}

include_once 'config/Database.php';
include_once 'models/User.php';

if (isset($_GET['id'])) {
    $database = new Database();
    $db = $database->getConnection();
    
    $user = new User($db);
    $employee = $user->getById($_GET['id']);
    
    if ($employee) {
        // Determine image path
        $image_path = 'uploads/profiles/';
        $default_avatar = '<i class="fas fa-user text-blue-600 text-3xl"></i>';
        
        $profile_image = '';
        if (!empty($employee['profile_image']) && $employee['profile_image'] != 'default-avatar.png') {
            $full_image_path = $image_path . $employee['profile_image'];
            if (file_exists($full_image_path)) {
                $profile_image = '<img src="' . $full_image_path . '" alt="Profile" class="w-full h-full object-cover">';
            } else {
                $profile_image = $default_avatar;
            }
        } else {
            $profile_image = $default_avatar;
        }
        ?>
        <div class="space-y-6">
            <!-- Profile Header -->
            <div class="flex items-center space-x-6">
                <div class="w-24 h-24 bg-blue-100 rounded-full flex items-center justify-center overflow-hidden border-2 border-blue-200">
                    <?php echo $profile_image; ?>
                </div>
                <div>
                    <h3 class="text-2xl font-bold text-gray-800"><?php echo htmlspecialchars($employee['full_name']); ?></h3>
                    <p class="text-gray-600"><?php echo htmlspecialchars($employee['role_name']); ?></p>
                    <p class="text-sm text-gray-500">Username: <?php echo htmlspecialchars($employee['username']); ?></p>
                </div>
            </div>

            <!-- Employee Details -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-4">
                    <h4 class="text-lg font-semibold text-gray-800 border-b pb-2">Employment Details</h4>
                    
                    <div class="space-y-3">
                        <div>
                            <label class="text-sm font-medium text-gray-500">Company</label>
                            <p class="text-gray-800"><?php echo htmlspecialchars($employee['company_name'] ?? 'Not set'); ?></p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-500">Brand</label>
                            <p class="text-gray-800"><?php echo htmlspecialchars($employee['brand_name'] ?? 'Not set'); ?></p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-500">Store</label>
                            <p class="text-gray-800"><?php echo htmlspecialchars($employee['store_name'] ?? 'Not set'); ?></p>
                        </div>
                    </div>
                </div>

                <div class="space-y-4">
                    <h4 class="text-lg font-semibold text-gray-800 border-b pb-2">Personal Details</h4>
                    
                    <div class="space-y-3">
                        <div>
                            <label class="text-sm font-medium text-gray-500">Department</label>
                            <p class="text-gray-800"><?php echo htmlspecialchars($employee['department_name'] ?? 'Not set'); ?></p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-500">Role</label>
                            <p class="text-gray-800"><?php echo htmlspecialchars($employee['role_name'] ?? 'Not set'); ?></p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-500">Date Hired</label>
                            <p class="text-gray-800"><?php echo date('F j, Y', strtotime($employee['date_hired'])); ?></p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-500">Account Created</label>
                            <p class="text-gray-800"><?php echo date('F j, Y g:i A', strtotime($employee['created_at'])); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex space-x-3 pt-6 border-t border-gray-200">
                <button onclick="openEditUserModal(<?php echo $employee['id']; ?>)" 
                        class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition duration-300 text-sm font-medium">
                    <i class="fas fa-edit mr-2"></i>Edit Employee
                </button>
                <button onclick="deleteEmployee(<?php echo $employee['id']; ?>)" 
                        class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition duration-300 text-sm font-medium">
                    <i class="fas fa-trash mr-2"></i>Delete Employee
                </button>
            </div>
        </div>

        <script>
            function deleteEmployee(userId) {
                if (confirm('Are you sure you want to delete this employee? This action cannot be undone.')) {
                    window.location.href = 'delete_user.php?id=' + userId;
                }
            }

            function openEditUserModal(userId) {
                // Close current modal first
                const employeeModal = document.getElementById('employeeModal');
                if (employeeModal) {
                    employeeModal.classList.remove('flex');
                    employeeModal.classList.add('hidden');
                }
                
                // Open edit modal (you'll need to implement this)
                setTimeout(() => {
                    alert('Edit functionality for user ID: ' + userId + ' will be implemented here.');
                    // You can implement a separate edit modal similar to create user modal
                }, 300);
            }
        </script>
        <?php
    } else {
        echo '<div class="text-center py-8"><p class="text-gray-500">Employee not found.</p></div>';
    }
} else {
    echo '<div class="text-center py-8"><p class="text-gray-500">No employee ID provided.</p></div>';
}
?>