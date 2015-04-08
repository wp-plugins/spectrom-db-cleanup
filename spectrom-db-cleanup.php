<?php
/*
Plugin Name: SpectrOM Database Cleanup
Plugin URI: http://SpectrOMtech.com/products/spectrom-db-cleanup/
Description: Removes unnecessary data and optimizes your database tables to ensuring site stability and performance.
Author: SpectrOMtech.com
Author URI: http://SpectrOMtech.com
Version: 1.0.0
Copyright: Copyright (c) 2014-2015 SpectrOMtech.com. All Rights Reserved.
Text Domain: spectrom-dbcleanup
Domain path: /languages
*/

/**
 * Plugin implementation
 * @package SpectrOMDBCleanup
 * @author SpectrOMtech.com
 */

class SpectrOMDBCleanup
{
	const PLUGIN_VERSION = '1.0.0';
	const PLUGIN_NAME = 'SpectrOM DB Cleanup';

	private static $_instance = NULL;
	private $_settings = NULL;
	private $_plugin_dir = NULL;
	private $_plugin_url = NULL;

	const SETTINGS_NAME = 'spectrom_dbcleanup_settings';
	const CRON_NAME = 'spectrom_dbcleanup_run';

	/**
	 * Initialize all variables, filters and actions
	 */
	private function __construct()
	{
		add_filter('cron_schedules', array(&$this, 'cron_add_schedule')); // make sure this is on top
		add_action('init', array(&$this, 'init'));
		add_action('plugins_loaded', array(&$this, 'load_textdomain'));

		// You can't call register_activation_hook() inside a function hooked to the 'plugins_loaded' or 'init' hooks
		register_activation_hook(__FILE__, array(&$this, 'activate'));
		register_deactivation_hook(__FILE__, array(&$this, 'deactivate'));

		if (!defined('DAY_IN_SECONDS'))		// for older versions of WP
			define('DAY_IN_SECONDS', 60 * 60 * 24);
		if (!defined('HOUR_IN_SECONDS'))	// for older versions of WP
			define('HOUR_IN_SECONDS', 60 * 60);
	}

	/**
	 * Retrieve singleton class instance
	 * @return SpectrOMDBCleanup instance
	 */
	public static function get_instance()
	{
		if (NULL === self::$_instance)
			self::$_instance = new self();
		return (self::$_instance);
	}

	/*
	 * Initialize the SpectrOMDBCleanup plugin
	 */
	public function init()
	{
		$this->_plugin_dir = dirname(__FILE__) . DIRECTORY_SEPARATOR;
		$this->_plugin_uri = plugin_dir_url(__FILE__);

		if (is_admin())
			require_once(dirname(__FILE__) . '/admin.php');

		// make sure the cron event is scheduled
		$next = wp_next_scheduled(self::CRON_NAME);
		if (FALSE === $next)
			$this->update_schedule($this->get_option('interval', DAY_IN_SECONDS));

		add_action(self::CRON_NAME, array(__CLASS__, 'cron_run'));
	}

	/**
	 * Load the plugin's language files
	 */
	public function load_textdomain()
	{
		load_plugin_textdomain('spectrom-dbcleanup', FALSE,
			dirname(plugin_basename(__FILE__)) . '/languages');
	}

	/**
	 * called on plugin first activation
	 */
	public function activate()
	{
		require_once(dirname(__FILE__) . '/install/activate.php');
	}

	/**
	 * called on plugin deactivation
	 */
	public function deactivate()
	{
		require_once(dirname(__FILE__) . '/install/deactivate.php');
	}

	/**
	 * Adds custom schedule to the existing schedules.
	 * @param array $schedules Existing schedules
	 * @return array $schedules Modified schedules
	 */
	public function cron_add_schedule($schedules)
	{
		$schedules['spectrom_dbcleanup_schedule'] = $this->get_cron_schedule();
		return ($schedules);
	}

	/**
	 * Load and return the config settings for schedule
	 * @return array Schedule data
	 */
	private function get_cron_schedule()
	{
		$schedule = array(
			'interval' => $this->get_option('interval'),
			'display' => __('SpectrOM DB Cleanup Cron Schedule', 'spectrom-dbcleanup')
		);
		return ($schedule);
	}

	/**
	 * Update cron schedule
	 * @param int $interval Number of interval in seconds
	 */
	public function update_schedule($interval = NULL)
	{
		// add schedule
		$time_start = strtotime('yesterday'); //strtotime(date('Y-m-d 00:00:00'));
		if (NULL === $interval)
			$interval = $this->get_option('interval');
		$timestamp = $time_start + $interval;
		wp_clear_scheduled_hook(self::CRON_NAME);
		wp_schedule_event($timestamp, 'spectrom_dbcleanup_schedule',
			self::CRON_NAME); // the 3rd parameter is hook name and not a callback
	}

	/**
	 * Get all configuration settings
	 * @return array Configuration settings
	 */
	public function get_options()
	{
		if (NULL === $this->_settings)
			$this->_settings = get_option(self::SETTINGS_NAME, array());
		return ($this->_settings);
	}

	/**
	 * Returns a single configuration setting
	 * @param string $name The name of the configuration setting to return
	 * @param mixed $default The default value of the configuration setting if it's not found in the config array
	 * @return mixed The config setting from the settings array or the `$default` value if not found
	 */
	public function get_option($name, $default = NULL)
	{
		if (NULL === $this->_settings)
			$this->_settings = get_option(self::SETTINGS_NAME, array());

		if (isset($this->_settings[$name]))
			return ($this->_settings[$name]);

		return ($default);
	}

	/**
	 * Calculate interval in seconds based on frequency and time
	 * @param int $frequency Frequency in days
	 * @param int $time Time of the day from 0 to 23
	 * @return int $interval Time interval in seconds
	 */
	public static function calculate_interval($frequency, $time)
	{
		$interval = ($frequency * DAY_IN_SECONDS) + ($time * HOUR_IN_SECONDS);
		return (intval($interval));
	}

	/**
	 * Run cron process
	 * @apram boolean $forced Set to TRUE if cron is forcibly invoked
	 */
	public static function cron_run($forced = FALSE)
	{
		if ($forced || (defined('DOING_CRON') && DOING_CRON))
			require_once(dirname(__FILE__) . '/cron.php');
	}
}

SpectrOMDBCleanup::get_instance();

// EOF
