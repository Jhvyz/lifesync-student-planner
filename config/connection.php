<?php
class Database {
    private $host = "localhost";
    private $db_name = "lifesync_db";
    private $username = "root";
    private $password = "";
    public $conn;

    public function __construct() {
        $requestHost = strtolower((string)parse_url(
            "http://" . ($_SERVER['HTTP_HOST'] ?? 'localhost'),
            PHP_URL_HOST
        ));

        if (in_array($requestHost, ['localhost', '127.0.0.1'], true)) {
            return;
        }

        $this->host = "sql208.infinityfree.com";
        $this->db_name = "if0_43233927_lifesync_db";
        $this->username = "if0_43233927";
        $this->password = "YOUR_INFINITYFREE_MYSQL_PASSWORD";
    }

    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }

        return $this->conn;
    }
}