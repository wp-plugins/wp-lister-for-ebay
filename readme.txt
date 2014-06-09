=== WP-Lister for eBay ===
Contributors: wp-lab
Tags: ebay, woocommerce, products, export
Requires at least: 3.6
Tested up to: 3.9.1
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

List products from WordPress on eBay. The easy way.

== Description ==

WP-Lister connects your WooCommerce shop with your eBay Store. You can select multiple products right from your products page, select a profile to apply a set of predefined options and list them all on eBay with just a few clicks.

We worked hard to make WP-Lister easy to use but flexible. The workflow of listing items requires not a single click more than neccessary. Due to its tight integration in WordPress you will feel right at home.

= Features =

* list any number of items
* create listing profiles and apply multiple products
* verify items and get listing fees before actually listing them
* choose categories from eBay and your eBay Store
* national and international shipping options
* support for product variations
* create simple listing templates using WordPress post editor
* advanced template editor with HTML / CSS syntax highlighting
* download / upload listing templates – makes life easy for 3rd party template developers

= Localization =

* english
* german
* french
* italian
* spanish
* dutch
* korean
* latvian
* bulgarian

To make WP-Lister available in more languages, we will provide a free license for WP-Lister Pro for everyone who will provide and maintain a new localization.

= Screencast =

http://www.youtube.com/watch?feature=player_embedded&v=zBQilzwr9UI

= More information and Pro version =

Visit http://www.wplab.com/plugins/wp-lister/ to read more about WP-Lister including documentation, installation instructions and user reviews.

To find out more about the different versions have a look on this feature comparison: http://www.wplab.com/plugins/wp-lister/feature-comparison/

== Installation ==

1. Install WP-Lister for eBay either via the WordPress.org plugin repository, or by uploading the files to your server
2. After activating the plugin, visit the new page WP-Lister and follow the setup instructions. 

== Frequently Asked Questions ==

= Does WP-Lister work with all eBay sites? =

Yes, it does.

= What are the requirements to run WP-Lister? =

WP-Lister requires a recent version of WordPress (3.6+) with WooCommerce 2.x installed. Your server should run on Linux and have PHP 5.2 or better with cURL support.

= I use products variations on my site but eBay doesn’t allow variations in the selected category. How can I find out in which categories variations are allowed? =

To learn more about variations and allowed categories you should visit this page: http://pages.ebay.com/help/sell/listing-variations.html. There you will find a link to eBay’s look up table for categories allowing variations. If you can only list to categories where no variations are allowed, consider purchasing WP-Lister Pro which can split variations into single listings.

= I already have listed my products on eBay. Can WP-Lister import them to WooCommerce? =

No, WP-Lister itself was created to let you manage your products in WordPress - and list them *from* WordPress *to* eBay. 

But if you need to import all your items from eBay to WooCommerce first to be able to use WP-Lister, you can use an importer add-on plugin we developed to get you started: http://www.wplab.com/plugins/import-from-ebay-to-woocommerce/. Since importing from eBay is rather complex and support intensive this add-on plugin does have a price tag attached. 

= Does WP-Lister support windows servers? =

Nope.

= Are there any more FAQ? =

Yes, there are more questions and answers on http://www.wplab.com/plugins/wp-lister/faq/

= Any plans for WP-Lister for Amazon? =

Working on it. Contact us if you want to become a beta tester.

== Screenshots ==

1. Listings Page
2. Profile Editor

== Changelog ==
= 1.4.4 =
* added option to show product thumbnails on listings page 
* improved warning before deleting listings from archive 
* removed deprecated order update mode setting 
* fixed layout issues in edit product page 
* fixed various non-translatable strings 
* fixed php 5.4 strict messages 
* fixed issue with non-existing products with pdf invoice plugin 
* fixed site url for eBay Malaysia

= 1.4.3 =
* added option to limit number of items displayed by gallery widgets 
* added option to select tax rate to be used when creating orders with VAT enabled 
* added support for editing imported compatibility tables (beta)
* show warning when scheduled wp-cron jobs are not executed 
* improved "(not) on ebay" product filters 

= 1.4.2 =
* fixed issue with VAT being added to item price in orders when prices are entered with tax 
* fixed rare issue regarding line endings 
* updated italian translation (thanks Valerio) 

= 1.4.1 =
* added support for classified ads (beta) (Pro) 
* update ended items automatically when deactivating auto-relist profile option 
* show when auto-relist is enabled in applied profile on listings page 

= 1.4.0 =
* improved auto relist option - filter scheduled items and option to cancel schedule 
* fixed undefined method wpdb::check_connection error in pre 3.9 
* tested with WordPress 3.9.1 and WooCommerce 2.1.9 

