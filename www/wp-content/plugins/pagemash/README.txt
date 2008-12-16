=== pageMash > pageManagement ===
Contributors: JoelStarnes
Tags: order pages, ajax, re-order, drag-and-drop, admin, manage, page, pages, sidebar, header, hide,
Requires at least: 2.1
Tested up to: 2.6
Stable tag: 1.1.6

Organise page order and manage page structure with this simple drag-and-drop Ajax interface.

== Description ==

Customise the order your pages are listed in and manage the parent structure with this simple Ajax drag-and-drop administrative interface with an option to toggle the page to be hidden from output. Great tool to quickly re-arrange your page menus.

Checkout the example page: http://joelstarnes.co.uk/pagemash/example
Feedback is greatly appreciated: http://joelstarnes.co.uk/contact

== Installation ==

1. Download Plugin
1. Unzip & Upload to `/wp-content/plugins/`
1. Activate in 'Plugins' admin menu
1. Then have fun..

pageMash works with the `wp_list_pages` function. The easiest way to use it is to put the pages widget in your sidebar [WP admin page > Presentation > Widgets]. Click the configure button on the widget and ensure that 'sort by' is set to 'page order'. Hey presto, you're done.

You can also use the function anywhere in your theme code. e.g. in your sidebar.php file (but the code in here will not run if you're using any widgets) or your header.php file (somewhere under the body tag, you may want to use the depth=1 parameter to only show top levle pages). The code should look something like the following:

`<?php wp_list_pages('title_li=<h2>Pages</h2>&depth=0'); ?>`

You can also hard-code pages to exclude and these will be merged with the pages you set to exclude in your pageMash admin.

The code here is very simple and flexible, for more information look up `wp_list_pages()` in the Wordpress Codex: http://codex.wordpress.org/Template_Tags/wp_list_pages


== Frequently Asked Questions ==

If you have any questions or comments, please drop me an email: http://joelstarnes.co.uk/contact

= None of it's working =
The most likely cause is that you have another plugin which has included an incompatible javascript library onto the pageMash admin page.

Try opening up your WP admin and browse to your pageMash page, then take a look at the page source. Check if the prototype or scriptaculous scripts are included in the header. If so then the next step is to track down the offending plugin, which you can do by disabling each of your plugins in turn and checking when the scripts are no longer included.

= Do I need any special code in my template =
No. As of v1.0.2 you no longer need to add any code to your template. PageMash adds a filter to the wp_list_pages() function and will also work just fine with the pages widget.

= Which browsers are supported =
Any good up-to-date browser should work fine. I test in Firefox, IE7, Safari and Opera. (NB in IE7 you need to use the page name as a drag handle.)

== Screenshots ==

1. Admin Interface

2. Setting up the page widget. [WP-Admin > Presentation > Widgets]


==Change Log==
= 1.1.6 =
 - Corrected filename case.
 
= 1.1.5 =
 - Updated for WP 2.6

= 1.1.4 =
 - Add config option to show debug info.

= 1.1.3 =
 - Fixed hide bug that appeared on some systems

= 1.1.2 =
 - Added Expand all | Collapse all buttons

= 1.1.1 =
 - Fix a bug with console.log for safari
 - Removed php code from js&css scripts to fix error

= 1.1.0 =
 - Added quick rename
 - Externalised scripts
 - Changed display of edit|hide|rename links
 - Deregisters prototypes
 
= 1.0.4 =
 - Removed shorthand PHP
 - Updated CSS and JS headers to admin_print_scripts hook.
 
= 1.0.3 =
 - Fixed datatype bug causing array problems
 
= 1.0.3 =
 - Fixed datatype bug causing array problems
 
= 1.0.2 =
 - Major code rewrite for exclude pages
 
= 1.0.1 beta =
 - fixed IE drag selects text =
 
= 1.0.0 beta =
 - Major rebuild to use vladimir's sortables class extension
 - Recusive page handles unlimited nested children
 - Collapsable list items
 - Interface makeover...
 
= 0.1.3 =
 - Fixed exclude pages feature
 
= 0.1.2 =
 - Fixed CSS&JS headers to only display on pageMash admin
 
= 0.1.1 =
 - Removed version check since some hosts will not allow external includes.
 
= 0.1.0 =
 - Initial Release

== Localization ==

Currently only available in english.