<?php
// Food image mapping helper
function get_food_image($itemName) {
    $name = strtolower($itemName);
    
    if (strpos($name, 'doro') !== false || strpos($name, 'wat') !== false) {
        return '/assets/images/food/doro-wat.jpg';
    }
    if (strpos($name, 'kitfo') !== false) {
        return '/assets/images/food/kitfo.jpg';
    }
    if (strpos($name, 'tibs') !== false) {
        return '/assets/images/food/tibs.jpg';
    }
    if (strpos($name, 'coffee') !== false) {
        return '/assets/images/food/coffee.jpg';
    }
    if (strpos($name, 'juice') !== false) {
        return '/assets/images/food/juice.jpg';
    }
    if (strpos($name, 'pizza') !== false) {
        return '/assets/images/food/pizza.jpg';
    }
    if (strpos($name, 'burger') !== false) {
        return '/assets/images/food/burger.jpg';
    }
    
    // Default to injera for Ethiopian dishes
    return '/assets/images/food/injera.jpg';
}
