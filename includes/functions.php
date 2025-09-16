<?php
/**
 * S2S Tracker - Core Functions
 * Utility functions and helpers
 */

// Prevent direct access
if (!defined('INSTALLED')) {
    die('Access denied');
}

/**
 * Get user's IP address
 */
function getUserIP() {
    $ipKeys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];
    
    foreach ($ipKeys as $key) {
        if (array_key_exists($key, $_SERVER) === true) {
            $ip = $_SERVER[$key];
            if (strpos($ip, ',') !== false) {
                $ip = explode(',', $ip)[0];
            }
            $ip = trim($ip);
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
        }
    }
    
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/**
 * Get user's location data from IP
 */
function getLocationData($ip) {
    try {
        // Use ipapi.co for geolocation
        $response = file_get_contents("https://ipapi.co/{$ip}/json/");
        if ($response) {
            return json_decode($response, true);
        }
    } catch (Exception $e) {
        error_log("Geolocation error: " . $e->getMessage());
    }
    
    return [
        'ip' => $ip,
        'country' => 'Unknown',
        'city' => 'Unknown',
        'region' => 'Unknown',
        'timezone' => 'UTC',
        'org' => 'Unknown'
    ];
}

/**
 * Get device information from User Agent
 */
function getDeviceInfo($userAgent) {
    $device = 'Desktop';
    $os = 'Unknown';
    $browser = 'Unknown';
    
    // Device detection
    if (preg_match('/tablet|ipad|playbook|silk/i', $userAgent)) {
        $device = 'Tablet';
    } elseif (preg_match('/mobile|iphone|ipod|android|blackberry|opera|mini|windows\sce|palm|smartphone|iemobile/i', $userAgent)) {
        $device = 'Mobile';
    }
    
    // OS detection
    if (preg_match('/windows nt 10/i', $userAgent)) $os = 'Windows 10';
    elseif (preg_match('/windows nt 6.3/i', $userAgent)) $os = 'Windows 8.1';
    elseif (preg_match('/windows nt 6.2/i', $userAgent)) $os = 'Windows 8';
    elseif (preg_match('/windows nt 6.1/i', $userAgent)) $os = 'Windows 7';
    elseif (preg_match('/windows nt/i', $userAgent)) $os = 'Windows';
    elseif (preg_match('/macintosh|mac os x/i', $userAgent)) $os = 'macOS';
    elseif (preg_match('/linux/i', $userAgent)) $os = 'Linux';
    elseif (preg_match('/android/i', $userAgent)) $os = 'Android';
    elseif (preg_match('/iphone|ipad|ipod/i', $userAgent)) $os = 'iOS';
    
    // Browser detection
    if (preg_match('/edg/i', $userAgent)) $browser = 'Edge';
    elseif (preg_match('/chrome/i', $userAgent)) $browser = 'Chrome';
    elseif (preg_match('/firefox/i', $userAgent)) $browser = 'Firefox';
    elseif (preg_match('/safari/i', $userAgent)) $browser = 'Safari';
    elseif (preg_match('/opera/i', $userAgent)) $browser = 'Opera';
    
    return [
        'device' => $device,
        'os' => $os,
        'browser' => $browser
    ];
}

/**
 * Generate unique transaction ID
 */
function generateTransactionId() {
    return 'txn_' . time() . '_' . substr(md5(uniqid(rand(), true)), 0, 8);
}

/**
 * Send postback request
 */
