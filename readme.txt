=== Integrity Checker ===
Contributors: eriktorsner
Tags: checksum, security, security, secure, security plugin, wordpress security, permissions
Requires at least: 4.4
Tested up to: 4.7
Stable tag: 0.9.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

The WordPress Integrity Checker checks your WordPress installation by detecting modified files, permissions issues and other common problems.

== Description ==

Integrity-checker uses a mix of traditional and new techniques to scan your website for potential issues. First and foremost, it verifies that all installed code is identical to it's original version. By comparing WordPress core, plugins and themes in your installation with the original versions available at wordpress.org, Integrity-checker can quickly determine if there are any changes you need to be aware of. Integrity-checker also lets you compare your local version to the original to help you determine if you've been hacked.

Additionally, Integrity-checker scans all installed files for permission issues. Ensuring correct permissions is vital for WordPress security, as with any PHP based web application.

Lastly, Integrity-checker will look through some of the basic WordPress configuration to look for common security problems like user enumeration, directory index weak credentials etc.

= Features =
* Helps you track down hacked WordPress files in core, plugins and themes  
* Makes it easy to find issues with file permissions
* Detects common configuration problems


== Installation ==

This section describes how to install the plugin and get it working.

e.g.

1. Upload the plugin files to the `/wp-content/plugins/integrity-checker` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Use the Tools->Plugin Name screen to use the plugin

== Frequently Asked Questions ==

= Why should I use Integrity Checker instead of... =

Integrity Checker have a few quite unique features: the ability to compare checksums for individual themes and plugins and the ability to see the diff between two versions of the same file. But there are lots of other great security tools around for WordPress and you should try them out. Some tools put an emphasis on preventing security problems while other tools, like Integrity Checker, deals more with trying to discover problems after the fact.

One very fundamental idea in all security related work is the concept of defense in depth. That means that you should not rely on any one single security technique. Instead, you should embrace multiple forms of security, good password standards, using https where it matters, keep WordPress updated at all times etc. As a consequence, you will want/need more than one security tool to help you with that. We think Integrity Checker is an excellent addition to your security toolbox, we hope you agree.

= Integrity Checker reports some issues, but how do I fix them =

Integrity Checker is a checker tool. It scans and reports but it doesn't have any ambition to fix anything. Some tools try to do both, Integrity Checker doesn't (yet).

= What does a SOFT issue mean? =

A soft issue is almost always a false positive, but something you'd want to have a look at. The most common reason for a SOFT issue is that the readme.txt file in a plugin is different. This is because a plugin developer might update the readme.txt without bumping the plugin to a new version. For instance when a new version of WordPress is released, a lot of plugin developers updates so that the "Tested up to" information reflects the new WordPress version number. Another common reason is that you (or someone else) may have added .htaccess files for added security, when Integrity Checker finds an .htaccess file, it will issue a SOFT warning.

= I'd like to run Integrity Checker on a schedule  =

Integrity Checker has an older brother, the wp-cli sub command [wp-checksum](https://github.com/eriktorsner/wp-checksum). Integrity Checker and wp-checksum uses the same backend database and shares a lot of code. So currently we think that the best way to schedule checksum scanning is via the wp-cli tool. Having said that, we'd like to hear your opinion how to go forward. One way would be to open up the API (see below) to Integrity Checker and have you solve the scheduling in your own environment, another way could be to integrate a scheduled into the plugin itself. Or both, let us know what would benefit you the most.

= Does Integrity Checker support wp-cli  =

No, but there [a separate tool](https://github.com/eriktorsner/wp-checksum) for that, see above.

= How about an API?  =

Integrity Checker actually implements a REST API (that's why it requires WordPress 4.4) that your web browser uses to scan and report issues. The authentication method is currently limited to cookies, meaning that the only practical way to use this API is via the Integrity Checker page in WordPress admin. Right now, WordPress doesn't ship with oAuth authentication for REST clients and therefore Integrity Checker doesn't even attempt to support oAuth. Secure access to the Integrity Checker API is something we're looking to add in the near future.

= How does Integrity Checker work =

We have a database and an API over at https://api.wpessentials.io where we collect data about most plugins and themes on the WordPress.org repo. As we get requests for comparing checksums for plugins we haven't previously seen, we add it to the database. Integrity Checker relies on using the API for this database. We index as many plugins and themes from the .org repository as we can and we've asked a few commercial plugin vendors if they want to contribute to the database.

= How does access to the backend API work =

Integrity Checker uses our backend api to retrieve checksums for themes and plugins. As an anonymous user, you can query our API 25 times per hour. We think (but would love your input) that this is sufficient for most small and medium sized WordPress installations with 20-25 plugins and a theme. There are some caching going on in the background, so repeated scans doesn't always result in more queries to us. We create an anonymous user in our database and assign an API key to that user, that API key is sent back to your WordPress installation and stored in your database. You can see your API key in the About section in Integrity Checker as well as your current API usage.

If you are willing to share your email address with us, we increase that hourly quota up to 75 requests per hour.

The API key's can be reused between sites, so once you have registered with us, you can use that key on more than one site.

If you need more than 75 requests per hour assigned to one API key, you can purchase a premium subscription.

Integrity Checker is currently in version 0.9 and we're actively trying to figure this out. Any feedback on rate limits is most welcome.


= Why isn't the backend API 100% free =

Because we need to eat and pay bills. We'd like our database to be 100% free for all and at the same time find a business model that allow us to devote 100% of our time to it. With the business model we're currently using, we can have most casual users access our database free and at the same time have a model where larger users can pay a monthly fee to access the database via the API.

The API keys can be shared between different WordPress installations and between Integrity Checker and the wp-cli tool so if you're hosting 10-20 WordPress installations on a few different server, you'll only need to get one premium subscription. If you're a hosting provider looking to analyze all your clients installations, we suggest you contact us.


== Screenshots ==

1. Scan results showing issues found in WordPress core. Some issues are marked as "SOFT" meaning that they're most likely a false positive. Other issues are marked "HARD" and needs to be examined.
2. Showing the diff between the version from the WordPress repository and the version currently found in your installation
3. Showing scan results for each plugin individually. Issues are marked as HARD of SOFT. Diff can be shown for modified files.
4. Showing results from scanning WordPress settings. 

== Changelog ==

= 0.9.1 =
* Ripped out CMB2, more/better docblocks

= 0.9 =
* Initial submit to WordPress repository