= 1.3.9.2 =
* added option to perform unit conversion on dimensions 
* fixed db error message during first time install 
* fixed item specifics box when creating new profile 
* fixed issue with product description not being updated (introduced in 1.3.9.1) 
* auto sync quantity is default for new profiles 
* compatible with mysqli extension 

= 1.3.9.1 =
* fixed quick edit for locked products 
* enabled product_content and other shortcodes in title prefix and suffix 
* added debug data to order creation process 

= 1.3.9 =
* fully tested with WordPress 3.9 and WooCommerce 2.1.7 

= 1.3.8.5 =
* improved error handling for CompleteSale requests 
* check for duplicate listing when selecting a profile 
* hide eBay Motors from eBay site selection during setup 
* fixed possible fatal error on edit product page 
* updated css for WordPress 3.9 

= 1.3.8.4 =
* improved performance of listings page (if many variations are displayed) 
* added option to limit the maximum number of displayed items 
* fixed issues with auto relist feature 
* added spanish translation 

= 1.3.8.3 =
* added profile option to select product attribute for variation images 
* added option to filter orders which were placed on eBay in WooCommerce 
* improved error handling if no template assigned or found 
* fixed issue with variable products imported from Products CSV Suite 
* fixed issue with Captcha Bank plugin 

= 1.3.8.2 =
* implemented support for multi value attributes / item specifics 
* fixed issues when seller shipping profiles were changed on eBay 
* improved error display on log records page 

= 1.3.8.1 =
* added store pickup profile option 
* fixed removal of secondary eBay category 
* fixed product level eBay price for variable products 
* updated to eBay API version 841 

= 1.3.8 =
* various improvements and fixes - read the full changelog below

= 1.3.7.6 =
* fixed item condition on product level missing "use profile setting" option when a default category was set 

= 1.3.7.5 =
* automatically strip CDATA tags when template is saved 
* added option to lock and unlock all items on tools page 
* improved quantity display for changed items on listings page 
* fixed issue with wrong quantity after updating listing template 

= 1.3.7.4 =
* added option to update item details for ended listings automatically 
* fixed rare MySQL errors on Mac servers 
* fixed bug in new auto relist api option 

= 1.3.7.3 =
* added option to enable relisting ended items automatically when they updated to back in stock via the API 
* improved send to support feature 

= 1.3.7.2 =
* added option to disable notification emails when updating the order status for eBay orders manually 
* improved developer settings page 

= 1.3.7.1 =
* disable template syntax check by default 
* added translation for Dutch/Netherlands (nl_NL) 

= 1.3.7 =
* added optimize log button 
* improved detection of relisted items 
* fixed possible issue with wrong products in created orders 

= 1.3.6.9 =
* enable listing of private products 
* added option to show category settings page in main menu 
* make category settings available in the free version 

= 1.3.6.8 =
* added clean archive feature 
* check PHP syntax of listing template 
* make replace add to cart button option available in free version 

= 1.3.6.7 =
* improved relist option on edit product page 
* added option to select how long to keep log records 
* only display update notice if current user can update plugins 
* fixed issue when free shipping was enabled in profile but disabled on product level 

= 1.3.6.6 =
* added option to disable VAT processing when creating orders 

= 1.3.6.5 =
* fixed check for existing transactions when processing orders for auction items 
* improved manual inventory checks 
* improved order details view 

= 1.3.6.4 =
* fixed issue with ended listing not being marked as ended
* explain error 21919028 

= 1.3.6.2 =
* added support for payment and return profiles on product level
* fixed sticky shipping profile and locations after disabling product level shipping options 
* disable GetItFast if dispatch time does not allow it - fix possible issue revising imported items

= 1.3.6.1 =
* fixed soap error (invalid value for Item.AutoPay)
* updated translation 

= 1.3.6 =
* fixed disabling immediate payment option
* various improvements and fixes - read the full changelog below

= 1.3.5.8 =
* fixed issue with ShippingPackage not being set
* fixed rare issue selecting locations for new added shipping services
* added listing template shortcodes ebay_store_category_id, ebay_store_category_name, ebay_store_url, ebay_item_id

= 1.3.5.7 =
* allow relisting of sold items from product page
* fetch exclude shipping locations (database version 34)
* hide Selling Manager Pro profile options unless active
* added button to update all relisted items to warning message (if listing were manually relisted on ebay)
* fixed missing package type option for flat domestic and calculated international shipping mode
* fixed issue with ended items being marked as sold

= 1.3.5.6 =
* added new listing filter to show ended listings which can be relisted
* added support for all available exclude ship to locations - requires updating eBay details
* mark ended listing as sold if all units are sold when updating ended listings
* update transactions cache for past orders automatically (db upgrade)
* improved duplicate orders warning message with delete option
* improved check if WooCommerce is installed
* fixed issue with split variations creating duplicates
* only allow prepared items to be split

