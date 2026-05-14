# Gebeta ERD

## Tables

- `users`
- `restaurants`
- `categories`
- `menu_items`
- `orders`
- `order_items`
- `reviews`
- `payments`

## Relationships

- `users.id` -> `restaurants.user_id`
- `restaurants.id` -> `categories.restaurant_id`
- `categories.id` -> `menu_items.category_id`
- `users.id` -> `orders.user_id`
- `restaurants.id` -> `orders.restaurant_id`
- `orders.id` -> `order_items.order_id`
- `menu_items.id` -> `order_items.menu_item_id`
- `orders.id` -> `reviews.order_id`
- `users.id` -> `reviews.user_id`
- `restaurants.id` -> `reviews.restaurant_id`
- `orders.id` -> `payments.order_id`