function sendPostback($url, $data, $timeout = 10) {
    $startTime = microtime(true);
    
    try {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($data),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT => 'S2S-Tracker/1.0',
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded',
                'Accept: */*'
            ]
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        $endTime = microtime(true);
        $responseTime = round(($endTime - $startTime) * 1000); // Convert to milliseconds
        
        if ($error) {
            throw new Exception("cURL Error: " . $error);
        }
        
        return [
            'success' => $httpCode >= 200 && $httpCode < 300,
            'http_code' => $httpCode,
            'response' => $response,
            'response_time' => $responseTime,
            'error' => null
        ];
        
    } catch (Exception $e) {
        $endTime = microtime(true);
        $responseTime = round(($endTime - $startTime) * 1000);
        
        return [
            'success' => false,
            'http_code' => 0,
            'response' => null,
            'response_time' => $responseTime,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Log postback attempt
 */
function logPostback($transactionId, $url, $data, $result) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO postback_logs (transaction_id, postback_url, request_data, response_code, response_body, response_time, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $status = $result['success'] ? 'success' : 'failed';
        if ($result['response_time'] > 5000) $status = 'timeout';
        
        $stmt->execute([
            $transactionId,
            $url,
            json_encode($data),
            $result['http_code'],
            $result['response'],
            $result['response_time'],
            $status
        ]);
        
        return true;
    } catch (Exception $e) {
        error_log("Postback logging error: " . $e->getMessage());
        return false;
    }
}

/**
 * Format time ago
 */
function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'just now';
    if ($time < 3600) return floor($time/60) . ' minutes ago';
    if ($time < 86400) return floor($time/3600) . ' hours ago';
    if ($time < 2592000) return floor($time/86400) . ' days ago';
    if ($time < 31536000) return floor($time/2592000) . ' months ago';
    
    return floor($time/31536000) . ' years ago';
}

/**
 * Sanitize input data
 */
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate email address
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate URL
 */
function validateUrl($url) {
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

/**
 * Get setting value
 */
function getSetting($key, $default = null) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetch();
        
        return $result ? $result['setting_value'] : $default;
    } catch (Exception $e) {
        error_log("Settings error: " . $e->getMessage());
        return $default;
    }
}

/**
 * Update setting value
 */
function updateSetting($key, $value) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) 
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
        ");
        return $stmt->execute([$key, $value]);
    } catch (Exception $e) {
        error_log("Settings update error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get offer by ID
 */
function getOffer($id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM offers WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    } catch (Exception $e) {
        error_log("Get offer error: " . $e->getMessage());
        return null;
    }
}

/**
 * Get all offers
 */
function getAllOffers($status = null) {
    global $pdo;
    
    try {
        $sql = "SELECT * FROM offers";
        $params = [];
        
        if ($status) {
            $sql .= " WHERE status = ?";
            $params[] = $status;
        }
        
        $sql .= " ORDER BY created_at DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Get offers error: " . $e->getMessage());
        return [];
    }
}

/**
 * Create new offer
 */
function createOffer($data) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO offers (title, description, type, payout, status) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        return $stmt->execute([
            $data['title'],
            $data['description'],
            $data['type'],
            $data['payout'],
            $data['status'] ?? 'active'
        ]);
    } catch (Exception $e) {
        error_log("Create offer error: " . $e->getMessage());
        return false;
    }
}

/**
 * Record conversion
 */
function recordConversion($data) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO conversions (
                transaction_id, offer_id, name, email, ip_address, country, city, region, 
                timezone, isp, device, os, browser, screen_resolution, language, 
                user_agent, referrer, goal, payout, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        return $stmt->execute([
            $data['transaction_id'],
            $data['offer_id'],
            $data['name'],
            $data['email'],
            $data['ip_address'],
            $data['country'],
            $data['city'],
            $data['region'],
            $data['timezone'],
            $data['isp'],
            $data['device'],
            $data['os'],
            $data['browser'],
            $data['screen_resolution'],
            $data['language'],
            $data['user_agent'],
            $data['referrer'],
            $data['goal'],
            $data['payout'],
            $data['status'] ?? 'converted'
        ]);
    } catch (Exception $e) {
        error_log("Record conversion error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get conversion by transaction ID
 */
function getConversion($transactionId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM conversions WHERE transaction_id = ?");
        $stmt->execute([$transactionId]);
        return $stmt->fetch();
    } catch (Exception $e) {
        error_log("Get conversion error: " . $e->getMessage());
        return null;
    }
}

/**
 * Update conversion status
 */
function updateConversionStatus($transactionId, $status, $postbackSent = false, $postbackResponse = null) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            UPDATE conversions 
            SET status = ?, postback_sent = ?, postback_response = ? 
            WHERE transaction_id = ?
        ");
        
        return $stmt->execute([$status, $postbackSent, $postbackResponse, $transactionId]);
    } catch (Exception $e) {
        error_log("Update conversion error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get dashboard statistics
 */
function getDashboardStats() {
    global $pdo;
    
    try {
        // Total clicks (all conversions)
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM conversions");
        $totalClicks = $stmt->fetch()['total'];
        
        // Total conversions
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM conversions WHERE status = 'converted'");
        $totalConversions = $stmt->fetch()['total'];
        
        // Conversion rate
        $conversionRate = $totalClicks > 0 ? ($totalConversions / $totalClicks) * 100 : 0;
        
        // Total revenue
        $stmt = $pdo->query("SELECT SUM(payout) as total FROM conversions WHERE status = 'converted'");
        $totalRevenue = $stmt->fetch()['total'] ?? 0;
        
        return [
            'total_clicks' => $totalClicks,
            'total_conversions' => $totalConversions,
            'conversion_rate' => $conversionRate,
            'total_revenue' => $totalRevenue
        ];
    } catch (Exception $e) {
        error_log("Dashboard stats error: " . $e->getMessage());
        return [
            'total_clicks' => 0,
            'total_conversions' => 0,
            'conversion_rate' => 0,
            'total_revenue' => 0
        ];
    }
}

/**
 * Get recent activity
 */
function getRecentActivity($limit = 10) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT c.*, o.title as offer_title 
            FROM conversions c 
            LEFT JOIN offers o ON c.offer_id = o.id 
            ORDER BY c.created_at DESC 
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Recent activity error: " . $e->getMessage());
        return [];
    }
}

