<?php
/**
 * S2S Tracker - Offer Landing Page
 * Dynamic offer pages with form submission and postback firing
 */

require_once 'config/config.php';
require_once 'includes/functions.php';

// Get offer ID from URL parameter
$offerId = $_GET['id'] ?? null;
$testMode = isset($_GET['test']);

if (!$offerId) {
    http_response_code(404);
    die('Offer not found');
}

// Get offer data
$offer = getOffer($offerId);
if (!$offer) {
    http_response_code(404);
    die('Offer not found');
}

// Handle form submission
if ($_POST && isset($_POST['name']) && isset($_POST['email'])) {
    $name = sanitizeInput($_POST['name']);
    $email = sanitizeInput($_POST['email']);
    
    if (!validateEmail($email)) {
        $error = 'Please enter a valid email address';
    } else {
        // Generate transaction ID
        $transactionId = generateTransactionId();
        
        // Get user data
        $ip = getUserIP();
        $locationData = getLocationData($ip);
        $deviceInfo = getDeviceInfo($_SERVER['HTTP_USER_AGENT'] ?? '');
        
        // Prepare conversion data
        $conversionData = [
            'transaction_id' => $transactionId,
            'offer_id' => $offer['id'],
            'name' => $name,
            'email' => $email,
            'ip_address' => $ip,
            'country' => $locationData['country'] ?? 'Unknown',
            'city' => $locationData['city'] ?? 'Unknown',
            'region' => $locationData['region'] ?? 'Unknown',
            'timezone' => $locationData['timezone'] ?? 'UTC',
            'isp' => $locationData['org'] ?? 'Unknown',
            'device' => $deviceInfo['device'],
            'os' => $deviceInfo['os'],
            'browser' => $deviceInfo['browser'],
            'screen_resolution' => $_POST['screen_resolution'] ?? 'Unknown',
            'language' => $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'Unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'referrer' => $_SERVER['HTTP_REFERER'] ?? '',
            'goal' => 'conversion',
            'payout' => $offer['payout'],
            'status' => 'converted'
        ];
        
        // Record conversion
        if (recordConversion($conversionData)) {
            // Fire postback
            $postbackUrl = getSetting('default_postback_url', 'https://tr.optimawall.com/pbtr');
            $postbackData = [
                'transaction_id' => $transactionId,
                'goal' => 'conversion',
                'payout' => $offer['payout'],
                'offer_id' => $offer['id'],
                'name' => $name,
                'email' => $email
            ];
            
            $postbackResult = sendPostback($postbackUrl, $postbackData);
            logPostback($transactionId, $postbackUrl, $postbackData, $postbackResult);
            
            // Update conversion with postback status
            updateConversionStatus(
                $transactionId, 
                'converted', 
                $postbackResult['success'], 
                $postbackResult['response']
            );
            
            // Show success message
            $success = true;
        } else {
            $error = 'Failed to process your submission. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($offer['title']) ?> - S2S Tracker</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .offer-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background: var(--bg-primary);
        }
        
        .offer-container {
            max-width: 600px;
            width: 100%;
            text-align: center;
        }
        
        .offer-header {
            margin-bottom: 40px;
        }
        
        .offer-icon {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: var(--gradient-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            font-size: 3rem;
            color: white;
            box-shadow: var(--shadow-glow);
            animation: pulse 2s infinite;
        }
        
        .offer-title {
            font-size: 2.5rem;
            font-weight: bold;
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 20px;
        }
        
        .offer-description {
            font-size: 1.2rem;
            color: var(--text-secondary);
            line-height: 1.6;
            margin-bottom: 30px;
        }
        
        .offer-payout {
            display: inline-block;
            background: var(--gradient-success);
            color: white;
            padding: 15px 30px;
            border-radius: 50px;
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 40px;
            box-shadow: var(--shadow-md);
        }
        
        .offer-form {
            background: var(--bg-glass);
            backdrop-filter: blur(20px);
            border: 1px solid var(--border-primary);
            border-radius: var(--radius-xl);
            padding: 40px;
            box-shadow: var(--shadow-lg);
        }
        
        .form-group {
            margin-bottom: 25px;
            text-align: left;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--text-primary);
            font-weight: 500;
        }
        
        .form-group input {
            width: 100%;
            padding: 15px;
            background: var(--bg-secondary);
            border: 1px solid var(--border-primary);
            border-radius: var(--radius-md);
            color: var(--text-primary);
            font-size: 1rem;
            transition: all var(--transition-normal);
        }
        
        .form-group input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(0, 212, 255, 0.1);
        }
        
        .form-group input::placeholder {
            color: var(--text-muted);
        }
        
        .submit-btn {
            width: 100%;
            padding: 18px;
            background: var(--gradient-primary);
            border: none;
            border-radius: var(--radius-md);
            color: white;
            font-size: 1.2rem;
            font-weight: bold;
            cursor: pointer;
            transition: all var(--transition-normal);
            position: relative;
            overflow: hidden;
        }
        
        .submit-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }
        
        .submit-btn:hover::before {
            left: 100%;
        }
        
        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-glow);
        }
        
        .success-message {
            background: var(--bg-glass);
            backdrop-filter: blur(20px);
            border: 1px solid var(--success);
            border-radius: var(--radius-xl);
            padding: 40px;
            text-align: center;
            box-shadow: var(--shadow-lg);
        }
        
        .success-icon {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: var(--gradient-success);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            font-size: 3rem;
            color: white;
            animation: bounce 1s ease;
        }
        
        .success-title {
            font-size: 2rem;
            color: var(--success);
            margin-bottom: 20px;
            font-weight: bold;
        }
        
        .success-message p {
            color: var(--text-secondary);
            font-size: 1.1rem;
            line-height: 1.6;
        }
        
        .error-message {
            background: rgba(255, 107, 107, 0.1);
            border: 1px solid var(--danger);
            border-radius: var(--radius-md);
            padding: 15px;
            color: var(--danger);
            margin-bottom: 20px;
            text-align: center;
        }
        
        .test-badge {
            position: fixed;
            top: 20px;
            right: 20px;
            background: var(--gradient-warning);
            color: white;
            padding: 10px 20px;
            border-radius: 25px;
            font-weight: bold;
            font-size: 0.9rem;
            z-index: 1000;
        }
        
        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-30px); }
            60% { transform: translateY(-15px); }
        }
        
        @media (max-width: 768px) {
            .offer-container {
                padding: 0 20px;
            }
            
            .offer-title {
                font-size: 2rem;
            }
            
            .offer-form {
                padding: 30px 20px;
            }
            
            .offer-icon {
                width: 100px;
                height: 100px;
                font-size: 2.5rem;
            }
        }
    </style>
