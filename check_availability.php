<?php
// check_availability.php
header('Content-Type: application/json');

include_once 'config/Database.php';
include_once 'models/User.php';

if (isset($_GET['type']) && isset($_GET['value'])) {
    $database = new Database();
    $db = $database->getConnection();
    $user = new User($db);
    
    $type = $_GET['type'];
    $value = trim($_GET['value']);
    
    $available = false;
    
    switch ($type) {
        case 'username':
            $available = !$user->usernameExists($value);
            break;
        case 'fullname':
            $available = !$user->fullNameExists($value);
            break;
    }
    
    echo json_encode(['available' => $available]);
} else {
    echo json_encode(['available' => false]);
}
?>