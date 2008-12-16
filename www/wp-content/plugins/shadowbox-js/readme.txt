=== Plugin Name ===
Contributors: sivel
Tags: images, formatting, links, post, posts, shadowbox, lightbox, thickbox, lightview
Requires at least: 2.5
Tested up to: 2.6.2
Stable tag: 2.0.2.1

A javascript media viewer similar to Lightbox and Thickbox.  Supports all types
of media, not just images.

== Description ==

A javascript media viewer similar to Lightbox and Thickbox.  Supports all types
of media, not just images.

This plugin uses Shadowbox.js written my Michael J. I. Jackson. 

Javascript libraries supported are: None, YUI, Prototype, jQuery,
Ext, Dojo and MooTools.  Ext, and MooTools are included in the
plugin, Prototype and jQuery are used from the Javascript libraries 
included with Wordpress, YUI is loaded from Yahoo APIs and Dojo is loaded
from Google APIs.  Once MooTools 1.2 makes its way onto Google APIs MooTools 
will be loaded from Google APIs.

This plugin can also be used as a drop in lightbox replacement, without
requiring you to edit posts already using lightbox.

By default this plugin will use Shadowbox for all image links including those
generated  by the [gallery] shortcode.  

== Installation ==

1. Upload the `shadowbox-js` folder to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Edit shadowbox-js.php and modify $jsLib, $sbAutoImg, $sbSkin and $sbLanguage
based on the comments preceeding each variable. Please note that this is an 
optional step. Shadowbox JS will function properly without modification.

NOTE: See "Other Notes" for Upgrade and Usage Instructions as well as other
pertinent topics.

== Screenshots ==

1. An Image
2. A Website
3. A YouTube Video
4. A FLV video with included flvplayer

== Upgrade ==

1. Deactivate the plugin through the 'Plugins' menu in WordPress
1. Delete the previous `shadowbox-js` folder from the `/wp-content/plugins/`
directory
1. Upload the new `shadowbox-js` folder to the `/wp-content/plugins/`
directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Edit shadowbox-js.php and modify $jsLib, $sbAutoImg, $sbSkin and $sbLanguage
based on the comments preceeding each variable. Please note that this is an 
optional step. Shadowbox JS will function properly without modification.

== Usage ==

1. By default this plugin will add the activator attributes to all image links
that do not already have the activator attribute.  Meaning basically, by
default all images in the posts on your site will automatically be displayed 
using Shadowbox. If you want more fine grain control over the links continue with 
the next steps.
1. Create a link in your post in the following format:

`&lt;a href="http://domain.tld/directory/to/image.jpg"
rel="shadowbox[album]"&gt;Image&lt;/a&gt;`

The above link can be to pretty much anything including websites, video files,
YouTube, Google Video, inline content.

1. Be sure to include `rel="shadowbox"` as this activates the plugin.
1. If `rel="shadowbox[album]"` is included the portion listed here as
`[album]` will group multiple pictures into an album called album. Do not use
[gallery] to define an album as WordPress has a shortcode that will interfere. 
1. If you are using this as a lightbox replacement you do not need to change
rel="lightbox" to rel="shadowbox".  Shadowbox.js supports rel="lightbox"
natively.
1. If you want to make a gallery/album and only want one link to display you
can use class="hidden" to hide the additional links.
1. See [http://mjijackson.com/shadowbox/doc/usage.html#markup](http://mjijackson.com/shadowbox/doc/usage.html#markup)
for detailed markup instructions.
1. If you are using using Shadowbox globally for all images but have an image 
you do not wish to use Shadowbox on use `rel="nobox"` in your image link.

= NOTE: = Do not use the visual editor for doing the above use the code
editor.  When modifying this post in the future do not use the visual editor;
please use the code editor always.

== File Payloads ==

= None: =
`450B	1	HTML/Text
5KB	6	Images
66KB	5	Javascript Files
6KB	2	Stylesheet Files
47KB	13	All`

= YUI: =
`576B 	1 	HTML/Text
5KB 	6 	Images
36KB 	4 	Javascript Files
6KB 	2 	Stylesheet Files
56KB 	14 	All`

= Dojo: =
`569B 	1 	HTML/Text
5KB 	6 	Images
64KB 	5 	Javascript Files
6KB 	2 	Stylesheet Files
74KB 	14 	All`

= jQuery: =
`565B 	1 	HTML/Text
5KB 	6 	Images
37KB 	4 	Javascript Files
6KB 	2 	Stylesheet Files
76KB 	14 	All`

= MooTools: =
`533B 	1 	HTML/Text
5KB 	6 	Images
77KB 	5 	Javascript Files
6KB 	2 	Stylesheet Files
86KB 	14 	All`

= Ext: =
`698B 	1 	HTML/Text
5KB 	6 	Images
154KB 	6 	Javascript Files
6KB 	2 	Stylesheet Files
164KB 	15 	All`

= Prototype: =
`564B 	1 	HTML/Text
5KB 	6 	Images
157KB 	5 	Javascript Files
6KB 	2 	Stylesheet Files
167KB 	14 	All`

== Change Log ==

= 2.0.2.1 (2008-09-22): =
* Fixed typo in variable name containing the previous rel attribute of the link

= 2.0.2 (2008-09-22): =
* Added support to automatically use Shadowbox to display all images in your posts including those generated by the [gallery] shortcode.
* Added deactivator rel attribute.  Use rel="nobox" to not use Shadowbox to display an image when global activation is configured.

= 2.0.1 (2008-08-25): =
* updated code for readability
* Added support for [gallery] shortcode

= 2.0 (2008-08-11): =
* Updated shadowbox.js to version 2.0
* Added various options and changes to support shadowbox.js version 2.0
* Updated javascript library locations to use Google APIs and Yahoo APIs where applicable.
* Changed versioning of the plugin to match that of the shadowbox.js version

= 0.4 (2008-04-10): =
* Updated to use assetURL for location to shadowbox files
* Cleaned up code and added extended comments
* Added extras.css with support for hidden class
* Added support to not include javascript libraries

= 0.3 (2008-02-26): =
* Updated Shadowbox.js to version 1.0 Final
* Added support for Ext, Dojo and MooTools Javascript Libraries
* Removed lightbox2shadowbox function/filter as Shadowbox.js now natively supports rel="lightbox"
* Consolidated repetitive code
* Removed images that were not in use
* Selected MooTools as the default as it contains the smallest payload

= 0.2 (2008-02-22): =
* Initial Public Release
