# 48-Hour Security Monitoring Guide

## 🚀 Immediate Monitoring (First 48 Hours)

### 1. Account Lockout Monitoring
```bash
# Check for lockout events in real-time
tail -f storage/logs/laravel.log | grep -i "lockout\|failed_login"

# Database query for locked accounts
php artisan tinker --execute="
\$locked = App\Models\User::whereNotNull('locked_until')->get();
echo 'Currently locked accounts: ' . \$locked->count() . PHP_EOL;
foreach(\$locked as \$user) {
    echo '- ' . \$user->email . ' (locked until: ' . \$user->locked_until . ')' . PHP_EOL;
}
"
```

### 2. 2FA Adoption Tracking
```bash
# Track 2FA setup rates
php artisan tinker --execute="
\$total = App\Models\User::whereNotNull('email_verified_at')->count();
\$two_fa = App\Models\User::whereNotNull('two_factor_confirmed_at')->count();
\$rate = \$total > 0 ? (\$two_fa / \$total) * 100 : 0;
echo '2FA Adoption Rate: ' . round(\$rate, 2) . '%' . PHP_EOL;
echo 'Total Users: ' . \$total . PHP_EOL;
echo '2FA Enabled: ' . \$two_fa . PHP_EOL;
"
```

### 3. Failed Login Pattern Analysis
```bash
# Monitor IP addresses with repeated failures
php artisan tinker --execute="
use Illuminate\Support\Facades\Log;
\$logs = file_get_contents(storage_path('logs/laravel.log'));
preg_match_all('/Failed login attempt.*?IP: ([\d\.]+)/', \$logs, \$matches);
\$ips = array_count_values(\$matches[1]);
arsort(\$ips);
echo 'Top 10 IPs with failed logins:' . PHP_EOL;
\$top = array_slice(\$ips, 0, 10, true);
foreach(\$top as \$ip => \$count) {
    echo \$ip . ': ' . \$count . ' attempts' . PHP_EOL;
}
"
```

### 4. Security Event Dashboard
```bash
# Create quick security summary
php artisan tinker --execute="
echo '=== SECURITY DASHBOARD ===' . PHP_EOL;
echo 'Time: ' . now() . PHP_EOL . PHP_EOL;

// Account lockouts (last 24h)
\$lockouts = App\Models\User::where('locked_until', '>', now()->subHours(24))->count();
echo 'Account lockouts (24h): ' . \$lockouts . PHP_EOL;

// Failed login attempts (last hour)
\$failed = Log::channel('security')->where('level', 'warning')
    ->where('created_at', '>', now()->subHour())->count();
echo 'Failed logins (1h): ' . \$failed . PHP_EOL;

// Successful logins (last hour)  
\$success = Log::channel('security')->where('level', 'info')
    ->where('created_at', '>', now()->subHour())->count();
echo 'Successful logins (1h): ' . \$success . PHP_EOL;

// New 2FA setups (last 24h)
\$new_2fa = App\Models\User::where('two_factor_confirmed_at', '>', now()->subHours(24))->count();
echo 'New 2FA setups (24h): ' . \$new_2fa . PHP_EOL;

// Password resets (last 24h)
\$resets = DB::table('password_resets')->where('created_at', '>', now()->subHours(24))->count();
echo 'Password resets (24h): ' . \$resets . PHP_EOL;
"
```

## 📊 Key Metrics to Watch

### Critical Thresholds
- **Lockout Rate**: >10 accounts/hour requires investigation
- **2FA Adoption**: Target >50% within 48 hours
- **Failed Login Success Rate**: Should be <5% of total attempts
- **Password Reset Volume**: Spike may indicate phishing attempts

### Automated Alerts Setup
```bash
# Create monitoring script for repeated checks
cat > monitor_security.sh << 'EOF'
#!/bin/bash

echo "$(date): Running security check..."

# Check for high lockout rate
LOCKOUTS=$(php artisan tinker --execute="echo App\Models\User::whereNotNull('locked_until')->count();" | tail -1)
if [ "$LOCKOUTS" -gt 10 ]; then
    echo "ALERT: High number of locked accounts: $LOCKOUTS"
    # Send alert (configure webhook/email)
fi

# Check 2FA adoption
TOTAL_USERS=$(php artisan tinker --execute="echo App\Models\User::whereNotNull('email_verified_at')->count();" | tail -1)
TWO_FA_USERS=$(php artisan tinker --execute="echo App\Models\User::whereNotNull('two_factor_confirmed_at')->count();" | tail -1)
ADOPTION_RATE=$(echo "scale=2; $TWO_FA_USERS * 100 / $TOTAL_USERS" | bc)
if (( $(echo "$ADOPTION_RATE < 25" | bc -l) )); then
    echo "INFO: Low 2FA adoption rate: $ADOPTION_RATE%"
fi

echo "$(date): Security check completed"
EOF

chmod +x monitor_security.sh

# Run every hour
echo "0 * * * * /path/to/staffing/monitor_security.sh >> /var/log/security_monitor.log" | crontab -
```

