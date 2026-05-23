# 🗺️ Gebeta Application Flow Diagram

## Complete User Journey Map

```
┌─────────────────────────────────────────────────────────────────┐
│                     🍽️ GEBETA PLATFORM                          │
│                  Ethiopian Food Delivery                         │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│                      📱 LANDING PAGE                             │
│                      (index.php)                                 │
│                                                                  │
│  • Hero section with search                                     │
│  • Featured restaurants                                         │
│  • Login/Register buttons                                       │
│  • Feature cards                                                │
└──────────────┬──────────────────────────────┬───────────────────┘
               │                              │
               ▼                              ▼
    ┌──────────────────┐          ┌──────────────────┐
    │   🔐 LOGIN       │          │  📝 REGISTER     │
    │  (login.php)     │          │ (register.php)   │
    │                  │          │                  │
    │ • Email/Password │          │ • Name, Email    │
    │ • Google OAuth   │          │ • Phone, Password│
    │ • OTP Verify     │          │ • Role Selection │
    └────────┬─────────┘          └────────┬─────────┘
             │                             │
             └──────────┬──────────────────┘
                        │
                        ▼
              ┌─────────────────┐
              │  Role Detection │
              └────────┬────────┘
                       │
        ┌──────────────┼──────────────┐
        │              │              │
        ▼              ▼              ▼
┌───────────────┐ ┌──────────┐ ┌────────────┐
│   CUSTOMER    │ │RESTAURANT│ │   ADMIN    │
│   PORTAL      │ │  PORTAL  │ │  PORTAL    │
└───────────────┘ └──────────┘ └────────────┘
```

---

## 👤 Customer Journey

