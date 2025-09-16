// S2S Postback Testing System
class S2STracker {
    constructor() {
        this.data = {
            clicks: 0,
            conversions: 0,
            offers: [],
            analytics: [],
            settings: {
                defaultPostbackUrl: 'https://tr.optimawall.com/pbtr',
                defaultTransactionParam: 'transaction_id',
                defaultGoalParam: 'goal'
            }
        };
        
        this.init();
    }

    init() {
        this.loadData();
        this.setupEventListeners();
        this.initializeCharts();
        this.loadPreBuiltOffers();
        this.updateDashboard();
        this.setupNavigation();
    }

    // Data Management
    loadData() {
        const saved = localStorage.getItem('s2s-tracker-data');
        if (saved) {
            this.data = { ...this.data, ...JSON.parse(saved) };
        }
    }

    saveData() {
        localStorage.setItem('s2s-tracker-data', JSON.stringify(this.data));
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
        
        navToggle.addEventListener('click', () => {
            navToggle.classList.toggle('active');
            navMenu.classList.toggle('active');
        });

        // Test form
        document.getElementById('testForm').addEventListener('submit', (e) => {
            e.preventDefault();
            this.testPostback();
        });

        // Offer form
        document.getElementById('offerForm').addEventListener('submit', (e) => {
            e.preventDefault();
            this.submitOffer();
        });

        // Create offer form
        document.getElementById('createOfferForm').addEventListener('submit', (e) => {
            e.preventDefault();
            this.createOffer();
        });

        // Settings form
        document.getElementById('settingsForm').addEventListener('submit', (e) => {
            e.preventDefault();
            this.saveSettings();
        });

        // Modal controls
        document.querySelectorAll('.close').forEach(closeBtn => {
            closeBtn.addEventListener('click', (e) => {
                const modal = e.target.closest('.modal');
                modal.classList.remove('active');
            });
        });

        // Create offer button
        document.getElementById('createOfferBtn').addEventListener('click', () => {
            document.getElementById('createOfferModal').classList.add('active');
        });

        // Export/Clear data buttons
        document.getElementById('exportDataBtn').addEventListener('click', () => {
            this.exportData();
        });

        document.getElementById('clearDataBtn').addEventListener('click', () => {
            this.clearData();
        });

        // Search functionality
        document.getElementById('offerSearch').addEventListener('input', (e) => {
            this.searchOffers(e.target.value);
        });

        // Analytics filters
        document.getElementById('dateRange').addEventListener('change', () => {
            this.updateAnalytics();
        });

        document.getElementById('offerFilter').addEventListener('change', () => {
            this.updateAnalytics();
        });
    }

    // Navigation
    setupNavigation() {
        // Handle URL parameters for offer testing
        const urlParams = new URLSearchParams(window.location.search);
        const offerId = urlParams.get('offer');
        
        if (offerId) {
            this.showOfferModal(offerId);
        }
    }

    showSection(sectionName) {
        // Hide all sections
        document.querySelectorAll('.section').forEach(section => {
            section.classList.remove('active');
        });

        // Show selected section
        document.getElementById(sectionName).classList.add('active');

        // Update navigation
        document.querySelectorAll('.nav-link').forEach(link => {
            link.classList.remove('active');
        });
        document.querySelector(`[data-section="${sectionName}"]`).classList.add('active');

        // Update content based on section
        if (sectionName === 'analytics') {
            this.updateAnalytics();
        } else if (sectionName === 'offers') {
            this.renderOffers();
        }
    }

    // Dashboard
    updateDashboard() {
        // Update stats
        document.getElementById('total-clicks').textContent = this.data.clicks;
        document.getElementById('total-conversions').textContent = this.data.conversions;
        
        const conversionRate = this.data.clicks > 0 ? 
            ((this.data.conversions / this.data.clicks) * 100).toFixed(2) : 0;
        document.getElementById('conversion-rate').textContent = conversionRate + '%';

        const totalRevenue = this.data.analytics
            .filter(a => a.status === 'conversion')
            .reduce((sum, a) => sum + (a.payout || 0), 0);
        document.getElementById('total-revenue').textContent = '$' + totalRevenue.toFixed(2);

        // Update activity list
        this.updateActivityList();
    }

