=== Alink Tap ===
Contributors: mrbrazzi, todoapuestas
Donate link: http://todoapuestas.org/
Tags: link
Requires at least: 3.5.1
Tested up to: 3.9.1
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

This plugin is a customization of KB Linker vTAP by Adam R. Brown to TodoApuestas.org. Looks for user-defined phrases in posts and automatically links them. Example: Link every occurrence of "TodoApuestas" to todoapuestas.org. It execute syncronizations task with TodoApuestas.org Server.

== Description ==

= IMPORTANT NOTE TO ANYBODY CONSIDERING ADDING THIS PLUGIN TO A WP-MU INSTALLATION: =

If you aren't sure whether you are using a WP-MU blog, then you aren't. Trust me. If this warning applies to you, then you will know it.

For WP-MU administrators: You should not use this plugin. Your users could use it to place (potentially malicious) javascript into their blogs.

This plugin is PERFECTLY SAFE for non-WP-MU blogs, so ignore this message if you're using regular wordpress (you probably are).

= DATABASE STRUCTURE =

The options->alink_tap_linker_remote page will create a set of matching terms and URLs that gets stored as a list.

structure of option "alink_tap_linker_remote":

  pairs => array(
    url,      url is the original one
    urles     urles is the url that serve the content in spanish
  )

  text=>	same content as pairs, but in unprocessed form (for displaying in the option's form)

  plurals => 1, 0		if 1, we should look for variants of the keywords ending in s or es

  licencias = 1, 0  if 1, we check if client's ip is from Spain and


== Installation ==

This section describes how to install the plugin and get it working.

= Using The WordPress Dashboard =

1. Navigate to the 'Add New' in the plugins dashboard
2. Search for 'Alink Tap'
3. Click 'Install Now'
4. Activate the plugin on the Plugin dashboard

= Uploading in WordPress Dashboard =

1. Navigate to the 'Add New' in the plugins dashboard
2. Navigate to the 'Upload' area
3. Select `alink-tap.zip` from your computer
4. Click 'Install Now'
5. Activate the plugin in the Plugin dashboard

= Using FTP =

1. Download `alink-tap.zip`
2. Extract the `plugin-name` directory to your computer
3. Upload the `plugin-name` directory to the `/wp-content/plugins/` directory
4. Activate the plugin in the Plugin dashboard


== Frequently Asked Questions ==



== Screenshots ==


== Changelog ==

= 1.0 =
* Initial release.

== Upgrade Notice ==


== Arbitrary section ==



== Updates ==

The basic structure of this plugin was cloned from the [WordPress-Plugin-Boilerplate](https://github.com/tommcfarlin/WordPress-Plugin-Boilerplate) project.
This plugin supports the [GitHub Updater](https://github.com/afragen/github-updater) plugin, so if you install that, this plugin becomes automatically updateable direct from GitHub. Any submission to WP.org repo will make this redundant.
