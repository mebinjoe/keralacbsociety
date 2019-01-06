=== Page Restrict ===
Contributors: theandystratton, sivel
Tags: pages, page, restrict, restriction, logged in, cms
Requires at least: 2.6
Tested up to: 3.4.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Stable tag: trunk

Restrict certain pages or posts to logged in users.

== Description ==

Restrict certain pages or posts to logged in users

This plugin will allow you to restrict all, none, or certain pages/posts to logged in users only.  

In some cases where you are using WordPress as a CMS and only want logged in users to have access to the content or where you want users to register for purposes unknown so that they can see the content, then this plugin is what you are looking for.

Simple admin interface to select all, none, or some of your pages/posts.  This now works for posts!

== Installation ==

1. Upload the `pagerestrict` folder to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress

NOTE: See "Other Notes" for Upgrade and Usage Instructions as well as other pertinent topics.

== Screenshots ==

1. Login Form
2. Admin Page

== Upgrade ==

1. Deactivate the plugin through the 'Plugins' menu in WordPress
1. Delete the previous `pagerestrict` folder from the `/wp-content/plugins/` directory
1. Upload the new `pagerestrict` folder to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress

== Usage ==

1. Visit Settings>Page Restrict in the admin area of your blog.
1. Select your restriction method (all, none, selected).
1. If you chose selected, select the pages you wish to restrict.
1. Enjoy.

== Changelog ==

= 2.2.6 =
* Additional i18n support.

= 2.2.5 =
* Added internationalization support for pending translate.wordpress.org updates.

= 2.2.4 =
* Fixed redirect URL issue.

= 2.2.3 =
* Removed wp_specialchars (deprecated function), replaced with esc_html.

= 2.2.2 =
* Updated possible security flaw.

= 2.2.0 =
* Added filters for modified page/post excerpt/archive content.

= 2.1.3 =
* Removed div.page-restrict-output from unmodified content output. This was causing theme compatibility issues.

= 2.1.2 =
* Fixed a bug with non-existent post object.

= 2.1.1 =
* Added filter for username and password labels in login form. More planned updates in 2014.

= 2.1 =
* Added login error messaging back to referring URI if the user's login is unsuccessful.

= 2.07 = 
* Added div around output with CSS class (page-restrict-output) for more styling flexibility.

= 2.05 (2011-12-17) =
* Quick update to remove some outdated functions and global vars (clean up).

= 2.05 (2011-12-17) =
* Fixed bug where pages were not being restricted. Confirmed most compatibility with WP 3.3.

= 2.04 (2011-09-07) =
* Added support for blog home page (included with archives and search listings) for post restriction.

= 2.03 =
* Update to fix bug where only 5 blog posts display in the settings screen.

= 2.01 (2010-05-27) =
* Quick update to fix an in_array() warning issued when settings are checked but have not been saved.

= 2.0 (2010-05-27) =
* Added support for restricting posts.

= 1.85 (2010-02-15) =
* Fixed bug where choosing the "selected" restriction method would result in all pages being restricted if no pages are checked in the plugin settings.

= 1.8 (2010-02-12) =
* Changed URL's to wp-login.php to use get_bloginfo('wpurl') instead of get_bloginfo('url') to ensure proper redirection when users install files outside of the site's document root.

= 1.7 (2010-01-18) =
* Fixed a bug that added slashes to quotes in the custom restriction message.

= 1.6 (2009-03-12): =
* Replaced while loop with foreach for display list of pages 
* Added meta box to write/edit pages page
* Added capability to display or not display the login form
* Updated admin styling
* Restrict commeting or viewing comments on restricted pages
* Restrict search results also so restricted pages are not shown

= 1.5 (2008-09-03): =
* Added ability to change restriction method
* Rewrote and simplified areas pertaining to the list of pages

= 1.4.1 (2008-08-25): =
* Added back no_cache add_action that was lost in the admin separation
* Removed duplicate add_action for the admin page

= 1.4 (2008-08-25): =
* Updated version number scheme
* Updated code for readability
* Moved admin functionality to separate file included only when is_admin is true

= 0.3.1 (2008-08-16): =
* Updated for PHP4 Support
* Restored end PHP tag at end of script

= 0.3 (2008-08-13): =
* Initial Public Release
