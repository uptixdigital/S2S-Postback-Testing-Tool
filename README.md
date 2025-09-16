# 🚀 S2S Tracker - Advanced Postback Testing System

A comprehensive, professional-grade S2S (Server-to-Server) postback testing system with advanced analytics, beautiful dark UI, and robust testing capabilities.

## ✨ Features

### 🎯 Core Functionality
- **S2S Postback Testing** - Test your postback URLs with real-time monitoring
- **Offer Management** - Create and manage multiple offers with different types
- **Advanced Analytics** - Detailed tracking with geographic, device, and network data
- **Real-time Monitoring** - Live dashboard with conversion tracking
- **Batch Testing** - Test multiple postbacks simultaneously
- **Stress Testing** - Load test your postback endpoints

### 🎨 User Interface
- **Dark Theme** - Beautiful black background with colorful accents
- **Glass Morphism** - Modern glass effects with backdrop blur
- **Responsive Design** - Works perfectly on desktop, tablet, and mobile
- **Interactive Charts** - Real-time data visualization with Chart.js
- **Smooth Animations** - Polished user experience with CSS animations

### 📊 Analytics & Tracking
- **IP Address Tracking** - Complete IP geolocation data
- **Device Detection** - Mobile, tablet, desktop identification
- **OS Detection** - Windows, macOS, iOS, Android, Linux
- **Browser Detection** - Chrome, Firefox, Safari, Edge, Opera
- **Network Provider** - ISP and network information
- **Geographic Data** - Country, city, region, timezone
- **Conversion Funnel** - Step-by-step conversion analysis
- **Real-time Stats** - Live dashboard updates

### 🛠️ Advanced Tools
- **URL Generator** - Create tracking URLs with custom parameters
- **Postback Validator** - Validate and test postback URLs
- **Performance Monitor** - System performance and uptime monitoring
- **Security Scanner** - Vulnerability scanning
- **Database Manager** - Database optimization and management
- **Data Exporter** - Export data in various formats

## 🚀 Installation

### Requirements
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- cURL extension
- JSON extension
- PDO MySQL extension

### Quick Install

1. **Download/Clone the project**
   ```bash
   git clone https://github.com/your-repo/s2s-tracker.git
   cd s2s-tracker
   ```

2. **Set permissions**
   ```bash
   chmod 755 config/
   chmod 755 assets/
   chmod 755 uploads/
   ```

3. **Run the installer**
   - Open your browser and navigate to `http://yourdomain.com/install.php`
   - Follow the installation wizard
   - Configure your database settings
   - Complete the installation

4. **Delete installer (Security)**
   ```bash
   rm install.php
   ```

### Manual Installation

1. **Create database**
   ```sql
   CREATE DATABASE s2s_tracker CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

2. **Configure database**
   - Copy `config/config.example.php` to `config/config.php`
   - Update database credentials

3. **Import database schema**
   ```bash
   mysql -u username -p s2s_tracker < database/schema.sql
   ```

4. **Set up web server**
   - Point document root to the project directory
   - Enable mod_rewrite (Apache) or configure URL rewriting (Nginx)

## 📖 Usage Guide

### Dashboard
The main dashboard provides an overview of your system:
- **Total Clicks** - Number of total interactions
- **Conversions** - Successful conversions
- **Conversion Rate** - Percentage of successful conversions
- **Revenue** - Total revenue generated
- **Real-time Charts** - Visual representation of data trends

### Creating Offers
1. Navigate to **Offers** section
2. Click **Create New Offer**
3. Fill in offer details:
   - Title and description
   - Offer type (Sweepstakes, Survey, Download, Subscription)
   - Payout amount
   - Status (Active/Inactive/Paused)
4. Save the offer

### Testing Postbacks
1. Go to **Testing** section
2. Enter your postback URL
3. Configure test parameters:
   - Transaction ID (auto-generated or custom)
   - Goal parameter
   - Additional parameters
4. Choose test type:
   - Single test
   - Batch test (10 requests)
   - Stress test (100 requests)
5. Run the test and view results

### Analytics
The analytics section provides detailed insights:
- **Geographic Distribution** - User locations on map/charts
- **Device Analysis** - Mobile vs desktop usage
- **OS Breakdown** - Operating system distribution
- **Network Providers** - ISP analysis
- **Conversion Funnel** - Step-by-step conversion flow
- **Detailed Table** - Raw data with filtering options

## 🔧 Configuration

### Postback Settings
Configure your default postback settings in the **Settings** section:
- **Default Postback URL** - Your main postback endpoint
- **Transaction ID Parameter** - Parameter name for transaction ID
- **Goal Parameter** - Parameter name for goal/action
- **Payout Parameter** - Parameter name for payout amount

### System Settings
- **Timezone** - Set your preferred timezone
- **Currency** - Set display currency
- **Data Retention** - How long to keep data (days)

## 📡 API Integration

### Postback URL Format
Your postback URLs should follow this format:
```
https://tr.optimawall.com/pbtr?transaction_id={transaction_id}&goal={goal}&payout={payout}
```

### Parameters
- `transaction_id` - Unique identifier for the conversion
- `goal` - Action type (conversion, click, etc.)
- `payout` - Revenue amount
- `offer_id` - ID of the offer
- `name` - User's name (if available)
- `email` - User's email (if available)

### Response Codes
The system expects these HTTP response codes:
- `200-299` - Success
- `400-499` - Client error
- `500-599` - Server error

## 🛡️ Security Features

### Built-in Security
- **SQL Injection Protection** - Prepared statements
- **XSS Protection** - Input sanitization
- **CSRF Protection** - Token validation
- **Rate Limiting** - Request throttling
- **Input Validation** - Data validation
- **Secure Headers** - Security headers

### Best Practices
- Keep the system updated
- Use strong database passwords
- Enable HTTPS
- Regular backups
- Monitor logs
- Delete installer after setup

## 📊 Database Schema

### Tables
- `offers` - Offer information
- `conversions` - Conversion tracking data
- `postback_logs` - Postback attempt logs
- `settings` - System configuration
- `test_results` - Testing results
- `analytics` - Daily analytics summary

### Key Fields
- **conversions table** - Stores all user interaction data
- **postback_logs table** - Logs all postback attempts
- **analytics table** - Daily aggregated data

## 🔄 Backup & Maintenance

### Automated Backups
The system includes automated backup functionality:
```php
// Create backup
$backupFile = backupDatabase();

