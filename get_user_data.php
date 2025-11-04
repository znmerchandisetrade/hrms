<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role_name'] == 'Employee')) {
    echo json_encode(['error' => 'Access denied']);
    exit();
}

include_once 'config/Database.php';
include_once 'models/User.php';
include_once 'models/Reference.php';

if (isset($_GET['id'])) {
    $database = new Database();
    $db = $database->getConnection();
    
    $user = new User($db);
    $reference = new Reference($db);
    
    $user_data = $user->getById($_GET['id']);
    
    if ($user_data) {
        // Get reference data for dropdowns
        $companies = $reference->getCompanies();
        $brands = $reference->getBrands();
        $departments = $reference->getDepartments();
        $stores = $reference->getStores();
        $roles = $reference->getRoles();
        
        // Prepare dropdown options
        $companies_html = '';
        while ($company = $companies->fetch(PDO::FETCH_ASSOC)) {
            $selected = $company['id'] == $user_data['company_id'] ? 'selected' : '';
            $companies_html .= "<option value='{$company['id']}' $selected>{$company['company_name']}</option>";
        }
        
        $brands_html = '';
        while ($brand = $brands->fetch(PDO::FETCH_ASSOC)) {
            $selected = $brand['id'] == $user_data['brand_id'] ? 'selected' : '';
            $brands_html .= "<option value='{$brand['id']}' $selected>{$brand['brand_name']}</option>";
        }
        
        $departments_html = '';
        while ($dept = $departments->fetch(PDO::FETCH_ASSOC)) {
            $selected = $dept['id'] == $user_data['department_id'] ? 'selected' : '';
            $departments_html .= "<option value='{$dept['id']}' $selected>{$dept['department_name']}</option>";
        }
        
        $stores_html = '';
        while ($store = $stores->fetch(PDO::FETCH_ASSOC)) {
            $selected = $store['id'] == $user_data['store_id'] ? 'selected' : '';
            $stores_html .= "<option value='{$store['id']}' $selected>{$store['store_name']}</option>";
        }
        
        $roles_html = '';
        while ($role = $roles->fetch(PDO::FETCH_ASSOC)) {
            $selected = $role['id'] == $user_data['role_id'] ? 'selected' : '';
            $roles_html .= "<option value='{$role['id']}' $selected>{$role['role_name']}</option>";
        }
        
        echo json_encode([
            'success' => true,
            'user' => $user_data,
            'dropdowns' => [
                'companies' => $companies_html,
                'brands' => $brands_html,
                'departments' => $departments_html,
                'stores' => $stores_html,
                'roles' => $roles_html
            ]
        ]);
    } else {
        echo json_encode(['error' => 'User not found']);
    }
} else {
    echo json_encode(['error' => 'No user ID provided']);
}
?>