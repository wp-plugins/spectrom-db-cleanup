<?php
/**
 * Performs deactivation process
 * @package SpectrOMDBCleanup
 * @author SpectrOM
 */

class SpectrOMDBCleanupDeactivate
{
	private static $_instance = NULL;
	private $_plugin = NULL;

	/**
	 * Class constructor and called on plugin deactivation; performs all uninstallation tasks
	 */
	private function __construct($plugin)
	{
		$this->_plugin = $plugin;
		$this->remove_settings();
		$this->clear_scheduled_events();
	}

	/**
	 * Retrieve singleton class instance
	 * @return SpectrOMDBCleanupDeactivate instance
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
	 * Removed settings if configuration is enabled to be removed
	 */
	public function remove_settings()
	{
		$settings = $this->_plugin->get_options();

		if ('1' === $this->_plugin->get_option('remove_settings', '0'))
			delete_option(SpectrOMDBCleanup::SETTINGS_NAME);
	}

	/**
	 * Cleanup previously created scheduled events
	 */
	public function clear_scheduled_events()
	{
		// clear schedule
		wp_clear_scheduled_hook(SpectrOMDBCleanup::CRON_NAME);
	}
}

SpectrOMDBCleanupDeactivate::get_instance();

// EOF
