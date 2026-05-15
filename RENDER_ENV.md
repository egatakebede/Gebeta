# 🔐 Gebeta Environment Variables for Render

## Required Environment Variables

### Database Configuration
DB_HOST=<your-render-database-host>
DB_NAME=gebeta
DB_USER=gebeta
DB_PASS=<your-database-password>

### Email Configuration (Brevo)
BREVO_API_KEY=<your-brevo-api-key>
BREVO_SENDER_EMAIL=egatakebede7@gmail.com

---

## How to Set on Render:

1. Go to your Render dashboard
2. Select your web service
3. Go to "Environment" tab
4. Add each variable:

### Copy these exact values:

**DB_HOST**
```
<Will be auto-filled by Render from database>
```

**DB_NAME**
```
gebeta
```

**DB_USER**
```
gebeta
```

**DB_PASS**
```
<Will be auto-filled by Render from database>
```

**BREVO_API_KEY**
```
<your-brevo-api-key>
```

**BREVO_SENDER_EMAIL**
```
egatakebede7@gmail.com
```

---

## Notes:

- Database variables (DB_HOST, DB_PASS) will be automatically set by Render when you connect the database
- Make sure to whitelist Render's IP in Brevo: https://app.brevo.com/security/authorised_ips
- After deployment, update the Brevo API key if needed
