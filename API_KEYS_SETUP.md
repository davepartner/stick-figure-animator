# API Keys Setup Guide

This guide will help you obtain all the necessary API keys to run the Stick Figure Video Platform.

## Required API Keys

### 1. OpenAI API Key (REQUIRED)

OpenAI is used for text generation (GPT), image generation (DALL-E 3), and voice generation (TTS).

**Steps to get your API key:**

1. Go to [OpenAI Platform](https://platform.openai.com/)
2. Sign up or log in to your account
3. Navigate to [API Keys](https://platform.openai.com/api-keys)
4. Click "Create new secret key"
5. Give it a name (e.g., "Stick Figure Platform")
6. Copy the key (it starts with `sk-proj-...` or `sk-...`)
7. Add to your `.env` file:
   ```
   OPENAI_API_KEY=sk-proj-your-key-here
   ```

**Pricing:**
- GPT-4.1 Mini: ~$0.0001 per 1K tokens
- GPT-4.1 Nano: ~$0.00005 per 1K tokens
- DALL-E 3: $0.04 per image (standard quality)
- TTS Standard: $0.015 per 1K characters
- TTS HD: $0.030 per 1K characters

**Free Tier:**
- New accounts get $5 free credits
- Expires after 3 months

**Important:**
- Add payment method to continue after free credits
- Monitor usage in [Usage Dashboard](https://platform.openai.com/usage)
- Set spending limits to avoid surprises

---

### 2. Stripe API Keys (REQUIRED for payments)

Stripe is used to process credit card payments for credit purchases.

**Steps to get your API keys:**

1. Go to [Stripe](https://stripe.com/)
2. Sign up or log in to your account
3. Navigate to [Dashboard](https://dashboard.stripe.com/)
4. Click "Developers" in the left sidebar
5. Click "API keys"
6. You'll see two sets of keys:
   - **Test keys** (for development): `pk_test_...` and `sk_test_...`
   - **Live keys** (for production): `pk_live_...` and `sk_live_...`
7. Copy both keys and add to your `.env` file:
   ```
   STRIPE_PUBLISHABLE_KEY=pk_test_your-key-here
   STRIPE_SECRET_KEY=sk_test_your-key-here
   ```

**For Production:**
1. Complete Stripe account verification
2. Switch to live keys in `.env`
3. Test with real payments in small amounts first

**Pricing:**
- 2.9% + $0.30 per successful transaction
- No monthly fees
- No setup fees

**Important:**
- Use test keys for development
- Never commit secret keys to version control
- Enable webhook endpoints for payment verification

---

### 3. Paystack API Keys (OPTIONAL - Alternative payment gateway)

Paystack is popular in Africa and supports local payment methods.

**Steps to get your API keys:**

1. Go to [Paystack](https://paystack.com/)
2. Sign up or log in to your account
3. Navigate to [Dashboard](https://dashboard.paystack.com/)
4. Go to Settings → API Keys & Webhooks
5. You'll see test and live keys
6. Copy both keys and add to your `.env` file:
   ```
   PAYSTACK_PUBLIC_KEY=pk_test_your-key-here
   PAYSTACK_SECRET_KEY=sk_test_your-key-here
   ```

**Pricing:**
- 1.5% + ₦100 per transaction (Nigeria)
- Varies by country
- No monthly fees

**Important:**
- Requires business verification for live mode
- Supports multiple African currencies
- Lower fees than Stripe in some regions

---

## Optional API Keys (For Cost Optimization)

### 4. DeepSeek API Key (OPTIONAL - Budget text generation)

DeepSeek offers cheaper text generation compared to GPT models.

**Steps to get your API key:**

1. Go to [DeepSeek Platform](https://platform.deepseek.com/)
2. Sign up for an account
3. Navigate to API Keys section
4. Create a new API key
5. Add to your `.env` file:
   ```
   DEEPSEEK_API_KEY=your-key-here
   ```

**Pricing:**
- ~$0.00003 per 1K tokens (much cheaper than GPT)
- Good quality for simple stories

**Note:** Currently, the platform uses OpenAI-compatible API, so DeepSeek can be accessed through OpenAI base URL configuration.

---

### 5. Segmind API Key (OPTIONAL - Budget image generation)

Segmind offers cheaper image generation compared to DALL-E 3.

**Steps to get your API key:**

1. Go to [Segmind](https://www.segmind.com/)
2. Sign up for an account
3. Navigate to API section
4. Create a new API key
5. Add to your `.env` file:
   ```
   SEGMIND_API_KEY=your-key-here
   ```

**Pricing:**
- ~$0.002 per image (20x cheaper than DALL-E 3)
- Good for high-volume generation

**Note:** Implementation for Segmind is planned but not yet complete in the codebase.

---

### 6. ElevenLabs API Key (OPTIONAL - Premium voice generation)

ElevenLabs offers high-quality, realistic voice generation.

**Steps to get your API key:**

1. Go to [ElevenLabs](https://elevenlabs.io/)
2. Sign up for an account
3. Navigate to Profile Settings
4. Copy your API key
5. Add to your `.env` file:
   ```
   ELEVENLABS_API_KEY=your-key-here
   ```

**Pricing:**
- Free tier: 10,000 characters/month
- Creator: $5/month for 30,000 characters
- Pro: $22/month for 100,000 characters

**Note:** Implementation for ElevenLabs is planned but not yet complete in the codebase.

---

## Quick Setup Checklist

### Minimum Setup (Development)
- [ ] OpenAI API key (with $5 free credits)
- [ ] Stripe test keys
- [ ] Database configured
- [ ] Run migrations: `php artisan migrate:fresh --seed`
- [ ] Start server: `php artisan serve`
- [ ] Start queue worker: `php artisan queue:work`

### Full Setup (Production)
- [ ] OpenAI API key (with payment method)
- [ ] Stripe live keys (account verified)
- [ ] Paystack keys (optional, if targeting Africa)
- [ ] Database configured (MySQL in production)
- [ ] Redis configured for caching and queues
- [ ] Mail server configured
- [ ] SSL certificate installed (HTTPS)
- [ ] Queue worker running (Supervisor)
- [ ] Cron jobs configured
- [ ] Monitoring and logging set up

---

## Cost Estimation

### Per 30-Second Video

**Budget Option (DeepSeek + Segmind + TTS Standard):**
- Text: $0.00003
- Images (10): $0.02
- Voice: $0.0005
- **Total: ~$0.02 per video**

**Premium Option (GPT-4 + DALL-E 3 + TTS Standard):**
- Text: $0.001
- Images (10): $0.40
- Voice: $0.0005
- **Total: ~$0.40 per video**

### Monthly Cost Estimates

**100 videos/month (Budget):**
- API costs: ~$2
- Stripe fees (assuming $500 revenue): ~$17
- **Total: ~$19/month**

**100 videos/month (Premium):**
- API costs: ~$40
- Stripe fees (assuming $500 revenue): ~$17
- **Total: ~$57/month**

**1000 videos/month (Budget):**
- API costs: ~$20
- Stripe fees (assuming $5000 revenue): ~$170
- **Total: ~$190/month**

---

## Security Best Practices

### API Key Management
1. **Never commit API keys to version control**
   - Add `.env` to `.gitignore` (already done)
   - Use `.env.example` as template

2. **Use test keys for development**
   - Stripe: Use `pk_test_...` and `sk_test_...`
   - Paystack: Use `pk_test_...` and `sk_test_...`

3. **Rotate keys regularly**
   - Change keys every 3-6 months
   - Immediately rotate if compromised

4. **Enable 2FA on all accounts**
   - OpenAI, Stripe, Paystack
   - Prevents unauthorized access

5. **Monitor API usage**
   - Set up alerts for unusual activity
   - Check dashboards weekly

6. **Set spending limits**
   - OpenAI: Set monthly spending cap
   - Stripe: Monitor transaction volumes

### Environment Variables
1. **Use strong database passwords**
2. **Generate new APP_KEY** for production: `php artisan key:generate`
3. **Set APP_DEBUG=false** in production
4. **Use HTTPS** in production
5. **Restrict file permissions** on `.env` file: `chmod 600 .env`

---

## Troubleshooting

### "OpenAI API key is not configured"
- Check `.env` file has `OPENAI_API_KEY=sk-...`
- Restart Laravel server after adding key
- Restart queue worker: `php artisan queue:restart`

### "API quota exceeded"
- Check OpenAI usage dashboard
- Add payment method if free credits exhausted
- Increase spending limit if needed

### "Stripe payment failed"
- Verify you're using correct keys (test vs live)
- Check Stripe dashboard for error details
- Ensure webhook endpoints are configured

### "Video generation stuck on processing"
- Check queue worker is running: `php artisan queue:work`
- Check logs: `storage/logs/laravel.log`
- Verify all API keys are valid

---

## Getting Help

- **Documentation**: README.md, DEPLOYMENT.md, QUICKSTART.md
- **Error Handling**: ERROR_HANDLING_IMPROVEMENTS.md
- **Issues**: [GitHub Issues](https://github.com/davepartner/stick-figure-video-platform/issues)
- **OpenAI Support**: https://help.openai.com/
- **Stripe Support**: https://support.stripe.com/
- **Paystack Support**: https://support.paystack.com/

---

## Next Steps

1. Copy `.env.example` to `.env`: `cp .env.example .env`
2. Fill in your API keys in `.env`
3. Configure database settings
4. Run migrations: `php artisan migrate:fresh --seed`
5. Start services:
   ```bash
   php artisan serve
   php artisan queue:work
   ```
6. Test video generation with your first prompt!

**Happy video creating!** 🎬✨
