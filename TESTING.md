# ✅ Gebeta Testing Checklist

Complete testing checklist for all features and user flows.

---

## 🔐 Authentication & Authorization

### Registration
- [ ] Customer registration with valid data
- [ ] Restaurant registration with valid data
- [ ] Email validation (format check)
- [ ] Phone validation (format check)
- [ ] Password strength validation (min 6 chars)
- [ ] Duplicate email prevention
- [ ] Duplicate phone prevention
- [ ] OTP email sent successfully
- [ ] OTP verification works
- [ ] Auto-login after registration
- [ ] Redirect to correct dashboard

### Login
- [ ] Login with email and password
- [ ] Login with incorrect password (error shown)
- [ ] Login with non-existent email (error shown)
- [ ] Login with suspended account (error shown)
- [ ] Remember me functionality
- [ ] Session persistence across pages
- [ ] Redirect based on role (customer/restaurant/admin)
- [ ] Google OAuth login (if configured)
- [ ] Location capture on login

### Logout
- [ ] Logout clears session
- [ ] Redirect to landing page
- [ ] Cannot access protected pages after logout

---

## 👤 Customer Portal

### Dashboard
- [ ] Dashboard loads with user name
- [ ] Location displayed correctly
- [ ] Search bar visible and functional
- [ ] Quick action cards displayed
- [ ] Stats cards show correct data (orders, saved, points, wallet)
- [ ] Food categories displayed
- [ ] Category filter works
- [ ] Top rated restaurants displayed
- [ ] New restaurants displayed
- [ ] Favorites displayed (if any)
- [ ] Recently ordered from displayed (if any)
- [ ] Restaurant cards clickable
- [ ] Pull-to-refresh works (mobile)
- [ ] Bottom navigation visible

### Search & Browse
- [ ] Live search works (debounced)
- [ ] Search shows restaurants
- [ ] Search shows dishes
- [ ] Search results clickable
- [ ] Empty state shown for no results
- [ ] Category pills filter results
- [ ] Search persists across page reload

### Restaurant View
- [ ] Restaurant page loads with correct data
- [ ] Restaurant image displayed
- [ ] Restaurant info (name, cuisine, location, rating) shown
- [ ] Menu categories displayed
- [ ] Menu items displayed per category
- [ ] Item images shown
- [ ] Item prices shown
- [ ] ADD button works
- [ ] Item added to cart (AJAX, no reload)
- [ ] Cart count updates
- [ ] Success toast shown
- [ ] Out of stock items disabled
- [ ] Back button works

### Shopping Cart
- [ ] Cart page shows all items
- [ ] Item images displayed
- [ ] Item names and prices shown
- [ ] Restaurant name shown per item
- [ ] Quantity controls work (+/-)
- [ ] Quantity updates via AJAX
- [ ] Total recalculates on quantity change
- [ ] Remove item works (quantity = 0)
- [ ] Empty cart state shown
- [ ] Proceed to checkout button visible
- [ ] Cart persists across pages
- [ ] Cart count in navigation updates

### Checkout
- [ ] Checkout page loads
- [ ] Delivery address field shown
- [ ] Payment method options displayed
- [ ] Cash on delivery selectable
- [ ] Bank transfer selectable
- [ ] Telebirr selectable
- [ ] M-Pesa selectable
- [ ] Wallet selectable (if balance available)
- [ ] Order summary shown
- [ ] Total amount correct
- [ ] Promo code input visible
- [ ] Apply promo code works
- [ ] Discount calculated correctly
- [ ] Place order button works
- [ ] Order validation (address required)
- [ ] Redirect to order confirmation

### Order Tracking
- [ ] Order detail page loads
- [ ] Order number displayed
- [ ] Order status shown
- [ ] Timeline displayed
- [ ] Current status highlighted
- [ ] Estimated delivery time shown
- [ ] Order items listed
- [ ] Item quantities and prices shown
- [ ] Delivery address shown
- [ ] Payment method shown
- [ ] Total amount shown
- [ ] Auto-refresh status (polling)
- [ ] Status updates in real-time
- [ ] Delivered status stops polling

