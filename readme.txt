=== Product Allocation for WooCommerce ===
Tags: product max, maximum quantity, allocation, wine, maximum product, limit product
Requires at least: 4.0.0
Tested up to: 4.5.2
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/license-list.html#GPLCompatibleLicenses

Set the maximum quantity of a WooCommerce product a customer is allowed to purchase.

== Description ==
Our  WooCommerce Product Allocation plugin allows you to set the maximum quantity of product an individual customer is allowed to purchase from your WooCommerce store. This quantity may be set differently for each customer and differently for each product (6 SKU limit at this time).

This allocation tool may be applied in situations where products have limited availability and demand exceeds supply. To ensure customers are given an opportunity to purchase the products, set maximum quantity limits for each product. Apply different maximums to different customers to reward loyalty or for other preferential treatment.

To set the allocation, go to the WordPress dashboard -> WooCommerce -> Settings and click on the “Allocations” tab. The table may be filled in with as few as one SKU and one Tier or as many as 6 of each to set the maximum quantity a customer is allowed to purchase a product. Enter the start and end dates for the allocation and chose whether you want to apply the allocation to all customer at once (BEWARE THIS IS IRREVERSABLE). Then go to the WordPress dashboard -> Users -> Click “Add New” or click “Edit” under an existing user. Scroll down to “Allocation Tier “. Set the Tier corresponding to the entry you made in the allocation table.
NOTE: a customer must be logged in to be able to purchase. New customers will be informed on screen if they try to purchase without an allocation with this warning: “You must be logged in and have an allocation. Please login or join.”


== Installation ==
INSTALLATION
1.Download the plugin file (.zip).
2.In your WordPress dashboard, go to “Plugins -> Add New”, and click “Upload”.
3.Upload the plugin file and activate it.
4.Go to WordPress dashboard -> WooCommerce -> Settings and click on “Allocations” tab. Select a product SKU for any or all of the cells in the header of the table (you must have already entered products in WooCommerce). For each SKU enter how many units of the product will be allocated for sale at each Tier. Use Tiers to differentiate allocations per customer.
5.Go to WordPress dashboard -> Users -> Click “Add New” or click “Edit” under an existing user. Scroll down to “Allocation Tier “. Set the Tier corresponding to the entry you made in step #4.
6.In the “Allocations” tab select Allocation start and Allocation ending dates and times
7.If you wish to change the allocation tier for all customers at once, select the tier for which you wish to perform this operation in the “Apply to All Customers” field. BEWARE as this action will irreversible change prior allocation tier setting for all customers.

== Frequently Asked Questions ==
FAQ
Can a new customer get an allocation?
No, not until you set the allocation on the back end. New customers will be informed on screen if they try to purchase without an allocation with this warning: “You must be logged in and have an allocation. Please login or join.”

Can I require a minimum purchase of any product?
Not at this time. Contact me to request this revision.

Why are there only 6 SKU options?
If this is not enough, contact me to request a revision.

How can I set an allocation tier to a group of customers at once?
Unless that group is every customer (see installation instruction #7), then it is not possible with this version.  We will offer a pro version with this feature in the future.  Please contact us at http://www.shop.hh-studio.com/contact/ to request.


== Screenshots ==
1. Set up allocation tiers
2. Apply tier to customer
3. View tier in customer profile

== Changelog ==
1.0