= 1.3.5.5 =
* show main image dimensions in listing preview
* added status filter and search box on transactions page
* added check for duplicate transactions and option to restore stock
* added transaction check on tools page to update transactions cache from orders
* prevent processing duplicate transactions in orders with different OrderIDs
* fix soap error when using CODCost with decimal comma
* improved display of warnings on edit product page
* clear EPS cache after upscaling images

= 1.3.5.4 =
* added images check and option to upscale images to 500px
* improved ebay log performance
* decode html entities in item specifics values
* fixed php 5.4 warning on edit product page
* define global constants WPL_EBAY_LISTING and WPL_EBAY_PREVIEW for 3rd party devs

= 1.3.5.3 =
* show warning when trying to prepare a single listing from a product draft
* fixed shipping date issue when completing orders automatically on eBay
* use BestEffort error handling option for CompleteSale

= 1.3.5.2 =
* added prepare listing action links on products page
* skip orders that are older than the oldest order in WP-Lister
* don't update order status if order is marked as completed, refunded, cancelled or failed
* compare rounded prices in inventory check

= 1.3.5.1 =
* added option to select order status for unpaid orders
* minor layout adjustments for WordPress 3.8 and WooCommerce 2.1
* show store category in listing preview
* fixed a rare XML decoding issue

= 1.3.5 =
* various improvements and fixes - read the full changelog below

= 1.3.4.11 =
* improved payment status on orders page
* fixed issue with IncludePrefilledItemInformation not being set
* compare variations price range (min / max) when running inventory check
* changed delete action parameters to avoid conflicts
* added wplister_relist_item action hook

= 1.3.4.10 =
* use site specific ShippingCostPaidBy options in profile
* strip invalid XML characters from listing description
* fixed variations cache for items with sales
* fixed empty IncludePrefilledItemInformation tag
* improved inventory check for variable products and custom quantities

= 1.3.4.9 =
* update ShippingPackageDetails with weight and dimensions of first variations
* fixed revising variable listings where both SKU and attributes were modified
* fixed issue with wpstagecoach
* WP 3.8.1 style adjustments and layout updates
* improved php error handling debug options
* show warning if WooCommerce is missing or outdated

= 1.3.4.8 =
* added inventory check on tools page - check price and stock for all published listings
* added option to mark listings as changed which were found by inventory check
* show warning on non-existing products on listings page and inventory check
* fixed calculating wrong VAT and store correct order total tax

= 1.3.4.7 =
* added option to mark locked listings as changed when updating a profile
* added message to deactivate the free version before installing WP-Lister Pro
* fixed order creation and VAT on WooCommerce 2.0 (was WC 2.1 only)
* fixed shipping cost in created WooCommerce orders

= 1.3.4.6 =
* check token expiration date and show warning if token expires in less than two weeka
* calculate VAT when creating WooCommerce orders for VAT enabled listings
* improved listings table - search by previous ebay id
* improved orders table - check if order has been deleted
* automatically switch old sites from transaction to order mode
* fixed possible issue of locked, reselected listings being stuck
* fixed incorrect cron job warning after fresh install

= 1.3.4.5 =
* added support for Woocommerce CSV importer
* added option to show link to ebay for all products - auctions and fixed price
* improved auto-complete order option - do not send seller feedback if default feedback text is empty
* mark ended listings as sold if no stock remains

= 1.3.4.4 =
* allow multiple shipping locations per international shipping service
* show warning if incompatible plugins detected (iThemes Slideshow)
* explain SOAP error 920002 caused by CDATA tags
* force UTF-8 for listing preview

= 1.3.4.3 =
* automatically reapply profile before relisting an ended item
* prevent running multiple cron jobs at the same time

= 1.3.4.2 =
* improved dynamic price parser to allow relative and absolute change at the same time (+10%+5)
* experimental support for WooCommerce Amazon Affiliates plugin (called via do-cron.php)
* log cURL errors messages like "Couldn't resolve host"

= 1.3.4.1 =
* added promotional shipping discount profile options
* added schedule minute profile option
* experimental support for WP All Import plugin
* don’t mark sold listings as ended when processing ended listings

= 1.3.4 =
* improved listing preview
* added ebay links for prepared, verified and ended items on edit product page
* added bulgarian translation
* tested with WooCommerce 2.1 beta

= 1.3.3.3 =
* improved updating locked variable items and messages on edit product page
* improved result of out of stock check on tools page
* added move to archive link for sold duplicates
* show product level start price in listings table
* fixed php warning on WooCommerce 2.1
* fixed issue when deleting wp content

= 1.3.3.2 =
* fixed issue when revising (ending) variation listings that are out of stock
* added option to skip orders from foreign ebay sites
* prevent editing of recommended item specifics names
* use total stock quantity for flattened variations