### Order History
- [ ] Orders page shows all past orders
- [ ] Order cards display correctly
- [ ] Order number, date, status shown
- [ ] Total amount shown
- [ ] Restaurant name shown
- [ ] Click order opens detail page
- [ ] Reorder button works
- [ ] Empty state shown if no orders
- [ ] Orders sorted by date (newest first)

### Profile
- [ ] Profile page loads
- [ ] User name displayed
- [ ] Email displayed
- [ ] Phone displayed
- [ ] Edit profile works
- [ ] Change password works
- [ ] Address management accessible
- [ ] Notification settings accessible
- [ ] Logout button works

---

## 🏪 Restaurant Owner Portal

### Dashboard
- [ ] Dashboard loads with restaurant name
- [ ] Open/Closed toggle visible
- [ ] Toggle changes restaurant status
- [ ] Today's stats displayed (orders, revenue, etc.)
- [ ] Pending orders count shown
- [ ] Completed orders count shown
- [ ] Cancelled orders count shown
- [ ] Average delivery time shown
- [ ] Items sold count shown
- [ ] New orders section displayed
- [ ] Preparing orders section displayed
- [ ] Ready orders section displayed
- [ ] Order cards show all details
- [ ] Accept/Reject buttons work
- [ ] Order status update works
- [ ] Top selling items displayed
- [ ] Recent reviews displayed
- [ ] Auto-refresh orders (polling)
- [ ] Sound notification for new orders

### Menu Management
- [ ] Menu page loads
- [ ] Categories displayed
- [ ] Menu items per category shown
- [ ] Add new item button works
- [ ] Add item modal opens
- [ ] Add item form validation works
- [ ] Item added successfully
- [ ] Edit item button works
- [ ] Edit item pre-populates form
- [ ] Item updated successfully
- [ ] Delete item works (with confirmation)
- [ ] Toggle availability works
- [ ] Image upload works
- [ ] Categories can be added
- [ ] Items organized by category

### Order Detail
- [ ] Order detail page loads
- [ ] Order number displayed
- [ ] Customer info shown (name, phone, address)
- [ ] Order timeline displayed
- [ ] Items list shown with quantities
- [ ] Total amount shown
- [ ] Status update dropdown works
- [ ] Update status button works
- [ ] Status changes reflected immediately
- [ ] Cancel order option available
- [ ] Cancel reason required
- [ ] Print receipt button works

### Restaurant Settings
- [ ] Settings page loads
- [ ] Restaurant name editable
- [ ] Description editable
- [ ] Cuisine types editable
- [ ] Location editable
- [ ] Phone editable
- [ ] Operating hours editable
- [ ] Opening time can be set
- [ ] Closing time can be set
- [ ] Days selection works
- [ ] Holiday mode toggle works
- [ ] Restaurant image upload works
- [ ] Save changes button works
- [ ] Changes reflected immediately

### Analytics
- [ ] Analytics page loads
- [ ] Date range selector works
- [ ] Revenue chart displayed
- [ ] Order volume chart displayed
- [ ] Customer ratings trend shown
- [ ] Top items analysis shown
- [ ] Peak hours analysis shown
- [ ] Export CSV works
- [ ] Export PDF works

---

## 👨‍💼 Admin Portal

### Dashboard
- [ ] Admin dashboard loads
- [ ] Total restaurants count shown
- [ ] Active orders count shown
- [ ] Total users count shown
- [ ] Revenue stats shown
- [ ] Pending approvals alert shown
- [ ] Top restaurants displayed
- [ ] Recent orders displayed
- [ ] Charts displayed correctly
- [ ] Date range filter works

### Restaurant Management
- [ ] Restaurants page loads
- [ ] All restaurants listed
- [ ] Search restaurants works
- [ ] Filter by status works (All/Pending/Active/Suspended)
- [ ] Restaurant details shown (name, owner, location, rating)
- [ ] Approve button works (for pending)
- [ ] Suspend button works
- [ ] Activate button works
- [ ] Delete button works (with confirmation)
- [ ] View details opens restaurant page
- [ ] Bulk actions work
- [ ] Export list works

### User Management
- [ ] Users page loads
- [ ] All users listed
- [ ] Search users works
- [ ] Filter by role works (All/Customers/Restaurants/Admins)
- [ ] User details shown (name, email, phone, role, status)
- [ ] View profile works
- [ ] Suspend user works
- [ ] Activate user works
- [ ] Reset password works
- [ ] Delete account works (with confirmation)
- [ ] View user activity works
- [ ] Export list works

