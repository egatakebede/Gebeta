# 👤 Default Test Accounts - Gebeta

All test accounts use the same password: **password123**

---

## 🔐 Admin Account

**Purpose**: Full platform administration and oversight

```
Email: admin@gebeta.com
Password: password123
Phone: +251911000001
```

**Access**:
- User management
- Restaurant approval
- Order oversight
- Payment management
- Promo code management
- Platform analytics

---

## 🏪 Restaurant Owner Account

**Purpose**: Manage restaurant, menu, and orders

```
Email: yod@restaurant.com
Password: password123
Phone: +251911000002
Restaurant: Yod Abyssinia
```

**Access**:
- Dashboard with order metrics
- Menu management (add/edit/delete items)
- Order management (accept/reject/update status)
- Restaurant profile settings
- Analytics and reports

---

## 🛒 Customer Account

**Purpose**: Browse restaurants and place orders

```
Email: customer@test.com
Password: password123
Phone: +251911000003
```

**Access**:
- Browse restaurants
- Search for food
- Add items to cart
- Place orders
- Track order status
- View order history
- Rate and review restaurants

---

## 🚚 Delivery Partner Accounts

### Delivery Partner 1 (Bike - Verified)

```
Email: delivery1@gebeta.com
Password: password123
Phone: +251911000004
Name: Abebe Kebede
Vehicle: Red Bike (AA-12345)
License: DL-2024-001
Status: Online
Rating: 4.8 ⭐
Total Deliveries: 156
```

### Delivery Partner 2 (Auto - Verified)

```
Email: delivery2@gebeta.com
Password: password123
Phone: +251911000005
Name: Tigist Alemu
Vehicle: Blue Auto/Tuk-tuk (AA-67890)
License: DL-2024-002
Status: Online
Rating: 4.6 ⭐
Total Deliveries: 89
```

### Delivery Partner 3 (Car - Verified)

```
Email: delivery3@gebeta.com
Password: password123
Phone: +251911000006
Name: Dawit Tesfaye
Vehicle: White Car (AA-11223)
License: DL-2024-003
Status: Offline
Rating: 4.9 ⭐
Total Deliveries: 234
```

**Access**:
- Dashboard with delivery metrics
- Available orders list
- Accept/reject delivery requests
- Update delivery status
- Track earnings
- View delivery history
- Manage availability (online/offline)

---

## 📝 How to Import Test Users

### Option 1: Using MySQL Command Line

```bash
mysql -u root -p gebeta < default-users.sql
```

### Option 2: Using phpMyAdmin

1. Open phpMyAdmin
2. Select `gebeta` database
3. Click on "SQL" tab
4. Copy and paste contents of `default-users.sql`
5. Click "Go"

### Option 3: Manual SQL Execution

Run the SQL statements from `default-users.sql` directly in your MySQL client.

---

## 🔑 Password Hash

All accounts use the same password hash for `password123`:

```
$2y$10$Z2nVTfsIoG5oDsJN8vnwQOtqDDwqUD6dlfFxzFqqOGbXPY8CaX/ai
```

This is generated using PHP's `password_hash()` function with `PASSWORD_DEFAULT` algorithm.

---

## 🧪 Testing Scenarios

### Customer Flow
1. Login as `customer@test.com`
2. Browse restaurants
3. Add items to cart
4. Place an order
5. Track order status

### Restaurant Flow
1. Login as `yod@restaurant.com`
2. View incoming orders
3. Accept/reject orders
4. Update order status (preparing → ready → out for delivery)
5. Manage menu items

### Delivery Flow
1. Login as `delivery1@gebeta.com`
2. Set status to "Online"
3. View available delivery requests
4. Accept a delivery
5. Update delivery status (picked up → in transit → delivered)
6. View earnings

### Admin Flow
1. Login as `admin@gebeta.com`
2. Approve new restaurants
3. Manage users
4. View all orders
5. Create promo codes
6. Generate reports

---

## 🔄 Reset Password

If you need to reset a password, use this PHP snippet:

```php
<?php
$password = 'password123';
$hash = password_hash($password, PASSWORD_DEFAULT);
echo $hash;
?>
```

Then update the database:

```sql
UPDATE users SET password = 'NEW_HASH_HERE' WHERE email = 'user@example.com';
```

---

## 🛡️ Security Notes

- **IMPORTANT**: Change all default passwords in production
- Never commit real credentials to version control
- Use environment variables for sensitive data
- Enable 2FA for admin accounts in production
- Regularly rotate passwords
- Monitor for suspicious login attempts

---

## 📞 Support

For issues with test accounts, contact: support@gebeta.com

---

**Last Updated**: January 2025
