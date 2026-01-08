# EVOAPP - Multi-instance WhatsApp Management

A web application for managing multiple WhatsApp instances via EvolutionAPI with a WhatsApp Web-like experience.

## Features

### Core Functionality
- **Multi-instance Management**: Operate multiple WhatsApp instances simultaneously
- **Real-time Messaging**: Send/receive text and media messages
- **WhatsApp Web UI**: Familiar interface similar to WhatsApp Web
- **Dashboard**: Overview with statistics and instance management
- **Webhook Integration**: Real-time updates via EvolutionAPI webhooks
- **Campaign System**: Schedule and send marketing campaigns
- **Contact Management**: Import/export contacts, lists, and group extraction
- **Group Management**: List groups and extract participants
- **Debug & Logs**: Comprehensive logging and debugging tools

### Technical Features
- **PHP 8.1+**: Modern PHP with PDO for database operations
- **MySQL/MariaDB**: Robust database schema
- **cPanel Compatible**: Designed for shared hosting environments
- **Security**: Session authentication, CSRF protection, XSS prevention
- **Responsive Design**: Mobile-friendly interface
- **Dark Theme**: WhatsApp-like dark theme

## Installation

### Prerequisites
- PHP 8.1 or higher
- MySQL 5.7+ or MariaDB 10.2+
- cPanel or similar hosting environment
- EvolutionAPI instance(s)

### Step 1: Upload Files
1. Upload all files to your desired directory (e.g., `/public_html/evoapp/`)
2. Ensure the directory is web-accessible

### Step 2: Configure Database
1. Create a MySQL database and user
2. Import the database schema:
   ```sql
   -- Import database/evoapp_schema.sql
   -- Import database/initial_data.sql
   ```

### Step 3: Configure Application
1. Copy `config/config.php.example` to `config/config.php`
2. Edit the configuration:
   ```php
   // Database
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'your_database_name');
   define('DB_USER', 'your_database_user');
   define('DB_PASS', 'your_database_password');
   
   // EvolutionAPI
   define('EVO_BASE_URL', 'https://your-evolution-api.com');
   define('APP_KEY', 'your-32-character-encryption-key');
   
   // Application
   define('APP_URL', 'https://yourdomain.com/evoapp');
   ```

### Step 4: Set Up Cron Job
Add this cron job in cPanel (run every minute):
```
/usr/local/bin/php -q /home/username/public_html/evoapp/cron.php
```

### Step 5: Configure Webhooks
For each EvolutionAPI instance, set the webhook URL to:
```
https://yourdomain.com/evoapp/index.php?r=webhook/evolution&instance=YOUR_INSTANCE_SLUG
```

### Step 6: Log In
1. Visit `https://yourdomain.com/evoapp/`
2. Default credentials:
   - Email: `admin@evoapp.com`
   - Password: `admin123`

## Configuration

### Environment Variables
Edit `config/config.php` to configure:

#### Database Settings
- `DB_HOST`: Database host (usually `localhost`)
- `DB_NAME`: Database name
- `DB_USER`: Database username
- `DB_PASS`: Database password

#### EvolutionAPI Settings
- `EVO_BASE_URL`: Base URL of your EvolutionAPI instance
- `APP_KEY`: 32-character encryption key for future use

#### Application Settings
- `APP_URL`: Your application URL
- `TIMEZONE`: Default timezone (default: `America/Bogota`)
- `DEBUG`: Enable/disable debug mode

#### Security Settings
- `SESSION_LIFETIME`: Session timeout in seconds (default: 7200)
- `MAX_UPLOAD_SIZE`: Maximum file upload size (default: 10MB)

## User Roles & Permissions

### Default Roles
1. **Admin**: Full access to all features
2. **Supervisor**: Most permissions except user management
3. **Agent**: Basic inbox and sending permissions
4. **Readonly**: View-only access

### Permissions List
- `dashboard.view`: View dashboard
- `instances.manage`: Manage instances
- `instances.view`: View instances
- `inbox.view`: View inbox
- `inbox.send_text`: Send text messages
- `inbox.send_media`: Send media messages
- `contacts.view`: View contacts
- `contacts.edit`: Edit contacts
- `contacts.import`: Import contacts
- `contacts.export`: Export contacts
- `lists.manage`: Manage contact lists
- `campaigns.view`: View campaigns
- `campaigns.edit`: Edit campaigns
- `campaigns.execute`: Execute campaigns
- `groups.view`: View groups
- `groups.extract`: Extract group participants
- `logs.view`: View logs
- `debug.test`: Test debug features
- `users.manage`: Manage users
- `audit.view`: View audit logs

## API Integration

### EvolutionAPI Endpoints Used
- `POST /chat/findChats/{slug}`: List chats
- `POST /chat/findMessages/{slug}`: Get messages
- `POST /message/sendText/{slug}`: Send text message
- `POST /message/sendMedia/{slug}`: Send media message
- `GET /group/participants/{slug}`: Get group participants

### Webhook Events
- `messages.upsert`: New or updated message
- `messages.update`: Message status update
- `messages.delete`: Message deletion

## File Structure

```
evoapp/
├── app/
│   ├── Controllers/     # MVC Controllers
│   ├── Core/           # Core classes (App, DB, Auth, etc.)
│   ├── Models/         # Data models
│   └── Views/          # Template files
├── assets/
│   ├── css/           # Stylesheets
│   └── js/            # JavaScript files
├── config/            # Configuration files
├── database/          # SQL schema and data
├── uploads/           # File uploads (create if needed)
├── index.php          # Front controller
├── cron.php           # Cron job runner
└── README.md          # This file
```

## Security Considerations

1. **Change Default Password**: Immediately change the default admin password
2. **Database Security**: Use strong database credentials
3. **File Permissions**: Ensure proper file permissions (755 for directories, 644 for files)
4. **HTTPS**: Use SSL/TLS for all communications
5. **Firewall**: Configure firewall rules as needed
6. **Regular Updates**: Keep PHP and dependencies updated

## Troubleshooting

### Common Issues

#### 1. Blank Page
- Check PHP error logs
- Ensure `config/config.php` exists and is configured
- Verify file permissions

#### 2. Database Connection Error
- Verify database credentials
- Check if database exists
- Ensure database user has proper permissions

#### 3. Webhook Not Working
- Verify webhook URL is accessible
- Check EvolutionAPI webhook configuration
- Review webhook event logs in Debug section

#### 4. Messages Not Sending
- Verify EvolutionAPI connection
- Check instance API key
- Review error logs

#### 5. File Upload Issues
- Ensure `uploads/` directory exists and is writable
- Check PHP upload limits
- Verify file size restrictions

### Debug Mode
Enable debug mode in `config/config.php`:
```php
define('DEBUG', true);
```

This will display detailed error messages and stack traces.

## Support

For issues and questions:
1. Check the Debug section in the application
2. Review error logs
3. Verify configuration settings
4. Test EvolutionAPI connection independently

## License

This project is proprietary software. All rights reserved.

## Changelog

### Version 1.0.0
- Initial release
- Multi-instance WhatsApp management
- Real-time messaging
- Campaign system
- Contact management
- Debug tools
- Dark theme UI