### Order Management
- [ ] Orders page loads
- [ ] All orders listed
- [ ] Search by order number works
- [ ] Filter by status works
- [ ] Filter by date range works
- [ ] Filter by restaurant works
- [ ] Filter by payment method works
- [ ] Order details shown
- [ ] View order detail works
- [ ] Update order status works (override)
- [ ] Process refund works
- [ ] Resolve disputes works
- [ ] Export orders works

### Payment Management
- [ ] Payments page loads
- [ ] Revenue summary displayed
- [ ] Transaction history shown
- [ ] Filter transactions works
- [ ] Approve bank transfer works
- [ ] Reject bank transfer works
- [ ] Process refund works
- [ ] View payment proof works
- [ ] Payment disputes listed
- [ ] Resolve dispute works
- [ ] Generate revenue report works
- [ ] Payout history shown

### Promo Code Management
- [ ] Promo codes page loads
- [ ] All promo codes listed
- [ ] Create new promo button works
- [ ] Create promo form validation works
- [ ] Promo code created successfully
- [ ] Edit promo works
- [ ] Delete promo works
- [ ] View promo stats works
- [ ] Promo usage count shown
- [ ] Expired promos marked
- [ ] Deactivate promo works

### Settings
- [ ] Settings page loads
- [ ] Commission settings editable
- [ ] Delivery fee editable
- [ ] Service fee editable
- [ ] Payment gateway config works
- [ ] Email service config works
- [ ] SMS service config works
- [ ] Email templates editable
- [ ] System settings editable (currency, timezone, language)
- [ ] Tax rate editable
- [ ] Save settings works
- [ ] Database backup works
- [ ] Download backup works

---

## 🔌 API Endpoints

### Customer APIs
- [ ] `/api/search.php` - Returns restaurants and dishes
- [ ] `/api/add-to-cart.php` - Adds item to cart
- [ ] `/api/update-cart.php` - Updates cart quantity
- [ ] `/api/apply-promo.php` - Applies promo code
- [ ] `/api/place-order.php` - Creates order
- [ ] `/api/order-status.php` - Returns order status
- [ ] `/api/check-email.php` - Checks email availability

### Restaurant APIs
- [ ] `/api/accept-order.php` - Accepts/rejects order
- [ ] `/api/update-status.php` - Updates order status

### All APIs
- [ ] Return proper JSON format
- [ ] Handle errors gracefully
- [ ] Validate authentication
- [ ] Validate input data
- [ ] Return appropriate HTTP status codes

---

## 🔒 Security Testing

### SQL Injection
- [ ] Test all input fields with SQL injection attempts
- [ ] Verify PDO prepared statements used everywhere
- [ ] No string concatenation with user input

### XSS (Cross-Site Scripting)
- [ ] Test all input fields with script tags
- [ ] Verify all output is escaped with htmlspecialchars()
- [ ] No unescaped user data in HTML

### CSRF (Cross-Site Request Forgery)
- [ ] All forms have CSRF tokens
- [ ] Token validation works
- [ ] Tokens regenerate after use

### Authentication
- [ ] Cannot access protected pages without login
- [ ] Role-based access control works
- [ ] Session timeout works
- [ ] Password reset secure

### Password Security
- [ ] Passwords hashed with password_hash()
- [ ] No plain text passwords stored
- [ ] Password verification with password_verify()
- [ ] Minimum password length enforced

### Input Validation
- [ ] Email format validated
- [ ] Phone format validated
- [ ] Numbers validated (prices, quantities)
- [ ] Dates validated
- [ ] Server-side validation always performed

---

## 📱 Responsive Design

### Mobile (375px - 768px)
- [ ] Landing page responsive
- [ ] Login/Register modals responsive
- [ ] Customer dashboard responsive
- [ ] Restaurant view responsive
- [ ] Cart responsive
- [ ] Checkout responsive
- [ ] Order tracking responsive
- [ ] Bottom navigation visible
- [ ] Touch-friendly buttons (min 44px)
- [ ] Swipe gestures work
- [ ] Pull-to-refresh works

