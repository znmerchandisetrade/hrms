<?php
class Database {
    private $host = "localhost";
    private $db_name = "leave_management_system";
    private $username = "root";
    private $password = "";
    public $conn;
    private $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_PERSISTENT => true, // Persistent connections for better performance
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
        PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
    ];

    public function getConnection() {
        if ($this->conn === null) {
            try {
                $this->conn = new PDO(
                    "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                    $this->username,
                    $this->password,
                    $this->options
                );
            } catch(PDOException $exception) {
                error_log("Database connection error: " . $exception->getMessage());
                throw new Exception("Database connection failed");
            }
        }
        return $this->conn;
    }

    public function closeConnection() {
        $this->conn = null;
    }
}
?>