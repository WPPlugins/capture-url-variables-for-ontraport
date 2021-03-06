﻿=== Capture URL Variables for Ontraport ===
Contributors: itmooti
Donate link: https://www.itmooti.com/ontraport-tracking-made-easy-order/
Tags: comments, spam
Requires at least: 3.0.1
Tested up to: 4.7.4
Stable tag: 1.3.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A plugin that makes it super easy to get URL variables submitted into hidden fields on an Ontraport/Office Autopilot Smart Form.

== Description ==

When somebody opts in to your Ontraport form you made want to collect other data that is being tracked. UTM variables for Google Analytics can be added to a link like this utm_source=google&utm_campaign=xmas these variables can then be added to a hidden field in the Ontraport form.

This can also be used for other hidden fields or even to pre-fill the first name and email for a particular form.

The plugin also works great for creating 2-step order form processes where you want to pass the name and email to the second form. Variables that have been collected in the cookie can additionally be added to any page on your site using short code [cuv field="fieldname"] where the fieldname is the name in the cookie of the field.

This works for both Office Autopilot 2.4 and Ontraport 3.0

== Installation ==

1. Upload `cature-url-variables-for-ontraport` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==

= Learn More In Our Knowledge Base =

<a href="https://itmooti.zendesk.com/hc/en-us/articles/202715690-How-to-use-the-OAP-UTM-Plugin">ITMOOTI Knowledge Base for Ontraport Apps/Integrations and Plugins</a>

= How do I get a License Key? =

Please visit this URL <a href="http://app.itmooti.com/wp-plugins/oap-utm/license/">http://app.itmooti.com/wp-plugins/oap-utm/license/</a> to get a License Key .

= Does it work for Office Autopilot and Ontraport? =

Yes this plugin is made to work for both versions of the application. You choose your version when adding in your API info

= How long is the Free Trial? =

The free trial is for 7 days. After that you can buy a <a href="http://app.itmooti.com/wp-plugins/oap-utm/license/">HERE</a> to get a License Key .

= Where can I get support? =

Send us an email to support@itmooti.com or visit our <a href="https://itmooti.zendesk.com/hc/en-us/">Support Page HERE</a>

== Screenshots ==

1. Example of how the plugin looks

Learn More Here

https://itmooti.zendesk.com/hc/en-us/articles/202715690-How-to-use-the-OAP-UTM-Plugin

== Upgrade Notice ==

Nothing yet.

== Changelog ==

= 1.3.4 =

* Removed license check on page load. License now checked twice daily.

= 1.3.3 =

* Fixed Connection Timeout Issue

= 1.3.2 =

* Fixed SSL Connection Issue

= 1.3.0 =

* Fixed Warning Display

= 1.3.0 =

* Fixed Form Cache Issue

= 1.2.9 =

* Fixed License Verification. 

= 1.2.7 =

* Fixed Custom Fields issue. 

= 1.2.7 =

* Added Custom Fields addition and other form fields parsing. 

= 1.2.6 =

* Fixed Clear Form Cache Function's query error

<<<<<<< .mine
= 1.2.5 =
=======
= 1.2.4 =
>>>>>>> .r1024391

<<<<<<< .mine
* Fixed Session Error
=======
* Fixed Timeout Issue by decreasing the number of form load
>>>>>>> .r1024391

<<<<<<< .mine
= 1.2.4 =
=======
= 1.2.3 =
>>>>>>> .r1024391

<<<<<<< .mine
* Fixed Timeout Issue by decreasing the number of form load
=======
* Added the ability to collect the landing page from where the form is submitted
* Added the list of shortcodes in the settings area ‘CUV Shortcodes’
>>>>>>> .r1024391

<<<<<<< .mine
= 1.2.3 =
=======
= 1.2.2 =
>>>>>>> .r1024391

<<<<<<< .mine
* Added the ability to collect the landing page from where the form is submitted
* Added the list of shortcodes in the settings area ‘CUV Shortcodes’

= 1.2.2 =

* Fixed ability to pass name fields to any page with short codes via cookie

= 1.2.1 =

* Fixed short code issue with variables not showing up on form thank you page based on var lookup

= 1.2.0 =

* Fixed issue referral_page not being collected
* Added the ability to pass variables to any page on the domain via a short code 

= 1.1.5 =

* Fixed another issue with OP2 not loading

=======
* Fixed ability to pass name fields to any page with short codes via cookie

= 1.2.1 =

* Fixed short code issue with variables not showing up on form thank you page based on var lookup

= 1.2.0 =

* Fixed issue referral_page not being collected
* Added the ability to pass variables to any page on the domain via a short code 

= 1.1.5 =

* Fixed another issue with OP2 not loading

>>>>>>> .r1024391
= 1.1.3 =

* Fixed issue with OP2 not loading

= 1.1.2 =

* Fixed form limitor

= 1.1.1 =

* Fixed issue with Form selector

= 1.1.0 =

* Fixed issue with OP2 not loading

= 1.0.9 =

* Fixed issues with UTMs in Ontraport 2.4 version

= 1.0.8 =

* Updating the IP JS

= 1.0.7 =

* Able to capture IP Address in to a smart forms IP field

= 1.0.6 =

* Removed the ability to set the ‘uid’ field as it was preventing forms to be submitted to Ontraport when selected as a variable.

= 1.0.5 =

* Fix issue with OP2 Live Editor and added ‘None’ selection to the drop down for custom field selector

= 1.0.4 =

* Fix admin message from remaining after license added

= 1.0.3 =

* ability to change license code and keep settings already saved
* dynamically checks license code to account holder

= 1.0.2 =

* fixed ‘undefined’ showing up in matched fields where there is no variable in the URL
* added license link to admin area

= 1.0.1 =

Update for the %40 @ symbol issue

= 1.0 =

First Release










