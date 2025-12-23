# Stuck Video Generation Fix

**Date**: December 23, 2024  
**Issue**: Videos stuck in "Generating video" status for hours  
**Status**: ✅ Fixed

## Problem

Videos were getting stuck in "pending" or "processing" status indefinitely when the queue worker wasn't running. Users had no feedback about what was wrong or how to fix it.

**Root Cause:**
1. Queue is set to "database" mode (correct)
2. Jobs are queued successfully
3. But if no queue worker is running (`php artisan queue:work`), jobs never process
4. Videos stay in "pending" status forever
5. Credits are deducted but never refunded
6. No user feedback about the issue

## Solutions Implemented

### 1. **Automatic Stuck Video Detection** ✅

Created a new artisan command: `php artisan videos:fail-stuck`

**What it does:**
- Scans for videos stuck in "pending" or "processing" status
- Configurable timeout (default: 30 minutes)
- Automatically marks stuck videos as "failed"
- Refunds credits to users
- Provides helpful error message

**Usage:**
```bash
# Fail videos stuck for more than 30 minutes (default)
php artisan videos:fail-stuck

# Custom timeout (e.g., 10 minutes)
php artisan videos:fail-stuck --timeout=10
```

**Scheduled Execution:**
- Runs automatically every 15 minutes via Laravel scheduler
- No manual intervention needed
- Ensures stuck videos are cleaned up automatically

**Example Output:**
```
Looking for videos stuck in pending/processing status for more than 30 minutes...
Found 2 stuck video(s).
Processing Prompt ID 1 (stuck for 5 hours ago)
  → Refunded 88 credits to user@example.com
  → Marked as failed

Processing Prompt ID 3 (stuck for 2 hours ago)
  → Refunded 120 credits to admin@example.com
  → Marked as failed

Completed!
Failed videos: 2
Total credits refunded: 208
```

### 2. **Enhanced Error Handling for Job Dispatch** ✅

Added try-catch block in `VideoController::store()`:

**Before:**
```php
VideoGenerationJob::dispatch($prompt->id);
```

**After:**
```php
try {
    VideoGenerationJob::dispatch($prompt->id);
} catch (\Exception $e) {
    // Refund credits and mark as failed
    $user->addCredits($totalCredits);
    $prompt->update([
        'status' => 'failed',
        'error_message' => 'Failed to queue video generation job: ' . $e->getMessage(),
    ]);
    
    return redirect()->back()
        ->with('error', 'Failed to start video generation. Your credits have been refunded. Please try again.');
}
```

**Benefits:**
- Catches job dispatch failures
- Automatically refunds credits
- Shows user-friendly error message
- Prevents stuck videos at the source

### 3. **Improved User Feedback** ✅

Enhanced the video status page with helpful messages:

#### **For Videos Stuck > 10 Minutes:**

Shows a prominent warning box with:
- ⚠️ Clear warning that video is taking longer than expected
- Time elapsed since video was started
- Explanation: "This usually means the queue worker is not running"
- **Step-by-step fix instructions:**
  1. Open a terminal in your project directory
  2. Run: `php artisan queue:work`
  3. Keep the terminal open while videos are generating
- Reassurance: "The video will automatically start processing once the queue worker is running"
- Auto-cleanup notice: "This video will automatically be marked as failed and your credits will be refunded within 30 minutes"

#### **Visual Design:**

```
┌─────────────────────────────────────────────────────────┐
│ ⚠️  Video generation is taking longer than expected     │
│                                                          │
│ Started 5 hours ago. This usually means the queue       │
│ worker is not running.                                  │
│                                                          │
│ ┌─────────────────────────────────────────────────────┐ │
│ │ To fix this issue:                                  │ │
│ │ 1. Open a terminal in your project directory       │ │
│ │ 2. Run: php artisan queue:work                     │ │
│ │ 3. Keep the terminal open while videos generate    │ │
│ │                                                      │ │
│ │ 💡 The video will automatically start processing    │ │
│ │    once the queue worker is running.                │ │
│ └─────────────────────────────────────────────────────┘ │
│                                                          │
│ If the queue worker is already running, this video will │
│ automatically be marked as failed and your credits will  │
│ be refunded within 30 minutes.                          │
└─────────────────────────────────────────────────────────┘
```

#### **Status Messages:**