## 🔍 Log Analysis Commands

### Real-time Log Monitoring
```bash
# Tail security logs with filtering
tail -f storage/logs/laravel.log | grep -E "(Failed login|Successful login|Lockout|2FA)" --color=always

# Monitor webhook verification
tail -f storage/logs/laravel.log | grep -i "webhook.*signature"
```

### Pattern Detection
```bash
# Detect brute force patterns
grep -E "Failed login.*IP:" storage/logs/laravel.log | awk '{print $NF}' | sort | uniq -c | sort -nr | head -10

# Detect password reset abuse
grep -c "password/reset" storage/logs/laravel.log | awk -F: '{print $1}' | sort | uniq -c
```

## 🛠️ Quick Response Scripts

### Emergency Lock Release
```bash
# Unlock specific user
php artisan tinker --execute="
\$email = 'user@example.com';
\$user = App\Models\User::where('email', \$email)->first();
if(\$user) {
    \$user->failed_login_attempts = 0;
    \$user->locked_until = null;
    \$user->save();
    echo 'User unlocked: ' . \$email . PHP_EOL;
} else {
    echo 'User not found: ' . \$email . PHP_EOL;
}
"
```

### Force 2FA for User
```bash
# Enable 2FA for specific user (admin function)
php artisan tinker --execute="
\$user = App\Models\User::find(1);
if(\$user && !\$user->two_factor_confirmed_at) {
    \$secret = \$user->createTwoFactorAuth();
    echo '2FA Secret for ' . \$user->email . ': ' . \$secret . PHP_EOL;
    echo 'QR Code URL: ' . \$user->twoFactorQrCodeUrl() . PHP_EOL;
}
"
```

## 📈 Reporting Templates

### Daily Security Report
```bash
# Generate daily summary
cat > daily_security_report.md << EOF
# Security Report - $(date +%Y-%m-%d)

## Overview
- Total Users: $(php artisan tinker --execute="echo App\Models\User::count();" | tail -1)
- Active Sessions: $(php artisan tinker --execute="echo DB::table('sessions')->count();" | tail -1)
- Locked Accounts: $(php artisan tinker --execute="echo App\Models\User::whereNotNull('locked_until')->count();" | tail -1)

## Authentication Events
- Failed Logins: $(grep -c "Failed login" storage/logs/laravel.log)
- Successful Logins: $(grep -c "Successful login" storage/logs/laravel.log)  
- Password Resets: $(grep -c "password/reset" storage/logs/laravel.log)

## 2FA Statistics
- Users with 2FA: $(php artisan tinker --execute="echo App\Models\User::whereNotNull('two_factor_confirmed_at')->count();" | tail -1)
- 2FA Adoption Rate: $(php artisan tinker --execute="\$t=App\Models\User::whereNotNull('email_verified_at')->count();\$f=App\Models\User::whereNotNull('two_factor_confirmed_at')->count();echo round((\$f/\$t)*100,2);")
%

## Alerts
- High lockout activity: $(php artisan tinker --execute="echo App\Models\User::where('locked_until','>',now()->subHours(24))->count();" | tail -1) accounts in 24h
- Suspicious IPs: $(grep -E "Failed login.*IP:" storage/logs/laravel.log | awk '{print $NF}' | sort | uniq -c | awk '$1>5' | wc -l)

## Recommendations
- Continue monitoring 2FA adoption
- Review any suspicious IP activity
- Ensure all users complete email verification

EOF
```

## 🚨 Escalation Procedures

### Level 1: Standard Alert
- **Trigger**: Single account lockout, normal failed login volume
- **Action**: Monitor trends, notify team via Slack
- **Timeline**: Within 1 hour

### Level 2: Elevated Alert  
- **Trigger**: >10 account lockouts/hour, unusual IP patterns
- **Action**: Investigate IP sources, consider blocking, notify security lead
- **Timeline**: Within 30 minutes

### Level 3: Critical Alert
- **Trigger**: >50 lockouts/hour, suspected breach, webhook verification failures
- **Action**: Emergency response team, consider service lockdown
- **Timeline**: Immediate (within 15 minutes)

## 📞 Contact List

### Security Team
- **Security Lead**: [Name/Contact]
- **Development Lead**: [Name/Contact]  
- **Operations Lead**: [Name/Contact]
- **Product Lead**: [Name/Contact]

### External Contacts
- **Hosting Provider**: [Contact Info]
- **Security Services**: [Contact Info]
- **Legal/Compliance**: [Contact Info]

---

**Monitor this guide for the first 48 hours post-deployment**  
**Report generated**: $(date)  
**Review schedule**: Daily for first week, then weekly