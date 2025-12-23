# Quick Start Guide - Stick Figure Animator

Get your platform running in 10 minutes!

## Prerequisites Checklist

- [ ] PHP 8.1 or higher installed
- [ ] Composer installed
- [ ] MySQL or SQLite database
- [ ] FFmpeg installed (`ffmpeg -version`)
- [ ] OpenAI API key ([Get one here](https://platform.openai.com/api-keys))
- [ ] Stripe account ([Sign up](https://dashboard.stripe.com/register))

## Step-by-Step Setup

### 1. Clone and Install (2 minutes)

```bash
git clone https://github.com/davepartner/stick-figure-animator.git
cd stick-figure-animator
composer install
npm install
```

### 2. Configure Environment (3 minutes)

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` file:

```env
# Application
APP_NAME="Stick Figure Animator"
APP_URL=http://localhost:8000

# Database (SQLite for quick start)
DB_CONNECTION=sqlite

# OpenAI (REQUIRED - Get from https://platform.openai.com/api-keys)
OPENAI_API_KEY=sk-proj-xxxxxxxxxxxxx

# Stripe (REQUIRED for payments - Get from https://dashboard.stripe.com/apikeys)
STRIPE_SECRET_KEY=sk_test_xxxxxxxxxxxxx
STRIPE_PUBLISHABLE_KEY=pk_test_xxxxxxxxxxxxx

# Paystack (OPTIONAL - Popular in Africa)
PAYSTACK_SECRET_KEY=sk_test_xxxxxxxxxxxxx
PAYSTACK_PUBLIC_KEY=pk_test_xxxxxxxxxxxxx

# Queue
QUEUE_CONNECTION=database
```

### 3. Setup Database (1 minute)

```bash
touch database/database.sqlite
php artisan migrate:fresh --seed
```

This creates:
- All database tables
- Admin account: `admin@stickfigure.com` / `password123`
- Test user: `user@stickfigure.com` / `password123`
- Default system settings

### 4. Create Storage Directories (30 seconds)

```bash
php artisan storage:link
chmod -R 775 storage bootstrap/cache
```

### 5. Start the Platform (1 minute)

**Terminal 1 - Web Server:**
```bash
php artisan serve
```

**Terminal 2 - Queue Worker:**
```bash
php artisan queue:work
```

### 6. Access the Platform

Open browser: **http://localhost:8000**

**Login as Admin:**
- Email: `admin@stickfigure.com`
- Password: `password123`

**⚠️ IMPORTANT:** Change this password immediately!

## First Steps After Login

### As Admin

1. **Go to Admin Panel** → `/admin/dashboard`
2. **Configure System Settings** → `/admin/settings`
   - Set default AI models
   - Adjust credit pricing
   - Configure video cleanup interval

3. **Change Admin Password** → Profile → Update Password

### As User

1. **Buy Credits** → Click "Buy Credits" in navigation
2. **Create Video** → Dashboard → Enter prompt
3. **Select Models** → Choose quality vs. cost
4. **Generate** → Wait 2-5 minutes
5. **Optimize for YouTube** → Click "Generate YouTube Details"
6. **Download** → Save your video

## Testing the Platform

### Create Your First Video

1. Login as user: `user@stickfigure.com` / `password123`
2. Go to Dashboard (auto-redirects to `/videos`)
3. Enter prompt: "A cat learning to fly"
4. Select duration: 30 seconds
5. Keep default models (cheapest option)
6. Click "Generate Video"
7. Wait 2-5 minutes (watch status updates)
8. Download your video!

### Estimated Cost
- 30-second video with default models: **~88 credits** (~$0.88)
- Test user has 100 free credits

## Common Issues & Solutions

### Issue: "Queue worker not running"
**Solution:**
```bash
php artisan queue:work
```
Keep this running in a separate terminal.

### Issue: "Video generation failed"
**Solutions:**
1. Check OpenAI API key is valid
2. Verify FFmpeg is installed: `ffmpeg -version`
3. Check logs: `tail -f storage/logs/laravel.log`
4. Ensure queue worker is running

### Issue: "Payment not working"
**Solutions:**
1. Verify Stripe keys in `.env`
2. Use test mode keys (start with `sk_test_`)
3. Check Stripe dashboard for errors

### Issue: "Permission denied" errors
**Solution:**
```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

## Production Deployment

For production deployment, see [DEPLOYMENT.md](DEPLOYMENT.md) for:
- Web server configuration (Nginx/Apache)
- Queue worker setup (Supervisor)
- Cron job configuration
- Security hardening
- Performance optimization

## Cost Optimization Tips

### Use Cheaper Models
- Text: `deepseek-chat` instead of `gpt-4.1-mini`
- Images: `segmind-consistent` instead of `dall-e-3`
- Voice: `tts-1` instead of `tts-1-hd`

**Savings:** ~$0.60 per video (75% reduction)

### Adjust Video Settings
- Reduce images per second (default: 0.33)
- Shorter videos for testing
- Use lower quality for drafts

## Next Steps

1. **Customize Branding**
   - Update logo in `resources/views/components/application-logo.blade.php`
   - Modify colors in `tailwind.config.js`

2. **Configure Pricing**
   - Admin Panel → Settings
   - Adjust credit costs per model
   - Set competitive credit package prices

3. **Setup Automated Cleanup**
   - Add cron job for scheduled tasks
   - Configure cleanup interval in admin panel

4. **Monitor Usage**
   - Admin Panel → Videos
   - Track costs and revenue
   - Adjust pricing as needed

## Support

- **Documentation**: [README.md](README.md)
- **Deployment Guide**: [DEPLOYMENT.md](DEPLOYMENT.md)
- **GitHub Issues**: https://github.com/davepartner/stick-figure-animator/issues

## Quick Reference

### Artisan Commands
```bash
# Start server
php artisan serve

# Run queue worker
php artisan queue:work

# Clean up expired videos
php artisan videos:cleanup

# Clear cache
php artisan cache:clear

# Run migrations
php artisan migrate

# Seed database
php artisan db:seed
```

### Default Accounts
- Admin: `admin@stickfigure.com` / `password123`
- User: `user@stickfigure.com` / `password123`

### Important URLs
- Home: `http://localhost:8000`
- Login: `http://localhost:8000/login`
- Register: `http://localhost:8000/register`
- Dashboard: `http://localhost:8000/videos`
- Admin Panel: `http://localhost:8000/admin/dashboard`
- Buy Credits: `http://localhost:8000/credits`

---

**🎉 Congratulations!** Your AI video creation platform is now running!

Start creating amazing stick figure animations! 🎬
