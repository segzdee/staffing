# DNS Configuration for overtimestaff.com

## Required DNS Records

Configure these records at your DNS provider (Cloudflare, Route53, GoDaddy, etc.):

### Primary Records

| Type | Name | Value | TTL | Notes |
|------|------|-------|-----|-------|
| A | @ | `YOUR_SERVER_IP` | 300 | Apex domain (overtimestaff.com) |
| A | www | `YOUR_SERVER_IP` | 300 | Main site (www.overtimestaff.com) |
| A | * | `YOUR_SERVER_IP` | 300 | Wildcard for agency subdomains |

### Alternative: Using CNAME (if you have a hostname)

| Type | Name | Value | TTL |
|------|------|-------|-----|
| A | @ | `YOUR_SERVER_IP` | 300 |
| CNAME | www | overtimestaff.com | 300 |
| CNAME | * | overtimestaff.com | 300 |

> **Note:** The apex domain (@) must be an A record. Only subdomains can use CNAME.

---

## Email Records (if using email)

| Type | Name | Value | Priority | TTL |
|------|------|-------|----------|-----|
| MX | @ | `mail.overtimestaff.com` | 10 | 3600 |
| TXT | @ | `v=spf1 include:_spf.google.com ~all` | - | 3600 |
| TXT | _dmarc | `v=DMARC1; p=quarantine; rua=mailto:admin@overtimestaff.com` | - | 3600 |

---

## Cloudflare-Specific Setup

If using Cloudflare:

1. **Proxy Status**: Enable orange cloud (Proxied) for DDoS protection
2. **SSL/TLS Mode**: Set to "Full (Strict)"
3. **Always Use HTTPS**: Enable
4. **Automatic HTTPS Rewrites**: Enable
5. **Minimum TLS Version**: TLS 1.2

### Cloudflare Page Rules (Optional)

```
# Force www
URL: overtimestaff.com/*
Setting: Forwarding URL (301)
Destination: https://www.overtimestaff.com/$1

# Cache static assets
URL: www.overtimestaff.com/*.css
Setting: Cache Level = Cache Everything, Edge TTL = 1 month
```

---

## AWS Route53 Setup

```json
{
  "Changes": [
    {
      "Action": "UPSERT",
      "ResourceRecordSet": {
        "Name": "overtimestaff.com",
        "Type": "A",
        "TTL": 300,
        "ResourceRecords": [{"Value": "YOUR_SERVER_IP"}]
      }
    },
    {
      "Action": "UPSERT",
      "ResourceRecordSet": {
        "Name": "www.overtimestaff.com",
        "Type": "A",
        "TTL": 300,
        "ResourceRecords": [{"Value": "YOUR_SERVER_IP"}]
      }
    },
    {
      "Action": "UPSERT",
      "ResourceRecordSet": {
        "Name": "*.overtimestaff.com",
        "Type": "A",
        "TTL": 300,
        "ResourceRecords": [{"Value": "YOUR_SERVER_IP"}]
      }
    }
  ]
}
```

---

## Verification Commands

After setting up DNS, verify with these commands:

```bash
# Check A records
dig overtimestaff.com A +short
dig www.overtimestaff.com A +short

# Check wildcard
dig test.overtimestaff.com A +short

# Check from different DNS servers
dig @8.8.8.8 www.overtimestaff.com A +short
dig @1.1.1.1 www.overtimestaff.com A +short

# Full DNS propagation check
nslookup www.overtimestaff.com
```

---

## DNS Propagation

- **TTL 300 (5 min)**: Changes propagate within 5-30 minutes
- **TTL 3600 (1 hour)**: Changes may take 1-4 hours
- **Global propagation**: Can take up to 48 hours for all DNS servers worldwide

### Check Propagation Status
- https://dnschecker.org/#A/www.overtimestaff.com
- https://www.whatsmydns.net/#A/www.overtimestaff.com

---

## Troubleshooting

### DNS Not Resolving
1. Wait for TTL to expire
2. Flush local DNS cache:
   - macOS: `sudo dscacheutil -flushcache; sudo killall -HUP mDNSResponder`
   - Windows: `ipconfig /flushdns`
   - Linux: `sudo systemd-resolve --flush-caches`

### SSL Certificate Issues
1. Ensure DNS is fully propagated before requesting SSL
2. For wildcard certs, use DNS validation (not HTTP)
3. Check certificate covers all required domains

### Subdomain Not Working
1. Verify wildcard A record exists
2. Check nginx config handles wildcard server_name
3. Ensure SSL certificate includes wildcard
