# TODO

## Fix non-working dashboard sidebar items (admin/restaurant)

- [ ] Identify which dashboard file renders the broken menu (“Orders, Menu, Analytics, Customers, Finance, Staff, Settings, Marketing, Account, Logout”).
  - Already found restaurant/dashboard.php sidebar uses tabs (orders/menu/analytics/customers/finance/staff/settings/marketing).

- [ ] Verify which tab handlers fail (common causes: JS errors preventing onclick navigation and/or missing DOM ids).
  - Run browser console checks after opening restaurant/dashboard.php.

- [ ] Wire missing “staff/settings/marketing” sections to real backend or hide them until implemented.

- [ ] Ensure menu tab (🍽️ Menu) uses correct API responses (api/menu-items.php).

- [ ] Ensure customers tab uses correct API response schema (api/customers.php).

- [ ] Ensure finance tab uses correct API response schema (api/financial.php).

- [ ] Fix admin vs restaurant mismatch if admin UI is wrongly showing restaurant tabs.

- [ ] Add minimal error banners in restaurant/dashboard.php when fetch to /api/... fails.

## Animated Footer (GEBETA footer)

- [ ] Add minimal animated footer markup + styles + JS to dashboards (admin/restaurant/customer/delivery)



