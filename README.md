# 🍽️ Gebeta - Ethiopian Food Delivery Platform

![Gebeta Logo](assets/images/logo.png)

**Gebeta** is a modern food delivery platform designed specifically for Ethiopia (Addis Ababa/Hawassa), featuring local payment methods, Ethiopian cuisine categories, and a Swiggy-inspired design.

---

## 📋 Table of Contents

- [Features](#features)
- [Tech Stack](#tech-stack)
- [Installation](#installation)
- [Configuration](#configuration)
- [Test Accounts](#test-accounts)
- [Project Structure](#project-structure)
- [API Endpoints](#api-endpoints)
- [Security Features](#security-features)
- [Deployment](#deployment)
- [Contributing](#contributing)

---

## ✨ Features

### Customer Features
- 🔍 **Search & Browse** - Find restaurants by name, cuisine, or location
- 🛒 **Shopping Cart** - Add items, update quantities, apply promo codes
- 💳 **Multiple Payment Methods** - Cash, Bank Transfer, Telebirr, M-Pesa, Wallet
- 📦 **Order Tracking** - Real-time order status updates
- ⭐ **Reviews & Ratings** - Rate restaurants and view reviews
- ❤️ **Favorites** - Save favorite restaurants for quick access
- 📍 **Location Services** - GPS-based restaurant discovery
- 🎟️ **Promo Codes** - Apply discount codes at checkout

### Restaurant Owner Features
- 📊 **Dashboard** - View orders, revenue, and performance metrics
- 🍕 **Menu Management** - Add, edit, delete menu items and categories
- 📋 **Order Management** - Accept/reject orders, update status
- 📈 **Analytics** - Track sales, top items, and customer reviews
- ⏰ **Operating Hours** - Set opening/closing times
- 🔔 **Real-time Notifications** - Get notified of new orders

### Admin Features
- 👥 **User Management** - Manage customers, restaurants, and admins
- 🏪 **Restaurant Approval** - Review and approve new restaurants
- 📦 **Order Oversight** - View all platform orders
- 💰 **Payment Management** - Process refunds and disputes
- 🎫 **Promo Code Management** - Create and track promotional campaigns
- 📊 **Reports & Analytics** - Platform-wide metrics and insights

---

## 🛠️ Tech Stack

- **Frontend**: HTML5, CSS3, Vanilla JavaScript
- **Backend**: PHP 8+
- **Database**: MySQL 8+ (Aiven Cloud)
- **Authentication**: Session-based + Google OAuth
- **Email**: Brevo API (formerly Sendinblue)
- **PWA**: Service Worker, Web Manifest
- **Deployment**: Docker, Render.com

---

## 📦 Installation

### Prerequisites
- PHP 8.0 or higher
- MySQL 8.0 or higher
- Composer (optional, for dependencies)
- Web server (Apache/Nginx) or XAMPP/WAMP

### Local Setup

1. **Clone the repository**
```bash
git clone https://github.com/yourusername/gebeta.git
cd gebeta
```

2. **Import the database**
```bash
mysql -u root -p < gebeta.sql
```

Or use phpMyAdmin:
- Create database `gebeta`
- Import `gebeta.sql` file

3. **Configure environment variables**
```bash
cp .env.local.example .env
```

Edit `.env` with your credentials:
```env
DB_HOST=localhost
DB_NAME=gebeta
DB_USER=root
DB_PASS=your_password

BREVO_API_KEY=your_brevo_api_key
BREVO_SENDER_EMAIL=noreply@gebeta.com

GOOGLE_CLIENT_ID=your_google_client_id
GOOGLE_CLIENT_SECRET=your_google_client_secret
```

4. **Create upload directories**
```bash
mkdir -p uploads/restaurants uploads/menu
chmod 755 uploads/restaurants uploads/menu
```

5. **Start the server**

Using PHP built-in server:
```bash
php -S localhost:8000
```

Using XAMPP/WAMP:
- Copy project to `htdocs/gebeta`
- Access at `http://localhost/gebeta`

6. **Access the application**
```
http://localhost:8000
```

---

## ⚙️ Configuration

### Database Configuration
Edit `includes/config.php` if not using `.env`:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'gebeta');
define('DB_USER', 'root');
define('DB_PASS', '');
```

### Email Configuration (Brevo)
1. Sign up at [Brevo](https://www.brevo.com/)
2. Get your API key from Settings > SMTP & API
3. Add to `.env`:
```env
BREVO_API_KEY=xkeysib-xxxxx
BREVO_SENDER_EMAIL=noreply@gebeta.com
```

### Google OAuth Setup
1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project
3. Enable Google+ API
4. Create OAuth 2.0 credentials
5. Add authorized redirect URIs:
   - `http://localhost:8000`
   - `https://yourdomain.com`
6. Add credentials to `.env`

See [GOOGLE_OAUTH_SETUP.md](docs/GOOGLE_OAUTH_SETUP.md) for detailed instructions.

---

## 👤 Test Accounts

### Admin Account
```
Email: admin@gebeta.com
Password: password123
```

### Restaurant Owner Account
```
Email: yod@restaurant.com
Password: password123
```

### Customer Account
```
Email: customer@test.com
Password: password123
```

### Delivery Partner Accounts
```
Email: delivery1@gebeta.com (Bike - Online - 4.8⭐)
Password: password123

Email: delivery2@gebeta.com (Auto - Online - 4.6⭐)
Password: password123

Email: delivery3@gebeta.com (Car - Offline - 4.9⭐)
Password: password123
```

**Note**: All passwords are hashed with `password_hash()` using `PASSWORD_DEFAULT`.

---

## 📁 Project Structure

```
gebeta/
├── admin/                  # Admin portal
│   ├── dashboard.php
│   ├── restaurants.php
│   ├── users.php
│   ├── orders.php
│   └── reports.php
├── api/                    # AJAX API endpoints
│   ├── add-to-cart.php
│   ├── update-cart.php
│   ├── place-order.php
│   ├── search.php
│   ├── apply-promo.php
│   ├── accept-order.php
│   └── order-status.php
├── assets/
│   ├── css/
│   │   └── style.css       # Main stylesheet
│   ├── js/
│   │   └── script.js       # Main JavaScript
│   └── images/
│       └── food/           # Food images
├── customer/               # Customer portal
│   ├── dashboard.php
│   ├── restaurant.php
│   ├── cart.php
│   ├── checkout.php
│   ├── order-detail.php
│   ├── orders.php
│   └── profile.php
├── includes/               # Core PHP files
│   ├── config.php          # Configuration
│   ├── db.php              # Database connection
│   ├── auth.php            # Authentication
│   ├── functions.php       # Helper functions
│   └── bottom-nav.php      # Mobile navigation
├── restaurant/             # Restaurant owner portal
│   ├── dashboard.php
│   ├── menu.php
│   ├── order-detail.php
│   └── profile.php
├── uploads/                # User uploads
│   ├── restaurants/
│   └── menu/
├── .env                    # Environment variables
├── .htaccess               # Apache configuration
├── index.php               # Landing page
├── login.php               # Login handler
├── register.php            # Registration handler
├── logout.php              # Logout handler
├── gebeta.sql              # Database schema
├── manifest.json           # PWA manifest
├── sw.js                   # Service worker
└── README.md               # This file
```

---

## 🔌 API Endpoints

### Customer APIs

#### Search
```
GET /api/search.php?q=pizza
Response: { restaurants: [...], dishes: [...] }
```

#### Add to Cart
```
POST /api/add-to-cart.php
Body: menu_item_id=1&quantity=2
Response: { success: true, count: 3, total: "450.00" }
```

#### Update Cart
```
POST /api/update-cart.php
Body: menu_item_id=1&quantity=3
Response: { success: true, count: 3, total: "675.00" }
```

#### Apply Promo Code
```
POST /api/apply-promo.php
Body: promo_code=WELCOME20&cart_total=500
Response: { success: true, discount: "100.00", new_total: "400.00" }
```

#### Place Order
```
POST /api/place-order.php
Body: delivery_address=...&payment_method=cash
Response: { success: true, order_id: 123, redirect: "/customer/order-detail.php?id=123" }
```

#### Get Order Status
```
GET /api/order-status.php?id=123
Response: { order_id: 123, status: "preparing", estimated_time: "20 mins" }
```

### Restaurant APIs

#### Accept/Reject Order
```
POST /api/accept-order.php
Body: order_id=123&action=accept
Response: { success: true, message: "Order accepted", new_status: "confirmed" }
```

#### Update Order Status
```
POST /api/update-status.php
Body: order_id=123&status=ready
Response: { success: true, message: "Order marked as ready" }
```

---

## 🔒 Security Features

### Implemented Security Measures

1. **SQL Injection Prevention**
   - All queries use PDO prepared statements
   - No string concatenation with user input

2. **XSS Prevention**
   - All output escaped with `htmlspecialchars()`
   - ENT_QUOTES flag for attribute protection

3. **Password Security**
   - Passwords hashed with `password_hash()`
   - Verification with `password_verify()`
   - Minimum 6 characters required

4. **Session Security**
   - `session_regenerate_id()` after login
   - HTTPOnly cookies
   - Secure flag for HTTPS (production)

5. **CSRF Protection**
   - Token generation and validation
   - Tokens in all forms

6. **Input Validation**
   - Server-side validation for all inputs
   - Email format validation
   - Phone number format validation
   - Sanitization with custom functions

7. **Rate Limiting**
   - Login attempts limited
   - API request throttling

---

## 🚀 Deployment

### Docker Deployment

1. **Build the image**
```bash
docker build -t gebeta .
```

2. **Run the container**
```bash
docker run -p 8080:80 --env-file .env gebeta
```

### Render.com Deployment

1. **Connect your GitHub repository**
2. **Configure environment variables** in Render dashboard
3. **Deploy** - Render will automatically build and deploy

See [DEPLOYMENT.md](DEPLOYMENT.md) for detailed instructions.

### Production Checklist

- [ ] Set `display_errors = 0` in PHP
- [ ] Enable HTTPS (SSL certificate)
- [ ] Set `session.cookie_secure = 1`
- [ ] Configure database backups
- [ ] Set up email service (Brevo)
- [ ] Configure SMS service (optional)
- [ ] Test all payment methods
- [ ] Set up monitoring (uptime, errors)
- [ ] Configure CDN for static assets
- [ ] Enable gzip compression
- [ ] Set up cron jobs (if needed)

---

## 📊 Database Schema

### Tables

1. **users** - All users (customers, restaurant owners, admins)
2. **restaurants** - Restaurant information
3. **categories** - Menu categories per restaurant
4. **menu_items** - Food items
5. **orders** - Customer orders
6. **order_items** - Items in each order
7. **reviews** - Restaurant reviews and ratings
8. **payments** - Payment records
9. **otps** - One-time passwords for verification

See [docs/erd.md](docs/erd.md) for the complete Entity Relationship Diagram.

---

## 🧪 Testing

### Manual Testing Checklist

**Authentication**
- [ ] Register new customer
- [ ] Register new restaurant
- [ ] Login with email
- [ ] Login with Google OAuth
- [ ] Logout
- [ ] Password reset

**Customer Flow**
- [ ] Browse restaurants
- [ ] Search for food
- [ ] Add items to cart
- [ ] Update cart quantities
- [ ] Apply promo code
- [ ] Checkout
- [ ] Place order
- [ ] Track order
- [ ] View order history

**Restaurant Flow**
- [ ] View dashboard
- [ ] Accept/reject orders
- [ ] Update order status
- [ ] Add menu items
- [ ] Edit menu items
- [ ] View analytics

**Admin Flow**
- [ ] Approve restaurants
- [ ] Manage users
- [ ] View all orders
- [ ] Create promo codes
- [ ] View reports

---

## 🐛 Troubleshooting

### Common Issues

**Database Connection Failed**
- Check `.env` credentials
- Verify MySQL is running
- Check database exists

**Email Not Sending**
- Verify Brevo API key
- Check sender email is verified
- Review error logs

**Google OAuth Not Working**
- Verify client ID and secret
- Check authorized redirect URIs
- Ensure Google+ API is enabled

**Images Not Loading**
- Check `uploads/` directory permissions
- Verify file paths in code
- Check `.htaccess` configuration

---

## 📝 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

---

## 👥 Contributing

Contributions are welcome! Please follow these steps:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

---

## 📞 Support

For support, email support@gebeta.com or open an issue on GitHub.

---

## 🙏 Acknowledgments

- Design inspired by Swiggy
- Icons from emoji set
- Food images from Unsplash
- Built with ❤️ for Ethiopia

---

**Made with 🍽️ in Hawassa, Ethiopia**
