<?php
/**
 * S2S Postback Testing System - Installer
 * Complete installation script with database setup
 */

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

$step = $_GET['step'] ?? 1;
$error = '';
$success = '';

// Handle form submissions
if ($_POST) {
    switch ($step) {
        case 2:
            // Database configuration
            $host = $_POST['db_host'] ?? 'localhost';
            $name = $_POST['db_name'] ?? 's2s_tracker';
            $user = $_POST['db_user'] ?? 'root';
            $pass = $_POST['db_pass'] ?? '';
            
            try {
                // Test database connection
                $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                ]);
                
                // Create database if not exists
                $pdo->exec("CREATE DATABASE IF NOT EXISTS `$name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                $pdo->exec("USE `$name`");
                
                // Store config in session
                $_SESSION['db_config'] = compact('host', 'name', 'user', 'pass');
                
                header('Location: install.php?step=3');
                exit;
            } catch (Exception $e) {
                $error = "Database connection failed: " . $e->getMessage();
            }
            break;
            
        case 3:
            // Create tables and default data
            try {
                require_once 'config/database.php';
                
                $db = new Database();
                $db->host = $_SESSION['db_config']['host'];
                $db->db_name = $_SESSION['db_config']['name'];
                $db->username = $_SESSION['db_config']['user'];
                $db->password = $_SESSION['db_config']['pass'];
                
                $conn = $db->getConnection();
                $db->createTables();
                $db->insertDefaultData();
                
                // Create config file
                $configContent = "<?php
// S2S Tracker Configuration
define('DB_HOST', '{$_SESSION['db_config']['host']}');
define('DB_NAME', '{$_SESSION['db_config']['name']}');
define('DB_USER', '{$_SESSION['db_config']['user']}');
define('DB_PASS', '{$_SESSION['db_config']['pass']}');
define('SITE_URL', 'http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . "');
define('INSTALLED', true);
?>";
                
                file_put_contents('config/config.php', $configContent);
                
                // Create .htaccess for security
                $htaccess = "RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^offer/([a-zA-Z0-9_-]+)/?$ offer.php?id=$1 [L,QSA]
RewriteRule ^test/([a-zA-Z0-9_-]+)/?$ test.php?id=$1 [L,QSA]

# Security headers
Header always set X-Content-Type-Options nosniff
Header always set X-Frame-Options DENY
Header always set X-XSS-Protection \"1; mode=block\"
Header always set Referrer-Policy \"strict-origin-when-cross-origin\"

# Hide sensitive files
<Files \"config.php\">
    Order allow,deny
    Deny from all
</Files>

<Files \"database.php\">
    Order allow,deny
    Deny from all
</Files>";
                
                file_put_contents('.htaccess', $htaccess);
                
                $success = "Installation completed successfully!";
                $step = 4;
            } catch (Exception $e) {
                $error = "Installation failed: " . $e->getMessage();
            }
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>S2S Tracker - Installation</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #0a0a0a 0%, #1a1a1a 100%);
            color: #ffffff;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow-x: hidden;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
                radial-gradient(circle at 20% 80%, rgba(0, 212, 255, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(255, 107, 107, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 40% 40%, rgba(81, 207, 102, 0.05) 0%, transparent 50%);
            z-index: -1;
            animation: backgroundShift 20s ease-in-out infinite;
        }

        @keyframes backgroundShift {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.8; }
        }

        .installer-container {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 40px;
            max-width: 600px;
            width: 90%;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            animation: slideInUp 0.6s ease;
        }

        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .installer-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .installer-header h1 {
            font-size: 2.5rem;
            background: linear-gradient(135deg, #00d4ff, #ff6b6b);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 10px;
        }

        .installer-header p {
            color: #b3b3b3;
            font-size: 1.1rem;
        }

        .step-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 40px;
        }

        .step {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 10px;
            font-weight: bold;
            transition: all 0.3s ease;
        }

        .step.active {
            background: linear-gradient(135deg, #00d4ff, #0099cc);
            color: white;
        }

        .step.completed {
            background: linear-gradient(135deg, #51cf66, #40c057);
            color: white;
        }

        .step.pending {
            background: rgba(255, 255, 255, 0.1);
            color: #666;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #ffffff;
            font-weight: 500;
        }

        .form-group input {
            width: 100%;
            padding: 15px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            color: #ffffff;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: #00d4ff;
            box-shadow: 0 0 0 3px rgba(0, 212, 255, 0.1);
        }

        .form-group input::placeholder {
            color: #666;
        }

        .btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #00d4ff, #0099cc);
            border: none;
            border-radius: 10px;
            color: white;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .btn:hover::before {
            left: 100%;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(0, 212, 255, 0.3);
        }

        .alert {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 4px solid;
        }

        .alert.error {
            background: rgba(255, 107, 107, 0.1);
            border-color: #ff6b6b;
            color: #ff6b6b;
        }

        .alert.success {
            background: rgba(81, 207, 102, 0.1);
            border-color: #51cf66;
            color: #51cf66;
        }

        .requirements {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
        }

        .requirements h3 {
            margin-bottom: 15px;
            color: #00d4ff;
        }

        .requirement {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .requirement:last-child {
            border-bottom: none;
        }

        .requirement .status {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: bold;
        }

        .requirement .status.ok {
            background: rgba(81, 207, 102, 0.2);
            color: #51cf66;
        }

        .requirement .status.error {
            background: rgba(255, 107, 107, 0.2);
            color: #ff6b6b;
        }

        .completion-message {
            text-align: center;
            padding: 40px 20px;
        }

        .completion-message h2 {
            color: #51cf66;
            margin-bottom: 20px;
            font-size: 2rem;
        }

        .completion-message p {
            color: #b3b3b3;
            margin-bottom: 30px;
            line-height: 1.6;
        }

        .completion-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.1);
            color: #ffffff;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }

        @media (max-width: 768px) {
            .installer-container {
                padding: 30px 20px;
                margin: 20px;
            }

            .installer-header h1 {
                font-size: 2rem;
            }

            .completion-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="installer-container">
        <div class="installer-header">
            <h1>🚀 S2S Tracker</h1>
            <p>Advanced Postback Testing System</p>
        </div>

        <div class="step-indicator">
            <div class="step <?= $step >= 1 ? ($step > 1 ? 'completed' : 'active') : 'pending' ?>">1</div>
            <div class="step <?= $step >= 2 ? ($step > 2 ? 'completed' : 'active') : 'pending' ?>">2</div>
            <div class="step <?= $step >= 3 ? ($step > 3 ? 'completed' : 'active') : 'pending' ?>">3</div>
            <div class="step <?= $step >= 4 ? 'active' : 'pending' ?>">4</div>
        </div>

        <?php if ($error): ?>
            <div class="alert error">
                <strong>Error:</strong> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert success">
                <strong>Success:</strong> <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <?php if ($step == 1): ?>
            <div class="requirements">
                <h3>📋 System Requirements</h3>
                <div class="requirement">
                    <span>PHP Version (7.4+)</span>
                    <span class="status <?= version_compare(PHP_VERSION, '7.4.0', '>=') ? 'ok' : 'error' ?>">
                        <?= version_compare(PHP_VERSION, '7.4.0', '>=') ? 'OK' : 'ERROR' ?>
                    </span>
                </div>
                <div class="requirement">
                    <span>MySQL Extension</span>
                    <span class="status <?= extension_loaded('pdo_mysql') ? 'ok' : 'error' ?>">
                        <?= extension_loaded('pdo_mysql') ? 'OK' : 'ERROR' ?>
                    </span>
                </div>
                <div class="requirement">
                    <span>cURL Extension</span>
                    <span class="status <?= extension_loaded('curl') ? 'ok' : 'error' : 'error' ?>">
                        <?= extension_loaded('curl') ? 'OK' : 'ERROR' ?>
                    </span>
                </div>
                <div class="requirement">
                    <span>JSON Extension</span>
                    <span class="status <?= extension_loaded('json') ? 'ok' : 'error' ?>">
                        <?= extension_loaded('json') ? 'OK' : 'ERROR' ?>
                    </span>
                </div>
                <div class="requirement">
                    <span>Config Directory Writable</span>
                    <span class="status <?= is_writable('config') ? 'ok' : 'error' ?>">
                        <?= is_writable('config') ? 'OK' : 'ERROR' ?>
                    </span>
                </div>
            </div>

            <p style="text-align: center; color: #b3b3b3; margin-bottom: 30px;">
                Welcome to S2S Tracker! This installer will help you set up your postback testing system.
            </p>

            <a href="install.php?step=2" class="btn">Start Installation</a>

        <?php elseif ($step == 2): ?>
            <h2 style="margin-bottom: 30px; color: #00d4ff;">🗄️ Database Configuration</h2>
            
            <form method="POST">
                <div class="form-group">
                    <label>Database Host</label>
                    <input type="text" name="db_host" value="localhost" required>
                </div>
                
                <div class="form-group">
                    <label>Database Name</label>
                    <input type="text" name="db_name" value="s2s_tracker" required>
                </div>
                
                <div class="form-group">
                    <label>Database Username</label>
                    <input type="text" name="db_user" value="root" required>
                </div>
                
                <div class="form-group">
                    <label>Database Password</label>
                    <input type="password" name="db_pass" placeholder="Enter database password">
                </div>
                
                <button type="submit" class="btn">Test Connection & Continue</button>
            </form>

        <?php elseif ($step == 3): ?>
            <h2 style="margin-bottom: 30px; color: #00d4ff;">⚙️ Installing System</h2>
            
            <p style="color: #b3b3b3; margin-bottom: 30px;">
                Creating database tables and inserting default data...
            </p>
            
            <form method="POST">
                <button type="submit" class="btn">Complete Installation</button>
            </form>

        <?php elseif ($step == 4): ?>
            <div class="completion-message">
                <h2>🎉 Installation Complete!</h2>
                <p>
                    Your S2S Tracker system has been successfully installed and configured. 
                    You can now start testing postbacks and managing offers.
                </p>
                
                <div class="completion-actions">
                    <a href="index.php" class="btn">Go to Dashboard</a>
                    <a href="admin.php" class="btn btn-secondary">Admin Panel</a>
                </div>
                
                <div style="margin-top: 30px; padding: 20px; background: rgba(255, 107, 107, 0.1); border-radius: 10px; border-left: 4px solid #ff6b6b;">
                    <strong style="color: #ff6b6b;">Security Notice:</strong>
                    <p style="color: #b3b3b3; margin-top: 10px;">
                        Please delete the <code>install.php</code> file for security reasons.
                    </p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>