    updateActivityList() {
        const activityList = document.getElementById('activityList');
        const recentActivity = this.data.analytics.slice(-10).reverse();

        activityList.innerHTML = recentActivity.map(activity => `
            <div class="activity-item">
                <div class="activity-icon">
                    <i class="fas ${activity.status === 'conversion' ? 'fa-check-circle' : 'fa-mouse-pointer'}"></i>
                </div>
                <div class="activity-content">
                    <div class="activity-title">
                        ${activity.status === 'conversion' ? 'Conversion' : 'Click'} - ${activity.offerTitle || 'Unknown Offer'}
                    </div>
                    <div class="activity-time">
                        ${new Date(activity.timestamp).toLocaleString()}
                    </div>
                </div>
            </div>
        `).join('');
    }

    // Charts
    initializeCharts() {
        this.initConversionChart();
        this.initTrafficChart();
    }

    initConversionChart() {
        const ctx = document.getElementById('conversionChart').getContext('2d');
        const last7Days = this.getLast7DaysData();

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: last7Days.labels,
                datasets: [{
                    label: 'Conversions',
                    data: last7Days.conversions,
                    borderColor: '#00d4ff',
                    backgroundColor: 'rgba(0, 212, 255, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                }, {
                    label: 'Clicks',
                    data: last7Days.clicks,
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
                        labels: {
                            color: '#ffffff'
                        }
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
        const ctx = document.getElementById('trafficChart').getContext('2d');
        const trafficData = this.getTrafficData();

        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: trafficData.labels,
                datasets: [{
                    data: trafficData.values,
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
                        labels: {
                            color: '#ffffff',
                            padding: 20
                        }
                    }
                }
            }
        });
    }

    getLast7DaysData() {
        const labels = [];
        const conversions = [];
        const clicks = [];
        
        for (let i = 6; i >= 0; i--) {
            const date = new Date();
            date.setDate(date.getDate() - i);
            const dateStr = date.toISOString().split('T')[0];
            labels.push(date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }));
            
            const dayData = this.data.analytics.filter(a => 
                a.timestamp.startsWith(dateStr)
            );
            
            conversions.push(dayData.filter(a => a.status === 'conversion').length);
            clicks.push(dayData.length);
        }
        
        return { labels, conversions, clicks };
    }

    getTrafficData() {
        const sources = {};
        this.data.analytics.forEach(activity => {
            const source = activity.source || 'Direct';
            sources[source] = (sources[source] || 0) + 1;
        });
        
        return {
            labels: Object.keys(sources),
            values: Object.values(sources)
        };
    }

    // Offer Management
    loadPreBuiltOffers() {
        const preBuiltOffers = [
            {
                id: 'sweepstakes-1',
                title: 'Win $1000 Cash Prize',
                description: 'Enter our exclusive sweepstakes and win amazing cash prizes! Complete the form to participate.',
                type: 'sweepstakes',
                payout: 1.50,
                conversions: 0,
                image: 'fas fa-trophy'
            },
            {
                id: 'survey-1',
                title: 'Quick Survey - $5 Reward',
                description: 'Take a quick 5-minute survey and earn $5 instantly. Share your opinions and get rewarded.',
                type: 'survey',
                payout: 0.75,
                conversions: 0,
                image: 'fas fa-clipboard-list'
            },
            {
                id: 'download-1',
                title: 'Download App - Get $3',
                description: 'Download our featured app and get $3 credited to your account. Simple and fast!',
                type: 'download',
                payout: 2.25,
                conversions: 0,
                image: 'fas fa-download'
            },
            {
                id: 'subscription-1',
                title: 'Free Trial - Premium Service',
                description: 'Start your free trial of our premium service. Cancel anytime, no commitment required.',
                type: 'subscription',
                payout: 4.00,
                conversions: 0,
                image: 'fas fa-crown'
            }
        ];

        this.data.offers = [...this.data.offers, ...preBuiltOffers];
        this.saveData();
        this.renderOffers();
    }

    renderOffers() {
        const offersGrid = document.getElementById('offersGrid');
        offersGrid.innerHTML = this.data.offers.map(offer => `
            <div class="offer-card glass" onclick="tracker.showOfferModal('${offer.id}')">
                <div class="offer-header">
                    <div>
                        <div class="offer-title">${offer.title}</div>
                        <div class="offer-type">${offer.type}</div>
                    </div>
                </div>
                <div class="offer-description">${offer.description}</div>
                <div class="offer-stats">
                    <div class="offer-payout">$${offer.payout}</div>
                    <div class="offer-conversions">${offer.conversions} conversions</div>
                </div>
                <div class="offer-actions">
                    <button class="btn btn-primary" onclick="event.stopPropagation(); tracker.testOffer('${offer.id}')">
                        <i class="fas fa-play"></i> Test
                    </button>
                    <button class="btn btn-secondary" onclick="event.stopPropagation(); tracker.copyOfferLink('${offer.id}')">
                        <i class="fas fa-copy"></i> Copy Link
                    </button>
                </div>
            </div>
        `).join('');
    }

    showOfferModal(offerId) {
        const offer = this.data.offers.find(o => o.id === offerId);
        if (!offer) return;

        document.getElementById('offerTitle').textContent = offer.title;
        document.querySelector('.offer-description').textContent = offer.description;
        document.querySelector('.offer-image i').className = offer.image;
        
        // Store current offer for form submission
        this.currentOffer = offer;
        
        document.getElementById('offerModal').classList.add('active');
    }

    testOffer(offerId) {
        const offer = this.data.offers.find(o => o.id === offerId);
        if (!offer) return;

        // Generate test URL
        const testUrl = `${window.location.origin}${window.location.pathname}?offer=${offerId}&test=true`;
        
        // Copy to clipboard
        navigator.clipboard.writeText(testUrl).then(() => {
            this.showNotification('Test URL copied to clipboard!', 'success');
        });

        // Open in new tab
        window.open(testUrl, '_blank');
    }

    copyOfferLink(offerId) {
        const offerUrl = `${window.location.origin}${window.location.pathname}?offer=${offerId}`;
        
        navigator.clipboard.writeText(offerUrl).then(() => {
            this.showNotification('Offer link copied to clipboard!', 'success');
        });
    }

    // Postback Testing
    async testPostback() {
        const postbackUrl = document.getElementById('postbackUrl').value;
        const transactionId = document.getElementById('transactionId').value || this.generateTransactionId();
        const goal = document.getElementById('goal').value || 'test';
        const additionalParams = document.getElementById('additionalParams').value;

        if (!postbackUrl) {
            this.showNotification('Please enter a postback URL', 'error');
            return;
        }

        // Build final URL
        let finalUrl = `${postbackUrl}?transaction_id=${transactionId}&goal=${goal}`;
        if (additionalParams) {
            finalUrl += `&${additionalParams}`;
        }

        // Update UI
        document.getElementById('testStatus').textContent = 'Testing...';
        document.getElementById('testStatus').className = 'result-value warning';
        document.getElementById('testResponse').textContent = 'Sending request...';
        document.getElementById('testTime').textContent = '-';

        const startTime = Date.now();

        try {
            // Test the postback
            const response = await fetch(finalUrl, {
                method: 'GET',
                mode: 'no-cors' // Handle CORS issues
            });

            const endTime = Date.now();
            const responseTime = endTime - startTime;

            // Update results
            document.getElementById('testStatus').textContent = 'Success';
            document.getElementById('testStatus').className = 'result-value success';
            document.getElementById('testResponse').textContent = 'Postback sent successfully';
            document.getElementById('testTime').textContent = responseTime + 'ms';

            this.showNotification('Postback test completed successfully!', 'success');

        } catch (error) {
            const endTime = Date.now();
            const responseTime = endTime - startTime;

            document.getElementById('testStatus').textContent = 'Error';
            document.getElementById('testStatus').className = 'result-value error';
            document.getElementById('testResponse').textContent = error.message;
            document.getElementById('testTime').textContent = responseTime + 'ms';

            this.showNotification('Postback test failed: ' + error.message, 'error');
        }
    }

    // Offer Submission
    async submitOffer() {
        const name = document.getElementById('userName').value;
        const email = document.getElementById('userEmail').value;

        if (!name || !email) {
            this.showNotification('Please fill in all fields', 'error');
            return;
        }

        // Generate transaction ID
        const transactionId = this.generateTransactionId();
        
        // Get user data
        const userData = await this.getUserData();
        
        // Record click/conversion
        const analyticsData = {
            id: transactionId,
            timestamp: new Date().toISOString(),
            name: name,
            email: email,
            offerId: this.currentOffer?.id,
            offerTitle: this.currentOffer?.title,
            status: 'conversion',
            payout: this.currentOffer?.payout || 0,
            ...userData
        };

        this.data.analytics.push(analyticsData);
        this.data.conversions++;
        
        if (this.currentOffer) {
            this.currentOffer.conversions++;
        }

        // Fire postback
        await this.firePostback(transactionId, 'conversion', this.currentOffer?.payout || 0);

        // Save data
        this.saveData();
        this.updateDashboard();

        // Show success message
        this.showNotification('Congratulations! You have successfully entered the offer!', 'success');

        // Close modal
        document.getElementById('offerModal').classList.remove('active');

        // Reset form
        document.getElementById('offerForm').reset();
    }

    // User Data Collection
    async getUserData() {
        try {
            // Get IP and location data
            const ipResponse = await fetch('https://ipapi.co/json/');
            const ipData = await ipResponse.json();

            // Get device info
            const deviceInfo = this.getDeviceInfo();

            return {
                ip: ipData.ip,
                country: ipData.country_name,
                city: ipData.city,
                region: ipData.region,
                timezone: ipData.timezone,
                isp: ipData.org,
                ...deviceInfo
            };
        } catch (error) {
            console.error('Error getting user data:', error);
            return this.getDeviceInfo();
        }
    }

    getDeviceInfo() {
        const userAgent = navigator.userAgent;
        
        return {
            device: this.getDeviceType(userAgent),
            os: this.getOperatingSystem(userAgent),
            browser: this.getBrowser(userAgent),
            screen: `${screen.width}x${screen.height}`,
            language: navigator.language,
            timezone: Intl.DateTimeFormat().resolvedOptions().timeZone
        };
    }

    getDeviceType(userAgent) {
        if (/tablet|ipad|playbook|silk/i.test(userAgent)) {
            return 'Tablet';
        }
        if (/mobile|iphone|ipod|android|blackberry|opera|mini|windows\sce|palm|smartphone|iemobile/i.test(userAgent)) {
            return 'Mobile';
        }
        return 'Desktop';
    }

    getOperatingSystem(userAgent) {
        if (userAgent.indexOf('Win') !== -1) return 'Windows';
        if (userAgent.indexOf('Mac') !== -1) return 'macOS';
        if (userAgent.indexOf('X11') !== -1) return 'UNIX';
        if (userAgent.indexOf('Linux') !== -1) return 'Linux';
        if (userAgent.indexOf('Android') !== -1) return 'Android';
        if (userAgent.indexOf('iPhone') !== -1) return 'iOS';
        return 'Unknown';
    }

    getBrowser(userAgent) {
        if (userAgent.indexOf('Chrome') !== -1) return 'Chrome';
        if (userAgent.indexOf('Firefox') !== -1) return 'Firefox';
        if (userAgent.indexOf('Safari') !== -1) return 'Safari';
        if (userAgent.indexOf('Edge') !== -1) return 'Edge';
        if (userAgent.indexOf('Opera') !== -1) return 'Opera';
        return 'Unknown';
    }

    // Postback System
    async firePostback(transactionId, goal, payout = 0) {
        const postbackUrl = this.data.settings.defaultPostbackUrl;
        const finalUrl = `${postbackUrl}?transaction_id=${transactionId}&goal=${goal}&payout=${payout}`;

        try {
            await fetch(finalUrl, {
                method: 'GET',
                mode: 'no-cors'
            });
            console.log('Postback fired:', finalUrl);
        } catch (error) {
            console.error('Postback failed:', error);
        }
    }

    // Analytics
    updateAnalytics() {
        this.updateGeoChart();
        this.updateDeviceChart();
        this.updateOSChart();
        this.updateNetworkChart();
        this.updateAnalyticsTable();
    }

    updateGeoChart() {
        const ctx = document.getElementById('geoChart').getContext('2d');
        const geoData = this.getGeoData();

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: geoData.labels,
                datasets: [{
                    label: 'Conversions',
                    data: geoData.values,
                    backgroundColor: '#00d4ff',
                    borderColor: '#00d4ff',
                    borderWidth: 1
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

    updateDeviceChart() {
        const ctx = document.getElementById('deviceChart').getContext('2d');
        const deviceData = this.getDeviceData();

        new Chart(ctx, {
            type: 'pie',
            data: {
                labels: deviceData.labels,
                datasets: [{
                    data: deviceData.values,
                    backgroundColor: ['#00d4ff', '#ff6b6b', '#51cf66']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        labels: { color: '#ffffff' }
                    }
                }
            }
        });
    }

    updateOSChart() {
        const ctx = document.getElementById('osChart').getContext('2d');
        const osData = this.getOSData();

        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: osData.labels,
                datasets: [{
                    data: osData.values,
                    backgroundColor: ['#00d4ff', '#ff6b6b', '#51cf66', '#ffd43b', '#9c88ff']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        labels: { color: '#ffffff' }
                    }
                }
            }
        });
    }

    updateNetworkChart() {
        const ctx = document.getElementById('networkChart').getContext('2d');
        const networkData = this.getNetworkData();

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: networkData.labels,
                datasets: [{
                    label: 'Users',
                    data: networkData.values,
                    backgroundColor: '#00d4ff'
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

    getGeoData() {
        const countries = {};
        this.data.analytics.forEach(activity => {
            const country = activity.country || 'Unknown';
            countries[country] = (countries[country] || 0) + 1;
        });

        const sorted = Object.entries(countries)
            .sort(([,a], [,b]) => b - a)
            .slice(0, 10);

        return {
            labels: sorted.map(([country]) => country),
            values: sorted.map(([,count]) => count)
        };
    }

    getDeviceData() {
        const devices = {};
        this.data.analytics.forEach(activity => {
            const device = activity.device || 'Unknown';
            devices[device] = (devices[device] || 0) + 1;
        });

        return {
            labels: Object.keys(devices),
            values: Object.values(devices)
        };
    }

    getOSData() {
        const os = {};
        this.data.analytics.forEach(activity => {
            const operatingSystem = activity.os || 'Unknown';
            os[operatingSystem] = (os[operatingSystem] || 0) + 1;
        });

        return {
            labels: Object.keys(os),
            values: Object.values(os)
        };
    }

    getNetworkData() {
        const networks = {};
        this.data.analytics.forEach(activity => {
            const network = activity.isp || 'Unknown';
            networks[network] = (networks[network] || 0) + 1;
        });

        const sorted = Object.entries(networks)
            .sort(([,a], [,b]) => b - a)
            .slice(0, 8);

        return {
            labels: sorted.map(([network]) => network),
            values: sorted.map(([,count]) => count)
        };
    }

    updateAnalyticsTable() {
        const tbody = document.getElementById('analyticsTableBody');
        const recentAnalytics = this.data.analytics.slice(-20).reverse();

        tbody.innerHTML = recentAnalytics.map(activity => `
            <tr>
                <td>${activity.ip || 'N/A'}</td>
                <td>${activity.country || 'N/A'}</td>
                <td>${activity.city || 'N/A'}</td>
                <td>${activity.device || 'N/A'}</td>
                <td>${activity.os || 'N/A'}</td>
                <td>${activity.isp || 'N/A'}</td>
                <td>${new Date(activity.timestamp).toLocaleString()}</td>
                <td>
                    <span class="${activity.status === 'conversion' ? 'success' : 'warning'}">
                        ${activity.status === 'conversion' ? 'Conversion' : 'Click'}
                    </span>
                </td>
            </tr>
        `).join('');
    }

    // Settings
    saveSettings() {
        this.data.settings.defaultPostbackUrl = document.getElementById('defaultPostbackUrl').value;
        this.data.settings.defaultTransactionParam = document.getElementById('defaultTransactionParam').value;
        this.data.settings.defaultGoalParam = document.getElementById('defaultGoalParam').value;
        
        this.saveData();
        this.showNotification('Settings saved successfully!', 'success');
    }

    // Utility Functions
    generateTransactionId() {
        return 'txn_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
    }

    createOffer() {
        const title = document.getElementById('newOfferTitle').value;
        const description = document.getElementById('newOfferDescription').value;
        const type = document.getElementById('newOfferType').value;
        const payout = parseFloat(document.getElementById('newOfferPayout').value) || 0;

        const newOffer = {
            id: 'custom_' + Date.now(),
            title: title,
            description: description,
            type: type,
            payout: payout,
            conversions: 0,
            image: this.getOfferIcon(type)
        };

        this.data.offers.push(newOffer);
        this.saveData();
        this.renderOffers();
        
        document.getElementById('createOfferModal').classList.remove('active');
        document.getElementById('createOfferForm').reset();
        
        this.showNotification('Offer created successfully!', 'success');
    }

    getOfferIcon(type) {
        const icons = {
            sweepstakes: 'fas fa-trophy',
            survey: 'fas fa-clipboard-list',
            download: 'fas fa-download',
            subscription: 'fas fa-crown'
        };
        return icons[type] || 'fas fa-gift';
    }

    searchOffers(query) {
        const offers = document.querySelectorAll('.offer-card');
        offers.forEach(offer => {
            const title = offer.querySelector('.offer-title').textContent.toLowerCase();
            const description = offer.querySelector('.offer-description').textContent.toLowerCase();
            const searchQuery = query.toLowerCase();
            
            if (title.includes(searchQuery) || description.includes(searchQuery)) {
                offer.style.display = 'block';
            } else {
                offer.style.display = 'none';
            }
        });
    }

    exportData() {
        const dataStr = JSON.stringify(this.data, null, 2);
        const dataBlob = new Blob([dataStr], {type: 'application/json'});
        const url = URL.createObjectURL(dataBlob);
        
        const link = document.createElement('a');
        link.href = url;
        link.download = 's2s-tracker-data.json';
        link.click();
        
        URL.revokeObjectURL(url);
        this.showNotification('Data exported successfully!', 'success');
    }

    clearData() {
        if (confirm('Are you sure you want to clear all data? This action cannot be undone.')) {
            this.data = {
                clicks: 0,
                conversions: 0,
                offers: [],
                analytics: [],
                settings: this.data.settings
            };
            this.saveData();
            this.updateDashboard();
            this.renderOffers();
            this.showNotification('All data cleared successfully!', 'success');
        }
    }

    showNotification(message, type = 'info') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.innerHTML = `
            <i class="fas ${type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle'}"></i>
            <span>${message}</span>
        `;

        // Add styles
        notification.style.cssText = `
            position: fixed;
            top: 90px;
            right: 20px;
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 8px;
            padding: 15px 20px;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 10px;
            z-index: 3000;
            animation: slideInRight 0.3s ease;
            max-width: 300px;
        `;

        // Add animation keyframes if not exists
        if (!document.querySelector('#notification-styles')) {
            const style = document.createElement('style');
            style.id = 'notification-styles';
            style.textContent = `
                @keyframes slideInRight {
                    from { transform: translateX(100%); opacity: 0; }
                    to { transform: translateX(0); opacity: 1; }
                }
                @keyframes slideOutRight {
                    from { transform: translateX(0); opacity: 1; }
                    to { transform: translateX(100%); opacity: 0; }
                }
            `;
            document.head.appendChild(style);
        }

        document.body.appendChild(notification);

        // Remove after 3 seconds
        setTimeout(() => {
            notification.style.animation = 'slideOutRight 0.3s ease';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }, 3000);
    }
}

// Initialize the tracker when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.tracker = new S2STracker();
});

// Handle page visibility changes to update data
document.addEventListener('visibilitychange', () => {
    if (!document.hidden && window.tracker) {
        window.tracker.updateDashboard();
    }
});