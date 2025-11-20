# 🧪 Sarap Local - Testing Guide

Comprehensive testing procedures for all features.

## 🎯 Test Accounts

### Vendors
- **Username:** vendor1 | **Password:** test123 | **Email:** vendor1@saraplocal.com
- **Username:** vendor2 | **Password:** test123 | **Email:** vendor2@saraplocal.com

### Customers
- **Username:** customer1 | **Password:** test123 | **Email:** customer1@saraplocal.com
- **Username:** customer2 | **Password:** test123 | **Email:** customer2@saraplocal.com

## 🔐 Authentication Testing

### Login Tests

```
✓ Test 1: Valid credentials
  - Username: vendor1
  - Password: test123
  - Expected: Redirect to vendor.php

✓ Test 2: Invalid email
  - Email: invalid@test.com
  - Password: test123
  - Expected: Error message "Invalid email or password"

✓ Test 3: Invalid password
  - Email: vendor1@saraplocal.com
  - Password: wrongpassword
  - Expected: Error message "Invalid email or password"

✓ Test 4: Empty fields
  - Email: (empty)
  - Password: (empty)
  - Expected: Error message "Please complete all fields"

✓ Test 5: Rate limiting
  - Try 5+ failed logins
  - Expected: Account locked for 15 minutes
```

### Signup Tests

```
✓ Test 1: Valid vendor signup
  - Username: testvendor
  - Email: testvendor@test.com
  - Password: Test123456
  - Confirm: Test123456
  - Role: Vendor
  - Expected: Success, redirect to login

✓ Test 2: Weak password
  - Password: test123
  - Expected: Error "Password must be at least 8 characters..."

✓ Test 3: Password mismatch
  - Password: Test123456
  - Confirm: Test123457
  - Expected: Error "Passwords do not match"

✓ Test 4: Duplicate email
  - Email: vendor1@saraplocal.com
  - Expected: Error "Email already registered"

✓ Test 5: Invalid email
  - Email: notanemail
  - Expected: Error "Invalid email format"
```

## 🛍️ Customer Features Testing

### Product Search

```
✓ Test 1: Search by product name
  - Search: "adobo"
  - Expected: Show adobo products

✓ Test 2: Search by vendor
  - Search: "Lola's Kitchen"
  - Expected: Show all products from vendor

✓ Test 3: Filter by price
  - Price range: 100-200
  - Expected: Show products in range

✓ Test 4: Sort by price (low to high)
  - Expected: Products sorted ascending

✓ Test 5: Sort by rating
  - Expected: Products sorted by rating

✓ Test 6: Empty search
  - Expected: Show all available products
```

### Shopping Cart

```
✓ Test 1: Add to cart
  - Product: Adobo
  - Quantity: 2
  - Expected: Item added, cart count updated

✓ Test 2: Update quantity
  - Change quantity to 5
  - Expected: Cart updated, total recalculated

✓ Test 3: Remove from cart
  - Click remove
  - Expected: Item removed, cart updated

✓ Test 4: Clear cart
  - Click clear all
  - Expected: All items removed

✓ Test 5: Insufficient stock
  - Try to add more than available
  - Expected: Error "Insufficient stock"

✓ Test 6: Cart persistence
  - Add items, refresh page
  - Expected: Items still in cart
```

### Checkout & Orders

```
✓ Test 1: Place order
  - Add items to cart
  - Enter delivery address
  - Select payment method
  - Click checkout
  - Expected: Order created, confirmation shown

✓ Test 2: Order validation
  - Try checkout with empty cart
  - Expected: Error "No items in order"

✓ Test 3: Order history
  - Go to orders page
  - Expected: All customer orders listed

✓ Test 4: Order details
  - Click on order
  - Expected: Show order items, status, total

✓ Test 5: Order status tracking
  - Expected: Show current status (pending, confirmed, etc.)
```

## 🏪 Vendor Features Testing

### Product Management

```
✓ Test 1: Add product
  - Name: Test Product
  - Description: Test description
  - Price: 150
  - Upload image
  - Expected: Product added to inventory

✓ Test 2: Edit product
  - Change price to 200
  - Expected: Product updated

✓ Test 3: Delete product
  - Click delete
  - Expected: Product removed

✓ Test 4: Toggle availability
  - Mark as unavailable
  - Expected: Product hidden from customers

✓ Test 5: Bulk actions
  - Select multiple products
  - Expected: Bulk edit/delete options

✓ Test 6: Product search
  - Search vendor's products
  - Expected: Filtered results
```

### Order Management

```
✓ Test 1: View orders
  - Expected: List of all vendor's orders

✓ Test 2: Update order status
  - Change status to "preparing"
  - Expected: Status updated, customer notified

✓ Test 3: View order details
  - Click on order
  - Expected: Show customer info, items, total

✓ Test 4: Order notifications
  - New order arrives
  - Expected: Notification bell updates

✓ Test 5: Mark notification as read
  - Click notification
  - Expected: Marked as read

✓ Test 6: Clear notifications
  - Click "clear all"
  - Expected: All notifications cleared
```

### Profile Management

