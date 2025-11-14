<?php

return [
    /*
    |--------------------------------------------------------------------------
    | E-commerce Translations
    |--------------------------------------------------------------------------
    |
    | These are the e-commerce specific translations for the application.
    |
    */

    // Products
    'products' => [
        'title' => 'Products',
        'no_products_found' => 'No products found.',
        'product_added' => 'Product added successfully.',
        'product_updated' => 'Product updated successfully.',
        'product_deleted' => 'Product deleted successfully.',
        'out_of_stock' => 'Out of Stock',
        'in_stock' => 'In Stock',
        'low_stock' => 'Low Stock',
        'featured_products' => 'Featured Products',
        'related_products' => 'Related Products',
        'product_details' => 'Product Details',
        'product_variants' => 'Product Variants',
        'select_variant' => 'Select Variant',
        'product_search' => 'Search Products',
        'search_placeholder' => 'Search for products...',
        'filter_by_category' => 'Filter by Category',
        'sort_by' => 'Sort by',
        'sort_options' => [
            'name_asc' => 'Name (A-Z)',
            'name_desc' => 'Name (Z-A)',
            'price_low' => 'Price (Low to High)',
            'price_high' => 'Price (High to Low)',
            'newest' => 'Newest First',
            'oldest' => 'Oldest First',
        ],
        'price_range' => 'Price Range',
        'min_price' => 'Min Price',
        'max_price' => 'Max Price',
        'apply_filters' => 'Apply Filters',
        'clear_filters' => 'Clear Filters',
    ],

    // Categories
    'categories' => [
        'title' => 'Categories',
        'all_categories' => 'All Categories',
        'category_added' => 'Category added successfully.',
        'category_updated' => 'Category updated successfully.',
        'category_deleted' => 'Category deleted successfully.',
        'no_categories_found' => 'No categories found.',
        'category_tree' => 'Category Tree',
        'subcategories' => 'Subcategories',
        'parent_category' => 'Parent Category',
        'no_products_in_category' => 'No products found in this category.',
    ],

    // Cart
    'cart' => [
        'title' => 'Shopping Cart',
        'add_to_cart' => 'Add to Cart',
        'added_to_cart' => 'Product added to cart!',
        'cart_updated' => 'Cart updated successfully.',
        'cart_cleared' => 'Cart cleared successfully.',
        'item_removed' => 'Item removed from cart.',
        'quantity_updated' => 'Quantity updated successfully.',
        'cart_empty' => 'Your cart is empty.',
        'cart_total' => 'Cart Total',
        'subtotal' => 'Subtotal',
        'tax' => 'Tax',
        'shipping' => 'Shipping',
        'discount' => 'Discount',
        'total' => 'Total',
        'continue_shopping' => 'Continue Shopping',
        'proceed_to_checkout' => 'Proceed to Checkout',
        'item_count' => ':count item(s)',
        'empty_cart_message' => 'Your cart is empty. Add some products to get started!',
    ],

    // Checkout
    'checkout' => [
        'title' => 'Checkout',
        'shipping_address' => 'Shipping Address',
        'billing_address' => 'Billing Address',
        'same_as_shipping' => 'Same as shipping address',
        'payment_method' => 'Payment Method',
        'place_order' => 'Place Order',
        'order_placed' => 'Order placed successfully!',
        'order_failed' => 'Order placement failed. Please try again.',
        'processing_payment' => 'Processing payment...',
        'payment_successful' => 'Payment successful!',
        'order_summary' => 'Order Summary',
        'order_items' => 'Order Items',
    ],

    // Orders
    'orders' => [
        'title' => 'My Orders',
        'order_history' => 'Order History',
        'order_details' => 'Order Details',
        'order_number' => 'Order Number',
        'order_date' => 'Order Date',
        'order_status' => 'Order Status',
        'payment_status' => 'Payment Status',
        'no_orders_found' => 'No orders found.',
        'order_cancelled' => 'Order cancelled successfully.',
        'order_tracking' => 'Order Tracking',
        'track_order' => 'Track Order',
        'shipping_info' => 'Shipping Information',
        'estimated_delivery' => 'Estimated Delivery',
        'delivered' => 'Delivered',
        'cancelled' => 'Cancelled',
        'refunded' => 'Refunded',
        'statuses' => [
            'pending' => 'Pending',
            'confirmed' => 'Confirmed',
            'processing' => 'Processing',
            'shipped' => 'Shipped',
            'delivered' => 'Delivered',
            'cancelled' => 'Cancelled',
            'refunded' => 'Refunded',
        ],
        'payment_statuses' => [
            'pending' => 'Pending',
            'paid' => 'Paid',
            'failed' => 'Failed',
            'refunded' => 'Refunded',
        ],
    ],

    // Wishlist
    'wishlist' => [
        'title' => 'Wishlist',
        'add_to_wishlist' => 'Add to Wishlist',
        'added_to_wishlist' => 'Product added to wishlist!',
        'wishlist_updated' => 'Wishlist updated successfully.',
        'wishlist_cleared' => 'Wishlist cleared successfully.',
        'item_removed' => 'Item removed from wishlist.',
        'move_to_cart' => 'Move to Cart',
        'moved_to_cart' => 'Item moved to cart!',
        'wishlist_empty' => 'Your wishlist is empty.',
        'no_wishlists_found' => 'No wishlists found.',
        'create_wishlist' => 'Create Wishlist',
        'wishlist_name' => 'Wishlist Name',
        'make_public' => 'Make Public',
        'make_private' => 'Make Private',
        'share_wishlist' => 'Share Wishlist',
    ],

    // Coupons
    'coupons' => [
        'title' => 'Coupons',
        'apply_coupon' => 'Apply Coupon',
        'coupon_code' => 'Coupon Code',
        'apply' => 'Apply',
        'remove' => 'Remove',
        'coupon_applied' => 'Coupon applied successfully!',
        'coupon_removed' => 'Coupon removed successfully.',
        'coupon_invalid' => 'Invalid coupon code.',
        'coupon_expired' => 'Coupon has expired.',
        'coupon_used' => 'Coupon has already been used.',
        'discount_applied' => 'Discount Applied',
        'no_coupons_available' => 'No coupons available.',
        'available_coupons' => 'Available Coupons',
    ],

    // User Account
    'account' => [
        'title' => 'My Account',
        'profile' => 'Profile',
        'addresses' => 'Addresses',
        'add_address' => 'Add Address',
        'edit_address' => 'Edit Address',
        'default_address' => 'Default Address',
        'shipping_address' => 'Shipping Address',
        'billing_address' => 'Billing Address',
        'set_as_default' => 'Set as Default',
        'address_saved' => 'Address saved successfully.',
        'address_deleted' => 'Address deleted successfully.',
        'no_addresses_found' => 'No addresses found.',
        'account_details' => 'Account Details',
        'update_profile' => 'Update Profile',
        'profile_updated' => 'Profile updated successfully.',
        'change_password' => 'Change Password',
        'password_changed' => 'Password changed successfully.',
        'order_history' => 'Order History',
        'total_spent' => 'Total Spent',
        'total_orders' => 'Total Orders',
    ],

    // Search
    'search' => [
        'title' => 'Search',
        'search_results' => 'Search Results',
        'searching_for' => 'Searching for...',
        'no_results_for' => 'No results found for ":query"',
        'results_found' => ':count result(s) found for ":query"',
        'did_you_mean' => 'Did you mean:',
        'search_suggestions' => 'Search Suggestions',
        'recent_searches' => 'Recent Searches',
        'popular_searches' => 'Popular Searches',
        'advanced_search' => 'Advanced Search',
        'filters' => 'Filters',
        'refine_search' => 'Refine Search',
    ],

    // Errors
    'errors' => [
        'product_not_found' => 'Product not found.',
        'category_not_found' => 'Category not found.',
        'order_not_found' => 'Order not found.',
        'access_denied' => 'Access denied.',
        'invalid_request' => 'Invalid request.',
        'server_error' => 'Server error. Please try again later.',
        'network_error' => 'Network error. Please check your connection.',
        'something_went_wrong' => 'Something went wrong. Please try again.',
        'page_not_found' => 'Page not found.',
        'unauthorized' => 'Unauthorized access.',
        'forbidden' => 'Forbidden access.',
        'too_many_requests' => 'Too many requests. Please try again later.',
        'maintenance_mode' => 'Site is under maintenance. Please try again later.',
    ],

    // Success Messages
    'success' => [
        'operation_completed' => 'Operation completed successfully.',
        'changes_saved' => 'Changes saved successfully.',
        'item_added' => 'Item added successfully.',
        'item_updated' => 'Item updated successfully.',
        'item_deleted' => 'Item deleted successfully.',
        'email_sent' => 'Email sent successfully.',
        'message_sent' => 'Message sent successfully.',
        'file_uploaded' => 'File uploaded successfully.',
        'data_loaded' => 'Data loaded successfully.',
    ],

    // Navigation
    'navigation' => [
        'home' => 'Home',
        'shop' => 'Shop',
        'about_us' => 'About Us',
        'contact_us' => 'Contact Us',
        'faq' => 'FAQ',
        'terms' => 'Terms & Conditions',
        'privacy' => 'Privacy Policy',
        'returns' => 'Returns & Refunds',
        'shipping_info' => 'Shipping Information',
        'payment_info' => 'Payment Information',
        'customer_service' => 'Customer Service',
        'blog' => 'Blog',
        'sale' => 'Sale',
        'new_arrivals' => 'New Arrivals',
        'best_sellers' => 'Best Sellers',
        'special_offers' => 'Special Offers',
        'my_account' => 'My Account',
        'logout' => 'Logout',
        'login' => 'Login',
        'register' => 'Register',
        'cart' => 'Cart',
        'wishlist' => 'Wishlist',
        'checkout' => 'Checkout',
    ],

    // Footer
    'footer' => [
        'about_us' => 'About Us',
        'contact_info' => 'Contact Information',
        'follow_us' => 'Follow Us',
        'newsletter' => 'Newsletter',
        'subscribe' => 'Subscribe',
        'copyright' => '© :year E-Commerce App. All rights reserved.',
        'all_rights_reserved' => 'All rights reserved.',
        'powered_by' => 'Powered by E-Commerce App',
    ],

    // Social
    'social' => [
        'facebook' => 'Facebook',
        'twitter' => 'Twitter',
        'instagram' => 'Instagram',
        'linkedin' => 'LinkedIn',
        'youtube' => 'YouTube',
        'pinterest' => 'Pinterest',
    ],

    // Payment Methods
    'payment_methods' => [
        'credit_card' => 'Credit Card',
        'debit_card' => 'Debit Card',
        'paypal' => 'PayPal',
        'stripe' => 'Stripe',
        'apple_pay' => 'Apple Pay',
        'google_pay' => 'Google Pay',
        'bank_transfer' => 'Bank Transfer',
        'cash_on_delivery' => 'Cash on Delivery',
    ],

    // Shipping Methods
    'shipping_methods' => [
        'standard_shipping' => 'Standard Shipping',
        'express_shipping' => 'Express Shipping',
        'free_shipping' => 'Free Shipping',
        'local_pickup' => 'Local Pickup',
        'international_shipping' => 'International Shipping',
    ],

    // Time
    'time' => [
        'now' => 'Now',
        'today' => 'Today',
        'yesterday' => 'Yesterday',
        'this_week' => 'This Week',
        'last_week' => 'Last Week',
        'this_month' => 'This Month',
        'last_month' => 'Last Month',
        'this_year' => 'This Year',
        'last_year' => 'Last Year',
        'ago' => ':time ago',
        'minutes_ago' => ':count minutes ago',
        'hours_ago' => ':count hours ago',
        'days_ago' => ':count days ago',
        'weeks_ago' => ':count weeks ago',
        'months_ago' => ':count months ago',
        'years_ago' => ':count years ago',
    ],
];