</head>
<body>
    <?php if ($testMode): ?>
        <div class="test-badge">
            <i class="fas fa-flask"></i> Test Mode
        </div>
    <?php endif; ?>
    
    <div class="offer-page">
        <div class="offer-container">
            <?php if (isset($success) && $success): ?>
                <!-- Success Message -->
                <div class="success-message">
                    <div class="success-icon">
                        <i class="fas fa-check"></i>
                    </div>
                    <h2 class="success-title">Congratulations!</h2>
                    <p>
                        You have successfully entered the <strong><?= htmlspecialchars($offer['title']) ?></strong> offer!<br>
                        Your submission has been recorded and you will be contacted soon.
                    </p>
                    <p style="margin-top: 20px; color: var(--primary);">
                        <strong>Transaction ID:</strong> <?= $transactionId ?? 'N/A' ?>
                    </p>
                </div>
            <?php else: ?>
                <!-- Offer Form -->
                <div class="offer-header">
                    <div class="offer-icon">
                        <?php
                        $icons = [
                            'sweepstakes' => 'fas fa-trophy',
                            'survey' => 'fas fa-clipboard-list',
                            'download' => 'fas fa-download',
                            'subscription' => 'fas fa-crown',
                            'custom' => 'fas fa-gift'
                        ];
                        $icon = $icons[$offer['type']] ?? 'fas fa-gift';
                        ?>
                        <i class="<?= $icon ?>"></i>
                    </div>
                    <h1 class="offer-title"><?= htmlspecialchars($offer['title']) ?></h1>
                    <p class="offer-description"><?= htmlspecialchars($offer['description']) ?></p>
                    <div class="offer-payout">
                        <i class="fas fa-dollar-sign"></i> <?= number_format($offer['payout'], 2) ?>
                    </div>
                </div>
                
                <form class="offer-form" method="POST">
                    <?php if (isset($error)): ?>
                        <div class="error-message">
                            <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label for="name">
                            <i class="fas fa-user"></i> Full Name
                        </label>
                        <input type="text" id="name" name="name" placeholder="Enter your full name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">
                            <i class="fas fa-envelope"></i> Email Address
                        </label>
                        <input type="email" id="email" name="email" placeholder="Enter your email address" required>
                    </div>
                    
                    <input type="hidden" name="screen_resolution" id="screen_resolution">
                    
                    <button type="submit" class="submit-btn">
                        <i class="fas fa-rocket"></i> Enter Now!
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        // Set screen resolution
        document.getElementById('screen_resolution').value = screen.width + 'x' + screen.height;
        
        // Add some interactivity
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('.offer-form');
            const submitBtn = document.querySelector('.submit-btn');
            
            if (form && submitBtn) {
                form.addEventListener('submit', function() {
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
                    submitBtn.disabled = true;
                });
            }
        });
    </script>
</body>
</html>