```
┌─────────────────────────────────────────────────────────────────┐
│                    CUSTOMER DASHBOARD                            │
│                  (customer/dashboard.php)                        │
│                                                                  │
│  📍 Location: Bole, Addis Ababa                                 │
│  🔍 Search: "Search restaurants and food..."                    │
│                                                                  │
│  ┌──────────┐ ┌──────────┐ ┌──────────┐                       │
│  │ Reorder  │ │Favorites │ │ Offers   │  Quick Actions         │
│  └──────────┘ └──────────┘ └──────────┘                       │
│                                                                  │
│  ┌────┐ ┌────┐ ┌────┐ ┌────┐                                  │
│  │ 24 │ │ 5  │ │720 │ │0.00│  Stats                           │
│  │Ord │ │Sav │ │Pts │ │Wal │                                  │
│  └────┘ └────┘ └────┘ └────┘                                  │
│                                                                  │
│  🫓 Injera  🍲 Wat  🥘 Tibs  ☕ Coffee  Categories             │
│                                                                  │
│  ┌──────────────────────────────────────┐                      │
│  │ 🏪 Top Rated Restaurants             │                      │
│  │  • Yod Abyssinia (4.8★)              │                      │
│  │  • Kategna Restaurant (4.5★)         │                      │
│  │  • Lucy Restaurant (4.7★)            │                      │
│  └──────────────────────────────────────┘                      │
└─────────────────────────────────────────────────────────────────┘
                        │
                        ▼ Click Restaurant
┌─────────────────────────────────────────────────────────────────┐
│                   RESTAURANT MENU VIEW                           │
│                 (customer/restaurant.php)                        │
│                                                                  │
│  ← Back                                    ❤️ Save              │
│                                                                  │
│  🏪 Yod Abyssinia                                               │
│  Ethiopian, Injera, Doro Wat                                    │
│  📍 Piassa, Hawassa                                             │
│  ⭐ 4.8 | ⏱️ 30-40 min | 💰 200 Birr for two                   │
│                                                                  │
│  ┌─ Main Dishes ──────────────────────────┐                    │
│  │                                         │                    │
│  │ Doro Wat with Injera        250 Birr   │                    │
│  │ Spicy chicken stew...       [+ ADD]    │                    │
│  │                                         │                    │
│  │ Kitfo                       280 Birr   │                    │
│  │ Minced raw beef...          [+ ADD]    │                    │
│  │                                         │                    │
│  │ Tibs                        220 Birr   │                    │
│  │ Sauteed beef...             [+ ADD]    │                    │
│  └─────────────────────────────────────────┘                    │
│                                                                  │
│  ┌─ Beverages ─────────────────────────────┐                   │
│  │ Ethiopian Coffee             50 Birr    │                    │
│  │ Fresh Juice                  40 Birr    │                    │
│  └─────────────────────────────────────────┘                    │
│                                                                  │
│  [🛒 View Cart (3 items) - 530 Birr]                           │
└─────────────────────────────────────────────────────────────────┘
                        │
                        ▼ View Cart
┌─────────────────────────────────────────────────────────────────┐
│                      SHOPPING CART                               │
│                    (customer/cart.php)                           │
│                                                                  │
│  ← Back to Dashboard                                            │
│                                                                  │
│  Your Cart (3 items)                                            │
│                                                                  │
│  ┌──────────────────────────────────────┐                      │
│  │ 🍲 Doro Wat with Injera              │                      │
│  │ Yod Abyssinia                        │                      │
│  │ 250 Birr              [−] 1 [+]      │                      │
│  └──────────────────────────────────────┘                      │
│                                                                  │
│  ┌──────────────────────────────────────┐                      │
│  │ 🥩 Kitfo                             │                      │
│  │ Yod Abyssinia                        │                      │
│  │ 280 Birr              [−] 1 [+]      │                      │
│  └──────────────────────────────────────┘                      │
│                                                                  │
│  ┌────────────────────────────────────┐                        │
│  │ Subtotal:              530.00 Birr │                        │
│  │ Delivery:                     FREE │                        │
│  │ ─────────────────────────────────  │                        │
│  │ Total:                 530.00 Birr │                        │
│  └────────────────────────────────────┘                        │
│                                                                  │
│  [🛒 PROCEED TO CHECKOUT]                                       │
└─────────────────────────────────────────────────────────────────┘
                        │
                        ▼ Checkout
┌─────────────────────────────────────────────────────────────────┐
│                       CHECKOUT                                   │
│                   (customer/checkout.php)                        │
│                                                                  │
│  📍 Delivery Address                                            │
│  ┌──────────────────────────────────────┐                      │
│  │ Bole, Hawassa, Building 12, Apt 5A  │                      │
│  └──────────────────────────────────────┘                      │
│                                                                  │
│  💳 Payment Method                                              │
│  ⚪ 💵 Cash on delivery                                         │
│  ⚪ 🏦 Bank transfer                                            │
│  ⚪ 📱 Telebirr                                                 │
│  ⚪ 📱 M-Pesa                                                   │
│  ⚪ 💰 Wallet (0.00 Birr)                                       │
│                                                                  │
│  🎟️ Promo Code                                                 │
│  ┌──────────────────┐ [Apply]                                  │
│  │ WELCOME20        │                                           │
│  └──────────────────┘                                           │
│                                                                  │
│  ┌────────────────────────────────────┐                        │
│  │ Total:                 530.00 Birr │                        │
│  └────────────────────────────────────┘                        │
│                                                                  │
│  [📦 PLACE ORDER]                                               │
└─────────────────────────────────────────────────────────────────┘
                        │
                        ▼ Place Order
┌─────────────────────────────────────────────────────────────────┐
│                   ORDER TRACKING                                 │
│                (customer/order-detail.php)                       │
│                                                                  │
│  Order #GB1234567890                                            │
│                                                                  │
│  🚴 Out for delivery                                            │
│  Estimated delivery: 15-20 minutes                              │
│                                                                  │
│  ┌─ Timeline ──────────────────────────┐                       │
│  │ ✅ Order placed        2:30 PM      │                       │
│  │ ✅ Confirmed           2:32 PM      │                       │
│  │ ✅ Preparing           2:35 PM      │                       │
│  │ 🟠 Out for delivery    2:50 PM      │ ← Current             │
│  │ ⚪ Delivered           Pending      │                       │
│  └─────────────────────────────────────┘                       │
│                                                                  │
│  📦 Order Details                                               │
│  • 1x Doro Wat with Injera - 250 Birr                          │
│  • 1x Kitfo - 280 Birr                                          │
│  ─────────────────────────────────────                          │
│  Total: 530.00 Birr                                             │
│                                                                  │
│  📍 Delivery: Bole, Hawassa, Building 12, Apt 5A               │
│  💳 Payment: Cash on delivery                                   │
└─────────────────────────────────────────────────────────────────┘
```

---

## 🏪 Restaurant Owner Journey

