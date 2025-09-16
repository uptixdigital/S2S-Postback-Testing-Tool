<?php
/**
 * Database Configuration
 * S2S Postback Testing System
 */

class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $conn;

    public function __construct() {
        $this->host = $_ENV['DB_HOST'] ?? 'localhost';
        $this->db_name = $_ENV['DB_NAME'] ?? 's2s_tracker';
        $this->username = $_ENV['DB_USER'] ?? 'root';
        $this->password = $_ENV['DB_PASS'] ?? '';
    }

    public function getConnection() {
        $this->conn = null;
        
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch(PDOException $exception) {
            throw new Exception("Connection error: " . $exception->getMessage());
        }
        
        return $this->conn;
    }

    public function createTables() {
        $sql = "
        CREATE TABLE IF NOT EXISTS offers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            type ENUM('sweepstakes', 'survey', 'download', 'subscription', 'custom') DEFAULT 'custom',
            payout DECIMAL(10,2) DEFAULT 0.00,
            status ENUM('active', 'inactive', 'paused') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS conversions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            transaction_id VARCHAR(100) UNIQUE NOT NULL,
            offer_id INT,
            name VARCHAR(255),
            email VARCHAR(255),
            ip_address VARCHAR(45),
            country VARCHAR(100),
            city VARCHAR(100),
            region VARCHAR(100),
            timezone VARCHAR(100),
            isp VARCHAR(255),
            device VARCHAR(50),
            os VARCHAR(50),
            browser VARCHAR(50),
            screen_resolution VARCHAR(20),
            language VARCHAR(10),
            user_agent TEXT,
            referrer TEXT,
            goal VARCHAR(100),
            payout DECIMAL(10,2) DEFAULT 0.00,
            status ENUM('pending', 'converted', 'failed') DEFAULT 'pending',
            postback_sent BOOLEAN DEFAULT FALSE,
            postback_response TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (offer_id) REFERENCES offers(id) ON DELETE SET NULL
        );

        CREATE TABLE IF NOT EXISTS postback_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            transaction_id VARCHAR(100),
            postback_url TEXT,
            request_data TEXT,
            response_code INT,
            response_body TEXT,
            response_time INT,
            status ENUM('success', 'failed', 'timeout') DEFAULT 'failed',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(100) UNIQUE NOT NULL,
            setting_value TEXT,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS test_results (
            id INT AUTO_INCREMENT PRIMARY KEY,
            test_name VARCHAR(255),
            postback_url TEXT,
            test_data TEXT,
            response_code INT,
            response_time INT,
            success BOOLEAN DEFAULT FALSE,
            error_message TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS analytics (
            id INT AUTO_INCREMENT PRIMARY KEY,
            date DATE,
            clicks INT DEFAULT 0,
            conversions INT DEFAULT 0,
            revenue DECIMAL(10,2) DEFAULT 0.00,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_date (date)
        );
        ";

        try {
            $this->conn->exec($sql);
            return true;
        } catch(PDOException $e) {
            throw new Exception("Error creating tables: " . $e->getMessage());
        }
    }

    public function insertDefaultData() {
        // Insert default settings
        $settings = [
            ['default_postback_url', 'https://tr.optimawall.com/pbtr'],
            ['default_transaction_param', 'transaction_id'],
            ['default_goal_param', 'goal'],
            ['default_payout_param', 'payout'],
            ['timezone', 'UTC'],
            ['currency', 'USD']
        ];

        $stmt = $this->conn->prepare("INSERT IGNORE INTO settings (setting_key, setting_value) VALUES (?, ?)");
        foreach ($settings as $setting) {
            $stmt->execute($setting);
        }

        // Insert default offers
        $offers = [
            [
                'Win $1000 Cash Prize!',
                'Enter our exclusive sweepstakes and win amazing cash prizes! Complete the form to participate.',
                'sweepstakes',
                1.50
            ],
            [
                'Quick Survey - $5 Reward',
                'Take a quick 5-minute survey and earn $5 instantly. Share your opinions and get rewarded.',
                'survey',
                0.75
            ],
            [
                'Download App - Get $3',
                'Download our featured app and get $3 credited to your account. Simple and fast!',
                'download',
                2.25
            ],
            [
                'Free Trial - Premium Service',
                'Start your free trial of our premium service. Cancel anytime, no commitment required.',
                'subscription',
                4.00
            ],
            [
                'Gift Card Giveaway',
                'Win a $50 gift card to your favorite store. Enter now for your chance to win!',
                'sweepstakes',
                2.00
            ]
        ];

        $stmt = $this->conn->prepare("INSERT IGNORE INTO offers (title, description, type, payout) VALUES (?, ?, ?, ?)");
        foreach ($offers as $offer) {
            $stmt->execute($offer);
        }
    }
}
?>