**Pending:**
- "Waiting to start..."
- "Queued for processing. Started X minutes ago."

**Processing:**
- "Generating your video..."
- "Creating images and voiceover. This may take 2-5 minutes."

**Failed:**
- "✗ Video generation failed"
- Clear error message
- "Your credits have been refunded."
- "Try again" link

### 4. **Better Success Message** ✅

Updated the success message when creating a video:

**Before:**
```
"Video generation started! This may take a few minutes."
```

**After:**
```
"Video generation started! This may take 2-5 minutes. Please keep this page open."
```

**Benefits:**
- Sets realistic expectations (2-5 minutes)
- Reminds users to keep page open for status updates
- More professional and informative

## Technical Implementation

### Files Created

1. **`app/Console/Commands/FailStuckVideos.php`**
   - New artisan command
   - Detects and fails stuck videos
   - Refunds credits automatically
   - Configurable timeout

### Files Modified

1. **`app/Http/Controllers/VideoController.php`**
   - Added try-catch for job dispatch
   - Improved error handling
   - Better success message

2. **`resources/views/videos/show.blade.php`**
   - Added stuck video warning (> 10 minutes)
   - Step-by-step fix instructions
   - Improved status messages
   - Better visual feedback

3. **`app/Console/Kernel.php`**
   - Added automatic stuck video detection
   - Runs every 15 minutes
   - Prevents overlap

### Scheduled Tasks

```php
// In app/Console/Kernel.php
$schedule->command('videos:fail-stuck --timeout=30')
    ->everyFifteenMinutes()
    ->withoutOverlapping();
```

**Cron setup (for production):**
```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

## User Experience Flow

### Scenario 1: Queue Worker Not Running

1. **User creates video** → Credits deducted, video queued
2. **After 5 seconds** → Page shows "Waiting to start..." with spinner
3. **After 10 minutes** → Warning appears with fix instructions
4. **After 30 minutes** → Automatic cleanup:
   - Video marked as failed
   - Credits refunded
   - Error message: "Queue worker not running"
5. **User refreshes page** → Sees failed status with refund notice
6. **User starts queue worker** → Can try again

### Scenario 2: Queue Worker Running

1. **User creates video** → Credits deducted, job dispatched
2. **Immediately** → Job starts processing (status: "processing")
3. **After 2-5 minutes** → Video completes
4. **Page auto-refreshes** → Shows video player and download button

### Scenario 3: Job Dispatch Fails

1. **User creates video** → Job dispatch fails (exception thrown)
2. **Immediately** → Credits refunded, prompt marked as failed
3. **User sees error** → "Failed to start video generation. Your credits have been refunded."
4. **User can try again** → No stuck video, no lost credits

## Benefits

### For Users

1. **Clear feedback**: Know exactly what's wrong
2. **Step-by-step fix**: Instructions to resolve the issue
3. **Automatic refunds**: Credits returned within 30 minutes
4. **No lost credits**: All failures result in refunds
5. **Better expectations**: Realistic time estimates

### For Admins

1. **Automatic cleanup**: No manual intervention needed
2. **Scheduled monitoring**: Runs every 15 minutes
3. **Audit trail**: All refunds are logged
4. **Easy troubleshooting**: Clear error messages

### For Platform

1. **Better UX**: Users understand what's happening
2. **Reduced support**: Self-service fix instructions
3. **Automatic recovery**: Stuck videos cleaned up automatically
4. **Data integrity**: No orphaned records or lost credits

## Error Messages

### Stuck Video (Auto-Failed)

```
Video generation timed out after 30 minutes. This usually means the queue 
worker is not running. Your credits have been refunded. Please ensure the 
queue worker is running (php artisan queue:work) and try again.
```

### Job Dispatch Failed

```
Failed to queue video generation job: [exception message]
```

## Configuration

### Timeout Settings

**Default timeout**: 30 minutes

**Change timeout:**
```bash
# In Kernel.php
$schedule->command('videos:fail-stuck --timeout=60') // 60 minutes
    ->everyFifteenMinutes();
```

### Check Frequency

**Default**: Every 15 minutes

**Change frequency:**
```php
// In Kernel.php
$schedule->command('videos:fail-stuck --timeout=30')
    ->hourly(); // or ->everyThirtyMinutes(), ->daily(), etc.
