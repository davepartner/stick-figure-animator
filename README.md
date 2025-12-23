# Stick Figure Animator - AI Video Creation Platform

An AI-powered platform that transforms text prompts into animated stick figure videos with voiceovers, optimized for social media sharing.

## 🎯 Overview

This platform allows users to:
- Generate animated videos from simple text prompts
- Choose video duration (10 seconds to 5 minutes)
- Select AI models for text, images, and voice generation
- Optimize videos for YouTube with AI-generated titles and hashtags
- Download videos or share directly to social media
- Purchase credits via Stripe or Paystack

## ✨ Key Features

### For Users
- **AI Video Generation**: Text → Script → Images → Voice → Video pipeline
- **Model Selection**: Choose between quality and cost for each component
- **YouTube Optimizer**: Generate 3 viral title options with virality scores
- **Credit System**: Pay-as-you-go with transparent pricing
- **Video Management**: Track generation status, download, and regenerate
- **Auto-Cleanup**: Videos expire after configurable period to save space

### For Admins
- **Full Configuration Panel**: Set default models, pricing, and cleanup intervals
- **User Management**: View users, adjust credits, monitor activity
- **Analytics Dashboard**: Track video generation, costs, and revenue
- **Flexible Pricing**: Configure credit costs for each AI model
- **System Settings**: Control cleanup frequency and video retention

## 🏗️ Technical Stack

- **Backend**: Laravel 10.x + PHP 8.1
- **Database**: MySQL/SQLite
- **Queue**: Database/Redis
- **AI**: OpenAI GPT-4, DALL-E 3, TTS
- **Video**: FFmpeg
- **Payments**: Stripe & Paystack
- **Frontend**: Blade + TailwindCSS

## 📊 Cost Breakdown

### Per 30-Second Video
- **Budget**: ~$0.02 - $0.22 (DeepSeek + Segmind + Standard TTS)
- **Premium**: ~$0.40 - $0.82 (GPT-4 + DALL-E 3 + HD TTS)

### Credit Packages
- Starter: 100 credits - $9.99
- Creator: 500 credits - $39.99 ⭐
- Pro: 1000 credits - $69.99
- Enterprise: 5000 credits - $299.99

## 🚀 Quick Start

```bash
# Clone and install
git clone https://github.com/davepartner/stick-figure-animator.git
cd stick-figure-animator
composer install && npm install

# Configure
cp .env.example .env
php artisan key:generate

# Setup database
php artisan migrate:fresh --seed

# Start services
php artisan serve
php artisan queue:work
```

**Default Credentials:**
- Admin: `admin@stickfigure.com` / `password123`
- User: `user@stickfigure.com` / `password123`

## 📁 Project Structure

```
app/
├── Console/Commands/
│   └── CleanupExpiredVideos.php    # Scheduled cleanup
├── Http/Controllers/
│   ├── AdminController.php         # Admin panel
│   ├── VideoController.php         # Video generation
│   └── PaymentController.php       # Payments
├── Jobs/
│   └── VideoGenerationJob.php      # Background processing
├── Models/
│   ├── User.php                    # User + credits
│   ├── Prompt.php                  # Video requests
│   ├── Video.php                   # Generated videos
│   └── SystemSetting.php           # Configuration
└── Services/
    ├── TextGenerationService.php   # AI text
    ├── ImageGenerationService.php  # AI images
    ├── VoiceGenerationService.php  # AI voice
    ├── VideoAssemblyService.php    # FFmpeg
    └── YouTubeOptimizerService.php # SEO
```

## 🎨 User Workflow

1. **Register** → Get free credits
2. **Create Video** → Enter prompt + select models
3. **Review Cost** → See credit estimate
4. **Generate** → AI creates video (2-5 minutes)
5. **Optimize** → Generate YouTube metadata
6. **Download** → Get MP4 file
7. **Buy Credits** → Purchase more when needed

## 🔧 Configuration

### Required Environment Variables
```env
OPENAI_API_KEY=your_key_here
STRIPE_SECRET_KEY=your_key_here
PAYSTACK_SECRET_KEY=your_key_here  # Optional
```

### Admin Panel Settings
- Default AI models
- Credit pricing
- Video cleanup interval
- Task frequency

## 📈 Scaling Tips

- Use Redis for queue/cache
- Enable CDN for videos
- Add rate limiting
- Implement caching for similar prompts
- Consider self-hosted models

## 🐛 Troubleshooting

```bash
# Check queue
php artisan queue:work --once

# Check logs
tail -f storage/logs/laravel.log

# Manual cleanup
php artisan videos:cleanup --force

# Verify FFmpeg
ffmpeg -version
```

## 📝 Documentation

- [Deployment Guide](DEPLOYMENT.md) - Full production setup
- [Laravel README](LARAVEL_README.md) - Framework documentation

## 🔐 Security

- Change default passwords immediately
- Use HTTPS in production
- Set `APP_DEBUG=false`
- Secure API keys
- Regular backups

## 👨‍💻 Author

**David Partner**
- GitHub: [@davepartner](https://github.com/davepartner)

## 📄 License

Proprietary - All rights reserved

---

For detailed deployment instructions, see [DEPLOYMENT.md](DEPLOYMENT.md)