= 1.3.3.1 =
* added bold title profile option
* added gallery type profile option
* added profile option to disable including prefilled product information for catalog products listed by UPC
* added check for out of stock products in WooCommerce on tools page
* reschedule cron job if missing - and show warning once
* added tooltips on license page
* added log refresh button

= 1.3.3 =
* hide toolbar if user can’t manage listings
* added support for WooCommerce Sequential Order Numbers Pro 1.5.x
* fixed possible issue when upgrading from version 1.2.x with a huge number of imported products

= 1.3.2.16 =
* improved log record search feature
* added check for duplicate orders and warning message
* don't mark locked items as changed when listing template is updated
* don’t change listing status of archived items when updating product
* fixed ajax error when revising locked variations without changes
* fixed advanced setting update on multisite network

= 1.3.2.15 =
* fixed issue when relisting an ended auction as fixed price
* fixed shipping package option on edit product page
* fixed issue of split variations not being updated when the product is changed in WooCommerce
* prevent UUID issue when ending and relisting an item within a short time period

= 1.3.2.14 =
* fixed View on eBay button and error message for prepared auctions
* prevent deleting profiles which are still applied to listings
* improved error message if template file could not be found
* show optional ErrorParameters from ebay response
* added ajax shutdown handler to display fatal errors on shutdown

= 1.3.2.13 =
* implemented native auto relist feature (beta)

= 1.3.2.12 =
* set maxlength attribute for custom ebay title and subtitle input fields on edit product page
* fixed possible fatal error caused by weird UTF characters returns description
* fixed missing shipping weight for flattened variations
* regard default variation and remove variation attributes from item specifics for flattened variations

= 1.3.2.11 =
* fixed disabling best offer option on published listings
* added php error handling option to developers settings
* added update timespan option for manual order updates

= 1.3.2.10 =
* explain error 488 - Duplicate UUID used
* fixed product galleries for split variations
* fixed custom ebay titles for split variations
* added link to open variations table in thickbox

= 1.3.2.9 =
* improved listing eBay catalog items by UPC
* fixed listings not being marked as changed when products are update via bulk edit
* fixed issue when updating variable products through WooCommerce Product CSV Import Suite
* added test if max_execution_time is ignored by server on tools page
* measure task execution time and show message if a http error occurs after 30 seconds
* adjusted CSS for WP 3.8

= 1.3.2.8 =
* speed and stability improvements when updating locked variable products
* fixed missing css styles on edit profile page

= 1.3.2.7 =
* fixed javascript error on edit product page when no primary category was selected
* fixed thickbox window width on edit product page

= 1.3.2.6 =
* fixed javascript error on profile page

= 1.3.2.5 =
* fixed issue with item specifics for split variations
* improved splitting variations and indicate single variations in listings table
* improved item specifics on product level
* added wplister_custom_attributes filter to allow adding virtual attributes to pull item specifics values from custom meta fields

= 1.3.2.4 =
* added support for item specifics on product level
* update price as well as quantity when revising locked items

= 1.3.2.3 =
* added support for ShipToLocations and ExcludeShipToLocations
* fixed saving seller shipping profile on edit product page
* prevent user from deleting transactions / orders

= 1.3.2.2 =
* fixed issue with variations out of stock if "hide out of stock items" option was enabled in WooCommerce inventory settings
* added descrription to explain eBay error 21916543 (ExternalPictureURL server not available)

= 1.3.2.1 =
* added auto replenish profile option (beta)
* implemented max. quantity support for variations and locked items
* fixed issue when assign a different profile to published listings
* show warming and link to faq when no variations are found on a variable product
* show mysql errors during update process

= 1.3.2 =
* per product item condition
* support for CSV import plugins
* seller profiles (shipping, payment, return policy)
* various bug fixes and stability improvements - see details below

= 1.3.1.9 =
* fixed manual inventory status updates for locked variable products
* show messages and errors when revising a product from the edit product page
* try to find existing order by OrderID and TransactionID before creating new woo order
* added optional weight column to listings table

= 1.3.1.8 =
* added support for WooCommerce Product CSV Import Suite
* added support for Woo Product Importer plugin

= 1.3.1.7 =
* added support for seller profiles on product level
* fixed an issue with eBay Motors which was introduced in 1.3.1.4

= 1.3.1.6 =
* ignore fixed quantity profile option for locked items (to prevent accidentally disabling inventory sync for imported items)
* fixed currency display in transaction details
* hide custom quantity profile options by default to make it clear that these are not required and should be used with care
* support for WP-Lister for Amazon (sync inventory between eBay and Amazon)<br>

= 1.3.1.5 =
* fixed fatal php error when uploading images to EPS which was introduced in 1.3.1.4
* attempt to reconnect to database when mysql server has gone away

= 1.3.1.4 =
* changed default timespan for updating orders to one day
* fixed issue with product level options for split variations
* fixed possible issue with listing preview when external scripts were loaded
* increase mysql connection timeout to prevent "server has gone away" errors on some servers

