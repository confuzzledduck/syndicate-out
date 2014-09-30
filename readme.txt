=== Plugin Name ===
Contributors: ConfuzzledDuck
Tags: syndication, xmlrpc, cross-post, post, content, autoblogging, duplicate
Requires at least: 3.4
Tested up to: 3.8
Stable tag: 0.8.3

Syndicates posts made in any specified category to another WP blog using WordPress' built in XML-RPC functionality.

== Description ==

Syndicate Out syndicates all posts made in a specified category (or optionally all posts) to any other WordPress
blog(s) in real time. This enables blog owners to create automatic aggregating, or topic specific blogs from any
number of different blog sources without relying on RSS feeds or any kind of timed events. Put simply: it re-posts
the content of one blog to one or more other blogs.

The plugin uses WordPress' built in XML-RPC functionality to push posts to the target blog. XML-RPC will need
to be enabled on the receiving blog in order for this plugin to work. For versions of WordPress 3.5 and later
the XML-RPC is enabled by default. For versions prior to 3.5 it will need enabling in the admin panel.

For versions 0.8 and higher of the plugin the sending blog needs to be WordPress 3.1 or higher due to the use of
the WordPress enhanced IXR client, and the receiving blog needs to be WordPress 3.4 or higher so that the XML-RPC
WordPress API posts functionality is available.

More information is available, feature requests and bug reports are gladly accepted over on the
[project's own blog](http://www.flutt.co.uk/development/wordpress-plugins/syndicate-out/).

== Installation ==

1. Set up a user on the receiving blog which has permissions to publish new posts
1. For versions of WordPress before 3.5 only: switch on XML-RPC remote publishing on the receiving blog ('Settings'->'Writing')
1. Upload the syndicate-out directory to the `/wp-content/plugins/` directory of the sending blog
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Configure the plugin through the 'Settings'->'Syndication' menu

You may then test the plugin by posting to the selected syndication category.
The post should immediately be cross-posted to the remote blog.

== Changelog ==

= 0.8.4 =
* Added hooks for actions and filters to syndication process.
* Added option to transmit all categories except for the syndication category. (Request by SpiritFly.)
* Added tabs to settings page for each syndication group to help improve usability.
* Improved status reporting on settings page.
* Fixed bug meaning schedule dates weren't getting sent to remote blog correctly.

= 0.8.3 =
* Efficiency improvements in syndication routine.
* Fully internationalized the plugin.
* Added Spanish translation. (Muchas gracias Andrew, Jelena and WebHostingHub.)
* Prevented trampling on https-based URLs. (Multumesc Marius.)
* Added per-post selection of syndication.
* Fixed some notices when running with PHP strict errors turned on.

= 0.8.2.2 =
* Fixed a bug relating to post categories being transmitted as Array sometimes. (Thanks ammonlee.)
* Bugfix relating to post status (inherit vs. post).
* Minor change to fix issue seen when posting to multiple blogs in one category. (Thanks mkokes and Free Refill.)
* Some very minor spacing changes.

= 0.8.2.1 =
* Version bump.

= 0.8.2 =
* Finalized bug fix to address taxonomy issue in 0.8 (thanks to everyone who reported and assisted squashing this one!).

= 0.8.1 =
* Experimenal bug fix to address an issues seen by lots of people in 0.8: failure to syndicate with the API resposne "Sorry, one of the given taxonomies is not supported by the post type".

= 0.8 =

* Switched to using WP_HTTP_IXR_CLIENT over raw IXR_Client for requests.
* Switched to using wp.newPost and wp.editPost over metaWebLog XMLRPC calls. Breaks backwards compatibility for remote blogs WP < 3.4.
* Switched to using save_post action hook to catch all published posts regardless of method. This also means scheduled posts are syndicated (thanks everyone who noticed problems posting from the WordPress mobile apps).
* Fixed bug with array re-numbering affecting syndication of edited / updated posts.
* Added authentication and API version checks to settings save routine.
* Options version incremented and new method for handling upgrades implemented (to handle http://make.wordpress.org/core/2010/10/27/plugin-activation-hooks/).

= 0.7 =

* Updates and testing for WordPress up to 3.5.
* Added settings link to plugin page.
* Overhauled look of the settings page.
* Added syndication of permalink.

= 0.6 =

* Added custom field syndication functionality.
* Added delete operations to tidy up all plugin data on uninstall.
* Fixed major bug in syndicating all categories.
* Re-worked sections of options sanitation to fix a bug on first save (thanks Kevin).
* Reworked the decision making logic around new posts / edit posts.
* Corrected some malformed markup in settings page.

= 0.5.1 =

* Bugfix to settings page category dropdown introduced in 0.5.

= 0.5 =

* Added syndication groups.
* Added the ability to syndicate all posts on a blog, not just one category (thanks jc).
* Added versioning to stored options to cater for future upgrades.
* Fixed a settings array related bug (thanks Dan, TJ).
* Re-housed a lonely PHP short tag (thanks TJ).

= 0.4.1 =

* Fixed a bug which was causing warnings to be issued in some circumstances (thanks Dan).

= 0.4 =

* Added ability to syndicate to multiple blogs (thanks Chris, Cat, Danel).
* Added ability to send category information with the syndicated post (thanks Martin).
* Posts which have already been syndicated will be syndicated from the source blog if they're edited.
* Modified the storage format of so_options to handle new functionality.

= 0.3.2 =

* Fixed so-options include bug (thanks Paul Bain).
* Modified permission levels for admin page to hopefully fix visibility bug (thanks randy, Adam and Paul).

= 0.3.1 =

* Changed IXR include line to use ABSPATH and WPINC.
* Modified handling of edited posts so they don't get re-posted on the remote blog.

= 0.3 =

* Fixed IXR include bug.
* Added tag handling (they are now passed on to the remote blog).

= 0.2 =

* First available public release.
