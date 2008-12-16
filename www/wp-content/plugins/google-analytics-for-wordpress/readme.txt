=== Google Analytics for WordPress ===
Contributors: joostdevalk
Donate link: http://yoast.com/donate/
Tags: analytics, google analytics, statistics
Requires at least: 2.2
Tested up to: 2.7
Stable tag: 2.6.7

The Google Analytics for WordPress plugin automatically tracks and segments all outbound links from within posts, comment author links, links within comments, blogroll links and downloads. It also allows you to track AdSense clicks, add extra search engines, track image search queries and it will even work together with Urchin.

== Description ==

The Google Analytics for WordPress plugin automatically tracks and segments all outbound links from within posts, comment author links, links within comments, blogroll links and downloads. It also allows you to track AdSense clicks, add extra search engines, track image search queries and it will even work together with Urchin.

In the options panel for the plugin, you can determine the prefixes to use for the different kinds of outbound links and downloads it tracks.

* [Google Analytics for WordPress](http://yoast.com/wordpress/google-analytics/).
* Other [Wordpress plugins](http://yoast.com/wordpress/) by the same author.
* You can hire this author to write [WordPress themes](http://www.altha.co.uk/wordpress/themes/) and [plugins](http://www.altha.co.uk/wordpress/plugins/)!

== Installation ==

This section describes how to install the plugin and get it working.

1. Delete any existing `gapp` folder from the `/wp-content/plugins/` directory
1. Upload `gapp` folder to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Go to the options panel under the 'Plugins' menu and add your Analytics account number and set the settings you want.

== Changelog ==

1. 2.6.6: Fixed settings link
1. 2.6.5: added Ozh admin menu icon and settings link
1. 2.6.4: fixes for 2.7
1. 2.6.3: fixed bug that didn't allow saving of outbound clicks from comments string
1. 2.6: fixed incompatibility with WP 2.6
1. 2.5.4: fixed an issue with pluginpath being used globally, and changed links to new domain.
1. 2.2: switched to the new tracking code
1. 2.1: made sure tracking was disabled on preview pages
1. 2.0: added AdSense tracking
1. 1.5: added option to enable admin tracking, off by default

== Frequently Asked Questions ==

= This inflates my clicks, can I filter those out? =

Yes you can, create a new profile based on your original profile and name it something like 'domain - clean'. For each different outbound clicks or download prefix you have, create an exclude filter. You do this by:

1. choosing a name for the filter, something like 'Exclude Downloads';
1. selecting 'Custom filter' from the dropdown;
1. selecting 'Exclude';
1. selecting 'Request URI' in the Filter Field dropdown;
1. setting the Filter Pattern to '/downloads/(.*)$' for a prefix '/downloads/';
1. setting case sensitive to 'No'.

For some more info, see the screenshot under Screenshots.

= Can I run this plugin together with another Google Analytics plugin? =

No. You can not. It will break tracking.

= How do I check the image search stats and keywords after installing this plugin? =

Check out this <a href="http://yoast.com/wordpress/google-analytics/how-to-check-your-image-search-stats-and-keywords/">tutorial on checking your image search stats and keywords</a>.

= How do I check my outbound link and download stats? =

Check out this <a href="http://yoast.com/wordpress/google-analytics/checking-your-outbound-click-stats/">tutorial on checking your outbound click stats</a>.

= I want the image search keywords in one big overview... =

Create a <a href="http://yoast.com/wordpress/google-analytics/creating-a-google-analytics-filter-for-image-search/">Google Analytics filter for image search</a>.

== Screenshots ==

1. Screenshot of the configuration panel for this plugin.
2. Example of the exclude filter in Google Analytics.

== More info ==

* For more info, version history, etc. check out the page on my site about the [Google Analytics for WordPress plugin](http://yoast.com/wordpress/google-analytics/). 
* To check out the other WordPress plugins I wrote, check out my [WordPress plugins](http://yoast.com/wordpress/) page.
* For updates about this plugin and other plugins I created read my [SEO blog](http://yoast.com/)