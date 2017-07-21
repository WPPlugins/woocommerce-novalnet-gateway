/**
 * README INSTRUCTIONS
 *
 * Direct Debit SEPA, Direct Debit SEPA with payment guarantee
 * Credit Card (3DSecure and non 3DSecure):
 * Visa, Mastercard, Amex, JCB, CUP, Cartasi and Maestro
 * Prepayment, Invoice, Invoice with payment guarantee
 * Online Transfer: giropay, eps, iDEAL and Instant Bank Transfer and Przelewy24
 * Wallet system: PayPal
 *
 * These modules are programmed in high standard and supports
 * PCI DSS standard and the trusted shops standard used for
 * real time processing of transactions through Novalnet
 *
 * Released under the GNU General Public License
 *
 * This free contribution made by request.
 * If you have found this script useful a small recommendation
 * as well as a comment on merchant form would be greatly appreciated.
 *
 * Copyright (c) Novalnet
 *
 **********************************************************************************
 * SPECIFICATION DETAILS
 *
 * Created                           - Novalnet
 *
 * CMS (Wordpress) Version           - 4.x
 *
 * Shop (WooCommerce) Version        - 2.2.x - 3.0.2
 *
 * Woocommerce subscription version  - 1.5.x - 2.2.4
 *
 * Novalnet Version                  - 11.2.0
 *
 * Last Updated                      - 13.04.2017
 *
 * Stability                         - Stable
 *
 * Categories                        - Payment Gateways
 *
 **/

----------
IMPORTANT:
----------

1. Please configure your server IP address on Novalnet Administration portal under the menu "Project", for transaction API access on Void, Capture, Refund and Transaction status enquiry from your shop.

2. Make sure that you have installed "curl" in your system. If not, please install curl. For installation help visit "http://curl.haxx.se/docs/ install.html".

    If you use Ubuntu/Debian, you can try the following commands:

	sudo apt-get install curl php5-curl php5-mcrypt
	apachectl restart (restart the Webserver)

3. The file "freewarelicenseagreement.txt" is part of this readme file.

-----------------------
Installation procedure:
-----------------------

Step 1:
=======

To install Novalnet payment module, kindly refer "IG-wordpress_v_4.x_woocommerce_v_2.2.x-3.0.2_woo-subscription_v_1.5.x-2.2.4_novalnet_v_11.2.0.pdf".

-----
Note:
-----

1. After Installing / Updating the Novalnet module, kindly save the Novalnet Global Configuration and payment settings.

2. Clear cache (browser cache) or cache folders if there are any.

3. If you require the customer note for renewal order confirmation E-mail, Kindly customize your renewal order mail templates. You will get Novalnet transaction details in customer note.

As per the shop flow, customer notes will display only for particular order status in the renewal order mail. If Novalnet transaction details are required in renewal order E-mail, Kindly customize your renewal order mail templates.

4. If you want to customize the Credit Card iframe form text, kindly customize the text in the respective language.

------------------------------------------------------------------------
AFFILIATE PROCESS: Follow the below necessary step to set up the process
------------------------------------------------------------------------
Follow the below necessary step to set up the process

* Set the shop website URL with the vendor id:

E.g.: https://woocommerce-demo.novalnet.de.novalnet.de/?wc-api=novalnet_affiliate&nn_aff_id=Vendor-ID

---------------
Important note:
---------------
Kindly, contact sales@novalnet.de / tel. +49 89 923068320 to get the test data to process the payment.

---------------------------------------------------------------------------------------------------------------------------------
Callback script: This is necessary for keeping your database/system actual and synchronize with the Novalnet's transaction status
---------------------------------------------------------------------------------------------------------------------------------

Your system will be notified through Novalnet system (asynchronous) about each transaction and its status.

For example, if you use Novalnet's "Invoice/Prepayment/PayPal/Przelewy24" payment methods then on receival of the credit entry, your system will be notified through the Novalnet system and your system can automatically change the status of the order: from "Order completion status/Order status for the pending payment" to "Callback order status/Order completion status".

Please use the "class-wc-novalnet-api-callback.php" provided in this payment package. Please follow the instructions in the "callback_script_testing_procedure.txt" file. You will find more details in the "class-wc-novalnet-api-callback.php" script itself under the path : <Root_Directory>/wp-content/plugins/woocommerce-novalnet-gateway/includes/api/

After logging into Novalnet administration area, please choose your particular project, navigate to "PROJECT" menu, then select appropriate "Project" and navigate to "Edit Project Overview" tab and then update callback script url in "Vendor script URL" field to update callback script URL in Novalnet administration area for callback script execution.

E.g.: https://woocommerce-demo.novalnet.de?wc-api=novalnet_callback

Please contact us on sales@novalnet.de for activating other payment methods
============================================================================

OUR CONTACT DETAILS / YOU CAN REACH US ON:

Tel    : +49 89 923 068 321
Web    : https://www.novalnet.de
E-mail : support@novalnet.de

***********End of File***********
