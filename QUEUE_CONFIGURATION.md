# Queue Configuration Guide

## The Timeout Problem

### What Happened

When generating a 10-second video with 4 images using DALL-E 3:
- Each image takes ~10-15 seconds to generate
- Total time: ~40-60 seconds
- PHP default execution time: **30 seconds**
- **Result**: "Maximum execution time of 30 seconds exceeded" error

### Root Cause

The `.env` file has `QUEUE_CONNECTION=sync`, which means:
- Jobs run **synchronously** (immediately in the web request)
- Subject to PHP's 30-second execution limit
- Blocks the user's browser until complete
- Times out for longer operations

## The Solution

### Option 1: Use Database Queue (Recommended for Development)

**Advantages:**
- No additional software needed
- Works with existing MySQL database
- Easy to set up
- Good for development and small-scale production

**Setup:**

1. **Update .env:**
```env
QUEUE_CONNECTION=database
```

2. **Create jobs table (if not exists):**
```bash
php artisan queue:table
php artisan migrate
```

3. **Start queue worker:**
```bash
php artisan queue:work
```

**Keep the queue worker running in a separate terminal!**

### Option 2: Use Redis Queue (Recommended for Production)

**Advantages:**
- Faster than database queue
- Better performance at scale
- Industry standard
- Supports job priorities

**Setup:**

1. **Install Redis:**
```bash
# macOS
brew install redis
brew services start redis

# Ubuntu/Debian
sudo apt-get install redis-server
sudo systemctl start redis
```

2. **Install PHP Redis extension:**
```bash
# macOS
pecl install redis

# Ubuntu/Debian
sudo apt-get install php-redis
```

3. **Update .env:**
```env
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

4. **Start queue worker:**
```bash
php artisan queue:work redis
```

### Option 3: Keep Sync (Not Recommended)

If you must use sync queue (e.g., shared hosting without queue support):

**Update php.ini or .htaccess:**
```ini
max_execution_time = 600
```

**Or use .htaccess:**
```apache
php_value max_execution_time 600
```

**Note:** This is not ideal because:
- User's browser is blocked during generation
- Server resources are tied up
- No retry mechanism on failure
- Poor user experience

## Current Fix Applied

I've added the following to `VideoGenerationJob.php`:

```php
public function handle(): void
{
    // Increase PHP execution time limit for this job
    set_time_limit(600); // 10 minutes
    ini_set('max_execution_time', '600');
    
    // ... rest of the code
}
```

**This helps but is not a complete solution!**

### Why This Helps

- Increases execution time for the job itself
- Works even with `QUEUE_CONNECTION=sync`
- No configuration changes needed

### Why This Isn't Enough

- Only works if PHP allows `set_time_limit()` (some hosts disable it)
- Still blocks the user's browser with sync queue
- No retry mechanism
- No progress tracking

## Recommended Production Setup

### 1. Use Database or Redis Queue

Update `.env`:
```env
QUEUE_CONNECTION=database  # or redis
```

### 2. Set Up Supervisor (Linux)

Supervisor keeps the queue worker running automatically.

**Install Supervisor:**
```bash
sudo apt-get install supervisor
```

**Create configuration:**
```bash
sudo nano /etc/supervisor/conf.d/stick-figure-queue.conf
```

**Add this content:**
```ini
[program:stick-figure-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/your/project/artisan queue:work database --sleep=3 --tries=1 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/your/project/storage/logs/queue-worker.log
stopwaitsecs=3600
```

**Start Supervisor:**
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start stick-figure-queue:*
```

### 3. Monitor Queue

**Check queue status:**
```bash
php artisan queue:work --once  # Process one job
php artisan queue:listen       # Listen and process jobs
php artisan queue:failed       # List failed jobs
```

**Retry failed jobs:**
```bash
php artisan queue:retry all
```

**Clear failed jobs:**
```bash
php artisan queue:flush
```

## For Your Current Setup (MAMP/Local)

Since you're using MAMP on port 8889, here's what to do:

### Quick Fix (Temporary)

1. **Keep your current .env** with `QUEUE_CONNECTION=sync`
2. **The code fix I applied** will increase execution time
3. **Test if it works** - it should now handle longer generation times

### Proper Fix (Recommended)

1. **Update .env:**
```env
QUEUE_CONNECTION=database
```

2. **Create jobs table:**
```bash
cd /path/to/stick-figure-animator
php artisan queue:table
php artisan migrate
```

3. **Start queue worker** (in a new terminal):
```bash
cd /path/to/stick-figure-animator
php artisan queue:work
```

4. **Keep the queue worker running** while testing

5. **Generate a video** - it should now work without timeout!

## Troubleshooting

### "Queue worker not processing jobs"

**Check if worker is running:**
```bash
ps aux | grep "queue:work"
```

**Restart worker:**
```bash
php artisan queue:restart
php artisan queue:work
```

### "Still getting timeout"

**Check PHP settings:**
```bash
php -i | grep max_execution_time
```

**Increase in php.ini:**
```ini
max_execution_time = 600
```

**Restart PHP:**
```bash
# MAMP: Restart servers in MAMP interface
# Or restart PHP-FPM
sudo service php8.2-fpm restart
```

### "Jobs stuck in processing"

**Clear stale jobs:**
```bash
php artisan queue:clear
php artisan queue:restart
```

**Check logs:**
```bash
tail -f storage/logs/laravel.log
```

## Testing

### Test with Database Queue

1. Update `.env`: `QUEUE_CONNECTION=database`
2. Run migrations: `php artisan migrate`
3. Start worker: `php artisan queue:work`
4. Generate a video
5. Watch the queue worker terminal - you'll see it processing
6. User's browser shows "Processing" immediately and isn't blocked

### Test with Sync Queue (Current Setup)

1. Keep `.env`: `QUEUE_CONNECTION=sync`
2. Generate a video
3. Browser will be blocked for 40-60 seconds
4. Should complete without timeout (thanks to code fix)
5. Not ideal but works

## Performance Comparison

| Queue Type | Setup Difficulty | Performance | User Experience | Production Ready |
|-----------|------------------|-------------|-----------------|------------------|
| **Sync** | Easy | Poor | Poor (blocking) | ❌ No |
| **Database** | Easy | Good | Good | ✅ Yes (small scale) |
| **Redis** | Medium | Excellent | Excellent | ✅ Yes (any scale) |

## Recommendations

### For Development (Your Current Setup)
1. Use **database queue**
2. Run `php artisan queue:work` in a terminal
3. Simple and effective

### For Production (cPanel VPS)
1. Use **Redis queue** if available
2. Set up **Supervisor** to keep worker running
3. Monitor with Laravel Horizon (optional but recommended)

### For Shared Hosting (Limited Options)
1. Use **database queue**
2. Set up **cron job** to process queue:
```cron
* * * * * cd /path/to/project && php artisan queue:work --stop-when-empty
```

## Next Steps

1. **Decide which queue driver to use**
2. **Update .env** accordingly
3. **Start queue worker** (if not sync)
4. **Test video generation**
5. **Set up Supervisor** for production

## Summary

- ✅ **Code fix applied**: Increased execution time in job
- ⚠️ **Current setup**: Still using sync queue (not ideal)
- 🎯 **Recommended**: Switch to database or Redis queue
- 📝 **Production**: Use Supervisor to keep worker running

The code fix will help, but switching to a proper queue system will provide a much better user experience!

---

**Need help setting up the queue?** Let me know which option you'd like to use and I can guide you through it step by step.