= 1.3.1.3 =
* process bulk actions on selected listings using ajax to prevent timeout issues
* update user preferences when updating ebay data
* added option to update orders on tools page
* cleaned up advanced and developers settings

= 1.3.1.2 =
* added item condition option on edit product page
* implemented support for seller profiles (shipping, payment, return policy)

= 1.3.1.1 =
* fixed issue where locked items could get stuck when selecting a different profile
* minor bugfixes

= 1.3.1 =
* first stable release since 1.2.8

= 1.3.0.17 =
* fixed shipping fee in created orders
* only enable auto complete option if default order status is not completed
* include sales record number in order comments
* added instructions regarding shipping service priority error #21915307

= 1.3.0.16 =
* fixed issue of orders not being marked as shipped when no tracking details were provided
* added support for RefundOption
* added instructions regarding promotional sale / selling manager error 219422
* fixed issue when custom menu label contains spaces
* support for MP6

= 1.3.0.15 =
* added search box and status views on order page
* added support for shipping cost paid by option in profile (return policy)
* added instructions regarding item specifics on error #21916519
* fixed possible issue saving a profile when mysql is in strict mode

= 1.3.0.14 =
* added support for cash on delivery fee if available
* improved site changed message with button to update ebay data

= 1.3.0.13 =
* added update schedule status box on settings page - show warning if wp_cron has been disabled
* added action hooks for 3rd party developers: wplister_revise_item, wplister_revise_inventory_status and wplister_product_has_changed

= 1.3.0.12 =
* new feature: locked listings only have their inventory status synced while other changes are ignored
* fixed possible errors during transaction update
* fixed possible php warning when saving profile

= 1.3.0.11 =
* improved eBay inventory status update during checkout - requires variations to have a SKU
* added warnings if variations have no SKU
* added filter wplister_ebay_price to enable custom price rounding code
* prevent error if history data has been corrupted

= 1.3.0.10 =
* send package weight and dimensions when freight shipping is used
* fixed profile not being re-applied when revise listing on update option was checked
* added dedicated free shipping option to enable free shipping when using calculated shipping services
* added check license activation button
* added custom update notification and check

= 1.3.0.9 =
* added option to show item compatibility list as new tab on single product page
* fixed possible display of ebay error messages during checkout if ajax was disabled
* fixed issue when price is less than one currency unit
* improved license page and renamed to updates

= 1.3.0.8 =
* added errors section to log record view
* added option to auto complete ebay sales when order status is changed to completed
* added options to disable WooCommerce email notifications when new orders are created by WP-Lister

= 1.3.0.7 =
* new listing archive to clean out the listings view without having to delete historical data
* added metabox to WooCommerce orders created in the new "orders" mode
* fixed issue when previewing a listing template with embedded widgets
* fix inventory sync for variations in order update mode
* fixed email generation for ebay orders by implementing a custom WC_Product_Ebay class
* fixed order creation when cost-of-goods plugin is enabled and foreign listings imported in order update mode
* fixed "create orders when purchase has been completed" option
* fixed ebay column on products page when listing was deleted

= 1.3.0.6 =
* added maximum quantity profile option (Thanks Shawn!)
* fixed "Free shipping is only applicable to the first shipping service" warning
* send UUID to prevent duplicate AddItem or RelistItem calls

= 1.3.0.5 =
* added warning that update mode "Order" only works on WooCommerce 2.0
* use proper url for View on eBay link for imported products (edit product page)
* fixed possible recursion issue when products have no featured image assigned

= 1.3.0.4 =
* mark listing as ended if revise action fails with error #291 (Auction ended)
* fixed item status change from sold to changed when product was modified after being sold
* fixed tooltip on edit products page

= 1.3.0.3 =
* added option to enable inventory management on external products
* show imported item compatibility list on edit product page (no editing yet)
* cleaned up advanced settings page

= 1.3.0.2 =
* added option to create ebay customers as WordPress users when creating orders
* create orders by default only when ebay purchase has been completed
* added option to create order immediatly in advanced settings

= 1.3.0.1 =
* new permission management to control who has access to WP-Lister and is allowed to publish listings
* added option to customize WP-Lister main menu label
* create orders by default only when ebay purchase has been completed - can be changed in advanced settings

= 1.2.8.2 =
* fixed issue regarding variation images when upload to EPS is enabled
* added option to force using built-in XML formatter to display log records
* fixed php warning in XML_Beautifier

= 1.2.8.1 =
* fixed php warning on variations without proper attributes
* fixed php error on grouped products
* fixed order total in created WooCommerce orders in transaction mode
* fixed non-leaf category warning

= 1.2.8 =
* added clear log button and show current db log size
* updated german localization 

= 1.2.7.6 =
* fetch available returns within options from selected eBay site when updating eBay details
* show warning if local category is mapped to non-leaf ebay category on category mappings page 