```

### Warning Threshold

**Default**: 10 minutes

**Change threshold:**
```php
// In resources/views/videos/show.blade.php
$isStuck = $minutesElapsed > 15; // Change from 10 to 15 minutes
```

## Testing

### Manual Testing

1. **Test stuck video detection:**
   ```bash
   # Create a video without queue worker running
   # Wait 1 minute
   php artisan videos:fail-stuck --timeout=1
   # Should mark video as failed and refund credits
   ```

2. **Test warning display:**
   ```bash
   # Create a video without queue worker
   # Wait 11 minutes
   # Refresh video page
   # Should see warning with fix instructions
   ```

3. **Test automatic cleanup:**
   ```bash
   # Create a video without queue worker
   # Wait 30 minutes
   # Run: php artisan schedule:run
   # Video should be failed and credits refunded
   ```

### Test Results

- ✅ Stuck video detection works
- ✅ Credits refunded correctly
- ✅ Error messages are clear
- ✅ Warning appears after 10 minutes
- ✅ Automatic cleanup works
- ✅ Job dispatch error handling works
- ✅ Status messages are helpful

## Production Deployment

### 1. Setup Cron Job

```bash
crontab -e
```

Add this line:
```
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

### 2. Start Queue Worker

**Option A: Manually (for testing)**
```bash
php artisan queue:work
```

**Option B: With Supervisor (recommended for production)**

Create `/etc/supervisor/conf.d/laravel-worker.conf`:
```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path-to-your-project/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path-to-your-project/storage/logs/worker.log
stopwaitsecs=3600
```

Then:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start laravel-worker:*
```

### 3. Monitor Queue

```bash
# Check queue status
php artisan queue:monitor

# Check failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all
```

## Troubleshooting

### Videos Still Getting Stuck

**Check:**
1. Is the queue worker running?
   ```bash
   ps aux | grep "queue:work"
   ```

2. Is the cron job set up?
   ```bash
   crontab -l
   ```

3. Are there errors in the logs?
   ```bash
   tail -f storage/logs/laravel.log
   ```

### Credits Not Refunded

**Check:**
1. Did the command run?
   ```bash
   php artisan videos:fail-stuck --timeout=1
   ```

2. Check transaction records:
   ```bash
   php artisan tinker
   >>> DB::table('transactions')->latest()->take(5)->get();
   ```

### Warning Not Showing

**Check:**
1. Is the video older than 10 minutes?
2. Is the status "pending" or "processing"?
3. Clear browser cache

## Future Enhancements

### Potential Improvements

1. **Email notifications**: Notify users when videos fail
2. **Retry button**: Allow users to retry failed videos with one click
3. **Queue health check**: Dashboard showing queue worker status
4. **Progress indicators**: Show % complete during generation
5. **Estimated time**: Calculate based on duration and queue length
6. **Priority queue**: Premium users get faster processing
7. **Webhook notifications**: Notify external systems when videos complete
8. **Auto-retry**: Automatically retry failed videos once

### Advanced Features

1. **Queue metrics**: Track average processing time
2. **Load balancing**: Multiple queue workers
3. **Failure analysis**: Categorize failure reasons
4. **User notifications**: In-app notifications for video status
5. **Admin dashboard**: Monitor all video generation activity

## Summary

### What Was Fixed

- ✅ Automatic stuck video detection
- ✅ Automatic credit refunds
- ✅ Helpful error messages
- ✅ Step-by-step fix instructions
- ✅ Better status feedback
- ✅ Job dispatch error handling
- ✅ Scheduled cleanup (every 15 minutes)

### Key Improvements

1. **User Experience**: Clear feedback and instructions
2. **Reliability**: Automatic cleanup and refunds
3. **Transparency**: Users know what's happening
4. **Self-Service**: Users can fix issues themselves
5. **Automation**: No manual intervention needed

### Impact

**Before:**
- Videos stuck forever ❌
- Credits lost ❌
- No user feedback ❌
- Manual cleanup needed ❌

**After:**
- Auto-cleanup within 30 minutes ✅
- Credits automatically refunded ✅
- Clear instructions for users ✅
- Fully automated ✅

---

**Status**: Production Ready  
**Tested**: ✅ All scenarios verified  
**Documented**: ✅ Complete documentation  
**Deployed**: Ready to push to GitHub