// Clean old data
cleanOldData(365); // Keep 1 year of data
```

### Manual Backup
```bash
mysqldump -u username -p s2s_tracker > backup.sql
```

### Maintenance Tasks
- Regular database optimization
- Log file cleanup
- Cache clearing
- Security updates

## 🚨 Troubleshooting

### Common Issues

**Installation fails**
- Check PHP version (7.4+ required)
- Verify database permissions
- Ensure all extensions are installed

**Postbacks not working**
- Check URL format
- Verify network connectivity
- Check server logs
- Test with curl

**Analytics not updating**
- Check database connection
- Verify cron jobs (if using)
- Check for JavaScript errors

**Performance issues**
- Optimize database queries
- Enable caching
- Check server resources
- Monitor logs

### Debug Mode
Enable debug mode in `config/config.php`:
```php
define('DEBUG', true);
```

### Logs
Check these log files:
- PHP error logs
- Web server logs
- Application logs in `logs/` directory

## 📈 Performance Optimization

### Database Optimization
- Add indexes on frequently queried columns
- Regular table optimization
- Query caching
- Connection pooling

### Caching
- Enable OPcache
- Use Redis/Memcached
- Browser caching
- CDN integration

### Server Optimization
- Use SSD storage
- Sufficient RAM
- Optimize PHP settings
- Enable compression

## 🔮 Advanced Features

### A/B Testing
Test different offer variations:
```php
// Create A/B test
$testId = createABTest($offerId, $variants);

// Get results
$results = getABTestResults($testId);
```

### Cohort Analysis
Analyze user behavior over time:
```php
$cohorts = getCohortAnalysis();
```

### Real-time Notifications
Get notified of important events:
- High conversion rates
- System errors
- Performance issues
- Security alerts

## 📞 Support

### Documentation
- [User Guide](docs/user-guide.md)
- [API Documentation](docs/api.md)
- [Developer Guide](docs/developer.md)
- [FAQ](docs/faq.md)

### Community
- [GitHub Issues](https://github.com/your-repo/s2s-tracker/issues)
- [Discord Community](https://discord.gg/your-server)
- [Email Support](mailto:support@yourdomain.com)

### Professional Support
For enterprise support and custom development:
- Email: enterprise@yourdomain.com
- Phone: +1-XXX-XXX-XXXX
- Custom development available

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🤝 Contributing

We welcome contributions! Please see our [Contributing Guide](CONTRIBUTING.md) for details.

### Development Setup
```bash
git clone https://github.com/your-repo/s2s-tracker.git
cd s2s-tracker
composer install
npm install
npm run dev
```

### Code Standards
- Follow PSR-12 coding standards
- Write unit tests
- Update documentation
- Submit pull requests

## 🎉 Acknowledgments

- Chart.js for beautiful charts
- Font Awesome for icons
- Bootstrap for responsive design
- All contributors and users

## 📊 System Requirements

### Minimum Requirements
- PHP 7.4+
- MySQL 5.7+
- 512MB RAM
- 1GB storage

### Recommended Requirements
- PHP 8.0+
- MySQL 8.0+
- 2GB+ RAM
- 10GB+ storage
- SSD storage
- CDN integration

## 🔄 Changelog

### Version 1.0.0
- Initial release
- Core S2S testing functionality
- Advanced analytics
- Dark UI with glass effects
- Mobile responsive design
- Comprehensive documentation

---

**Made with ❤️ for the affiliate marketing community**

For more information, visit [your-website.com](https://your-website.com)