= 1.2.7.5 =
* fixed wrong quantity issue when revising variable products that were imported from eBay 
* fixed check for changed shipping address on orders and transactions
* use wp_localize_script to allow translation of javascript code 

= 1.2.7.4 =
* update billing address when updating an order or transaction 
* show either transactions or orders page in menu and toolbar 
* fixed tooltip display issue on Firefox 

= 1.2.7.3 =
* added support for shipping discount profiles (beta) 
* improved link removal for multiple links per line
* fixed possible issue on processing GetItem results (on some MySQL servers)

= 1.2.7.2 =
* added option to set local timezone 
* convert order creation date from UTC to local time 
* update shipping address when updating an order or transaction 

= 1.2.7.1 =
* added option to disable the WP wysiwyg editor for editing listing templates 
* fixed issue when listing variations with individual variation images on the free version of WP-Lister 
* only show order history section in order details if an order in WooCommerce has been created 
* copy template config when duplicating listing templates 

= 1.2.7 =
* added template preview feature 
* added option to open listing preview in new tab 
* automatically convert iframe tags to allow embedding YouTube videos 

= 1.2.6.2 =
* show warning if item details are missing and display "Update from eBay" link 
* added note regarding ebay sites where STP / MAP are available 
* fixed issue saving profile condition when no category was selected 
* fixed possible issue when saving settings 
* removed line break from thumbnails.php 

= 1.2.6.1 =
* added tooltips to shipping options table headers 
* added warning symbols for fixed quantity and backorders if inventory sync is enabled 
* fixed issue when category mapping lead to the primary and secondary category being the same 
* reset invalid token status when check for token expiry time succeeds 
* minor visual alignment improvements 

= 1.2.6 =
* added tooltips to all settings and profile options 
* dynamically hide some profile options when not applicable
* increased range for [add_img_1] short code to 1-12 
* hide empty log records by default 

= 1.2.5.2 =
* fixed issue when uploading variation images to EPS 
* fixed default sort order on transactions and orders page 
* added warning if more than 5 shipping services are selected when editing a profile 
* added WC order notes to order details view 

= 1.2.5.1 =
* omit title and subtitle when revising an item that ends within 12 hours or has sales 
* new connection check tool to test outgoing connections 
* added check if PHP safe_mode is enabled 
* fixed shipping options on product level 
* fixed issue with variations for themes like Frenzy which hook into WC without proper data validation 

= 1.2.5 =
* minor cosmetic improvements

= 1.2.4.7 =
* added custom sort order for profiles
* display profiles ordered by profile name by default
* changed "link to ebay" behavior to "only if there are bids on eBay or the auction ends within 12 hours"
* show relist link on sold items
* show image URL when upload to EPS failed
* changed menu position to decimal to prevent colliding position ids

= 1.2.4.6 =
* compatibility update for Import from eBay 1.3.16
* added note and link to ebay for products which were imported from ebay

= 1.2.4.5 =
* added validation for shipping services on product page
* fixed missing validation for shipping services on profile page
* fixed issue when disabling shipping service type on product level

= 1.2.4.4 =
* added global option to allow backorders (off by default)
* show warning if backorders are enabled on a product
* fixed issue with attribute names containing an apostrophe

= 1.2.4.3 =
* fixed paypal addess not being stored when updating settings
* fixed order complete status (if new update was was enabled)
* added description on categories settings page

= 1.2.4.2 =
* fixed issue when preparing new listings
* prevent importing an order for an already imported transaction

= 1.2.4.1 =
* new update mode to process multiple line item orders instead of single transactions
* added advanced settings tab and cleaned out general settings
* skip pending products and drafts when preparing listings

= 1.2.4 =
* calculated shipping is no longer limited to WP-Lister Pro 

= 1.2.3.3 =
* added custom ebay gallery image to product level options 
* added freight shipping option 
* re-added warning if run on windows server
* fixed local "view on ebay" button for imported auction type listings without end date 
* fixed whitespace pasted from ms word and added wpautop filter to additional content 
* fixed undefined function is_ajax error on WPeC 

= 1.2.3.2 =
* added filter wplister_get_product_main_image 
* added beta support for qTranslate when preparing listings 
* show proper message when trying to use bulk actions without a transaction selected
* fixed possible mysql issue during categories update 

= 1.2.3.1 =
* added primary and secondary ebay category to product level options
* added ebay shipping services to product level options
* added listing duration to product level options
* strip slashes from custom item specifics values in profile
* improved handling of available item conditions
* set listing status to changed when product is updated on WP e-Commerce
* fallback for mb_strlen and mb_substr if PHP was compiled without multibyte support

= 1.2.3 =
* added transaction history 
* improved error handling when creating templates 
* improved handling of available item conditions 

