=== Plugin Name ===
Contributors: joshlevinson
Donate link: http://redactweb.com
Tags: woocommerce, faq, frequently asked questions, faqs, woocommerce faqs, woocommerce frequently asked questions
Requires at least: 3.5.1
Tested up to: 3.6
Stable tag: 1.0.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Extends WooCommerce to allow for the asking, answering, and viewing of FAQs in a similar experience as on eBay.

== Description ==

If you have ever used eBay's 'Ask Buyer' system before, you will know how this plugin behaves.

This plugin requires WooCommerce (and will not activate until WooCommerce is active).

It extends WooCommerce to allow visitors to ask questions about specific products, get answers, and view other threads.

The submission of FAQs fights spam in one of two ways, with the admin being able to pick between:

1. AYAH (Are you a human?) anti-spam. Google it.

2. "Honeypot" input field. This adds an extra field to the form, hidden with css, that will cause submissions to fail if it is filled out.

Notifications are in place that will notify the admin of new questions, with links to administer each question
Notifications are in place that will notify the asker when their question has been answered, with links to view the product

Each notification email has a system to highlight the question specified in the email,
so in the case of numerous questions, the user does not have to hunt for his question.

As of 1.0.0, admins can 'quick approve' questions from the front end after clicking the 'Preview' link from the Dashboard (see screenshots).

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


= To Do: =
* Consider adding inline registration option to FAQ form

* Transition answerer capability to product author

* Localize everything

* Add more filters

* Complete documentation

* Consider what (if any) actions need to be added

* Make the 'pending approval' front-end status only show if a faq is indeed pending approval - DONE @ 1.0.0

* Allow approval of FAQ from front-end - DONE @ 1.0.0

* Consider a settings field for admin notification email

= Incompatibilities =
*Disqus - interferes with the `comment_form` function. Will hopefully rectify this soon.

== Installation ==

1. Upload this plugin to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Visit WooCommerce -> Settings -> FAQs to manage the settings of the plugin
4. Upon *uninstallation (deletion)* of this plugin, this plugin will delete its settings, but not the questions in the database

== Frequently Asked Questions ==

= The answer/reply form never shows up for me! =

This plugin is currently incompatible with Disqus. For now, you will have to pick between these two plugins.

== Screenshots ==

1. Upon submission of a question
2. An expanded faq
3. Plugin settings
4. Approve/preview/edit screen
5. Administrator previewing question on front-end (with quick approve)

== Changelog ==
= 1.0.2 =
* Better email support *

= 1.0.1 =
* Added `$args` to the `comment_form` function to disclude everything but the textarea.
* Moved $_GET style requests to query_vars and parse_request *

= 1.0.0 =
* Initial release *