```
┌─────────────────────────────────────────────────────────────────┐
│                  RESTAURANT DASHBOARD                            │
│                (restaurant/dashboard.php)                        │
│                                                                  │
│  🏪 Yod Abyssinia                    [🟢 OPEN] [Menu]          │
│                                                                  │
│  ┌────┐ ┌────────┐ ┌────┐ ┌────┐                              │
│  │ 24 │ │12,450  │ │ 5  │ │4.8★│  Today's Stats                │
│  │Ord │ │Revenue │ │Pend│ │Rate│                               │
│  └────┘ └────────┘ └────┘ └────┘                              │
│                                                                  │
│  📋 Recent Orders                                               │
│                                                                  │
│  ┌──────────────────────────────────────┐                      │
│  │ #GB1234567890          [PENDING]     │                      │
│  │ Abebe Kebede • 2:30 PM               │                      │
│  │ 530 Birr                             │                      │
│  │ [✅ Accept] [❌ Reject]              │                      │
│  └──────────────────────────────────────┘                      │
│                                                                  │
│  ┌──────────────────────────────────────┐                      │
│  │ #GB1234567891          [PREPARING]   │                      │
│  │ Tigist Alemu • 2:15 PM               │                      │
│  │ 420 Birr                             │                      │
│  │ [👨🍳 Cooking] [✅ Mark Ready]      │                      │
│  └──────────────────────────────────────┘                      │
│                                                                  │
│  📊 Top Selling Items                                           │
│  • Doro Wat (15 sold today)                                     │
│  • Kitfo (12 sold today)                                        │
│  • Tibs (10 sold today)                                         │
└─────────────────────────────────────────────────────────────────┘
                        │
                        ▼ Menu
┌─────────────────────────────────────────────────────────────────┐
│                     MENU MANAGER                                 │
│                  (restaurant/menu.php)                           │
│                                                                  │
│  [➕ Add New Item]                                              │
│                                                                  │
│  ▼ Main Dishes (3 items)                                        │
│  ┌──────────────────────────────────────┐                      │
│  │ 🍲 Doro Wat with Injera              │                      │
│  │ 250 Birr • In Stock                  │                      │
│  │ [✏️ Edit] [🗑️ Delete] [👁️ Hide]    │                      │
│  └──────────────────────────────────────┘                      │
│                                                                  │
│  ┌──────────────────────────────────────┐                      │
│  │ 🥩 Kitfo                             │                      │
│  │ 280 Birr • In Stock                  │                      │
│  │ [✏️ Edit] [🗑️ Delete] [👁️ Hide]    │                      │
│  └──────────────────────────────────────┘                      │
│                                                                  │
│  ▼ Beverages (2 items)                                          │
│  ┌──────────────────────────────────────┐                      │
│  │ ☕ Ethiopian Coffee                  │                      │
│  │ 50 Birr • In Stock                   │                      │
│  │ [✏️ Edit] [🗑️ Delete] [👁️ Hide]    │                      │
│  └──────────────────────────────────────┘                      │
└─────────────────────────────────────────────────────────────────┘
```

---

## 👨💼 Admin Journey

```
┌─────────────────────────────────────────────────────────────────┐
│                     ADMIN DASHBOARD                              │
│                   (admin/dashboard.php)                          │
│                                                                  │
│  👨💼 Admin Dashboard                                           │
│  Welcome back! Here's what's happening today.                   │
│                                                                  │
│  ┌────┐ ┌────┐ ┌────┐ ┌────────┐                              │
│  │312 │ │156 │ │45K │ │2.5M    │  Platform Stats               │
│  │Rest│ │Ord │ │User│ │Revenue │                               │
│  └────┘ └────┘ └────┘ └────────┘                              │
│                                                                  │
│  ⚠️ 5 restaurants pending approval [Review Now]                │
│                                                                  │
│  🏆 Top Restaurants                                             │
│  • Yod Abyssinia (4.8★)                                         │
│  • Lucy Restaurant (4.7★)                                       │
│  • Kategna Restaurant (4.5★)                                    │
│                                                                  │
│  📦 Recent Orders                                               │
│  • #GB1234567890 - Delivered - 530 Birr                        │
│  • #GB1234567891 - Preparing - 420 Birr                        │
│  • #GB1234567892 - Pending - 680 Birr                          │
└─────────────────────────────────────────────────────────────────┘
                        │
                        ▼ Manage Restaurants
┌─────────────────────────────────────────────────────────────────┐
│                  MANAGE RESTAURANTS                              │
│                  (admin/restaurants.php)                         │
│                                                                  │
│  🔍 Search restaurants...                                       │
│  [All] [Pending] [Active] [Suspended]                          │
│                                                                  │
│  ┌──────────────────────────────────────┐                      │
│  │ 🏪 New Restaurant Name                │                      │
│  │ Owner: John Doe                       │                      │
│  │ Location: Bole, Addis Ababa          │                      │
│  │ Status: PENDING                       │                      │
│  │ [✅ Approve] [❌ Reject] [👁️ View]  │                      │
│  └──────────────────────────────────────┘                      │
│                                                                  │
│  ┌──────────────────────────────────────┐                      │
│  │ 🏪 Yod Abyssinia                     │                      │
│  │ Owner: Yod Restaurant                 │                      │
│  │ Location: Piassa, Hawassa            │                      │
│  │ Status: ACTIVE (4.8★)                │                      │
│  │ [⏸️ Suspend] [👁️ View] [📊 Stats]  │                      │
│  └──────────────────────────────────────┘                      │
└─────────────────────────────────────────────────────────────────┘
```

