<?php
/**
 * Novalnet table creation for 11.0.0.
 *
 * @author   Novalnet
 * @category Admin
 * @package  Novalnet-gateway/Updates
 * @version  11.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Including upgrader file to perform table creation.
require_once ABSPATH . 'wp-admin/includes/upgrade.php';

$collate = $wpdb->get_charset_collate();

// Creating transaction details table to maintain the transaction log.
wc_novalnet_query_process( dbDelta( "CREATE TABLE {$wpdb->prefix}novalnet_transaction_detail (
    id int(11) unsigned AUTO_INCREMENT COMMENT 'Auto increment ID',
    order_no int(11) unsigned COMMENT 'Post ID for the order in shop',
    order_number_formatted text COMMENT 'Formatted order number',
    vendor_id int(8) unsigned COMMENT 'Novalnet vendor ID',
    auth_code varchar(50) COMMENT 'Novalnet authentication code',
    product_id int(8) unsigned COMMENT 'Novalnet project ID',
    tariff_id int(8) unsigned COMMENT 'Novalnet tariff ID',
    payment_id int(4) unsigned COMMENT 'Payment ID',
    payment_type varchar(50) COMMENT 'Executed Payment type of this order',
    tid bigint(20) unsigned COMMENT 'Novalnet Transaction Reference ID',
    subs_id int(8) unsigned COMMENT 'Subscription ID',
    amount int(11) unsigned COMMENT 'Transaction amount in minimum unit of currency. E.g. enter 100 which is equal to 1.00',
    callback_amount int(11) unsigned DEFAULT '0' COMMENT 'Transaction paid amount in minimum unit of currency. E.g. enter 100 which is equal to 1.00',
    refunded_amount int(11) unsigned DEFAULT '0' COMMENT 'Transaction refunded amount in minimum unit of currency. E.g. enter 100 which is equal to 1.00',
    currency varchar(5) COMMENT 'Transaction currency',
    gateway_status int(11) unsigned COMMENT 'Novalnet transaction status',
    test_mode enum('0','1') DEFAULT '0' COMMENT 'Transaction test mode status',
    customer_id int(11) unsigned COMMENT 'Customer ID from shop',
    customer_email varchar(255) COMMENT 'Customer Email from shop',
    `date` datetime COMMENT 'Transaction Date for reference',
    payment_ref enum('0','1') DEFAULT '0' COMMENT 'Payment reference transaction',
    booked enum('0','1') DEFAULT '1' COMMENT 'Transaction booked',
    payment_params text COMMENT 'Payment params used for zero amount booking',
    bank_details text COMMENT 'Bank details used in gateways',
    PRIMARY KEY  (id),
    KEY tid (tid),
    KEY customer_id (customer_id),
    KEY payment_ref (payment_ref),
    KEY booked (booked),
    KEY payment_type (payment_type),
    KEY order_no (order_no)
    )" . $collate . " COMMENT='Novalnet Transaction History';" ) );

// Creating subscription table to maintain the subscription log.
wc_novalnet_query_process( dbDelta( "CREATE TABLE {$wpdb->prefix}novalnet_subscription_details (
    id int(11) unsigned AUTO_INCREMENT COMMENT 'Auto increment ID',
    order_no int(11) unsigned COMMENT 'Post ID for the order in shop',
    payment_type varchar(50) COMMENT 'Payment Type',
    recurring_payment_type varchar(50) COMMENT 'Recurring payment Type',
    recurring_amount int(11) unsigned COMMENT 'Amount in minimum unit of currency. E.g. enter 100 which is equal to 1.00',
    tid bigint(20) unsigned COMMENT 'Novalnet Transaction Reference ID',
    recurring_tid bigint(20) unsigned COMMENT 'Novalnet transaction reference ID',
    subs_id int(8) unsigned COMMENT 'Subscription ID in Novalnet',
    signup_date datetime COMMENT 'Subscription signup date',
    next_payment_date datetime COMMENT 'Subscription next cycle date',
    suspended_date datetime COMMENT 'Subscription suspended date',
    termination_reason varchar(255) COMMENT 'Subscription termination reason',
    termination_at datetime COMMENT 'Subscription terminated date',
    subscription_length int(8) unsigned COMMENT 'Length of the subscription',
    PRIMARY KEY  (id),
    KEY order_no (order_no),
    KEY tid (tid)
	)" . $collate . " COMMENT='Novalnet Subscription Payment Details'" ) );

// Creating callback table to maintain callback log.
wc_novalnet_query_process( dbDelta( "CREATE TABLE {$wpdb->prefix}novalnet_callback_history (
    id int(11) unsigned AUTO_INCREMENT COMMENT 'Auto increment ID',
    `date` datetime COMMENT 'Callback execution date and time',
    payment_type varchar(50) COMMENT 'Callback Payment Type',
    status int(11) unsigned COMMENT 'Callback Status',
    callback_tid bigint(20) unsigned COMMENT 'Callback Reference ID',
    org_tid bigint(20) unsigned COMMENT 'Original Transaction ID',
    amount int(11) unsigned COMMENT 'Amount in minimum unit of currency. E.g. enter 100 which is equal to 1.00',
    order_no int(11) unsigned COMMENT 'Post ID for the order in shop',
    PRIMARY KEY  (id)
    )" . $collate . " COMMENT='Novalnet callback history';" ) );

// Creating Affliate process table.
wc_novalnet_query_process( dbDelta( "CREATE TABLE {$wpdb->prefix}novalnet_aff_account_detail (
    id int(11) unsigned AUTO_INCREMENT COMMENT 'Auto increment ID',
    vendor_id int(8) unsigned COMMENT 'ID of the vendor',
    vendor_authcode varchar(40) COMMENT 'Authentication code of the vendor',
    product_id int(8) unsigned COMMENT 'Project ID for the affiliate',
    product_url text COMMENT 'Product URL for the affiliate',
    activation_date datetime COMMENT 'Affiliate activation date',
    aff_id int(11) unsigned COMMENT 'ID for the affiliate',
    aff_authcode varchar(50) COMMENT 'Authentication code for the affiliate',
    aff_accesskey varchar(50) COMMENT 'Access key for the affiliate',
    PRIMARY KEY  (id),
    KEY aff_id (aff_id)
    )" . $collate . " COMMENT='Novalnet merchant / affiliate account information';" ) );

// Creating Affliate user detail table.
wc_novalnet_query_process( dbDelta( "CREATE TABLE {$wpdb->prefix}novalnet_aff_user_detail (
    id int(11) unsigned AUTO_INCREMENT COMMENT 'Auto increment ID',
    aff_id int(8) unsigned COMMENT 'Affiliate merchant ID',
    customer_id int(11) unsigned COMMENT 'Affiliate customer ID',
    aff_shop_id int(11) unsigned COMMENT 'Post ID for the order in shop',
    aff_order_no varchar(20) COMMENT 'Order ID for the order in shop',
    PRIMARY KEY  (id),
    KEY customer_id (customer_id)
    )" . $collate . " COMMENT='Novalnet affiliate customer account information'" ) );
