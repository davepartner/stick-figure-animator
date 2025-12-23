# Stick Figure Animator - Deployment Guide

## Overview

This is a complete AI-powered video creation platform that generates stick figure animations from text prompts. Users can create videos with AI-generated scripts, images, and voiceovers, then optimize them for YouTube sharing.

## Features

- **AI Video Generation**: Text-to-video pipeline using GPT, DALL-E, and TTS
- **Credit System**: Pay-per-use model with Stripe and Paystack integration
- **YouTube Optimization**: AI-generated viral titles, descriptions, and hashtags
- **Admin Panel**: Configure AI models, pricing, and system settings
- **Automated Cleanup**: Scheduled task to delete expired videos
- **User Authentication**: Registration, login, and role-based access control

## System Requirements

- **PHP**: 8.1 or higher
- **Composer**: Latest version
- **Database**: MySQL 5.7+ or SQLite
- **FFmpeg**: 4.0 or higher (for video assembly)
- **Node.js**: 16+ (for asset compilation)
- **Web Server**: Apache or Nginx

## Installation Steps

### 1. Clone the Repository

```bash
git clone https://github.com/davepartner/stick-figure-animator.git
cd stick-figure-animator
```

### 2. Install Dependencies

```bash
composer install
npm install
```

### 3. Environment Configuration

Copy the `.env.example` to `.env`:

```bash
cp .env .env.example
```

Configure the following environment variables:

```env
# Application
APP_NAME="Stick Figure Animator"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_database_user
DB_PASSWORD=your_database_password

# OpenAI API (Required)
OPENAI_API_KEY=your_openai_api_key

# Stripe (Required for payments)
STRIPE_SECRET_KEY=your_stripe_secret_key
STRIPE_PUBLISHABLE_KEY=your_stripe_publishable_key

# Paystack (Optional, alternative payment gateway)
PAYSTACK_SECRET_KEY=your_paystack_secret_key
PAYSTACK_PUBLIC_KEY=your_paystack_public_key

# Queue (Recommended: database or Redis)
QUEUE_CONNECTION=database
```

### 4. Generate Application Key

```bash
php artisan key:generate
```

### 5. Run Migrations and Seeders

```bash
php artisan migrate --seed
```

This will create:
- All database tables
- Default system settings
- Admin user: `admin@stickfigure.com` / `password123`
- Test user: `user@stickfigure.com` / `password123`

**⚠️ IMPORTANT**: Change these default passwords immediately after first login!

### 6. Create Storage Symlink

```bash
php artisan storage:link
```

### 7. Set Directory Permissions

```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### 8. Compile Assets

```bash
npm run build
```

### 9. Configure Queue Worker

The platform uses queues for video generation. Set up a supervisor or systemd service:

**Supervisor Configuration** (`/etc/supervisor/conf.d/stick-figure-worker.conf`):

```ini
[program:stick-figure-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/stick-figure-animator/artisan queue:work --sleep=3 --tries=1 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/stick-figure-animator/storage/logs/worker.log
stopwaitsecs=3600
```

Then:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start stick-figure-worker:*
```

### 10. Configure Scheduled Tasks

Add to crontab:

```bash
crontab -e
```

Add this line:

```
* * * * * cd /path/to/stick-figure-animator && php artisan schedule:run >> /dev/null 2>&1
```

This enables automatic video cleanup based on admin settings.

### 11. Web Server Configuration

**Nginx Example**:

```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /path/to/stick-figure-animator/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

**Apache Example** (`.htaccess` already included):

```apache
<VirtualHost *:80>
    ServerName yourdomain.com
    DocumentRoot /path/to/stick-figure-animator/public

    <Directory /path/to/stick-figure-animator/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

## API Keys and Costs

### OpenAI API

- **Required for**: Text generation, image generation, voice synthesis
- **Get API key**: https://platform.openai.com/api-keys
- **Approximate costs per video**:
  - Text (30s video): $0.0001 - $0.001
  - Images (10 images): $0.40 - $0.80
  - Voice (30s): $0.0005 - $0.001
  - **Total**: ~$0.40 - $0.82 per 30-second video

### Stripe

- **Required for**: Credit card payments
- **Get API keys**: https://dashboard.stripe.com/apikeys
- **Fees**: 2.9% + $0.30 per transaction

### Paystack

- **Optional**: Alternative payment gateway (popular in Africa)
- **Get API keys**: https://dashboard.paystack.com/#/settings/developers
- **Fees**: Varies by region

## Admin Configuration

After deployment, log in as admin and configure:

1. **System Settings** (`/admin/settings`):
   - Default AI models for text, images, and voice
   - Credit pricing for each model
   - Video cleanup interval (24 hours, 7 days, etc.)
   - Cleanup task frequency (hourly, daily, weekly)

2. **User Management** (`/admin/users`):
   - Adjust user credits manually
   - View user activity
   - Manage user roles

3. **Video Analytics** (`/admin/videos`):
   - Monitor video generation
   - Track costs and credits
   - View system usage

## Maintenance

### Manual Video Cleanup

```bash
php artisan videos:cleanup
```

### Check Queue Status

```bash
php artisan queue:work --once
php artisan queue:failed
```

### Clear Cache

```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### Backup Database

```bash
php artisan db:backup  # If backup package is installed
# Or manually:
mysqldump -u username -p database_name > backup.sql
```

## Troubleshooting

### Video Generation Fails

1. Check queue worker is running: `supervisorctl status`
2. Check logs: `storage/logs/laravel.log`
3. Verify OpenAI API key is valid
4. Ensure FFmpeg is installed: `ffmpeg -version`

### Payment Issues

1. Verify Stripe/Paystack keys are correct
2. Check webhook endpoints are configured
3. Review transaction logs in database

### Disk Space Issues

1. Reduce video cleanup interval in admin settings
2. Run manual cleanup: `php artisan videos:cleanup --force`
3. Monitor disk usage: `df -h`

## Security Recommendations

1. **Change default admin password immediately**
2. **Use HTTPS** (Let's Encrypt recommended)
3. **Set `APP_DEBUG=false`** in production
4. **Restrict admin panel** to specific IP addresses
5. **Regular backups** of database and uploaded files
6. **Keep dependencies updated**: `composer update`
7. **Monitor API usage** to prevent abuse

## Scaling Considerations

### High Traffic

- Use **Redis** for queue and cache
- Enable **CDN** for video delivery
- Implement **rate limiting** for API endpoints
- Use **load balancer** for multiple servers

### Cost Optimization

- Implement **video caching** for regenerated prompts
- Use **cheaper AI models** for non-premium users
- Add **bulk pricing tiers**
- Consider **self-hosted models** for images (Stable Diffusion)

## Support

For issues or questions:
- GitHub Issues: https://github.com/davepartner/stick-figure-animator/issues
- Documentation: https://github.com/davepartner/stick-figure-animator/wiki

## License

This project is proprietary software. All rights reserved.