/**
 * Get top performing offers
 */
function getTopOffers($limit = 5) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT o.*, 
                   COUNT(c.id) as conversions,
                   SUM(c.payout) as revenue
            FROM offers o 
            LEFT JOIN conversions c ON o.id = c.offer_id AND c.status = 'converted'
            GROUP BY o.id 
            ORDER BY conversions DESC 
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Top offers error: " . $e->getMessage());
        return [];
    }
}

/**
 * Send notification
 */
function sendNotification($message, $type = 'info') {
    // This could be extended to send emails, push notifications, etc.
    error_log("Notification [{$type}]: {$message}");
}

/**
 * Generate random string
 */
function generateRandomString($length = 10) {
    return substr(str_shuffle(str_repeat($x='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length/strlen($x)) )),1,$length);
}

/**
 * Format currency
 */
function formatCurrency($amount, $currency = 'USD') {
    return '$' . number_format($amount, 2);
}

/**
 * Check if request is AJAX
 */
function isAjaxRequest() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
}

/**
 * Return JSON response
 */
function jsonResponse($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Log error
 */
function logError($message, $context = []) {
    $logMessage = date('Y-m-d H:i:s') . " - " . $message;
    if (!empty($context)) {
        $logMessage .= " - Context: " . json_encode($context);
    }
    error_log($logMessage);
}

/**
 * Clean old data
 */
function cleanOldData($days = 365) {
    global $pdo;
    
    try {
        $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        // Clean old postback logs
        $stmt = $pdo->prepare("DELETE FROM postback_logs WHERE created_at < ?");
        $stmt->execute([$cutoffDate]);
        
        // Clean old test results
        $stmt = $pdo->prepare("DELETE FROM test_results WHERE created_at < ?");
        $stmt->execute([$cutoffDate]);
        
        return true;
    } catch (Exception $e) {
        error_log("Clean old data error: " . $e->getMessage());
        return false;
    }
}

/**
 * Backup database
 */
function backupDatabase() {
    global $pdo;
    
    try {
        $backupFile = 'backups/backup_' . date('Y-m-d_H-i-s') . '.sql';
        
        // Create backups directory if it doesn't exist
        if (!is_dir('backups')) {
            mkdir('backups', 0755, true);
        }
        
        // Get database configuration
        $config = require 'config/config.php';
        
        // Create mysqldump command
        $command = sprintf(
            'mysqldump --host=%s --user=%s --password=%s %s > %s',
            escapeshellarg($config['DB_HOST']),
            escapeshellarg($config['DB_USER']),
            escapeshellarg($config['DB_PASS']),
            escapeshellarg($config['DB_NAME']),
            escapeshellarg($backupFile)
        );
        
        exec($command, $output, $returnCode);
        
        if ($returnCode === 0) {
            return $backupFile;
        } else {
            throw new Exception("Backup failed with return code: " . $returnCode);
        }
    } catch (Exception $e) {
        error_log("Backup error: " . $e->getMessage());
        return false;
    }
}
?>