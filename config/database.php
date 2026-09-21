<?php
// config/database.php

class Database {
    private static $instance = null;
    private $conn;

    private $host = "localhost";
    private $db_name = "school_repair";
    private $username = "root";
    private $password = "";

    // Constructor เป็น private เพื่อป้องกันการ new Object โดยตรงจากภายนอก
    private function __construct() {
        try {
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4";
            
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // แสดงข้อผิดพลาดเป็น Exception
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,     // ดึงข้อมูลเป็น Array Key-Value
                PDO::ATTR_EMULATE_PREPARES   => false,                 // ป้องกัน SQL Injection แบบ Native
            ];

            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
            
        } catch (PDOException $exception) {
            error_log("Database Connection Error: " . $exception->getMessage());
            throw new Exception("ไม่สามารถเชื่อมต่อฐานข้อมูลได้ กรุณาลองใหม่อีกครั้งในภายหลัง");
        }
    }

    /**
     * ดึงอินสแตนซ์ตัวเชื่อมต่อฐานข้อมูล PDO
     * @return PDO
     */
    public static function getInstance(): PDO {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance->conn;
    }
}
?>