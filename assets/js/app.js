/**
 * S2S Tracker - Main Application JavaScript
 * Enhanced functionality with modern ES6+ features
 */

class S2STracker {
    constructor() {
        this.currentSection = 'dashboard';
        this.charts = {};
        this.notifications = [];
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.initializeCharts();
        this.loadDashboardData();
        this.setupRealTimeUpdates();
        this.setupNotifications();
    }

    // Event Listeners
    setupEventListeners() {
        // Navigation
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const section = link.dataset.section;
                this.showSection(section);
            });
        });

        // Mobile menu toggle
        const navToggle = document.querySelector('.nav-toggle');
        const navMenu = document.querySelector('.nav-menu');
        
        if (navToggle && navMenu) {
            navToggle.addEventListener('click', () => {
                navToggle.classList.toggle('active');
                navMenu.classList.toggle('active');
            });
        }

        // Test form
        const testForm = document.getElementById('testForm');
        if (testForm) {
            testForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.runPostbackTest();
            });
        }

        // Settings form
        const settingsForm = document.getElementById('settingsForm');
        if (settingsForm) {
            settingsForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.saveSettings();
            });
        }

        // Create offer form
        const createOfferForm = document.getElementById('createOfferForm');
        if (createOfferForm) {
            createOfferForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.createOffer();
            });
        }

        // Modal controls
        document.querySelectorAll('.close').forEach(closeBtn => {
            closeBtn.addEventListener('click', (e) => {
                const modal = e.target.closest('.modal');
                if (modal) {
                    this.closeModal(modal.id);
                }
            });
        });

        // Chart controls
        document.querySelectorAll('.chart-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                this.handleChartControl(e.target);
            });
        });

        // Search functionality
        const offerSearch = document.getElementById('offerSearch');
        if (offerSearch) {
            offerSearch.addEventListener('input', (e) => {
                this.searchOffers(e.target.value);
            });
        }

        // Analytics filters
        const dateRange = document.getElementById('dateRange');
        if (dateRange) {
            dateRange.addEventListener('change', () => {
                this.updateAnalytics();
            });
        }

        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            this.handleKeyboardShortcuts(e);
        });

        // Window events
        window.addEventListener('resize', () => {
            this.handleResize();
        });

        // Visibility change
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) {
                this.refreshCurrentSection();
            }
        });
    }

    // Navigation
    showSection(sectionName) {
        // Hide all sections
        document.querySelectorAll('.section').forEach(section => {
            section.classList.remove('active');
        });

        // Show selected section
        const targetSection = document.getElementById(sectionName);
        if (targetSection) {
            targetSection.classList.add('active');
            this.currentSection = sectionName;
        }

        // Update navigation
        document.querySelectorAll('.nav-link').forEach(link => {
            link.classList.remove('active');
        });
        
        const activeLink = document.querySelector(`[data-section="${sectionName}"]`);
        if (activeLink) {
            activeLink.classList.add('active');
        }

        // Load section-specific data
        this.loadSectionData(sectionName);

        // Close mobile menu
        const navMenu = document.querySelector('.nav-menu');
        const navToggle = document.querySelector('.nav-toggle');
        if (navMenu && navToggle) {
            navMenu.classList.remove('active');
            navToggle.classList.remove('active');
        }
    }

    loadSectionData(sectionName) {
        switch (sectionName) {
            case 'dashboard':
                this.loadDashboardData();
                break;
            case 'offers':
                this.loadOffers();
                break;
            case 'analytics':
                this.updateAnalytics();
                break;
            case 'testing':
                this.loadTestHistory();
                break;
        }
    }

    // Dashboard
    async loadDashboardData() {
        try {
            const response = await fetch('api/dashboard.php');
            const data = await response.json();
            
            if (data.success) {
                this.updateDashboardStats(data.stats);
                this.updateCharts(data.charts);
                this.updateActivityList(data.activity);
                this.updateTopOffers(data.topOffers);
            }
        } catch (error) {
            console.error('Error loading dashboard data:', error);
            this.showNotification('Failed to load dashboard data', 'error');
        }
    }

    updateDashboardStats(stats) {
        const elements = {
            'total-clicks': stats.total_clicks,
            'total-conversions': stats.total_conversions,
            'conversion-rate': stats.conversion_rate.toFixed(2) + '%',
            'total-revenue': '$' + stats.total_revenue.toFixed(2)
        };

        Object.entries(elements).forEach(([id, value]) => {
            const element = document.getElementById(id);
            if (element) {
                this.animateNumber(element, value);
            }
        });
    }

    animateNumber(element, targetValue) {
        const currentValue = parseFloat(element.textContent.replace(/[^\d.-]/g, '')) || 0;
        const isPercentage = targetValue.includes('%');
        const isCurrency = targetValue.includes('$');
        
        const target = parseFloat(targetValue.replace(/[^\d.-]/g, ''));
        const increment = (target - currentValue) / 50;
        let current = currentValue;

        const timer = setInterval(() => {
            current += increment;
            if ((increment > 0 && current >= target) || (increment < 0 && current <= target)) {
                current = target;
                clearInterval(timer);
            }

            let displayValue = Math.round(current * 100) / 100;
            if (isPercentage) {
                displayValue += '%';
            } else if (isCurrency) {
                displayValue = '$' + displayValue.toFixed(2);
            } else {
                displayValue = Math.round(displayValue).toLocaleString();
            }

            element.textContent = displayValue;
        }, 20);
    }

    updateActivityList(activities) {
        const activityList = document.getElementById('activityList');
        if (!activityList) return;

        activityList.innerHTML = activities.map(activity => `
            <div class="activity-item">
                <div class="activity-icon ${activity.status}">
                    <i class="fas ${activity.status === 'conversion' ? 'fa-check-circle' : 'fa-mouse-pointer'}"></i>
                </div>
                <div class="activity-content">
                    <div class="activity-title">${activity.offer_title || 'Unknown Offer'}</div>
                    <div class="activity-meta">
                        <span class="activity-type">${activity.status}</span>
                        <span class="activity-time">${this.timeAgo(activity.created_at)}</span>
                    </div>
                </div>
                <div class="activity-value">
                    $${parseFloat(activity.payout || 0).toFixed(2)}
                </div>
            </div>
        `).join('');
    }

    updateTopOffers(offers) {
        const offersList = document.querySelector('.offers-list');
        if (!offersList) return;

        offersList.innerHTML = offers.map((offer, index) => `
            <div class="offer-item">
                <div class="offer-rank">#${index + 1}</div>
                <div class="offer-info">
                    <div class="offer-title">${offer.title}</div>
                    <div class="offer-stats">
                        <span>${offer.conversions} conversions</span>
                        <span>$${parseFloat(offer.revenue || 0).toFixed(2)}</span>
                    </div>
                </div>
                <div class="offer-performance">
                    <div class="performance-bar">
                        <div class="performance-fill" style="width: ${offers.length > 0 ? (offer.conversions / offers[0].conversions) * 100 : 0}%"></div>
                    </div>
                </div>
            </div>
        `).join('');
    }

    // Charts
    initializeCharts() {
        this.initConversionChart();
        this.initTrafficChart();
        this.initGeoChart();
        this.initDeviceChart();
    }

    initConversionChart() {
        const ctx = document.getElementById('conversionChart');
        if (!ctx) return;

        this.charts.conversion = new Chart(ctx, {
            type: 'line',
            data: {
                labels: [],
                datasets: [{
                    label: 'Conversions',
                    data: [],
                    borderColor: '#00d4ff',
                    backgroundColor: 'rgba(0, 212, 255, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                }, {
                    label: 'Clicks',
                    data: [],
                    borderColor: '#ff6b6b',
                    backgroundColor: 'rgba(255, 107, 107, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        labels: { color: '#ffffff' }
                    }
                },
                scales: {
                    x: {
                        ticks: { color: '#b3b3b3' },
                        grid: { color: 'rgba(255, 255, 255, 0.1)' }
                    },
                    y: {
                        ticks: { color: '#b3b3b3' },
                        grid: { color: 'rgba(255, 255, 255, 0.1)' }
                    }
                }
            }
        });
    }

    initTrafficChart() {
        const ctx = document.getElementById('trafficChart');
        if (!ctx) return;

        this.charts.traffic = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: [],
                datasets: [{
                    data: [],
                    backgroundColor: [
                        '#00d4ff',
                        '#ff6b6b',
                        '#51cf66',
                        '#ffd43b',
                        '#9c88ff'
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { color: '#ffffff', padding: 20 }
                    }
                }
            }
        });
    }

    initGeoChart() {
        const ctx = document.getElementById('geoChart');
        if (!ctx) return;

        this.charts.geo = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: [],
                datasets: [{
                    label: 'Conversions',
                    data: [],
                    backgroundColor: '#00d4ff',
                    borderColor: '#00d4ff',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { labels: { color: '#ffffff' } }
                },
                scales: {
                    x: {
                        ticks: { color: '#b3b3b3' },
                        grid: { color: 'rgba(255, 255, 255, 0.1)' }
                    },
                    y: {
                        ticks: { color: '#b3b3b3' },
                        grid: { color: 'rgba(255, 255, 255, 0.1)' }
                    }
                }
            }
        });
    }

    initDeviceChart() {
        const ctx = document.getElementById('deviceChart');
        if (!ctx) return;

        this.charts.device = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: [],
                datasets: [{
                    data: [],
                    backgroundColor: ['#00d4ff', '#ff6b6b', '#51cf66']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { labels: { color: '#ffffff' } }
                }
            }
        });
    }

    updateCharts(chartData) {
        // Update conversion chart
        if (this.charts.conversion && chartData.conversion) {
            this.charts.conversion.data.labels = chartData.conversion.labels;
            this.charts.conversion.data.datasets[0].data = chartData.conversion.conversions;
            this.charts.conversion.data.datasets[1].data = chartData.conversion.clicks;
            this.charts.conversion.update();
        }

        // Update traffic chart
        if (this.charts.traffic && chartData.traffic) {
            this.charts.traffic.data.labels = chartData.traffic.labels;
            this.charts.traffic.data.datasets[0].data = chartData.traffic.values;
            this.charts.traffic.update();
        }

        // Update geo chart
        if (this.charts.geo && chartData.geo) {
            this.charts.geo.data.labels = chartData.geo.labels;
            this.charts.geo.data.datasets[0].data = chartData.geo.values;
            this.charts.geo.update();
        }

        // Update device chart
        if (this.charts.device && chartData.device) {
            this.charts.device.data.labels = chartData.device.labels;
            this.charts.device.data.datasets[0].data = chartData.device.values;
            this.charts.device.update();
        }
    }

    // Testing
    async runPostbackTest() {
        const form = document.getElementById('testForm');
        const formData = new FormData(form);
        
        // Update UI
        this.updateTestStatus('testing', 'Running test...');
        
        try {
            const response = await fetch('api/test-postback.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.updateTestResults(result.data);
                this.updateTestStatus('success', 'Test completed successfully');
                this.showNotification('Postback test completed successfully!', 'success');
            } else {
                this.updateTestStatus('error', 'Test failed');
                this.showNotification('Postback test failed: ' + result.message, 'error');
            }
        } catch (error) {
            this.updateTestStatus('error', 'Test failed');
            this.showNotification('Postback test failed: ' + error.message, 'error');
        }
    }

    updateTestStatus(status, message) {
        const statusElement = document.getElementById('testStatus');
        const resultStatus = document.getElementById('resultStatus');
        
        if (statusElement) {
            statusElement.textContent = message;
            statusElement.className = `status-indicator ${status}`;
        }
        
        if (resultStatus) {
            resultStatus.textContent = message;
        }
    }

    updateTestResults(data) {
        const elements = {
            'resultCode': data.response_code || '-',
            'resultTime': data.response_time ? data.response_time + 'ms' : '-',
            'resultSuccessRate': data.success_rate ? data.success_rate + '%' : '-'
        };

        Object.entries(elements).forEach(([id, value]) => {
            const element = document.getElementById(id);
            if (element) {
                element.textContent = value;
            }
        });

        const detailsElement = document.getElementById('resultDetails');
        if (detailsElement) {
            detailsElement.textContent = data.response || 'No response data';
        }
    }

    // Offers
    async loadOffers() {
        try {
            const response = await fetch('api/offers.php');
            const data = await response.json();
            
            if (data.success) {
                this.renderOffers(data.offers);
            }
        } catch (error) {
            console.error('Error loading offers:', error);
            this.showNotification('Failed to load offers', 'error');
        }
    }

    renderOffers(offers) {
        const offersGrid = document.getElementById('offersGrid');
        if (!offersGrid) return;

        offersGrid.innerHTML = offers.map(offer => `
            <div class="offer-card glass-card">
                <div class="offer-header">
                    <div>
                        <div class="offer-title">${offer.title}</div>
                        <div class="offer-type">${offer.type}</div>
                    </div>
                </div>
                <div class="offer-description">${offer.description}</div>
                <div class="offer-stats">
                    <div class="offer-payout">$${parseFloat(offer.payout).toFixed(2)}</div>
                    <div class="offer-conversions">${offer.conversions || 0} conversions</div>
                </div>
                <div class="offer-actions">
                    <button class="btn btn-primary" onclick="tracker.testOffer('${offer.id}')">
                        <i class="fas fa-play"></i> Test
                    </button>
                    <button class="btn btn-secondary" onclick="tracker.copyOfferLink('${offer.id}')">
                        <i class="fas fa-copy"></i> Copy Link
                    </button>
                </div>
            </div>
        `).join('');
    }

    testOffer(offerId) {
        const testUrl = `${window.location.origin}${window.location.pathname}?offer=${offerId}&test=true`;
        
        navigator.clipboard.writeText(testUrl).then(() => {
            this.showNotification('Test URL copied to clipboard!', 'success');
        });

        window.open(testUrl, '_blank');
    }

    copyOfferLink(offerId) {
        const offerUrl = `${window.location.origin}${window.location.pathname}?offer=${offerId}`;
        
        navigator.clipboard.writeText(offerUrl).then(() => {
            this.showNotification('Offer link copied to clipboard!', 'success');
        });
    }

    // Analytics
    async updateAnalytics() {
        try {
            const filters = this.getAnalyticsFilters();
            const response = await fetch('api/analytics.php?' + new URLSearchParams(filters));
            const data = await response.json();
            
            if (data.success) {
                this.updateAnalyticsCharts(data.charts);
                this.updateAnalyticsTable(data.table);
            }
        } catch (error) {
            console.error('Error updating analytics:', error);
            this.showNotification('Failed to update analytics', 'error');
        }
    }

    getAnalyticsFilters() {
        return {
            dateRange: document.getElementById('dateRange')?.value || '7d',
            offerFilter: document.getElementById('offerFilter')?.value || '',
            countryFilter: document.getElementById('countryFilter')?.value || ''
        };
    }

    updateAnalyticsCharts(charts) {
        // Update analytics charts
        Object.entries(charts).forEach(([chartName, chartData]) => {
            if (this.charts[chartName]) {
                this.charts[chartName].data.labels = chartData.labels;
                this.charts[chartName].data.datasets[0].data = chartData.values;
                this.charts[chartName].update();
            }
        });
    }

    updateAnalyticsTable(data) {
        const tbody = document.getElementById('analyticsTableBody');
        if (!tbody) return;

        tbody.innerHTML = data.map(row => `
            <tr>
                <td>${row.transaction_id}</td>
                <td>${row.ip_address}</td>
                <td>${row.country}</td>
                <td>${row.city}</td>
                <td>${row.device}</td>
                <td>${row.os}</td>
                <td>${row.browser}</td>
                <td>${row.isp}</td>
                <td>${this.formatDateTime(row.created_at)}</td>
                <td>
                    <span class="${row.status === 'converted' ? 'success' : 'warning'}">
                        ${row.status}
                    </span>
                </td>
                <td>$${parseFloat(row.payout || 0).toFixed(2)}</td>
            </tr>
        `).join('');
    }

    // Settings
    async saveSettings() {
        const form = document.getElementById('settingsForm');
        const formData = new FormData(form);
        
        try {
            const response = await fetch('api/settings.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showNotification('Settings saved successfully!', 'success');
            } else {
                this.showNotification('Failed to save settings: ' + result.message, 'error');
            }
        } catch (error) {
            this.showNotification('Failed to save settings: ' + error.message, 'error');
        }
    }

    // Modals
    showModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    }

    closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('active');
            document.body.style.overflow = '';
        }
    }

    // Notifications
    setupNotifications() {
        this.notificationContainer = document.getElementById('notificationContainer');
        if (!this.notificationContainer) {
            this.notificationContainer = document.createElement('div');
            this.notificationContainer.id = 'notificationContainer';
            this.notificationContainer.className = 'notification-container';
            document.body.appendChild(this.notificationContainer);
        }
    }

    showNotification(message, type = 'info', duration = 3000) {
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        
        const icon = {
            success: 'fa-check-circle',
            error: 'fa-exclamation-circle',
            warning: 'fa-exclamation-triangle',
            info: 'fa-info-circle'
        }[type] || 'fa-info-circle';
        
        notification.innerHTML = `
            <i class="fas ${icon}"></i>
            <span>${message}</span>
        `;
        
        this.notificationContainer.appendChild(notification);
        
        setTimeout(() => {
            notification.style.animation = 'slideOutRight 0.3s ease';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }, duration);
    }

    // Utility Functions
    timeAgo(dateString) {
        const now = new Date();
        const date = new Date(dateString);
        const diffInSeconds = Math.floor((now - date) / 1000);
        
        if (diffInSeconds < 60) return 'just now';
        if (diffInSeconds < 3600) return Math.floor(diffInSeconds / 60) + ' minutes ago';
        if (diffInSeconds < 86400) return Math.floor(diffInSeconds / 3600) + ' hours ago';
        if (diffInSeconds < 2592000) return Math.floor(diffInSeconds / 86400) + ' days ago';
        
        return date.toLocaleDateString();
    }

    formatDateTime(dateString) {
        return new Date(dateString).toLocaleString();
    }

    handleKeyboardShortcuts(e) {
        // Ctrl/Cmd + number keys for navigation
        if ((e.ctrlKey || e.metaKey) && e.key >= '1' && e.key <= '6') {
            e.preventDefault();
            const sections = ['dashboard', 'offers', 'testing', 'analytics', 'tools', 'settings'];
            const sectionIndex = parseInt(e.key) - 1;
            if (sections[sectionIndex]) {
                this.showSection(sections[sectionIndex]);
            }
        }
        
        // Escape key to close modals
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal.active').forEach(modal => {
                this.closeModal(modal.id);
            });
        }
    }

    handleResize() {
        // Update charts on resize
        Object.values(this.charts).forEach(chart => {
            if (chart && chart.resize) {
                chart.resize();
            }
        });
    }

    refreshCurrentSection() {
        this.loadSectionData(this.currentSection);
    }

    // Public API methods
    refreshDashboard() {
        this.loadDashboardData();
    }

    exportData() {
        window.open('api/export.php', '_blank');
    }

    clearTestResults() {
        if (confirm('Are you sure you want to clear all test results?')) {
            // Implementation for clearing test results
            this.showNotification('Test results cleared', 'success');
        }
    }

    runBatchTest() {
        this.showNotification('Batch testing feature coming soon!', 'info');
    }

    exportAnalytics() {
        window.open('api/export-analytics.php', '_blank');
    }

    scheduleReport() {
        this.showNotification('Report scheduling feature coming soon!', 'info');
    }

    showCreateOfferModal() {
        this.showModal('createOfferModal');
    }

    importOffers() {
        this.showNotification('Import feature coming soon!', 'info');
    }

    exportAllData() {
        window.open('api/export-all.php', '_blank');
    }

    importData() {
        this.showNotification('Import feature coming soon!', 'info');
    }

    clearAllData() {
        if (confirm('Are you sure you want to clear ALL data? This action cannot be undone.')) {
            this.showNotification('Data clearing feature coming soon!', 'warning');
        }
    }

    // Real-time updates
    setupRealTimeUpdates() {
        // Update dashboard every 30 seconds
        setInterval(() => {
            if (this.currentSection === 'dashboard' && !document.hidden) {
                this.loadDashboardData();
            }
        }, 30000);
    }
}

// Initialize the application
document.addEventListener('DOMContentLoaded', () => {
    window.tracker = new S2STracker();
});

// Global functions for HTML onclick handlers
function refreshDashboard() {
    if (window.tracker) {
        window.tracker.refreshDashboard();
    }
}

function exportData() {
    if (window.tracker) {
        window.tracker.exportData();
    }
}

function showCreateOfferModal() {
    if (window.tracker) {
        window.tracker.showCreateOfferModal();
    }
}

function runBatchTest() {
    if (window.tracker) {
        window.tracker.runBatchTest();
    }
}

function clearTestResults() {
    if (window.tracker) {
        window.tracker.clearTestResults();
    }
}

function exportAnalytics() {
    if (window.tracker) {
        window.tracker.exportAnalytics();
    }
}

function scheduleReport() {
    if (window.tracker) {
        window.tracker.scheduleReport();
    }
}

function exportAllData() {
    if (window.tracker) {
        window.tracker.exportAllData();
    }
}

function importData() {
    if (window.tracker) {
        window.tracker.importData();
    }
}

function clearAllData() {
    if (window.tracker) {
        window.tracker.clearAllData();
    }
}

function closeModal(modalId) {
    if (window.tracker) {
        window.tracker.closeModal(modalId);
    }
}