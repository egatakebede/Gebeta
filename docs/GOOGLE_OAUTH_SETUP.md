# Google OAuth Setup Guide for Gebeta

## Overview
This guide explains how to set up Google OAuth authentication for the Gebeta food delivery app.

## Prerequisites
- Google Developer Account
- Google Cloud Project

## Steps to Setup Google OAuth

### 1. Create a Google Cloud Project
- Go to [Google Cloud Console](https://console.cloud.google.com/)
- Create a new project
- Enable the following APIs:
  - Google+ API
  - Google Sign-In API (or Google Identity Services API)

### 2. Create OAuth 2.0 Credentials
- Go to "APIs & Services" → "Credentials"
- Click "Create Credentials" → "OAuth 2.0 Client IDs"
- Choose "Web application"
- Add authorized redirect URIs:
  - `http://localhost:3000` (for development)
  - `http://localhost` (for local testing)
  - `https://yourdomain.com` (for production)
  - `https://yourdomain.com/api/google-auth.php`
- Save the Client ID and Client Secret

### 3. Configure Gebeta
1. Update `/home/e/Gebeta/index.php` line 144:
   ```javascript
   client_id: 'YOUR_GOOGLE_CLIENT_ID.apps.googleusercontent.com',
   ```

2. (Optional) Add Client Secret to `/home/e/Gebeta/includes/config.php`:
   ```php
   define('GOOGLE_CLIENT_ID', 'YOUR_CLIENT_ID');
   define('GOOGLE_CLIENT_SECRET', 'YOUR_CLIENT_SECRET');
   ```

### 4. Database Schema
The system uses these fields in the `users` table:
- `google_id` - Store Google's unique user ID (optional but recommended)

Add the column if not exists:
```sql
ALTER TABLE users ADD COLUMN google_id VARCHAR(255) UNIQUE DEFAULT NULL;
```

### 5. Location Table (Optional)
If storing user locations:
```sql
CREATE TABLE IF NOT EXISTS user_addresses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    address_type VARCHAR(50),
    name VARCHAR(255),
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```

## Features

### Login with Google
- Users can click the Google button on the login modal
- Redirects to Google Sign-In flow
- On success, creates session and logs in user
- Redirects to appropriate dashboard (customer/restaurant/admin)

### Sign Up with Google
- Users can create new account via Google
- Auto-fills name and email from Google account
- User location is captured and stored
- Automatically sets role to "customer"

### Security Notes
- In production, verify the ID token with Google's servers
- Store `google_id` to link accounts to Google profiles
- Never expose Client Secret in frontend code
- Use HTTPS for all OAuth redirects

## Files Modified
- `/home/e/Gebeta/index.php` - Added Google Sign-In SDK and buttons
- `/home/e/Gebeta/assets/css/style.css` - Added styles for Google button
- `/home/e/Gebeta/assets/js/script.js` - Added Google auth handler
- `/home/e/Gebeta/api/google-auth.php` - Backend OAuth handler (created)

## Troubleshooting

### "Cross-origin request blocked" error
- Add your domain to authorized origins in Google Cloud Console
- Use HTTPS in production

### Token verification fails
- Verify the Client ID matches your project
- Check token expiration (tokens expire in 1 hour)
- Use `google/auth` composer package for production verification

### User not created
- Check database connection
- Verify user table has all required columns
- Check email uniqueness constraint

## Production Checklist
- [ ] Replace placeholder Client ID with actual value
- [ ] Add Client Secret to environment variables (not hardcoded)
- [ ] Implement proper token verification using Google Auth library
- [ ] Enable HTTPS
- [ ] Add authorized domains in Google Console
- [ ] Test login and signup flows
- [ ] Set up error logging
- [ ] Document user privacy (Google data collection)

## Additional Resources
- [Google Sign-In Documentation](https://developers.google.com/identity/sign-in/web)
- [Google OAuth 2.0 Guide](https://developers.google.com/identity/protocols/oauth2)
- [Google Auth Library for PHP](https://github.com/googleapis/google-auth-library-php)
