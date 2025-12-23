<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EmailNotificationService
{
    protected $apiUrl;
    protected $apiId;
    protected $apiSecret;
    protected $fromEmail;
    protected $fromName;

    public function __construct()
    {
        $this->apiUrl = 'https://api.sendpulse.com';
        $this->apiId = env('SENDPULSE_API_ID');
        $this->apiSecret = env('SENDPULSE_API_SECRET');
        $this->fromEmail = env('MAIL_FROM_ADDRESS', 'noreply@stickfigure.com');
        $this->fromName = env('MAIL_FROM_NAME', 'Stick Figure Animator');
    }

    /**
     * Get access token from SendPulse
     */
    protected function getAccessToken()
    {
        try {
            $response = Http::post("{$this->apiUrl}/oauth/access_token", [
                'grant_type' => 'client_credentials',
                'client_id' => $this->apiId,
                'client_secret' => $this->apiSecret,
            ]);

            if ($response->successful()) {
                return $response->json()['access_token'];
            }

            Log::error('SendPulse auth failed: ' . $response->body());
            return null;
        } catch (\Exception $e) {
            Log::error('SendPulse auth error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Send video completion notification
     */
    public function sendVideoCompletionEmail($user, $prompt, $video)
    {
        // If SendPulse is not configured, use Laravel's default mail
        if (!$this->apiId || !$this->apiSecret) {
            return $this->sendViaLaravelMail($user, $prompt, $video);
        }

        $token = $this->getAccessToken();
        if (!$token) {
            return $this->sendViaLaravelMail($user, $prompt, $video);
        }

        try {
            $videoUrl = url('/videos/' . $prompt->id);
            $downloadUrl = url('/videos/' . $video->id . '/download');
            
            $htmlContent = $this->getEmailHtml($user, $prompt, $video, $videoUrl, $downloadUrl);
            $textContent = $this->getEmailText($user, $prompt, $video, $videoUrl, $downloadUrl);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
            ])->post("{$this->apiUrl}/smtp/emails", [
                'email' => [
                    'html' => $htmlContent,
                    'text' => $textContent,
                    'subject' => '🎬 Your Video is Ready!',
                    'from' => [
                        'name' => $this->fromName,
                        'email' => $this->fromEmail,
                    ],
                    'to' => [
                        [
                            'name' => $user->name,
                            'email' => $user->email,
                        ],
                    ],
                ],
            ]);

            if ($response->successful()) {
                Log::info("Video completion email sent to {$user->email} for prompt ID {$prompt->id}");
                return true;
            }

            Log::error('SendPulse email failed: ' . $response->body());
            return false;

        } catch (\Exception $e) {
            Log::error('Email sending error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Fallback to Laravel's default mail system
     */
    protected function sendViaLaravelMail($user, $prompt, $video)
    {
        try {
            $videoUrl = url('/videos/' . $prompt->id);
            $downloadUrl = url('/videos/' . $video->id . '/download');

            \Mail::send([], [], function ($message) use ($user, $prompt, $video, $videoUrl, $downloadUrl) {
                $message->to($user->email, $user->name)
                    ->subject('🎬 Your Video is Ready!')
                    ->html($this->getEmailHtml($user, $prompt, $video, $videoUrl, $downloadUrl));
            });

            Log::info("Video completion email sent via Laravel Mail to {$user->email} for prompt ID {$prompt->id}");
            return true;

        } catch (\Exception $e) {
            Log::error('Laravel Mail error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get HTML email content
     */
    protected function getEmailHtml($user, $prompt, $video, $videoUrl, $downloadUrl)
    {
        $expiresIn = $video->expires_at->diffForHumans();
        $duration = $prompt->duration_seconds;

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
        .content { background: #f9fafb; padding: 30px; border-radius: 0 0 10px 10px; }
        .button { display: inline-block; padding: 15px 30px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; margin: 10px 5px; font-weight: bold; }
        .button:hover { background: #5568d3; }
        .info-box { background: white; border-left: 4px solid #667eea; padding: 15px; margin: 20px 0; border-radius: 5px; }
        .warning { background: #fef3c7; border-left-color: #f59e0b; padding: 15px; margin: 20px 0; border-radius: 5px; }
        .footer { text-align: center; padding: 20px; color: #6b7280; font-size: 14px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎬 Your Video is Ready!</h1>
            <p>Your stick figure animation has been generated successfully</p>
        </div>
        <div class="content">
            <p>Hi {$user->name},</p>
            
            <p>Great news! Your video "<strong>{$prompt->original_prompt}</strong>" has been generated and is ready to download.</p>
            
            <div class="info-box">
                <strong>Video Details:</strong><br>
                Duration: {$duration} seconds<br>
                Generated: {$video->created_at->format('M d, Y \\a\\t g:i A')}<br>
                Credits Used: {$prompt->credits_used}
            </div>
            
            <div style="text-align: center; margin: 30px 0;">
                <a href="{$videoUrl}" class="button">View Video</a>
                <a href="{$downloadUrl}" class="button">Download Video</a>
            </div>
            
            <div class="warning">
                <strong>⚠️ Important:</strong> This video will be automatically deleted {$expiresIn} to save server space. Please download it now if you want to keep it permanently!
            </div>
            
            <p>You can view your video, download it, and generate YouTube-optimized titles and descriptions from your dashboard.</p>
            
            <p>Ready to create more videos? <a href="{$videoUrl}">Get started now!</a></p>
            
            <p>Happy creating!<br>
            <strong>The Stick Figure Animator Team</strong></p>
        </div>
        <div class="footer">
            <p>You're receiving this email because you generated a video on Stick Figure Animator.</p>
            <p>&copy; 2024 Stick Figure Animator. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Get plain text email content
     */
    protected function getEmailText($user, $prompt, $video, $videoUrl, $downloadUrl)
    {
        $expiresIn = $video->expires_at->diffForHumans();
        $duration = $prompt->duration_seconds;

        return <<<TEXT
Your Video is Ready!

Hi {$user->name},

Great news! Your video "{$prompt->original_prompt}" has been generated and is ready to download.

Video Details:
- Duration: {$duration} seconds
- Generated: {$video->created_at->format('M d, Y \\a\\t g:i A')}
- Credits Used: {$prompt->credits_used}

View your video: {$videoUrl}
Download your video: {$downloadUrl}

IMPORTANT: This video will be automatically deleted {$expiresIn} to save server space. Please download it now if you want to keep it permanently!

You can view your video, download it, and generate YouTube-optimized titles and descriptions from your dashboard.

Ready to create more videos? Visit: {$videoUrl}

Happy creating!
The Stick Figure Animator Team

---
You're receiving this email because you generated a video on Stick Figure Animator.
© 2024 Stick Figure Animator. All rights reserved.
TEXT;
    }

    /**
     * Send video failure notification
     */
    public function sendVideoFailureEmail($user, $prompt, $errorMessage)
    {
        // If SendPulse is not configured, use Laravel's default mail
        if (!$this->apiId || !$this->apiSecret) {
            return $this->sendFailureViaLaravelMail($user, $prompt, $errorMessage);
        }

        $token = $this->getAccessToken();
        if (!$token) {
            return $this->sendFailureViaLaravelMail($user, $prompt, $errorMessage);
        }

        try {
            $dashboardUrl = url('/videos');
            
            $htmlContent = $this->getFailureEmailHtml($user, $prompt, $errorMessage, $dashboardUrl);
            $textContent = $this->getFailureEmailText($user, $prompt, $errorMessage, $dashboardUrl);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
            ])->post("{$this->apiUrl}/smtp/emails", [
                'email' => [
                    'html' => $htmlContent,
                    'text' => $textContent,
                    'subject' => '❌ Video Generation Failed',
                    'from' => [
                        'name' => $this->fromName,
                        'email' => $this->fromEmail,
                    ],
                    'to' => [
                        [
                            'name' => $user->name,
                            'email' => $user->email,
                        ],
                    ],
                ],
            ]);

            if ($response->successful()) {
                Log::info("Video failure email sent to {$user->email} for prompt ID {$prompt->id}");
                return true;
            }

            Log::error('SendPulse failure email failed: ' . $response->body());
            return false;

        } catch (\Exception $e) {
            Log::error('Failure email sending error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Fallback failure email via Laravel Mail
     */
    protected function sendFailureViaLaravelMail($user, $prompt, $errorMessage)
    {
        try {
            $dashboardUrl = url('/videos');

            \Mail::send([], [], function ($message) use ($user, $prompt, $errorMessage, $dashboardUrl) {
                $message->to($user->email, $user->name)
                    ->subject('❌ Video Generation Failed')
                    ->html($this->getFailureEmailHtml($user, $prompt, $errorMessage, $dashboardUrl));
            });

            Log::info("Video failure email sent via Laravel Mail to {$user->email} for prompt ID {$prompt->id}");
            return true;

        } catch (\Exception $e) {
            Log::error('Laravel Mail failure error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get failure email HTML
     */
    protected function getFailureEmailHtml($user, $prompt, $errorMessage, $dashboardUrl)
    {
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #ef4444; color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
        .content { background: #f9fafb; padding: 30px; border-radius: 0 0 10px 10px; }
        .button { display: inline-block; padding: 15px 30px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; margin: 10px 0; font-weight: bold; }
        .error-box { background: #fee2e2; border-left: 4px solid #ef4444; padding: 15px; margin: 20px 0; border-radius: 5px; }
        .info-box { background: #dbeafe; border-left: 4px solid #3b82f6; padding: 15px; margin: 20px 0; border-radius: 5px; }
        .footer { text-align: center; padding: 20px; color: #6b7280; font-size: 14px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>❌ Video Generation Failed</h1>
            <p>We encountered an issue generating your video</p>
        </div>
        <div class="content">
            <p>Hi {$user->name},</p>
            
            <p>We're sorry, but we encountered an issue while generating your video "<strong>{$prompt->original_prompt}</strong>".</p>
            
            <div class="error-box">
                <strong>Error Details:</strong><br>
                {$errorMessage}
            </div>
            
            <div class="info-box">
                <strong>✅ Good News:</strong><br>
                Your {$prompt->credits_used} credits have been automatically refunded to your account.
            </div>
            
            <p>You can try generating the video again. If the problem persists, please contact our support team.</p>
            
            <div style="text-align: center; margin: 30px 0;">
                <a href="{$dashboardUrl}" class="button">Try Again</a>
            </div>
            
            <p>We apologize for the inconvenience.<br>
            <strong>The Stick Figure Animator Team</strong></p>
        </div>
        <div class="footer">
            <p>&copy; 2024 Stick Figure Animator. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Get failure email text
     */
    protected function getFailureEmailText($user, $prompt, $errorMessage, $dashboardUrl)
    {
        return <<<TEXT
Video Generation Failed

Hi {$user->name},

We're sorry, but we encountered an issue while generating your video "{$prompt->original_prompt}".

Error Details:
{$errorMessage}

GOOD NEWS: Your {$prompt->credits_used} credits have been automatically refunded to your account.

You can try generating the video again. If the problem persists, please contact our support team.

Try again: {$dashboardUrl}

We apologize for the inconvenience.
The Stick Figure Animator Team

---
© 2024 Stick Figure Animator. All rights reserved.
TEXT;
    }
}
