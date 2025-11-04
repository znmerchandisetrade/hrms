<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role_name'] == 'Employee')) {
    header("Location: index.php");
    exit();
}

include_once 'config/Database.php';
include_once 'models/User.php';
include_once 'models/Reference.php';

$database = new Database();
$db = $database->getConnection();

$user = new User($db);
$reference = new Reference($db);

// Get filter parameters
$filters = [];
if (isset($_GET['search'])) {
    $filters['search'] = $_GET['search'];
}
if (isset($_GET['role_id'])) {
    $filters['role_id'] = $_GET['role_id'];
}
if (isset($_GET['department_id'])) {
    $filters['department_id'] = $_GET['department_id'];
}

// Get all users with filters
$users = $user->getAllWithFilters($filters);
$roles = $reference->getRoles();
$departments = $reference->getDepartments();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Leave Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <?php include 'includes/header.php'; ?>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Page Header -->
        <div class="mb-8">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">User Management</h1>
                    <p class="text-gray-600">Manage all system users</p>
                </div>
                <div class="flex space-x-3">
                    <a href="dashboard.php" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition duration-300 text-sm font-medium">
                        <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
                    </a>
                 
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Filters</h3>
            <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                    <input type="text" name="search" value="<?php echo $_GET['search'] ?? ''; ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           placeholder="Search by name or username">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                    <select name="role_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">All Roles</option>
                        <?php while ($role = $roles->fetch(PDO::FETCH_ASSOC)): ?>
                        <option value="<?php echo $role['id']; ?>" <?php echo (isset($_GET['role_id']) && $_GET['role_id'] == $role['id']) ? 'selected' : ''; ?>>
                            <?php echo $role['role_name']; ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Department</label>
                    <select name="department_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">All Departments</option>
                        <?php while ($dept = $departments->fetch(PDO::FETCH_ASSOC)): ?>
                        <option value="<?php echo $dept['id']; ?>" <?php echo (isset($_GET['department_id']) && $_GET['department_id'] == $dept['id']) ? 'selected' : ''; ?>>
                            <?php echo $dept['department_name']; ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="md:col-span-4 flex space-x-3">
                    <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition duration-300 font-medium">
                        <i class="fas fa-filter mr-2"></i>Apply Filters
                    </button>
                    <a href="users.php" class="bg-gray-600 text-white px-6 py-2 rounded-lg hover:bg-gray-700 transition duration-300 font-medium">
                        <i class="fas fa-redo mr-2"></i>Reset
                    </a>
                </div>
            </form>
        </div>

        <!-- Users Table -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-lg font-semibold text-gray-800">All Users</h2>
                <span class="text-sm text-gray-500"><?php echo $users->rowCount(); ?> users found</span>
            </div>
            
            <?php if ($users->rowCount() > 0): ?>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200">
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Username</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Store</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date Hired</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php while ($row = $users->fetch(PDO::FETCH_ASSOC)): ?>
                        <tr class="hover:bg-gray-50 transition duration-150">
                           <td class="px-4 py-3">
    <div class="flex items-center space-x-3">
        <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center overflow-hidden border border-blue-200">
            <?php 
            $image_path = 'uploads/profiles/';
            if (!empty($row['profile_image']) && $row['profile_image'] != 'default-avatar.png' && file_exists($image_path . $row['profile_image'])): 
            ?>
                <img src="<?php echo $image_path . $row['profile_image']; ?>" alt="Profile" class="w-full h-full object-cover">
            <?php else: ?>
                <i class="fas fa-user text-blue-600 text-sm"></i>
            <?php endif; ?>
        </div>
        <div>
            <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($row['full_name']); ?></p>
            <p class="text-xs text-gray-500"><?php echo htmlspecialchars($row['company_name']); ?></p>
        </div>
    </div>