= 1.2.2.16 =
* improved listing filter - added search by SKU and product ID 
* prevent listing title from growing beyond 80 chars by embedded attribute shortcodes 
* skip item specifics values with more than 50 characters 
* fixed custom product attributes 

= 1.2.2.15 =
* improved listing filter - search by profile, template, status, duration and more... 
* fixed order creation for variable products 
* fixed possible issue with multibyte values in details objects 

= 1.2.2.14 =
* create additional images thumbnails html from exchangeable thumbnails.php 
* fixed possible php warning 

= 1.2.2.13 =
* improved products filter - on ebay / not on ebay views 
* added option to hide products from "no on ebay" list 
* cleaned up ebay options on edit product screen 

= 1.2.2.12 =
* fixed wrong order total on multiple item purchases 
* added option to disable variations 
* improved database log view 

= 1.2.2.11 =
* fixed listing title of split variations when when profile is applied 
* add main image to list of additional images when WC2 Product Gallery is used 
* added some hooks and filters for virtual categories 

= 1.2.2.10 =
* replace add to cart button in products archive if product is on auction 
* process template shortcodes in condition description 
* make profile and template titles clickable 

= 1.2.2.9 =
* fixed issue regarding inventory sync for variations (wp to ebay) 
* fixed issue truncating listing titles on multibyte characters 
* support for windows servers (beta) 

= 1.2.2.8 =
* new option to customize product details page if an item is currently on auction 
* added import and export options for categories settings 

= 1.2.2.7 =
* fixed saving UPC field on edit products page 
* check if item specifics values are longer than 50 characters 
* added sold items filter on listings page 

= 1.2.2.6 =
* improved updating ended listings in order to relist 
* improved revise on local sale processing 

= 1.2.2.5 =
* added UPC field on edit product page 
* store previous item id when relisting an item 
* fixed a possible blank screen when connecting to ebay 

= 1.2.2.4 =
* added gallery image size and fallback options 
* fixed order total for multiple line item orders 

= 1.2.2.3 =
* fetch start price details from eBay and show warning if minimum price is not met 
* use full size featured image by default 

= 1.2.2.2 =
* improved listing status filter 
* added javascript click event for image thumbnails 
* url encode image filename for EPS upload 
* check if attribute values are longer than 50 characters 
* fix profile prices - remove $ sign and convert decimal point 
* fixed license deactivation after site migration 

= 1.2.2.1 =
* added new revise on update option on edit product screen 
* added filter wplister_product_images 
* fixed compatibility with Import from eBay plugin 1.3.8 and before 
* fix stock status update for products with stock level management disabled 
* force SQL to use GMT timezone when using NOW() 


= 1.2.1.6 = 
* fix for order total missing shipping fee 
* show warning if template contains CDATA tags 
* added hook wplister_after_create_order 
* updated localization 
* added pot file

= 1.2.1.5 = 
* remember and use images already uploaded to EPS 
* upload images to EPS one by one via ajax 
* fixed possible php warning 

= 1.2.1.4 = 
* added prepare listing action to toolbar 
* show warning if listing is missing a profile or template 
* add ebay transaction id to order notes 
* code cleanup 

= 1.2.1.3 = 
* added buy now price field on edit product page 

= 1.2.1.2 = 
* added restocking fee value option 
* fix for relative image urls
* improved account handling
* updated inline documentation

= 1.2.1.1 = 
* fixed some php warnings


= 1.2.0.20 =
* added WP-Lister toolbar links - along with a link to open a product on eBay in a new tab

= 1.2.0.19 =
* added option to show only product which have not been listed on eBay yet

= 1.2.0.18 =
* added options for listing type, start price and reserved price on product level

= 1.2.0.17 =
* fix to allow percent and plus sign in profile prices again
* changed column type for references to post_id to bigint(20)

= 1.2.0.16 =
* added Global Shipping option on product level (Pro only)
* added Payment Instructions on profile and product level

= 1.2.0.15 =
* added SKU column to listings page
* added verify and publish buttons on top of listings page
* remove currency symbols from profile prices automatically

= 1.2.0.14 =
* fixed possible "Invalid ShippingPackage" issue

= 1.2.0.12 =
* fixed possible issue where item conditions and item specifics were empty
* fixed weight conversion issue on WP e-Commerce

= 1.2.0.11 =
* fixed "You need to add at least one image to your product" if upload to EPS is disabled (Pro only)
* improved removing links from description
* filter options for log page

= 1.2.0.10 =
* fixed "Too Many Pictures" error

= 1.2.0.9 =
* improved sanity checks before listing and verifying
* fixed error message when no featured image is found

= 1.2.0.8 =
* new option to import transaction for items which were not listed by WP-Lister
* search listings by item id

= 1.2.0.7 =
* fetch item conditions via ajax when primary category is selected

= 1.2.0.6 =
* added support for item condition description