### Tablet (768px - 1024px)
- [ ] All pages responsive
- [ ] 2-column layouts work
- [ ] Side panels work
- [ ] Navigation appropriate

### Desktop (1024px+)
- [ ] All pages responsive
- [ ] 3-4 column grids work
- [ ] Hover effects work
- [ ] Tooltips work
- [ ] Keyboard shortcuts work (if any)

---

## 🌐 Cross-Browser Testing

### Chrome
- [ ] All features work
- [ ] CSS renders correctly
- [ ] JavaScript functions properly

### Firefox
- [ ] All features work
- [ ] CSS renders correctly
- [ ] JavaScript functions properly

### Safari
- [ ] All features work
- [ ] CSS renders correctly
- [ ] JavaScript functions properly

### Edge
- [ ] All features work
- [ ] CSS renders correctly
- [ ] JavaScript functions properly

### Mobile Browsers
- [ ] Chrome Mobile works
- [ ] Safari iOS works
- [ ] Touch interactions work

---

## ⚡ Performance Testing

### Page Load Times
- [ ] Landing page loads < 2 seconds
- [ ] Dashboard loads < 2 seconds
- [ ] Restaurant page loads < 2 seconds
- [ ] Cart page loads < 1 second
- [ ] Checkout page loads < 1 second

### AJAX Performance
- [ ] Search responds < 500ms
- [ ] Add to cart responds < 300ms
- [ ] Update cart responds < 300ms
- [ ] Place order responds < 1 second

### Database Performance
- [ ] Queries optimized with indexes
- [ ] No N+1 query problems
- [ ] Pagination implemented

### Asset Optimization
- [ ] Images lazy loaded
- [ ] CSS minified (production)
- [ ] JavaScript minified (production)
- [ ] Gzip compression enabled

---

## 🎨 UI/UX Testing

### Visual Design
- [ ] Consistent color scheme
- [ ] Consistent typography
- [ ] Consistent spacing
- [ ] Consistent button styles
- [ ] Icons consistent
- [ ] Images display correctly

### User Experience
- [ ] Navigation intuitive
- [ ] Forms easy to fill
- [ ] Error messages clear
- [ ] Success messages shown
- [ ] Loading states shown
- [ ] Empty states shown
- [ ] Confirmation dialogs for destructive actions

### Accessibility
- [ ] Alt text on images
- [ ] ARIA labels on buttons
- [ ] Keyboard navigation works
- [ ] Color contrast sufficient (4.5:1)
- [ ] Screen reader compatible

---

## 📧 Email Testing

### Email Delivery
- [ ] Registration OTP email sent
- [ ] Login OTP email sent (if enabled)
- [ ] Password reset email sent
- [ ] Order confirmation email sent
- [ ] Order status update emails sent

### Email Content
- [ ] Emails formatted correctly
- [ ] Links work
- [ ] Branding consistent
- [ ] Unsubscribe link present

---

## 🚀 Deployment Testing

### Production Environment
- [ ] Database connection works
- [ ] Environment variables set
- [ ] HTTPS enabled
- [ ] SSL certificate valid
- [ ] Error logging configured
- [ ] Backup schedule configured
- [ ] Monitoring configured

### Post-Deployment
- [ ] All pages accessible
- [ ] All features work
- [ ] No console errors
- [ ] No PHP errors
- [ ] Performance acceptable
- [ ] Email service works
- [ ] Payment methods work

---

## 📊 Test Results Summary

**Date**: _______________
**Tester**: _______________
**Environment**: _______________

**Total Tests**: _______________
**Passed**: _______________
**Failed**: _______________
**Blocked**: _______________

**Critical Issues**: _______________
**Major Issues**: _______________
**Minor Issues**: _______________

**Overall Status**: ⬜ PASS ⬜ FAIL

**Notes**:
_______________________________________
_______________________________________
_______________________________________

---

## 🐛 Bug Report Template

**Title**: Brief description of the bug

**Severity**: ⬜ Critical ⬜ Major ⬜ Minor

**Steps to Reproduce**:
1. 
2. 
3. 

**Expected Result**:


**Actual Result**:


**Screenshots**:


**Environment**:
- Browser: 
- OS: 
- Device: 

**Additional Notes**:


---

**Happy Testing! 🧪**
