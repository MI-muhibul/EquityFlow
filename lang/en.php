<?php
declare(strict_types=1);

/**
 * EquityFlow - English Language Pack
 * Tagline: Where Financial Records Flow with Accuracy
 */

return [
    // App Meta
    'app_name'                => 'EquityFlow',
    'app_tagline'             => 'Where Financial Records Flow with Accuracy',
    'currency_symbol'         => '৳',
    'toggle_language'         => 'বাংলা',

    // Navigation
    'nav_dashboard'           => 'Dashboard',
    'nav_customers'           => 'Customer Ledger',
    'nav_suppliers'           => 'Supplier Ledger',
    'nav_products'            => 'Inventory / Products',
    'nav_transactions'        => 'Ledger Entries',
    'nav_cashbook'            => 'Daily Khata',
    'nav_reports'             => 'Financial Reports',
    'nav_settings'            => 'Settings',
    'nav_backup'              => 'Database Backup',
    'nav_logout'              => 'Logout',

    // Action Buttons
    'btn_save'                => 'Save',
    'btn_cancel'              => 'Cancel',
    'btn_delete'              => 'Delete',
    'btn_edit'                => 'Edit',
    'btn_update'              => 'Update',
    'btn_add_customer'        => 'Add Customer',
    'btn_add_product'         => 'Add Product',
    'btn_new_transaction'     => 'New Transaction',
    'btn_record_credit'       => 'Give Credit (Baki)',
    'btn_record_payment'      => 'Receive Payment (Joma)',
    'btn_search'              => 'Search',
    'btn_filter'              => 'Filter',
    'btn_reset'               => 'Reset',
    'btn_back'                => 'Back',
    'btn_print'               => 'Print Slip',
    'btn_download_backup'     => 'Download Backup (.sql)',
    'btn_change_password'     => 'Change Password',
    'btn_login'               => 'Log In',

    // Form Field Labels
    'label_name'              => 'Full Name',
    'label_phone'             => 'Mobile Phone Number',
    'label_address'           => 'Address / Location',
    'label_balance'           => 'Current Balance',
    'label_credit_limit'      => 'Credit Limit (Max Due)',
    'label_initial_balance'   => 'Starting Balance',
    'label_amount'            => 'Amount (৳)',
    'label_transaction_type'  => 'Transaction Type',
    'label_note'              => 'Note / Description',
    'label_date'              => 'Date',
    'label_product_name'      => 'Product Name',
    'label_category'          => 'Category',
    'label_retail_price'      => 'Retail Price (Sale ৳)',
    'label_cost_price'        => 'Cost Price (Purchase ৳)',
    'label_stock'             => 'Current Stock',
    'label_min_stock'         => 'Minimum Alert Stock',
    'label_quantity'          => 'Quantity',
    'label_username'          => 'Username',
    'label_password'          => 'Password',
    'label_current_password'  => 'Current Password',
    'label_new_password'      => 'New Password',
    'label_confirm_password'  => 'Confirm New Password',
    'label_security_pin'      => 'Security PIN (4-digit)',

    // Inventory Stock Statuses
    'status_in_stock'         => 'In Stock',
    'status_low_stock'        => 'Low Stock Alert',
    'status_out_of_stock'     => 'Out of Stock',

    // Validation & Security Errors
    'error_required_field'    => 'This field is required.',
    'error_invalid_phone'     => 'Please enter a valid Bangladeshi mobile number (e.g. 017xxxxxxxx).',
    'error_phone_exists'      => 'This phone number is already registered for another customer.',
    'error_amount_positive'   => 'Amount must be greater than zero (৳0.00).',
    'error_credit_limit'      => 'Transaction exceeds customer credit limit.',
    'error_product_exists'    => 'A product with this name already exists in inventory.',
    'error_invalid_login'     => 'Incorrect username or password. Please try again.',
    'error_account_locked'    => 'Account is locked due to multiple failed login attempts. Contact support or try later.',
    'error_session_timeout'   => 'Your session has expired due to inactivity. Please log in again.',
    'error_password_match'    => 'The entered passwords do not match.',
    'error_password_length'   => 'New password must be at least 8 characters long.',
    'error_current_password_invalid' => 'Current password is incorrect.',
    'error_csrf_invalid'      => 'Security token invalid or expired. Please submit the form again.',
    'error_stock_insufficient'=> 'Insufficient product stock available for this sale.',
    'error_customer_has_due'  => 'Cannot delete customer with an outstanding due balance.',

    // Success Messages
    'msg_saved_success'       => 'Record saved successfully.',
    'msg_updated_success'     => 'Record updated successfully.',
    'msg_deleted_success'     => 'Record deleted successfully.',
    'msg_login_success'       => 'Welcome back! Logged in successfully.',
    'msg_logout_success'      => 'You have been logged out securely.',
    'msg_password_changed'    => 'Password updated successfully.',
    'msg_backup_created'      => 'Database backup created successfully.',

    // Authentication & Login Page
    'login_title'             => 'Shop Owner Login',
    'login_subtitle'          => 'Enter your credentials to access the digital Khata',
    'login_failed_attempts'   => 'Remaining attempts before lockout: :attempts',

    // Customer Module
    'customer_title'          => 'Customer Ledger Book',
    'customer_details'        => 'Customer Profile & Due Records',
    'customer_total_due'      => 'Total Due Outstanding',
    'customer_advance_paid'   => 'Advance Payment Balance',
    'customer_zero_balance'   => 'Account Settled (No Due)',
    'customer_empty_list'     => 'No customers added yet. Click "Add Customer" to start.',
    'customer_delete_confirm' => 'Are you sure you want to delete this customer? This action cannot be undone.',

    // Transaction & Khata Module
    'txn_title'               => 'Transaction History',
    'txn_type_credit'         => 'Credit Given (Baki)',
    'txn_type_payment'        => 'Payment Received (Joma)',
    'txn_voucher_no'          => 'Receipt / Voucher #',
    'txn_running_balance'     => 'Running Balance',
    'txn_empty_list'          => 'No transactions recorded yet.',

    // Product & Stock Module
    'product_title'           => 'Product & Inventory Stock',
    'product_profit_per_unit' => 'Profit / Unit',
    'product_margin'          => 'Profit Margin (%)',
    'product_empty_list'      => 'No products found in inventory.',

    // Report Module
    'report_title'            => 'Monthly Financial Summary',
    'report_period'           => 'Period',
    'report_total_sales'      => 'Total Sales',
    'report_total_credit'     => 'Total Credit (Baki Given)',
    'report_total_cash'       => 'Total Cash Collected (Joma)',
    'report_net_profit'       => 'Estimated Profit',
    'report_outstanding_due'  => 'Total Uncollected Market Due',

    // Settings Module
    'settings_title'          => 'Shop Profile & Preferences',
    'settings_shop_name'      => 'Shop Name',
    'settings_shop_phone'     => 'Shop Phone Number',
    'settings_shop_address'   => 'Shop Address',
    'settings_language'       => 'Default Language',
    'settings_backup_heading' => 'Database Backup & Safety',

    // Months (Calendar)
    'month_1'                 => 'January',
    'month_2'                 => 'February',
    'month_3'                 => 'March',
    'month_4'                 => 'April',
    'month_5'                 => 'May',
    'month_6'                 => 'June',
    'month_7'                 => 'July',
    'month_8'                 => 'August',
    'month_9'                 => 'September',
    'month_10'                => 'October',
    'month_11'                => 'November',
    'month_12'                => 'December',

    // 404 & Error Fallbacks
    'page_not_found'          => 'Page Not Found',
    'page_not_found_desc'     => 'The page you requested does not exist or has been moved.',
    'controller_missing'      => 'Controller Not Found',
    'controller_missing_desc' => 'The controller for this route is not yet implemented.',
    'back_to_home'            => 'Back to Dashboard',
];
