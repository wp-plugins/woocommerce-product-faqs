=== Plugin Name ===
Contributors: joshlevinson
Donate link: http://redactweb.com
Tags: woocommerce, faq, frequently asked questions, faqs, woocommerce faqs, woocommerce frequently asked questions
Requires at least: 3.5.1
Tested up to: 3.6
Stable tag: 1.0.7
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Extends WooCommerce to allow for the asking, answering, and viewing of FAQs in a similar experience as on eBay.

== Description ==

If you have ever used eBay's 'Ask Buyer' system before, you will know how this plugin behaves.

It extends WooCommerce to allow visitors to ask questions about specific products, get answers, and view other threads.

It also allows you, the store owner, to manually add questions to specific products.

This plugin requires WooCommerce 1.6.6 or higher, though 2.x is preferred.

[Click here](http://redactweb.com/shop/flying-ninja/#tab-faqs) for a sample WooCommerce product page with some FAQs.

= FAQ Spam =
The submission of FAQs fights spam in one of two ways, with the admin being able to pick between:

1. AYAH (Are you a human?) anti-spam. Google it.

2. "Honeypot" input field. This adds an extra field to the form, hidden with css, that will cause submissions to fail if it is filled out.

= FAQ Notifications =
Notifications are in place that will notify the admin of new questions, with links to administer each question.  

Notifications are in place that will notify the asker when their question has been answered, with links to view the product.

Each notification email has a system to highlight the question specified in the email,
so in the case of numerous questions, the user does not have to hunt for his question.

== Other Notes ==
= To Do: =
* Add ajax loaders where ajax is used so the user knows that their request is being processed DONE *

* Consider adding inline registration option to FAQ form

* Transition answerer capability to product author

* Localize everything DONE

* Add more filters

* Complete documentation

* Consider what (if any) actions need to be added

* Make the 'pending approval' front-end status only show if a faq is indeed pending approval - DONE @ 1.0.0

* Allow approval of FAQ from front-end - DONE @ 1.0.0

* Consider a settings field for admin notification email

* Allow for creating FAQs from/by admin - DONE @ 1.0.6

= Incompatibilities =

* Disqus - interferes with the `comment_form` function. Will hopefully rectify this soon.

* 404 Redirected - strips $_GET parameters from URL, removing 'View' and 'Preview' functionality

== Installation ==

1. Upload this plugin to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Visit WooCommerce -> Settings -> FAQs to manage the settings of the plugin
4. Upon *uninstallation (deletion)* of this plugin, this plugin will delete its settings, but not the questions in the database

= Usage =
User Submitted FAQs
1. A user will visit the single product page.
2. They can click the "FAQs" tab, and view other questions, along with a question form.
3. They may submit the form, pending all required information is submitted.
4. You will receive an email with the product, question, and a link to administer the question.
5. If you desire, you can click the 'Approve' link for that question (which just publishes it).
6. After approving it, you can click 'View', to view it on the front-end.
7. From the front-end, you may click that question to expand it and reply to it.
8. Once you have replied to a question, the asker will receive an email with a link to that product's page/faq tab.

= Manual FAQ Entry =
1. Go to WooFAQs -> Add New
2. Type a title (for reference, not display)
3. Enter the question in the content area
4. Choose the product to add the question to.
5. Click Publish
6. Immediately after publishing, you may click "Add Answer" in the "Answers" metabox to answer the question.

== Frequently Asked Questions ==

= Usage =
User Submitted FAQs
1. A user will visit the single product page.
2. They can click the "FAQs" tab, and view other questions, along with a question form.
3. They may submit the form, pending all required information is submitted.
4. You will receive an email with the product, question, and a link to administer the question.
5. If you desire, you can click the 'Approve' link for that question (which just publishes it).
6. After approving it, you can click 'View', to view it on the front-end.
7. From the front-end, you may click that question to expand it and reply to it.
8. Once you have replied to a question, the asker will receive an email with a link to that product's page/faq tab.

= Manual FAQ Entry =
1. Go to WooFAQs -> Add New
2. Type a title (for reference, not display)
3. Enter the question in the content area
4. Choose the product to add the question to.
5. Click Publish
6. Immediately after publishing, you may click "Add Answer" in the "Answers" metabox to answer the question.

= The answer/reply form never shows up for me! =

This plugin is currently incompatible with Disqus. For now, you will have to pick between these two plugins.

= When I click "View" or "Preview", the FAQ never shows up/is highlighted! =

This plugin is currently incompatible with the 404 Redirected plugin. This plugin causes $_GET parameters to be stripped from the url.

== Screenshots ==

1. Upon submission of a question
2. An expanded faq
3. Plugin settings
4. Approve/preview/edit screen
5. Administrator previewing question on front-end (with quick approve)
6. Adding a FAQ Manually

== Changelog ==
= 1.0.8 =
* Full localization
* Thanks to @OniX777 for partial localization and for Russian translation!

= 1.0.7 =
* Hotfix to increase number of available products in FAQ editor to unlimited.

= 1.0.6 =
* Filtered the post type columns to only include relevant information
* Added ability to create FAQs from the admin
* FAQs are now ordered by menu_order

= 1.0.5 =
* Compatibility with WooCommerce 1.6.6

= 1.0.4 =
* Fixed fatal error with theme_locals
* Removed unnecessary comment filter
* Reverted to $_GET paramaters and discovered incompatibility with 404 Redirected plugin

= 1.0.2 & 1.0.3 =
* Better email support

= 1.0.1 =
* Added `$args` to the `comment_form` function to disclude everything but the textarea.
* Moved $_GET style requests to query_vars and parse_request

= 1.0.0 =
* Initial release

== Upgrade Notice ==
Coming from 1.0.0 to 1.0.2, any FAQs that were posted will not support notifications to the question author.
All releases 1.0.1 and up include this feature.

== Developers ==
= Available filters: =

Format: (filter ; filtered variable ; available variables: var1 (explanation [var type]), var2, var3, ...)

*All filters are prefixed/begin with `woocommerce_faqs_`*

* admin_faq_highlight_color ; Highlight color in the Dashboard when administering a question from the email link

* front_faq_highlight_color ; Highlight color on the front-end when viewing a question from the email link

* antispam_error_message ; Error message when the anti-spam check fails ; $result['message'] (error message [string]), posted form (full $_POST[array])

* answerer_email ; Email for administering FAQs; $post_data ('question_title', 'faq_author_name', 'product_title', 'question_content' [array])

* asker_email ; Email for the asker; $post_data ('question_title', 'product_title', 'post_id' [array])

* answerer_email_subject ; Subject of administer email; same as answerer_email vars

* answerer_email_message ; Final adminster message; same as answerer email vars

* asker_email_subject; Subject of asker email; same as asker_email vars

* asker_email_message; Final asker message; same as asker_email vars

* answer_caps; Capability of answering questions