---

## 🔌 API Flow

```
┌─────────────────────────────────────────────────────────────────┐
│                        API ENDPOINTS                             │
└─────────────────────────────────────────────────────────────────┘

Customer APIs:
  POST /api/add-to-cart.php
    ↓ { menu_item_id: 1, quantity: 2 }
    ↑ { success: true, count: 3, total: "450.00" }

  POST /api/update-cart.php
    ↓ { menu_item_id: 1, quantity: 3 }
    ↑ { success: true, count: 3, total: "675.00" }

  POST /api/apply-promo.php
    ↓ { promo_code: "WELCOME20", cart_total: 500 }
    ↑ { success: true, discount: "100.00", new_total: "400.00" }

  POST /api/place-order.php
    ↓ { delivery_address: "...", payment_method: "cash" }
    ↑ { success: true, order_id: 123, redirect: "/customer/order-detail.php?id=123" }

  GET /api/search.php?q=pizza
    ↑ { restaurants: [...], dishes: [...] }

  GET /api/order-status.php?id=123
    ↑ { order_id: 123, status: "preparing", estimated_time: "20 mins" }

Restaurant APIs:
  POST /api/accept-order.php
    ↓ { order_id: 123, action: "accept" }
    ↑ { success: true, message: "Order accepted", new_status: "confirmed" }

  POST /api/update-status.php
    ↓ { order_id: 123, status: "ready" }
    ↑ { success: true, message: "Order marked as ready" }
```

---

## 🔒 Security Flow

```
┌─────────────────────────────────────────────────────────────────┐
│                      SECURITY LAYERS                             │
└─────────────────────────────────────────────────────────────────┘

1. Input Validation
   User Input → Sanitize → Validate → Process
   
2. SQL Injection Prevention
   Query → PDO Prepare → Bind Parameters → Execute
   
3. XSS Prevention
   Output → htmlspecialchars() → Display
   
4. CSRF Protection
   Form → Generate Token → Validate Token → Process
   
5. Authentication
   Login → Verify Password → Create Session → Redirect
   
6. Authorization
   Request → Check Session → Verify Role → Allow/Deny
```

---

## 📱 Responsive Breakpoints

```
┌─────────────────────────────────────────────────────────────────┐
│                    RESPONSIVE DESIGN                             │
└─────────────────────────────────────────────────────────────────┘

Mobile (375px - 768px)
  • Single column layout
  • Bottom navigation
  • Touch-friendly buttons (44px min)
  • Swipe gestures
  • Pull-to-refresh

Tablet (768px - 1024px)
  • 2-column layout
  • Side navigation
  • Larger touch targets

Desktop (1024px+)
  • 3-4 column grid
  • Top navigation
  • Hover effects
  • Keyboard shortcuts
```

---

## 🎯 Complete Feature Map

```
GEBETA PLATFORM
├── Authentication
│   ├── Registration (Customer/Restaurant)
│   ├── Login (Email/Google OAuth)
│   ├── OTP Verification
│   ├── Password Reset
│   └── Logout
│
├── Customer Portal
│   ├── Dashboard (Browse/Search)
│   ├── Restaurant View (Menu)
│   ├── Shopping Cart
│   ├── Checkout (Payment)
│   ├── Order Tracking
│   ├── Order History
│   ├── Favorites
│   ├── Wallet
│   └── Profile
│
├── Restaurant Portal
│   ├── Dashboard (Orders/Stats)
│   ├── Menu Manager
│   ├── Order Detail
│   ├── Settings
│   └── Analytics
│
├── Admin Portal
│   ├── Dashboard (Overview)
│   ├── Manage Restaurants
│   ├── Manage Users
│   ├── Manage Orders
│   ├── Payments
│   ├── Promo Codes
│   └── Settings
│
└── Features
    ├── Real-time Search
    ├── AJAX Cart
    ├── Order Tracking
    ├── Email Notifications
    ├── Promo Codes
    ├── Multiple Payments
    ├── Responsive Design
    └── PWA Support
```

---

**🎉 Your complete Gebeta platform is ready to serve Ethiopia! 🇪🇹**