= 1.2.0.5 =
* transaction update reports shows listing titles again
* fixed cross selling widgets for servers which send the X-Frame-Options HTTP header

= 1.2.0.4 =
* beta support for variations with attributes without values (like "all Sizes" instead of a value)

= 1.2.0.3 =
* fix for new cross-selling widgets

= 1.2.0.2 =
* enabled listing product attributes as item specifics again

= 1.2.0.1 =
* new eBay metabox on edit product page to set listing title and subtitle on product level

= 1.2.0 =
* new default template with options and color pickers
* new cross-selling widgets to display your other active listings

= 1.1.7.5 =
* fixed missing package weight for split variations issue (Pro only)

= 1.1.7.4 =
* fixed item specifics and conditions for eBay Motors categories when using eBay US as main site

= 1.1.7.3 =
* fixed issue with empty titles when splitting variations (Pro only)

= 1.1.7.2 =
* support for WooCommerce 2.0 Product Galleries

= 1.1.7 =
* new option to schedule listings
* new template engine with hooks and custom template options (beta)

= 1.1.6.11 =
* load admin scripts and stylesheets using SSL if enabled
* WP-Lister won't update the order status if already completed orders anymore (Pro only)

= 1.1.6.9 =
* fixed an issue regarding inventory sync on WooCommerce 2.0 (Pro only)
* added order note when revising an item during checkout (Pro only)
* added one day listing duration

= 1.1.6.8 =
* fixed error regarding shipping service for flat shipping
* improved debug log submission

= 1.1.6.7 =
* fixed paging issue on transaction update
* more options for uploading images to EPS (Pro only)

= 1.1.6.5 =
* new option to duplicate listing templates
* catch invalid token error (931) and prompt user to re-authenticate
* added support for the WooCommerce Product Addons extension

= 1.1.6.3 =
* improvements on handling items manually relisted on ebay website

= 1.1.6 =
* updated eBay API to version 789
* fixed global shipping option
* minor improvements

= 1.1.5.6 =
* improvements for creating and updating orders in WooCommerce (Pro only)
* beta support for listing to eBay US and eBay Motors without switching sites
* set PicturePack to Supersize when uploading images to EPS (Pro only)
* added Global Shipping option
* fixed an issue with item specifics when no default category was selected

= 1.1.5 =
* beta support for Automated Relisting Rules (Seller Manager Pro account required) (Pro only)
* support for pulling best offer options from WooCommerce Name Your Price plugin
* fixed an issue with PackagingHandlingCosts when calculated shipping is used (Pro only)

= 1.1.4.1 =
* various improvements on calculated shipping services (Pro only)
* use SKU for item specifics
* new shortcodes for passing product excerpt through nl2br()
* faster inventory sync (Pro only)

= 1.1.3.4 =
* added italian localization (thanks Giuseppe)
* updated german localization
* various minor fixes and improvements

= 1.1.3 =
* compatible with WooCommerce 2.0 RC1
* fixed error when shipping options were not properly set up
* new default category for item conditions (Pro)

= 1.1.2 =
* new option to switch ebay accounts
* new network admin page to manage multisite networks
* improved multisite installation
* fixed issues creating orders on WooCommerce
* truncate listing title after 80 characters automatically

= 1.1.1 =
* support for JigoShop (beta) 
* support for custom post meta overrides
  (ebay_title, ebay_title_prefix, ebay_title_suffix, ebay_subtitle, ebay_image_url)
* more listing shortcodes for product category and custom product meta
* most listing shortcodes work in title prefix and suffix as well
* option to remove links from product description
* option to hide warning about duplicate listings
* fixed issues revising items
* fixed issue with PHP 5.4

= 1.1.0 =
* tested with WordPress 3.5
* various UI improvements
* new option for private listings
* code cleanup
* bug fixes

= 1.0.9.2 =
* support for multisite network activation (beta)
* support for product images from NextGen gallery
* improved support for Shopp
* new option to flatten variations
* other improvements and fixes

= 1.0.8.5 =
* new options to process variations
* update all prepared, verified or published items when saving a profile
* improved attribute handling in Shopp
* the usual bug fixes

= 1.0.7.4 =
* updated german localization
* support for variations using custom product attributes in WooCommerce
* proper error handling if uploads folder is not writeable

= 1.0.7 =
* various bug fixes
* support for new eBay to WooCommerce product importer
* developer options were not saved (free version only)
* support for tracking numbers, feedback and best offer added (Pro only)

= 1.0.6 =
* german localization
* improved inventory sync for WooCommerce (Pro only)

= 1.0.5 =
* various bug fixes

= 1.0.2 =
* improved inventory sync for variations
* added advanced options to listing edit page
* MarketPress: added support for calculated shipping services

= 1.0.1 =
* support for MarketPress

= 1.0 =
* Initial release

