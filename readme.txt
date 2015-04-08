=== SpectrOM DB Cleanup ===
Contributors: SpectrOMtech davejesch
Donate link: http://SpectrOMtech.com/products/spectrom-db-cleanup
Tags: database, cleanup, optimize, optimizer, optimization, performance, report, spectromtech, davejesch
Requires at least: 3.5
Tested up to: 4.1
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Removes unnecessary data with Reporting features. Reduces Site Crash and Downtime.

== Description ==

The SpectrOM DB Cleanup tool will remove selected old and unused data in your database tables and then rebuild the MySQL indexes for all of your tables. This helps to ensure that your index files do not get corrupted and that your data can be accessed efficiently.

><strong>Support Details:</strong> We are happy to provide support and help troubleshoot issues. Users should know however, that we check the WordPress.org support forums once a week on Fridays from 10am to 12pm PST (UTC -8). Daily support and issue reports are handled via our GitHub repository here: <a href="https://github.com/spectrom/spectrom-db-cleanup/issues" target="_blank">https://github.com/spectrom/spectrom-db-cleanup/issues</a>. Please read our support notes here before creating new issues.

The SpectrOM DB Cleanup tool was specifically designed for data-driven sites such as eCommerce, Learning Management Systems and BuddyPress. 

The SpectrOM DB Cleanup Features:
* Removal of selected old and unused data in your database tables
* Rebuilds the MySQL indexes for all of your tables
* Configurable so you can indicate what sections to Cleanup
* eMail a report to indicated recipients
* Automation at specified day and time

This DB Cleanup helps to ensure that your database index files do not get corrupted and that your data can be accessed efficiently. 

Want to help make this tool even better? Participate in GitHuub at <a href="https://github.com/spectrom/spectrom-db-cleanup" target="_blank">https://github.com/spectrom/spectrom-db-cleanup</a>. Or <a href="http://SpectrOMtech.com/contact-spectrom-tech/products-contact/" target="_blank">Contact SpectrOM</a> for more information on how you can participate.

== Installation ==

Installation instructions: To install, do the following:

1. From the dashboard of your site, navigate to Plugins --> Add New.
2. Select the "Upload Plugin" button.
3. Click on the "Choose File" button to upload your file.
3. When the Open dialog appears select the spectrom-db-cleanup.zip file from your desktop.
4. Follow the on-screen instructions and wait until the upload is complete.
5. When finished, activate the plugin via the prompt. A confirmation message will be displayed.

or, you can upload the files directly to your server.

1. Upload all of the files in `spectrom-db-cleanup.zip` to your  `/wp-content/plugins/spectrom-db-cleanup` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.

Once activated, you can configure the plugin via the Settings -> SpectrOM DB Cleanup menu.

== Frequently Asked Questions ==

= How often should I have the Cleanup operation scheduled. =

This depends on the size of your site and how often things are being updated. If you are posting new content all the time or have several users making comments, you might want to run the Cleanup every other day. If you have an eCommerce site with several purchases made every day, running the Cleanup every day will keep the session table from becoming corrupted and ensure that users can continue using the shopping cart.

Some recommended settings:

* Sites with 0-5,000 visitors per day should schedule the Cleanup for every seven days.
* Sites with 5,000-10,000 visitors per day should schedule the Cleanup for every five days.
* Sites with 10,000-30,000 visitors per day should schedule the Cleanup for every other day.
* Sites with over 30,000 visitors per days should schedule the Cleanup for every day.

= What time of day should I schedule the Cleanup? =

It's best to run the SpectrOM DB Cleanup at a time when your site has few visitors. The Cleanup can take anywhere from five seconds to as much as a minute or two, depending on the size of the database. Users can view the site during this time, but they might notice some slowness.

To find what time of day has the lowest traffic on your site, you can use Google Analytics. To do this, click on "Customization" in the top bar of your Analytics Dashboard then choose "Custom Report."

This brings you to the report builder table. Here you can build a basic visitors and eCommerce report so you can view visitor behavior based on day of week and hour of day.

Give your report a name and set up two Metric Groups: one for Visitors and another for eCommerce.

You can add any metrics you like here by just clicking on “+ add metric” and searching for the data you need. Once you have the metrics labeled and selected, you need to choose the "Dimensions." This is where you set day and time.

Let this report run for a couple of weeks to gather data on your user's behavior. Then you can adjust the time that the SpectrOM DB Cleanup is run accordingly.

== Screenshots ==

1. Configuration page, Automation section: select how often and when to perform Cleanup.
2. Configuration page, Customization section: select which cleanup operations to perform.
3. Email report, a Sample Report generated by the SpectrOM DB Cleanup tool.

== Changelog ==

= 1.0 =
* First release.

== Upgrade Notice ==

= 1.0 =
First release.
