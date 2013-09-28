=== WP-Lister for eBay ===
Contributors: wp-lab
Tags: ebay, products, export
Requires at least: 3.3
Tested up to: 3.6
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

List products from WordPress on eBay. The easy way.

== Description ==

WP-Lister connects your WooCommerce shop with your eBay Store. You can select multiple products right from your products page, select a profile to apply a set of predefined options and list them all on eBay with just a few clicks.

We worked hard to make WP-Lister easy to use but flexible. The workflow of listing items requires not a single click more than neccessary. Due to its tight integration in WordPress, your client will not have to learn anything new as he will feel right at home.

= Features =

* list any number of items at once
* set up a profile once and apply to any number of products
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
* french
* korean

To make WP-Lister available in more languages, we will provide a free license for WP-Lister Pro for everyone who will provide and maintain a new localization.

= Screencast =

http://www.youtube.com/watch?feature=player_embedded&v=zBQilzwr9UI

= More information and Pro version =

Visit http://www.wplab.com/plugins/wp-lister/ to read more about WP-Lister including documentation, installation instructions and user reviews.

To find out more about the different versions have a look on this feature comparison: http://www.wplab.com/plugins/wp-lister/feature-comparison/

== Installation ==

1. Install WP-Lister for eBay either via the WordPress.org plugin directory, or by uploading the files to your server
2. After activating the plugin, visit the new page WP-Lister and follow the setup instructions. 

== Frequently Asked Questions ==

= Does WP-Lister work with all eBay sites? =

Yes, it does.

= What are the requirements to run WP-Lister? =

WP-Lister requires a recent version of WordPress (3.3+) with WooCommerce 1.6.6 or 2.x installed. Your server should run on Linux and have PHP 5.2 or better with cURL support.

= I use products variations on my site but eBay doesn’t allow variations in the selected category. How can I find out in which categories variations are allowed? =

To learn more about variations and allowed categories you should visit this page: http://pages.ebay.com/help/sell/listing-variations.html. There you will find a link to eBay’s look up table for categories allowing variations. If you can only list to categories where no variations are allowed, consider purchasing WP-Lister Pro which can split variations into single listings.

= I already have listed my products on eBay. Can WP-Lister import them to WordPress? =

WP-Lister is specifically designed to let you manage your products in WordPress - and list them from there on eBay. If you would like to use WP-Lister but you need to import all your items from eBay to WooCommerce first, you might want to use this plugin which we developed to get you started: http://www.wplab.com/plugins/import-from-ebay-to-woocommerce/

= Does WP-Lister support windows servers? =

The current version does run on windows servers, but this feature is still experimental.

= Are there any more FAQ? =

Yes, there are more questions and answers on http://www.wplab.com/plugins/wp-lister/faq/

= Any plans for WP-Lister for Amazon? =

Already working on it. Expect it this summer.

== Screenshots ==

1. Listings Page
2. Profile Editor

== Changelog ==
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

