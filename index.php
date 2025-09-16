<?php
/**
 * S2S Postback Testing System - Main Dashboard
 * Enhanced dark UI with glass effects and colorful accents
 */

require_once 'config/config.php';
require_once 'includes/functions.php';
require_once 'includes/analytics.php';

$analytics = new Analytics();
$stats = $analytics->getDashboardStats();
$recentActivity = $analytics->getRecentActivity(10);
$topOffers = $analytics->getTopOffers(5);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>S2S Tracker - Advanced Postback Testing System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-logo">
                <i class="fas fa-rocket"></i>
                <span>S2S Tracker</span>
                <div class="nav-badge">Pro</div>
            </div>
            <div class="nav-menu">
                <a href="#dashboard" class="nav-link active" data-section="dashboard">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
                <a href="#offers" class="nav-link" data-section="offers">
                    <i class="fas fa-gift"></i>
                    <span>Offers</span>
                </a>
                <a href="#testing" class="nav-link" data-section="testing">
                    <i class="fas fa-flask"></i>
                    <span>Testing</span>
                </a>
                <a href="#analytics" class="nav-link" data-section="analytics">
                    <i class="fas fa-chart-line"></i>
                    <span>Analytics</span>
                </a>
                <a href="#tools" class="nav-link" data-section="tools">
                    <i class="fas fa-tools"></i>
                    <span>Tools</span>
                </a>
                <a href="#settings" class="nav-link" data-section="settings">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </a>
            </div>
            <div class="nav-actions">
                <div class="notification-bell">
                    <i class="fas fa-bell"></i>
                    <span class="notification-count">3</span>
                </div>
                <div class="user-menu">
                    <img src="assets/images/avatar.png" alt="User" class="user-avatar">
                    <span class="user-name">Admin</span>
                    <i class="fas fa-chevron-down"></i>
                </div>
            </div>
            <div class="nav-toggle">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Dashboard Section -->
        <section id="dashboard" class="section active">
            <div class="container">
                <div class="page-header">
                    <h1 class="page-title">
                        <i class="fas fa-tachometer-alt"></i>
                        Dashboard
                    </h1>
                    <div class="page-actions">
                        <button class="btn btn-primary" onclick="refreshDashboard()">
                            <i class="fas fa-sync-alt"></i> Refresh
                        </button>
                        <button class="btn btn-secondary" onclick="exportData()">
                            <i class="fas fa-download"></i> Export
                        </button>
                    </div>
                </div>

                <!-- Stats Grid -->
                <div class="stats-grid">
                    <div class="stat-card glass-card">
                        <div class="stat-icon clicks">
                            <i class="fas fa-mouse-pointer"></i>
                        </div>
                        <div class="stat-content">
                            <h3 class="stat-number"><?= number_format($stats['total_clicks']) ?></h3>
                            <p class="stat-label">Total Clicks</p>
                            <div class="stat-change positive">
                                <i class="fas fa-arrow-up"></i>
                                <span>+12.5%</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="stat-card glass-card">
                        <div class="stat-icon conversions">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-content">
                            <h3 class="stat-number"><?= number_format($stats['total_conversions']) ?></h3>
                            <p class="stat-label">Conversions</p>
                            <div class="stat-change positive">
                                <i class="fas fa-arrow-up"></i>
                                <span>+8.3%</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="stat-card glass-card">
                        <div class="stat-icon rate">
                            <i class="fas fa-percentage"></i>
                        </div>
                        <div class="stat-content">
                            <h3 class="stat-number"><?= number_format($stats['conversion_rate'], 2) ?>%</h3>
                            <p class="stat-label">Conversion Rate</p>
                            <div class="stat-change negative">
                                <i class="fas fa-arrow-down"></i>
                                <span>-2.1%</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="stat-card glass-card">
                        <div class="stat-icon revenue">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                        <div class="stat-content">
                            <h3 class="stat-number">$<?= number_format($stats['total_revenue'], 2) ?></h3>
                            <p class="stat-label">Total Revenue</p>
                            <div class="stat-change positive">
                                <i class="fas fa-arrow-up"></i>
                                <span>+15.7%</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Grid -->
                <div class="charts-grid">
                    <div class="chart-container glass-card">
                        <div class="chart-header">
                            <h3>Conversion Trends</h3>
                            <div class="chart-controls">
                                <select class="chart-period">
                                    <option value="7d">Last 7 Days</option>
                                    <option value="30d">Last 30 Days</option>
                                    <option value="90d">Last 90 Days</option>
                                </select>
                            </div>
                        </div>
                        <canvas id="conversionChart"></canvas>
                    </div>
                    
                    <div class="chart-container glass-card">
                        <div class="chart-header">
                            <h3>Traffic Sources</h3>
                            <div class="chart-controls">
                                <button class="chart-btn active" data-type="pie">Pie</button>
                                <button class="chart-btn" data-type="doughnut">Doughnut</button>
                            </div>
                        </div>
                        <canvas id="trafficChart"></canvas>
                    </div>
                </div>

                <!-- Additional Charts -->
                <div class="charts-grid">
                    <div class="chart-container glass-card">
                        <div class="chart-header">
                            <h3>Geographic Distribution</h3>
                        </div>
                        <canvas id="geoChart"></canvas>
                    </div>
                    
                    <div class="chart-container glass-card">
                        <div class="chart-header">
                            <h3>Device Types</h3>
                        </div>
                        <canvas id="deviceChart"></canvas>
                    </div>
                </div>

                <!-- Recent Activity & Top Offers -->
                <div class="dashboard-grid">
                    <div class="activity-container glass-card">
                        <div class="card-header">
                            <h3>Recent Activity</h3>
                            <a href="#analytics" class="view-all">View All</a>
                        </div>
                        <div class="activity-list">
                            <?php foreach ($recentActivity as $activity): ?>
                            <div class="activity-item">
                                <div class="activity-icon <?= $activity['status'] ?>">
                                    <i class="fas <?= $activity['status'] === 'conversion' ? 'fa-check-circle' : 'fa-mouse-pointer' ?>"></i>
                                </div>
                                <div class="activity-content">
                                    <div class="activity-title"><?= htmlspecialchars($activity['offer_title']) ?></div>
                                    <div class="activity-meta">
                                        <span class="activity-type"><?= ucfirst($activity['status']) ?></span>
                                        <span class="activity-time"><?= timeAgo($activity['created_at']) ?></span>
                                    </div>
                                </div>
                                <div class="activity-value">
                                    $<?= number_format($activity['payout'], 2) ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="top-offers-container glass-card">
                        <div class="card-header">
                            <h3>Top Performing Offers</h3>
                            <a href="#offers" class="view-all">View All</a>
                        </div>
                        <div class="offers-list">
                            <?php foreach ($topOffers as $index => $offer): ?>
                            <div class="offer-item">
                                <div class="offer-rank">#<?= $index + 1 ?></div>
                                <div class="offer-info">
                                    <div class="offer-title"><?= htmlspecialchars($offer['title']) ?></div>
                                    <div class="offer-stats">
                                        <span><?= $offer['conversions'] ?> conversions</span>
                                        <span>$<?= number_format($offer['revenue'], 2) ?></span>
                                    </div>
                                </div>
                                <div class="offer-performance">
                                    <div class="performance-bar">
                                        <div class="performance-fill" style="width: <?= ($offer['conversions'] / $topOffers[0]['conversions']) * 100 ?>%"></div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Offers Section -->
        <section id="offers" class="section">
            <div class="container">
                <div class="page-header">
                    <h1 class="page-title">
                        <i class="fas fa-gift"></i>
                        Offer Management
                    </h1>
                    <div class="page-actions">
                        <button class="btn btn-primary" onclick="showCreateOfferModal()">
                            <i class="fas fa-plus"></i> Create Offer
                        </button>
                        <button class="btn btn-secondary" onclick="importOffers()">
                            <i class="fas fa-upload"></i> Import
                        </button>
                    </div>
                </div>

                <div class="offers-filters">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" placeholder="Search offers..." id="offerSearch">
                    </div>
                    <select class="filter-select" id="typeFilter">
                        <option value="">All Types</option>
                        <option value="sweepstakes">Sweepstakes</option>
                        <option value="survey">Survey</option>
                        <option value="download">Download</option>
                        <option value="subscription">Subscription</option>
                    </select>
                    <select class="filter-select" id="statusFilter">
                        <option value="">All Status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="paused">Paused</option>
                    </select>
                </div>

                <div class="offers-grid" id="offersGrid">
                    <!-- Offers will be loaded here -->
                </div>
            </div>
        </section>

        <!-- Testing Section -->
        <section id="testing" class="section">
            <div class="container">
                <div class="page-header">
                    <h1 class="page-title">
                        <i class="fas fa-flask"></i>
                        S2S Testing Lab
                    </h1>
                    <div class="page-actions">
                        <button class="btn btn-primary" onclick="runBatchTest()">
                            <i class="fas fa-play-circle"></i> Batch Test
                        </button>
                        <button class="btn btn-secondary" onclick="clearTestResults()">
                            <i class="fas fa-trash"></i> Clear Results
                        </button>
                    </div>
                </div>

                <div class="testing-grid">
                    <div class="test-form-container glass-card">
                        <div class="card-header">
                            <h3>Postback Testing</h3>
                            <div class="test-presets">
                                <select id="testPresets">
                                    <option value="">Select Preset</option>
                                    <option value="optimawall">Optimawall</option>
                                    <option value="custom">Custom</option>
                                </select>
                            </div>
                        </div>
                        <form id="testForm" class="test-form">
                            <div class="form-group">
                                <label>Postback URL</label>
                                <input type="url" id="postbackUrl" value="https://tr.optimawall.com/pbtr" required>
                                <div class="input-help">Enter your postback URL for testing</div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Transaction ID</label>
                                    <input type="text" id="transactionId" placeholder="Auto-generated">
                                    <button type="button" class="btn-generate" onclick="generateTransactionId()">
                                        <i class="fas fa-sync"></i>
                                    </button>
                                </div>
                                <div class="form-group">
                                    <label>Goal</label>
                                    <input type="text" id="goal" value="conversion">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label>Additional Parameters</label>
                                <div class="params-builder">
                                    <div class="param-row">
                                        <input type="text" placeholder="Parameter name" class="param-name">
                                        <input type="text" placeholder="Value" class="param-value">
                                        <button type="button" class="btn-remove-param">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                    <button type="button" class="btn-add-param">
                                        <i class="fas fa-plus"></i> Add Parameter
                                    </button>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label>Test Type</label>
                                <div class="test-type-selector">
                                    <label class="radio-option">
                                        <input type="radio" name="testType" value="single" checked>
                                        <span>Single Test</span>
                                    </label>
                                    <label class="radio-option">
                                        <input type="radio" name="testType" value="batch">
                                        <span>Batch Test (10 requests)</span>
                                    </label>
                                    <label class="radio-option">
                                        <input type="radio" name="testType" value="stress">
                                        <span>Stress Test (100 requests)</span>
                                    </label>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary btn-large">
                                <i class="fas fa-play"></i> Run Test
                            </button>
                        </form>
                    </div>

                    <div class="test-results-container glass-card">
                        <div class="card-header">
                            <h3>Test Results</h3>
                            <div class="test-status">
                                <span class="status-indicator" id="testStatus">Ready</span>
                            </div>
                        </div>
                        <div class="test-results">
                            <div class="result-summary">
                                <div class="result-item">
                                    <span class="result-label">Status:</span>
                                    <span class="result-value" id="resultStatus">-</span>
                                </div>
                                <div class="result-item">
                                    <span class="result-label">Response Code:</span>
                                    <span class="result-value" id="resultCode">-</span>
                                </div>
                                <div class="result-item">
                                    <span class="result-label">Response Time:</span>
                                    <span class="result-value" id="resultTime">-</span>
                                </div>
                                <div class="result-item">
                                    <span class="result-label">Success Rate:</span>
                                    <span class="result-value" id="resultSuccessRate">-</span>
                                </div>
                            </div>
                            
                            <div class="result-details">
                                <h4>Response Details</h4>
                                <pre id="resultDetails">No test results yet</pre>
                            </div>
                            
                            <div class="result-history">
                                <h4>Test History</h4>
                                <div class="history-list" id="testHistory">
                                    <!-- Test history will be populated here -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Analytics Section -->
        <section id="analytics" class="section">
            <div class="container">
                <div class="page-header">
                    <h1 class="page-title">
                        <i class="fas fa-chart-line"></i>
                        Advanced Analytics
                    </h1>
                    <div class="page-actions">
                        <button class="btn btn-primary" onclick="exportAnalytics()">
                            <i class="fas fa-download"></i> Export Report
                        </button>
                        <button class="btn btn-secondary" onclick="scheduleReport()">
                            <i class="fas fa-calendar"></i> Schedule Report
                        </button>
                    </div>
                </div>

                <div class="analytics-filters">
                    <div class="filter-group">
                        <label>Date Range</label>
                        <select id="dateRange">
                            <option value="24h">Last 24 Hours</option>
                            <option value="7d" selected>Last 7 Days</option>
                            <option value="30d">Last 30 Days</option>
                            <option value="90d">Last 90 Days</option>
                            <option value="custom">Custom Range</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Offer Filter</label>
                        <select id="offerFilter">
                            <option value="">All Offers</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Country Filter</label>
                        <select id="countryFilter">
                            <option value="">All Countries</option>
                        </select>
                    </div>
                    <button class="btn btn-primary" onclick="applyFilters()">
                        <i class="fas fa-filter"></i> Apply Filters
                    </button>
                </div>

                <div class="analytics-grid">
                    <div class="analytics-card glass-card">
                        <div class="card-header">
                            <h3>Geographic Distribution</h3>
                            <div class="chart-controls">
                                <button class="chart-btn active" data-type="map">Map</button>
                                <button class="chart-btn" data-type="bar">Bar</button>
                            </div>
                        </div>
                        <canvas id="analyticsGeoChart"></canvas>
                    </div>
                    
                    <div class="analytics-card glass-card">
                        <div class="card-header">
                            <h3>Device & OS Analysis</h3>
                        </div>
                        <canvas id="analyticsDeviceChart"></canvas>
                    </div>
                    
                    <div class="analytics-card glass-card">
                        <div class="card-header">
                            <h3>Network Providers</h3>
                        </div>
                        <canvas id="analyticsNetworkChart"></canvas>
                    </div>
                    
                    <div class="analytics-card glass-card">
                        <div class="card-header">
                            <h3>Conversion Funnel</h3>
                        </div>
                        <canvas id="analyticsFunnelChart"></canvas>
                    </div>
                </div>

                <div class="detailed-analytics glass-card">
                    <div class="card-header">
                        <h3>Detailed Analytics</h3>
                        <div class="table-controls">
                            <button class="btn btn-secondary" onclick="refreshTable()">
                                <i class="fas fa-sync"></i> Refresh
                            </button>
                            <button class="btn btn-secondary" onclick="exportTable()">
                                <i class="fas fa-download"></i> Export
                            </button>
                        </div>
                    </div>
                    <div class="table-container">
                        <table id="analyticsTable">
                            <thead>
                                <tr>
                                    <th>Transaction ID</th>
                                    <th>IP Address</th>
                                    <th>Country</th>
                                    <th>City</th>
                                    <th>Device</th>
                                    <th>OS</th>
                                    <th>Browser</th>
                                    <th>Network</th>
                                    <th>Time</th>
                                    <th>Status</th>
                                    <th>Payout</th>
                                </tr>
                            </thead>
                            <tbody id="analyticsTableBody">
                                <!-- Analytics data will be populated here -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>

        <!-- Tools Section -->
        <section id="tools" class="section">
            <div class="container">
                <div class="page-header">
                    <h1 class="page-title">
                        <i class="fas fa-tools"></i>
                        Advanced Tools
                    </h1>
                </div>

                <div class="tools-grid">
                    <div class="tool-card glass-card">
                        <div class="tool-icon">
                            <i class="fas fa-link"></i>
                        </div>
                        <h3>URL Generator</h3>
                        <p>Generate tracking URLs with custom parameters</p>
                        <button class="btn btn-primary" onclick="openURLGenerator()">Open Tool</button>
                    </div>
                    
                    <div class="tool-card glass-card">
                        <div class="tool-icon">
                            <i class="fas fa-code"></i>
                        </div>
                        <h3>Postback Validator</h3>
                        <p>Validate and test postback URLs</p>
                        <button class="btn btn-primary" onclick="openPostbackValidator()">Open Tool</button>
                    </div>
                    
                    <div class="tool-card glass-card">
                        <div class="tool-icon">
                            <i class="fas fa-chart-bar"></i>
                        </div>
                        <h3>Performance Monitor</h3>
                        <p>Monitor system performance and uptime</p>
                        <button class="btn btn-primary" onclick="openPerformanceMonitor()">Open Tool</button>
                    </div>
                    
                    <div class="tool-card glass-card">
                        <div class="tool-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <h3>Security Scanner</h3>
                        <p>Scan for security vulnerabilities</p>
                        <button class="btn btn-primary" onclick="openSecurityScanner()">Open Tool</button>
                    </div>
                    
                    <div class="tool-card glass-card">
                        <div class="tool-icon">
                            <i class="fas fa-database"></i>
                        </div>
                        <h3>Database Manager</h3>
                        <p>Manage database and optimize performance</p>
                        <button class="btn btn-primary" onclick="openDatabaseManager()">Open Tool</button>
                    </div>
                    
                    <div class="tool-card glass-card">
                        <div class="tool-icon">
                            <i class="fas fa-file-export"></i>
                        </div>
                        <h3>Data Exporter</h3>
                        <p>Export data in various formats</p>
                        <button class="btn btn-primary" onclick="openDataExporter()">Open Tool</button>
                    </div>
                </div>
            </div>
        </section>

        <!-- Settings Section -->
        <section id="settings" class="section">
            <div class="container">
                <div class="page-header">
                    <h1 class="page-title">
                        <i class="fas fa-cog"></i>
                        System Settings
                    </h1>
                </div>

                <div class="settings-grid">
                    <div class="settings-card glass-card">
                        <div class="card-header">
                            <h3>Postback Configuration</h3>
                        </div>
                        <form id="settingsForm" class="settings-form">
                            <div class="form-group">
                                <label>Default Postback URL</label>
                                <input type="url" id="defaultPostbackUrl" value="https://tr.optimawall.com/pbtr">
                            </div>
                            <div class="form-group">
                                <label>Transaction ID Parameter</label>
                                <input type="text" id="defaultTransactionParam" value="transaction_id">
                            </div>
                            <div class="form-group">
                                <label>Goal Parameter</label>
                                <input type="text" id="defaultGoalParam" value="goal">
                            </div>
                            <div class="form-group">
                                <label>Payout Parameter</label>
                                <input type="text" id="defaultPayoutParam" value="payout">
                            </div>
                            <button type="submit" class="btn btn-primary">Save Settings</button>
                        </form>
                    </div>

                    <div class="settings-card glass-card">
                        <div class="card-header">
                            <h3>System Configuration</h3>
                        </div>
                        <form id="systemForm" class="settings-form">
                            <div class="form-group">
                                <label>Timezone</label>
                                <select id="timezone">
                                    <option value="UTC">UTC</option>
                                    <option value="America/New_York">Eastern Time</option>
                                    <option value="America/Chicago">Central Time</option>
                                    <option value="America/Denver">Mountain Time</option>
                                    <option value="America/Los_Angeles">Pacific Time</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Currency</label>
                                <select id="currency">
                                    <option value="USD">USD</option>
                                    <option value="EUR">EUR</option>
                                    <option value="GBP">GBP</option>
                                    <option value="CAD">CAD</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Data Retention (Days)</label>
                                <input type="number" id="dataRetention" value="365" min="30" max="3650">
                            </div>
                            <button type="submit" class="btn btn-primary">Save Settings</button>
                        </form>
                    </div>

                    <div class="settings-card glass-card">
                        <div class="card-header">
                            <h3>Data Management</h3>
                        </div>
                        <div class="settings-actions">
                            <button class="btn btn-secondary" onclick="exportAllData()">
                                <i class="fas fa-download"></i> Export All Data
                            </button>
                            <button class="btn btn-secondary" onclick="importData()">
                                <i class="fas fa-upload"></i> Import Data
                            </button>
                            <button class="btn btn-danger" onclick="clearAllData()">
                                <i class="fas fa-trash"></i> Clear All Data
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Modals -->
    <div id="createOfferModal" class="modal">
        <div class="modal-content glass-card">
            <div class="modal-header">
                <h2>Create New Offer</h2>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <form id="createOfferForm">
                    <div class="form-group">
                        <label>Offer Title</label>
                        <input type="text" id="newOfferTitle" required>
                    </div>
                    <div class="form-group">
                        <label>Offer Description</label>
                        <textarea id="newOfferDescription" required></textarea>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Offer Type</label>
                            <select id="newOfferType">
                                <option value="sweepstakes">Sweepstakes</option>
                                <option value="survey">Survey</option>
                                <option value="download">Download</option>
                                <option value="subscription">Subscription</option>
                                <option value="custom">Custom</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Payout ($)</label>
                            <input type="number" id="newOfferPayout" step="0.01" min="0">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select id="newOfferStatus">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="paused">Paused</option>
                        </select>
                    </div>
                    <div class="modal-actions">
                        <button type="button" class="btn btn-secondary" onclick="closeModal('createOfferModal')">Cancel</button>
                        <button type="submit" class="btn btn-primary">Create Offer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Notification Container -->
    <div id="notificationContainer" class="notification-container"></div>

    <!-- Scripts -->
    <script src="assets/js/app.js"></script>
    <script src="assets/js/charts.js"></script>
    <script src="assets/js/analytics.js"></script>
    <script src="assets/js/testing.js"></script>
</body>
</html>