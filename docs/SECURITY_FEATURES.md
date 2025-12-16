# Security Features Documentation

## Overview

OvertimeStaff has been enhanced with comprehensive security features to protect user accounts and prevent unauthorized access. This document outlines the implemented security measures and how they work.

## 🔐 Account Security Features

### 1. Account Lockout
- **Trigger**: 5 failed login attempts
- **Duration**: User account locked for increasing time periods
- **Database Fields**: `failed_login_attempts`, `locked_until`
- **Notification**: Users receive email notifications about lockouts
- **Admin Control**: Admins can manually unlock accounts via admin panel

### 2. Two-Factor Authentication (2FA)
- **Support**: TOTP (Time-based One-Time Password) using authenticator apps
- **Recovery**: 8 backup recovery codes per user
- **Setup Process**: QR code scan + manual confirmation
- **Database Fields**: `two_factor_secret`, `two_factor_recovery_codes`, `two_factor_confirmed_at`

### 3. Enhanced Password Requirements
- **Minimum Length**: 12 characters (increased from previous requirement)
- **Validation**: Enforced at registration and password change
- **Hashing**: Secure bcrypt hashing with proper salt rounds

## 🛡️ Session Security

### 1. Session Encryption
- **All session data encrypted** using Laravel's built-in encryption
- **Secure cookie handling** with HttpOnly and SameSite flags
- **CSRF protection** on all state-changing requests

### 2. Remember Token Rotation
- **Custom SessionGuard** implementation
- **Token rotation** on every successful login with "Remember Me"
- **60-character tokens** with cryptographically secure generation
- **Automatic cleanup** of old tokens

## 🚦 Rate Limiting

### Authentication Endpoints
- **Login**: `throttle:login` (5 attempts per minute)
- **Registration**: `throttle:registration` (3 attempts per minute)  
- **Password Reset**: `throttle:password-reset` (3 attempts per 5 minutes)
- **Password Change**: `throttle:password-change` (5 attempts per minute)

### API Endpoints
- **Business Registration**: 5 attempts per minute
- **Worker Registration**: 5 attempts per minute
- **All Authenticated API**: Default Laravel rate limiting

## 📝 Security Logging

### Events Logged
- **Failed login attempts** with IP, email, timestamp
- **Successful logins** with user ID, IP, timestamp
- **Account lockouts** with duration and reason
- **Password changes** with user ID and timestamp
- **2FA events** (setup, verification, recovery usage)

### Log Channels
- **Security Channel**: Dedicated `security` log channel
- **Storage**: Configurable (file/database/Splunk/etc.)
- **Rotation**: Daily log rotation with retention policy

## 🔒 Webhook Security

### Signature Verification
- **Incoming webhooks** must include valid signature
- **HMAC-SHA256** verification algorithm
- **Timestamp validation** to prevent replay attacks
- **Configured for**: PayPal, Paystack, Stripe, Razorpay

### Implementation
```php
// Example webhook verification
Route::post('/webhooks/paypal', [PayPalWebhookController::class, 'handle'])
    ->middleware('webhook.signature:paypal');
```

## 🌐 Environment Hardening

### Production Security
- **Dev routes disabled** in production environment
- **Debug mode off** in production
- **Error handling** with generic messages
- **HTTPS enforcement** (when configured)

### Configuration
```php
// .env for production
APP_ENV=production
APP_DEBUG=false
SESSION_ENCRYPT=true
LOG_CHANNEL=daily
```

## 👤 User Registration Process

### Enhanced Security
1. **Email verification required** before account activation
2. **12+ character password** enforced
3. **Rate limiting** prevents spam registrations
4. **IP-based monitoring** for suspicious patterns

### Account Types
- **Worker Registration**: Additional verification steps
- **Business Registration**: Company verification required
- **Agency Registration**: Compliance checks and document upload

## 🔑 Password Reset Flow

### Secure Process
1. **Rate limited** password reset requests
2. **Signed tokens** with expiration (60 minutes)
3. **Single-use tokens** invalidated after use
4. **Security logging** of all reset attempts

### User Experience
- **Email notifications** with reset link
- **Token expiry** warnings
- **Success confirmations** after reset

## 📊 Monitoring & Alerts

### First 48 Hours Monitoring
- **Account lockout events** monitored for patterns
- **2FA adoption rates** tracked
- **Failed login spikes** trigger alerts
- **Webhook verification failures** logged

### Ongoing Monitoring
- **Security dashboards** for admin visibility
- **Automated alerts** for suspicious activity
- **Regular security audits** scheduled
- **Compliance reporting** available

## 🛠️ Admin Management Tools

### Account Management
- **Manual unlock** of locked accounts
- **2FA reset** for lost access scenarios
- **Security event viewer** with search/filter
- **Bulk operations** for security maintenance

### Configuration
- **Rate limiting** adjustment per environment
- **Security logging** level configuration
- **Webhook key** management
- **Security policies** enforcement

## 🔧 Technical Implementation

### Custom Authentication Guard
```php
// config/auth.php
'guards' => [
    'web' => [
        'driver' => 'session-rotating', // Custom guard
        'provider' => 'users',
    ],
],
```

### Security Middleware Stack
```php
// Authentication routes with security
Route::post('login', [LoginController::class, 'login'])
    ->middleware('throttle:login');
    
Route::post('register', [RegisterController::class, 'register'])
    ->middleware('throttle:registration');
```

### Database Schema
```sql
-- Account lockout fields
ALTER TABLE users 
ADD COLUMN failed_login_attempts INT DEFAULT 0,
ADD COLUMN locked_until TIMESTAMP NULL;

-- 2FA fields  
ALTER TABLE users
ADD COLUMN two_factor_secret VARCHAR(255) NULL,
ADD COLUMN two_factor_recovery_codes TEXT NULL,
ADD COLUMN two_factor_confirmed_at TIMESTAMP NULL;
```

## 🚨 Security Incident Response

### Automated Responses
- **Account lockouts** after failed attempts
- **IP blocking** for repeated attacks
- **Admin notifications** for security events
- **Emergency lockdown** capabilities

### Manual Interventions
- **Mass password reset** capability
- **Session invalidation** for all users
- **Emergency 2FA** enforcement
- **Whitelist/blacklist** management

## 📋 Security Checklist

### Pre-Deployment ✅
- [ ] All security migrations applied
- [ ] Session encryption enabled
- [ ] Rate limiting configured
- [ ] Webhook keys set
- [ ] Security logging tested
- [ ] Dev routes disabled in production

### Post-Launch 🚀
- [ ] Monitor login patterns
- [ ] Track 2FA adoption
- [ ] Review security logs
- [ ] Update documentation
- [ ] Train support team
- [ ] Schedule security audits

## 🆘 Support Procedures

### Common Issues
1. **Locked Account**: Guide user to wait or contact support
2. **2FA Problems**: Provide recovery code process
3. **Password Reset**: Explain email delivery expectations
4. **Webhook Issues**: Verify signature configuration

### Escalation Paths
- **Level 1**: Basic security issues (password reset, account lockout)
- **Level 2**: 2FA problems, suspicious activity
- **Level 3**: Security incidents, data breaches

## 📞 Contact & Resources

### Security Team
- **Email**: security@overtimestaff.com
- **Documentation**: Internal Confluence/Notion
- **Alerting**: PagerDuty/Slack notifications

### External Resources
- **OWASP Guidelines**: https://owasp.org/
- **Laravel Security**: https://laravel.com/docs/security
- **Security Updates**: Laravel security mailing list

---

**Last Updated**: December 15, 2025  
**Version**: 1.0  
**Next Review**: January 15, 2026