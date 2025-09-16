<?php
/**
 * S2S Tracker - Analytics Class
 * Advanced analytics and reporting functionality
 */

// Prevent direct access
if (!defined('INSTALLED')) {
    die('Access denied');
}

class Analytics {
    private $pdo;
    
    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
    }
    
    /**
     * Get dashboard statistics
     */
    public function getDashboardStats() {
        try {
            // Total clicks (all conversions)
            $stmt = $this->pdo->query("SELECT COUNT(*) as total FROM conversions");
            $totalClicks = $stmt->fetch()['total'];
            
            // Total conversions
            $stmt = $this->pdo->query("SELECT COUNT(*) as total FROM conversions WHERE status = 'converted'");
            $totalConversions = $stmt->fetch()['total'];
            
            // Conversion rate
            $conversionRate = $totalClicks > 0 ? ($totalConversions / $totalClicks) * 100 : 0;
            
            // Total revenue
            $stmt = $this->pdo->query("SELECT SUM(payout) as total FROM conversions WHERE status = 'converted'");
            $totalRevenue = $stmt->fetch()['total'] ?? 0;
            
            // Today's stats
            $stmt = $this->pdo->query("SELECT COUNT(*) as total FROM conversions WHERE DATE(created_at) = CURDATE()");
            $todayClicks = $stmt->fetch()['total'];
            
            $stmt = $this->pdo->query("SELECT COUNT(*) as total FROM conversions WHERE DATE(created_at) = CURDATE() AND status = 'converted'");
            $todayConversions = $stmt->fetch()['total'];
            
            // Yesterday's stats for comparison
            $stmt = $this->pdo->query("SELECT COUNT(*) as total FROM conversions WHERE DATE(created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)");
            $yesterdayClicks = $stmt->fetch()['total'];
            
            $stmt = $this->pdo->query("SELECT COUNT(*) as total FROM conversions WHERE DATE(created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY) AND status = 'converted'");
            $yesterdayConversions = $stmt->fetch()['total'];
            
            // Calculate growth rates
            $clickGrowth = $yesterdayClicks > 0 ? (($todayClicks - $yesterdayClicks) / $yesterdayClicks) * 100 : 0;
            $conversionGrowth = $yesterdayConversions > 0 ? (($todayConversions - $yesterdayConversions) / $yesterdayConversions) * 100 : 0;
            
            return [
                'total_clicks' => $totalClicks,
                'total_conversions' => $totalConversions,
                'conversion_rate' => $conversionRate,
                'total_revenue' => $totalRevenue,
                'today_clicks' => $todayClicks,
                'today_conversions' => $todayConversions,
                'click_growth' => $clickGrowth,
                'conversion_growth' => $conversionGrowth
            ];
        } catch (Exception $e) {
            error_log("Dashboard stats error: " . $e->getMessage());
            return [
                'total_clicks' => 0,
                'total_conversions' => 0,
                'conversion_rate' => 0,
                'total_revenue' => 0,
                'today_clicks' => 0,
                'today_conversions' => 0,
                'click_growth' => 0,
                'conversion_growth' => 0
            ];
        }
    }
    
    /**
     * Get recent activity
     */
    public function getRecentActivity($limit = 10) {
        try {
            $stmt = $this->pdo->prepare("
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
    public function getTopOffers($limit = 5) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT o.*, 
                       COUNT(c.id) as conversions,
                       SUM(c.payout) as revenue,
                       AVG(c.payout) as avg_payout
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
     * Get conversion trends data
     */
    public function getConversionTrends($days = 7) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    DATE(created_at) as date,
                    COUNT(*) as clicks,
                    SUM(CASE WHEN status = 'converted' THEN 1 ELSE 0 END) as conversions,
                    SUM(CASE WHEN status = 'converted' THEN payout ELSE 0 END) as revenue
                FROM conversions 
                WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
                GROUP BY DATE(created_at)
                ORDER BY date ASC
            ");
            $stmt->execute([$days]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Conversion trends error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get geographic distribution
     */
    public function getGeographicData() {
        try {
            $stmt = $this->pdo->query("
                SELECT 
                    country,
                    COUNT(*) as clicks,
                    SUM(CASE WHEN status = 'converted' THEN 1 ELSE 0 END) as conversions,
                    SUM(CASE WHEN status = 'converted' THEN payout ELSE 0 END) as revenue
                FROM conversions 
                WHERE country IS NOT NULL AND country != 'Unknown'
                GROUP BY country
                ORDER BY conversions DESC
                LIMIT 20
            ");
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Geographic data error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get device analytics
     */
    public function getDeviceAnalytics() {
        try {
            $stmt = $this->pdo->query("
                SELECT 
                    device,
                    COUNT(*) as clicks,
                    SUM(CASE WHEN status = 'converted' THEN 1 ELSE 0 END) as conversions
                FROM conversions 
                WHERE device IS NOT NULL
                GROUP BY device
                ORDER BY conversions DESC
            ");
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Device analytics error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get OS analytics
     */
    public function getOSAnalytics() {
        try {
            $stmt = $this->pdo->query("
                SELECT 
                    os,
                    COUNT(*) as clicks,
                    SUM(CASE WHEN status = 'converted' THEN 1 ELSE 0 END) as conversions
                FROM conversions 
                WHERE os IS NOT NULL AND os != 'Unknown'
                GROUP BY os
                ORDER BY conversions DESC
                LIMIT 10
            ");
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("OS analytics error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get browser analytics
     */
    public function getBrowserAnalytics() {
        try {
            $stmt = $this->pdo->query("
                SELECT 
                    browser,
                    COUNT(*) as clicks,
                    SUM(CASE WHEN status = 'converted' THEN 1 ELSE 0 END) as conversions
                FROM conversions 
                WHERE browser IS NOT NULL AND browser != 'Unknown'
                GROUP BY browser
                ORDER BY conversions DESC
                LIMIT 10
            ");
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Browser analytics error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get network provider analytics
     */
    public function getNetworkAnalytics() {
        try {
            $stmt = $this->pdo->query("
                SELECT 
                    isp,
                    COUNT(*) as clicks,
                    SUM(CASE WHEN status = 'converted' THEN 1 ELSE 0 END) as conversions
                FROM conversions 
                WHERE isp IS NOT NULL AND isp != 'Unknown'
                GROUP BY isp
                ORDER BY conversions DESC
                LIMIT 15
            ");
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Network analytics error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get traffic sources
     */
    public function getTrafficSources() {
        try {
            $stmt = $this->pdo->query("
                SELECT 
                    CASE 
                        WHEN referrer IS NULL OR referrer = '' THEN 'Direct'
                        WHEN referrer LIKE '%google%' THEN 'Google'
                        WHEN referrer LIKE '%facebook%' THEN 'Facebook'
                        WHEN referrer LIKE '%twitter%' THEN 'Twitter'
                        WHEN referrer LIKE '%instagram%' THEN 'Instagram'
                        WHEN referrer LIKE '%youtube%' THEN 'YouTube'
                        ELSE 'Other'
                    END as source,
                    COUNT(*) as clicks,
                    SUM(CASE WHEN status = 'converted' THEN 1 ELSE 0 END) as conversions
                FROM conversions 
                GROUP BY source
                ORDER BY conversions DESC
            ");
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Traffic sources error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get hourly distribution
     */
    public function getHourlyDistribution() {
        try {
            $stmt = $this->pdo->query("
                SELECT 
                    HOUR(created_at) as hour,
                    COUNT(*) as clicks,
                    SUM(CASE WHEN status = 'converted' THEN 1 ELSE 0 END) as conversions
                FROM conversions 
                WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                GROUP BY HOUR(created_at)
                ORDER BY hour ASC
            ");
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Hourly distribution error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get conversion funnel data
     */
    public function getConversionFunnel() {
        try {
            $stmt = $this->pdo->query("
                SELECT 
                    'Total Clicks' as step,
                    COUNT(*) as count,
                    100 as percentage
                FROM conversions
                UNION ALL
                SELECT 
                    'Conversions' as step,
                    SUM(CASE WHEN status = 'converted' THEN 1 ELSE 0 END) as count,
                    ROUND((SUM(CASE WHEN status = 'converted' THEN 1 ELSE 0 END) / COUNT(*)) * 100, 2) as percentage
                FROM conversions
                UNION ALL
                SELECT 
                    'Revenue Generated' as step,
                    SUM(CASE WHEN status = 'converted' THEN payout ELSE 0 END) as count,
                    ROUND((SUM(CASE WHEN status = 'converted' THEN payout ELSE 0 END) / (COUNT(*) * 1.0)) * 100, 2) as percentage
                FROM conversions
            ");
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Conversion funnel error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get detailed analytics table data
     */
    public function getDetailedAnalytics($filters = []) {
        try {
            $where = [];
            $params = [];
            
            // Date range filter
            if (!empty($filters['date_from'])) {
                $where[] = "DATE(created_at) >= ?";
                $params[] = $filters['date_from'];
            }
            
            if (!empty($filters['date_to'])) {
                $where[] = "DATE(created_at) <= ?";
                $params[] = $filters['date_to'];
            }
            
            // Offer filter
            if (!empty($filters['offer_id'])) {
                $where[] = "offer_id = ?";
                $params[] = $filters['offer_id'];
            }
            
            // Country filter
            if (!empty($filters['country'])) {
                $where[] = "country = ?";
                $params[] = $filters['country'];
            }
            
            // Status filter
            if (!empty($filters['status'])) {
                $where[] = "status = ?";
                $params[] = $filters['status'];
            }
            
            $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
            
            $stmt = $this->pdo->prepare("
                SELECT 
                    c.*,
                    o.title as offer_title
                FROM conversions c
                LEFT JOIN offers o ON c.offer_id = o.id
                {$whereClause}
                ORDER BY c.created_at DESC
                LIMIT 1000
            ");
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Detailed analytics error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get performance metrics
     */
    public function getPerformanceMetrics($days = 30) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    COUNT(*) as total_clicks,
                    SUM(CASE WHEN status = 'converted' THEN 1 ELSE 0 END) as total_conversions,
                    ROUND((SUM(CASE WHEN status = 'converted' THEN 1 ELSE 0 END) / COUNT(*)) * 100, 2) as conversion_rate,
                    SUM(CASE WHEN status = 'converted' THEN payout ELSE 0 END) as total_revenue,
                    ROUND(AVG(CASE WHEN status = 'converted' THEN payout ELSE 0 END), 2) as avg_payout,
                    COUNT(DISTINCT country) as unique_countries,
                    COUNT(DISTINCT device) as unique_devices,
                    COUNT(DISTINCT os) as unique_os
                FROM conversions 
                WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
            ");
            $stmt->execute([$days]);
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log("Performance metrics error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get real-time stats
     */
    public function getRealTimeStats() {
        try {
            // Last hour
            $stmt = $this->pdo->query("
                SELECT 
                    COUNT(*) as clicks_last_hour,
                    SUM(CASE WHEN status = 'converted' THEN 1 ELSE 0 END) as conversions_last_hour
                FROM conversions 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
            ");
            $lastHour = $stmt->fetch();
            
            // Last 24 hours
            $stmt = $this->pdo->query("
                SELECT 
                    COUNT(*) as clicks_last_24h,
                    SUM(CASE WHEN status = 'converted' THEN 1 ELSE 0 END) as conversions_last_24h
                FROM conversions 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ");
            $last24h = $stmt->fetch();
            
            // Active users (last 5 minutes)
            $stmt = $this->pdo->query("
                SELECT COUNT(DISTINCT ip_address) as active_users
                FROM conversions 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
            ");
            $activeUsers = $stmt->fetch();
            
            return array_merge($lastHour, $last24h, $activeUsers);
        } catch (Exception $e) {
            error_log("Real-time stats error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Export analytics data
     */
    public function exportData($format = 'csv', $filters = []) {
        try {
            $data = $this->getDetailedAnalytics($filters);
            
            if ($format === 'csv') {
                $filename = 'analytics_export_' . date('Y-m-d_H-i-s') . '.csv';
                $filepath = 'exports/' . $filename;
                
                // Create exports directory if it doesn't exist
                if (!is_dir('exports')) {
                    mkdir('exports', 0755, true);
                }
                
                $file = fopen($filepath, 'w');
                
                // Write CSV header
                if (!empty($data)) {
                    fputcsv($file, array_keys($data[0]));
                    
                    // Write data rows
                    foreach ($data as $row) {
                        fputcsv($file, $row);
                    }
                }
                
                fclose($file);
                return $filepath;
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Export data error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get A/B test results
     */
    public function getABTestResults($testId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    variant,
                    COUNT(*) as impressions,
                    SUM(CASE WHEN status = 'converted' THEN 1 ELSE 0 END) as conversions,
                    ROUND((SUM(CASE WHEN status = 'converted' THEN 1 ELSE 0 END) / COUNT(*)) * 100, 2) as conversion_rate,
                    SUM(CASE WHEN status = 'converted' THEN payout ELSE 0 END) as revenue
                FROM conversions 
                WHERE test_id = ?
                GROUP BY variant
                ORDER BY conversion_rate DESC
            ");
            $stmt->execute([$testId]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("A/B test results error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get cohort analysis
     */
    public function getCohortAnalysis() {
        try {
            $stmt = $this->pdo->query("
                SELECT 
                    DATE_FORMAT(created_at, '%Y-%m') as cohort_month,
                    COUNT(DISTINCT ip_address) as cohort_size,
                    COUNT(*) as total_clicks,
                    SUM(CASE WHEN status = 'converted' THEN 1 ELSE 0 END) as conversions,
                    ROUND((SUM(CASE WHEN status = 'converted' THEN 1 ELSE 0 END) / COUNT(*)) * 100, 2) as conversion_rate
                FROM conversions 
                WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                ORDER BY cohort_month DESC
            ");
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Cohort analysis error: " . $e->getMessage());
            return [];
        }
    }
}
?>