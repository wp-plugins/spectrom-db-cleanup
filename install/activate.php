<?php
/**
 * Performs installation process
 * @package SpectrOMDBCleanup
 * @author SpectrOM
 */

class SpectrOMDBCleanupActivate
{
	private static $_instance = NULL;
	private $_plugin = NULL;

	/**
	 * Class constructor and called on plugin activation; performs all installation tasks
	 */
	private function __construct($plugin)
	{
		$this->_plugin = $plugin;
		$this->set_default_options();
	}

	/**
	 * Retrieve singleton class instance
	 * @return SpectrOMDBCleanupActivate instance
	 */
	public static function get_instance()
	{
		if (NULL === self::$_instance) {
			$plugin = SpectrOMDBCleanup::get_instance();
			self::$_instance = new self($plugin);
		}
		return (self::$_instance);
	}

	/**
	 * Set default options if not set
	 */
	private function set_default_options()
	{
		$opt = $this->_plugin->get_options();
		$settings = array(
			'frequency' => '2',
			'time' => '1',
			'emails' => get_option('admin_email'),
			'plain_emails' => '0',
			'remove_posts' => '0',
			'remove_comments' => '0',
			'remove_usermeta' => '0',
			'remove_settings' => '0',
		);
		
		// merge default settings and previously stored settings
		if (0 !== count($opt))
			$settings = array_merge($settings, $opt);

		// calculate default interval
		$settings['interval'] = SpectrOMDBCleanup::calculate_interval($settings['frequency'], $settings['time']);

		$add = (0 === count($opt));
		if ($add)
			$res = add_option(SpectrOMDBCleanup::SETTINGS_NAME, $settings);
		else
			$res = update_option(SpectrOMDBCleanup::SETTINGS_NAME, $settings);

		$this->_plugin->update_schedule($settings['interval']);
	}
}

SpectrOMDBCleanupActivate::get_instance();

// EOF