```
✓ Test 1: Update profile
  - Change business name
  - Update phone number
  - Expected: Changes saved

✓ Test 2: Upload profile image
  - Upload JPG/PNG image
  - Expected: Image saved and displayed

✓ Test 3: Upload business logo
  - Upload logo
  - Expected: Logo saved and displayed

✓ Test 4: Set location
  - Enter latitude/longitude
  - Expected: Location saved

✓ Test 5: Image validation
  - Try uploading non-image file
  - Expected: Error "Invalid file type"

✓ Test 6: Image size limit
  - Try uploading >5MB image
  - Expected: Error "File too large"
```

## 📱 Responsive Design Testing

### Mobile (375px - 640px)

```
✓ Test 1: Navigation
  - Expected: Hamburger menu visible

✓ Test 2: Product grid
  - Expected: Single column layout

✓ Test 3: Forms
  - Expected: Full width inputs

✓ Test 4: Images
  - Expected: Properly scaled

✓ Test 5: Touch targets
  - Expected: Buttons >44px for touch
```

### Tablet (641px - 1024px)

```
✓ Test 1: Product grid
  - Expected: 2-column layout

✓ Test 2: Navigation
  - Expected: Horizontal menu visible

✓ Test 3: Modals
  - Expected: Properly sized
```

### Desktop (1025px+)

```
✓ Test 1: Product grid
  - Expected: 3-4 column layout

✓ Test 2: Sidebar
  - Expected: Visible and functional

✓ Test 3: Full features
  - Expected: All features accessible
```

## 🔒 Security Testing

### SQL Injection

```
✓ Test 1: Search injection
  - Search: "'; DROP TABLE products; --"
  - Expected: Treated as literal string, no error

✓ Test 2: Login injection
  - Email: "admin'--"
  - Expected: No access granted

✓ Test 3: Product filter injection
  - Price: "1 OR 1=1"
  - Expected: Treated as literal value
```

### XSS (Cross-Site Scripting)

```
✓ Test 1: Search XSS
  - Search: "<script>alert('xss')</script>"
  - Expected: No alert, script escaped

✓ Test 2: Product name XSS
  - Product name: "<img src=x onerror=alert('xss')>"
  - Expected: No alert, HTML escaped

✓ Test 3: Comment XSS
  - Comment: "<svg onload=alert('xss')>"
  - Expected: No alert, SVG escaped
```

### CSRF (Cross-Site Request Forgery)

```
✓ Test 1: Form CSRF token
  - Check form has csrf_token
  - Expected: Token present and valid

✓ Test 2: API CSRF
  - Try POST without token
  - Expected: Request rejected

✓ Test 3: Token validation
  - Use invalid token
  - Expected: Request rejected
```

### Authentication

```
✓ Test 1: Session timeout
  - Login, wait 1 hour
  - Expected: Session expires, redirect to login

✓ Test 2: Cross-role access
  - Login as customer, try accessing vendor.php
  - Expected: Redirect to customer.php

✓ Test 3: Session regeneration
  - Login
  - Expected: Session ID changes

✓ Test 4: Logout
  - Click logout
  - Expected: Session destroyed, redirect to home
```

## ⚡ Performance Testing

### Page Load Time

```
✓ Test 1: Home page
  - Expected: < 2 seconds

✓ Test 2: Search results
  - Expected: < 1 second

✓ Test 3: Product details
  - Expected: < 1 second

✓ Test 4: Checkout
  - Expected: < 2 seconds
```

### Database Performance

```
✓ Test 1: Search with 1000 products
  - Expected: < 500ms response

✓ Test 2: Load orders with pagination
  - Expected: < 500ms response

✓ Test 3: Get notifications
  - Expected: < 200ms response
```

### API Performance

```
✓ Test 1: Search API
  - 100 concurrent requests
  - Expected: All complete successfully

✓ Test 2: Cart API
  - 50 concurrent add/remove
  - Expected: All operations succeed

✓ Test 3: Orders API
  - 50 concurrent order creation
  - Expected: All orders created
```

## 🐛 Bug Reporting

When reporting bugs, include:

1. **Steps to reproduce**
   - Exact steps to trigger the bug

2. **Expected behavior**
   - What should happen

3. **Actual behavior**
   - What actually happened

4. **Environment**
   - Browser and version
   - OS
   - Screen size

5. **Screenshots/Videos**
   - Visual evidence of the bug

6. **Error logs**
   - Console errors (F12)
   - Server logs

## ✅ Sign-Off Checklist

- [ ] All authentication tests pass
- [ ] All customer features work
- [ ] All vendor features work
- [ ] Responsive design works on all devices
- [ ] Security tests pass
- [ ] Performance meets requirements
- [ ] No console errors
- [ ] No database errors
- [ ] All forms validate properly
- [ ] File uploads work correctly
- [ ] Notifications work
- [ ] Search functionality works
- [ ] Cart operations work
- [ ] Order creation works
- [ ] Profile updates work
- [ ] All API endpoints work
- [ ] Error handling works
- [ ] Rate limiting works
- [ ] Session management works
- [ ] CSRF protection works

---

**Ready for Production! ✅**