</td>
                            <td class="px-4 py-3 text-sm text-gray-900"><?php echo htmlspecialchars($row['username']); ?></td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                    <?php echo $row['role_name'] == 'HR Manager' ? 'bg-red-100 text-red-800' : 
                                           ($row['role_name'] == 'Store Manager' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800'); ?>">
                                    <?php echo htmlspecialchars($row['role_name']); ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-900"><?php echo htmlspecialchars($row['department_name']); ?></td>
                            <td class="px-4 py-3 text-sm text-gray-900"><?php echo htmlspecialchars($row['store_name']); ?></td>
                            <td class="px-4 py-3 text-sm text-gray-500">
                                <?php echo date('M j, Y', strtotime($row['date_hired'])); ?>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex space-x-2">
                                    <button onclick="viewEmployeeDetails(<?php echo $row['id']; ?>)" 
                                            class="text-blue-600 hover:text-blue-900 text-sm font-medium">
                                        <i class="fas fa-eye mr-1"></i>View
                                    </button>
                                    <button onclick="openEditUserModal(<?php echo $row['id']; ?>)" 
                                            class="text-green-600 hover:text-green-900 text-sm font-medium">
                                        <i class="fas fa-edit mr-1"></i>Edit
                                    </button>
                                    <button onclick="deleteUser(<?php echo $row['id']; ?>)" 
                                            class="text-red-600 hover:text-red-900 text-sm font-medium">
                                        <i class="fas fa-trash mr-1"></i>Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="text-center py-8">
                <i class="fas fa-users text-gray-300 text-4xl mb-3"></i>
                <p class="text-gray-500">No users found.</p>
            </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Create User Modal -->
    <div id="userModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center p-4 z-50">
        <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="p-6 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h3 class="text-lg font-semibold text-gray-800">Create New User</h3>
                    <button onclick="closeUserModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            
            <form id="userForm" action="create_user.php" method="POST" class="p-6 space-y-4" enctype="multipart/form-data">
                <!-- Form fields same as before -->
                <?php include 'user_form_fields.php'; ?>
            </form>
        </div>
    </div>

   <script>
// Delete User Function
function deleteUser(userId) {
    if (confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
        window.location.href = 'delete_user.php?id=' + userId;
    }
}

// Edit User Function
function openEditUserModal(userId) {
    fetch('get_user_data.php?id=' + userId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const user = data.user;
                const dropdowns = data.dropdowns;
                
                // Create edit modal
                const modal = document.createElement('div');
                modal.innerHTML = `
                    <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
                        <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                            <div class="p-6 border-b border-gray-200">
                                <div class="flex justify-between items-center">
                                    <h3 class="text-lg font-semibold text-gray-800">Edit User: ${user.full_name}</h3>
                                    <button onclick="this.parentElement.parentElement.parentElement.parentElement.remove()" class="text-gray-400 hover:text-gray-600">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                            <form action="edit_user.php" method="POST" class="p-6 space-y-4" enctype="multipart/form-data">
                                <input type="hidden" name="id" value="${user.id}">
                                <input type="hidden" name="existing_profile_image" value="${user.profile_image || ''}">
                                
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Username *</label>
                                        <input type="text" name="username" value="${user.username}" required 
                                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Full Name *</label>
                                        <input type="text" name="full_name" value="${user.full_name}" required 
                                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Profile Image</label>
                                    <input type="file" name="profile_image" accept="image/*"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    <p class="text-xs text-gray-500 mt-1">Leave empty to keep current image</p>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Company *</label>
                                        <select name="company_id" required 
                                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                            <option value="">Select Company</option>
                                            ${dropdowns.companies}
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Brand *</label>
                                        <select name="brand_id" required 
                                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                            <option value="">Select Brand</option>
                                            ${dropdowns.brands}
                                        </select>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Department *</label>
                                        <select name="department_id" required 
                                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                            <option value="">Select Department</option>
                                            ${dropdowns.departments}
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Store *</label>
                                        <select name="store_id" required 
                                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                            <option value="">Select Store</option>
                                            ${dropdowns.stores}
                                        </select>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Role *</label>
                                        <select name="role_id" required 
                                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                            <option value="">Select Role</option>
                                            ${dropdowns.roles}
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Date Hired *</label>
                                        <input type="date" name="date_hired" value="${user.date_hired}" required 
                                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    </div>
                                </div>

                                <div class="flex space-x-3 pt-4">
                                    <button type="button" onclick="this.parentElement.parentElement.parentElement.parentElement.remove()" 
                                            class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition duration-300">
                                        Cancel
                                    </button>
                                    <button type="submit" 
                                            class="flex-1 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition duration-300 font-medium">
                                        <i class="fas fa-save mr-2"></i>Update User
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                `;
                document.body.appendChild(modal);
            } else {
                alert('Error: ' + data.error);
            }
        })
        .catch(error => {
            alert('Error loading user data: ' + error);
        });
}

// View Employee Details
function viewEmployeeDetails(userId) {
    fetch('get_employee_details.php?id=' + userId)
        .then(response => response.text())
        .then(data => {
            const modal = document.createElement('div');
            modal.innerHTML = `
                <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
                    <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                        <div class="p-6 border-b border-gray-200">
                            <div class="flex justify-between items-center">
                                <h3 class="text-lg font-semibold text-gray-800">Employee Details</h3>
                                <button onclick="this.parentElement.parentElement.parentElement.parentElement.remove()" class="text-gray-400 hover:text-gray-600">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                        <div class="p-6">${data}</div>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
        });
}

// Modal functions
function openUserModal() {
    document.getElementById('userModal').classList.remove('hidden');
    document.getElementById('userModal').classList.add('flex');
}

function closeUserModal() {
    document.getElementById('userModal').classList.remove('flex');
    document.getElementById('userModal').classList.add('hidden');
}
</script>
</body>
</html>