=== Easy YouTube Gallery ===
Contributors: urkekg, techwebux
Tags: youtube, video, gallery, thumbnail, lightbox
Requires at least: 3.9.0
Tested up to: 6.7.1
Stable tag: 1.0.5
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Quick and easy make gallery for custom set of YouTube videos provided in shortcode, and autoplay video on click in Magnific PopUp lightbox.

== Description ==

Use this plugin when you wish to quick insert gallery grid composed from custom selected YouTube videos.

For automated latest or random videos collected from YouTube channel, favourites, liked videos or playlist check out [YouTube Channel](https://wordpress.org/plugins/youtube-channel/)

= Features =

* Custom set of ID's provided as shortcode attribute `id` (single of multiple ID's separated by comma)
* Custom additional class for targeted styling (if you need to blend gallery in your theme)
* Custom number of columns to distribute thumbnails to (min 1, max 8)
* Custom set video titles as shortcode content (separate by newline or pipe)
* Responsive thumbnails
* Autoplay with [Magnific PopUp](https://dimsemenov.com/plugins/magnific-popup/) lightbox
* Well marked with classes

= Classes =

* Main container:
  * `.easy_youtube_gallery`
  * `.col-#` for number of columns (default is `1`, supported up to `8`)
  * `.ar-16_9` for 16:9, `.ar-4_3` for 4:3 or `.ar-square` for 1:1 aspect ratio
  * custom class provided by shortcode attribute `class`
* Anchor:
  * `.eytg-item`
  * `.eytg-item-#` for order number of item
  * `.eytg-item-first` for first item in gallery block
  * `.eytg-item-mid` for middle items in gallery block
  * `.eytg-item-last` for last item in gallery block
* Title: (new in 1.0.4)
  * `.eytg-title` for custom video title
  * `.top` positioning custom video title on top of thumbnail
  * `.bottom` positioning custom video title on bottom of thumbnail
* Thumbnail:
 * `.eytg-thumbnail` is class for span where we set video thumbnail as background image
* Play icon
 * `.eytg-thumbnail:before` is pseudoclass for play icon

= How To Use? =

*Full shortcode*

`[easy_youtube_gallery id=uMK0prafzw0,8Uee_mcxvrw,HcXNPI-IPPM,JvMXVHVr72A,AIXUgtNC4Kc,K8nrF5aXPlQ,cegdR0GiJl4,L-wpS49KN00,KbW9JqM7vho ar=16_9 cols=3 thumbnail=hqdefault controls=0 playsinline=1 privacy=1 title=top wall=1 class=mySuperClass]
Title One
Second Title
Video 3
Fourth Video
YouTube 5
Sixth YouTube
Player #8
9th item
[/easy_youtube_gallery]`

*Basic shortcode*

`[easy_youtube_gallery id=uMK0prafzw0,8Uee_mcxvrw,HcXNPI-IPPM cols=3]`

**Please note!** If you doing copy&paste from code above, before you paste content to page, post or text widget content, clear all formatting by paste&copy to/from Notepad or other plain text editor!

= Shortcode parameters =

* `id` (required) single YouTube video ID or multiple ID's separated with comma
* `ar` (optional) aspect ratio of thumbnails; default is `ar-16_9` for 16:9, but also supported `ar-4_3` for 4:3 and `ar-square` for 1:1
* `cols` (optional) for number of columns to distribute thumbnails in; devault is `1`, supported up to `8`
* `thumbnail` (optional) for YouTube size of thumbnail; default is `hqdefault` but we can use:
  * `0` have resolution 480x360px
  * `1`, `2` and `3` have resolution 120x90px (first, second or third frame)
  * `default` have resolution 120x90px (Default Quality)
  * `mqdefault` have resolution 320x180px (Medium Quality)
  * `hqdefault` have resolution 480x360px (High Quality)
  * `sddefault` have resolution 640x480px (Standard Definition) and does not exists for lowres videos
  * `maxresdefault` have resolution 1920x1080px (Full HD) and does not exists for lowres videos
* `controls` (optional) to optionally hide playback controls in lightbox player (default is `1` that means "display controls", but you can set it to `0` to hide controls)
* `privacy` (optional) enables enhanced privacy which means that YouTube won’t store information about visitors on your web page unless they play the video. (`0` or `1`)
* `playsinline` controls whether videos play inline or fullscreen in an HTML5 player on iOS. Learn more on [Google Developers](https://developers.google.com/youtube/player_parameters?hl=en#playsinline) (`0` or `1`)
* `class` (optional) to add custom style class if you wish to target specific styling for your own needs
* `wall` (optional) render video wall with player at top and thumbnails below (`0` or `1`)
* `title` (optional) set custom video titles position (`top` or `bottom`)

[youtube http://www.youtube.com/watch?v=EbYfwzmCVJI]

== Installation ==

1. Login to your WordPress.
1. Go to **Plugins** -> **Add New**.
1. Type to **Search Plugins** field keyword *Easy YouTube Gallery* and press **Enter** on your keyboard.
1. Click **Install Now** button.
1. When plugin is successfully installed, clik link **Activate Plugin**
1. Insert shortcode `[easy_youtube_gallery id=YT_VIDEO_ID,YT_VIDEO_ID,YT_VIDEO_ID...,YT_VIDEO_ID]` (replace `YT_VIDEO_ID` with your set of YouTube video ID's)

== Frequently Asked Questions ==

= Do I need to wrap shortcode parameters to doublequotes or singlequotes? =

No. I even suggest to you avoid wrapping shortcode parameters to double/single quotes to prevent broken output when some plugins modify content with nasty filters.

Just avoid empty space between ID's.

= Is there any way to make the pop up player any bigger? =

You can use custom style with following selector and a rule:

`.ytc-mfp-container.ytc-mfp-iframe-holder .ytc-mfp-content {
  max-width: 1200px;
}`

Just set preferred popup max width in pixels.

== Upgrade Notice ==

= 1.0.5 =
Resolved XSS vulnerability

= 1.0.4 =
Feature enhancements and bugfixes

= 1.0.3 =
Feature enhancements

= 1.0.2 =
Synchronizing library with YouTube Channel

= 1.0.1 =
New TinyMCE button to easy compose shortcode

= 1.0.0 =
Initial release

== ChangeLog ==

= 1.0.5 (2025-01-15) =

* Test on WordPress 6.7.1 and PHP 8.3.13
* Refactor code
* Fixed XSS vulneability reported on patchstack by muhammad yudha researcher

= 1.0.4 (2017-01-20) =

* (2017-01-20) Fix: Wall mode TinyMCE value not respected
* (2016-03-24) Add: Support for custom video titles set as shortcode content
* Fix: Add missing clearfix
* (2015-10-23) Fix: TinyMCE button does not have icon when new post/page is created.
* Add: Wall mode to play videos in big screen above thumbnails instead to open popup.
* Change: Sassify plugin style files.

= 1.0.3 (2015-10-04) =

* Add: Support for YouTube features Ehnanced Privacy and PlaysInline
* Enhance: Use minified version of CSS and JS
* Cleanup: Make code compliant to WordPress Core coding standard

= 1.0.2 (2015-06-22) =

* Update: MagnificPopupAU library to latest version (sync to YouTube Channel)
* Update: Support to init lightbox on AJAX content loading

= 1.0.1 (2015-05-27) =

* Add: TinyMCE button to easy compose shortcode

= 1.0.0 (2015-05-26) =

* Initial plugin release

== Screenshots ==

1. Easy YouTube Gallery full shortcode and 9 videos distributed to 3 column example
2. TinyMCE addon to easy insert shortcode

== TODO ==

* Replace MagnificPopUp with newer library
* VisualComposer block
* Inline player instead of opening in lightbox
