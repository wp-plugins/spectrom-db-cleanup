<?php
/**
 * Performs tasks for Admin page requests
 * @package SpectrOMDBCleanup
 * @author SpectrOMtech.com
 */

class SpectrOMDBCleanupAdmin
{
	private static $_instance = NULL;
	private $_plugin = NULL;

	/**
	 * class contructor, setup all actions and filters
	 */
	private function __construct($plugin)
	{
		$this->_plugin = $plugin;				// save instance of parent plugin

		add_action('admin_menu', array(&$this, 'admin_menu'));
		add_action('admin_init', array(&$this, 'admin_init'));
	}

	/**
	 * return singleton instance of SpectrOMDBCleanupAdmin
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
	 * callback for admin_menu event. set up menus
	 */
	public function admin_menu()
	{
		if (!current_user_can('manage_options'))
			wp_die(__('You do not have sufficient permissions to access this page.', 'spectrom-dbcleanup'));

		add_options_page(__('SpectrOM DB Cleanup', 'spectrom-dbcleanup'),
			__('SpectrOM DB Cleanup', 'spectrom-dbcleanup'),
			'manage_options', 'spectrom-dbcleanup',
			array(&$this, 'settings_page'));
	}

	/**
	 * callback for admin_init event. initialize settings page
	 */
	public function admin_init()
	{
		register_setting('settings-group', SpectrOMDBCleanup::SETTINGS_NAME, array(&$this, 'settings_validate'));

		$plugin = $this->_plugin;

		add_settings_section('settings-section', '',
			array(&$this, 'settings_section_callback'), 'spectrom-dbcleanup');

		add_settings_field('frequency', __('Frequency of Cleanup:', 'spectrom-dbcleanup'),
			array(&$this, 'dropdown_options_callback'), 'spectrom-dbcleanup', 'settings-section',
			array('name' => SpectrOMDBCleanup::SETTINGS_NAME.'[frequency]',
				'value' => $plugin->get_option('frequency', 2), 'options' => $this->_get_setting_frequencies()));

		add_settings_field('time', __('Time of Day:', 'spectrom-dbcleanup'),
			array(&$this, 'dropdown_options_callback'), 'spectrom-dbcleanup', 'settings-section',
			array('name' => SpectrOMDBCleanup::SETTINGS_NAME.'[time]',
				'value' => $plugin->get_option('time', 1), 'options' => $this->_get_setting_times()));

		add_settings_field('emails', __('Emails to Send Reports To:', 'spectrom-dbcleanup'),
			array(&$this, 'emails_callback'), 'spectrom-dbcleanup', 'settings-section',
			array('name' => SpectrOMDBCleanup::SETTINGS_NAME.'[emails]',
				'value' => $plugin->get_option('emails', get_option('admin_email'))));

		add_settings_field('remove_posts', __('Remove Posts marked as Trash:', 'spectrom-dbcleanup'),
			array(&$this, 'radio_yn_callback'), 'spectrom-dbcleanup', 'settings-section',
			array('name' => SpectrOMDBCleanup::SETTINGS_NAME.'[remove_posts]',
				'message' => __('When enabled, will remove posts marked as trash and their associated postmeta data', 'spectrom-dbcleanup'),
				'value' => $plugin->get_option('remove_posts', '0')));

		add_settings_field('remove_comments', __('Remove Comments marked as Trash:', 'spectrom-dbcleanup'),
			array(&$this, 'radio_yn_callback'), 'spectrom-dbcleanup', 'settings-section',
			array('name' => SpectrOMDBCleanup::SETTINGS_NAME.'[remove_comments]',
				'message' => __('When enabled, will remove any comments marked as Trash', 'spectrom-dbcleanup'),
				'value' => $plugin->get_option('remove_comments', '0')));

		add_settings_field('remove_usermeta', __('Remove Orphaned Usermeta:', 'spectrom-dbcleanup'),
			array(&$this, 'radio_yn_callback'), 'spectrom-dbcleanup', 'settings-section',
			array('name' => SpectrOMDBCleanup::SETTINGS_NAME.'[remove_usermeta]',
				'message' => __('When enabled, will remove Usermeta records that do not have a valid user id', 'spectrom-dbcleanup'),
				'value' => $plugin->get_option('remove_usermeta', '0')));

		add_settings_field('remove_expired_transients', __('Remove expired Transient Data:', 'spectrom-dbcleanup'),
			array(&$this, 'radio_yn_callback'), 'spectrom-dbcleanup', 'settings-section',
			array('name' => SpectrOMDBCleanup::SETTINGS_NAME.'[remove_expired_transients]',
				'message' => __('When enabled, will remove expired Transient data.', 'spectrom-dbcleanup'),
				'value' => $plugin->get_option('remove_expired_transients', '0')));

		add_settings_field('plain_emails', __('Send Emails in plain text:', 'spectrom-dbcleanup'),
			array(&$this, 'checkbox_callback'), 'spectrom-dbcleanup', 'settings-section',
				array('name' => SpectrOMDBCleanup::SETTINGS_NAME.'[plain_emails]',
					'message' => __('If checked, emails will be sent as plain text rather than in HTML format.', 'spectrom-dbcleanup'),
					'value' => $plugin->get_option('plain_emails', '0')));

		add_settings_field('remove_settings', __('Plugin Uninstallation', 'spectrom-dbcleanup'),
			array(&$this, 'checkbox_callback'), 'spectrom-dbcleanup', 'settings-section',
				array('name' => SpectrOMDBCleanup::SETTINGS_NAME.'[remove_settings]',
					'message' => __('Remove all stored settings when plugin is uninstalled.', 'spectrom-dbcleanup'),
					'value' => $plugin->get_option('remove_settings', '0')));
	}

	// Start callbacks section

	/**
	 * Callback for settings section
	 */
	public function settings_section_callback()
	{
		echo '<style>',
			'.form-table th { width:250px }',
			'</style>';
	}

	/**
	 * Callback for frequency and time dropdown fields
	 * @param array $args Input field name and its value including its dropdown options
	 */
	public function dropdown_options_callback($args)
	{
		echo '<select name="', $args['name'], '">';
		foreach ($args['options'] as $key => $value)
			echo '	<option value="', $key, '" ', (($key == $args['value']) ? 'selected="selected"' : ''),
				'>', esc_html($value), '</option>';
		echo '</select>';
	}

	/**
	 * Callback for email addresses field
	 * @param array $args Input field name and its value
	 */
	public function emails_callback($args)
	{
		echo '<textarea name="', $args['name'], '" rows="4" cols="50">', esc_html($args['value']), '</textarea>',
			'<br />', '<label>',
			esc_html(__('List of email addresses to send notifications to, separated by commas.', 'spectrom-dbcleanup')), '</label>';
	}

	/**
	 * Callback for remove settings checkbox
	 *
	 * @param array $args Input field name and its value
	 */
	public function checkbox_callback($args)
	{
		echo '<input type="hidden" name="', $args['name'], '" value="0" />';
		echo '<input type="checkbox" name="', $args['name'], '" value="1" ',
				(('1' == $args['value']) ? 'checked="checked"' : ''), '/> ',
				esc_html($args['message']);
	}


	/**
	 * Callback for common Yes or No radio buttons
	 * @param array $args Input field name and its value
	 */
	public function radio_yn_callback($args)
	{
		// NOTE: There's no duplicate checked="checked" here, only one radio button will be checked depending upon the value of $args['value']
		echo '<input type="radio" name="', $args['name'], '" value="1" ',
				(('1' == $args['value']) ? 'checked="checked"' : ''), '/> Yes',
			'&nbsp;&nbsp;',
			'<input type="radio" name="', $args['name'], '" value="0" ',
				(('0' == $args['value']) ? 'checked="checked"' : ''), '/> No';
		if (isset($args['message']))
			echo '&nbsp;&mdash;&nbsp;', $args['message'];
	}
	// End callbacks section

	/**
	 * Displays/prints the settings/configuration page
	 */
	public function settings_page()
	{
		wp_enqueue_style('spectrom-admin', $this->_plugin->_plugin_uri . 'assets/css/spectrom-admin.css',
			array(), SpectrOMDBCleanup::PLUGIN_VERSION);

		require_once(dirname(__FILE__) . '/class.spectromsettingspage.php');
		add_action('spectrom_page', array(&$this, 'output_settings_form'));
		new SpectrOMSettingsPage();
	}

	/**
	 * Outputs the settings page using the WP Settings API
	 */
	public function output_settings_form()
	{
		echo '<div class="wrap">';
		echo	'<h2>', sprintf(__('SpectrOM DB Cleanup v%1$s Settings', 'spectrom-dbcleanup'), SpectrOMDBCleanup::PLUGIN_VERSION), '</h2>';
		echo	'<form action="options.php" method="POST">';
		settings_fields('settings-group');
		do_settings_sections('spectrom-dbcleanup');
		submit_button();
		echo	'</form>';
		echo '</div>';
	}

	/**
	 * Callback for input validation or sanitation of inputs
	 * @param array $input POST data
	 * @return array $output Sanitized inputs
	 */
	public function settings_validate($input)
	{
		$plugin = $this->_plugin;
		$valid = array();

		$email_list = array();
		$bad_email = FALSE;
		if (isset($input['emails'])) {
			$emails = explode(',', $input['emails']);
			foreach ($emails as $email) {
				if (is_email($email))
					$email_list[] = $email;
				else
					$bad_email = TRUE;
			}
		}

		$values = array(
			'frequency' => 'int',
			'time' => 'int',
			'emails' => 'email',
			'plain_emails' => 'int',
			'remove_posts' => 'int',
			'remove_comments' => 'int',
			'remove_usermeta' => 'int',
			'remove_expired_transients' => 'int',
			'remove_settings' => 'int',
		);

		foreach ($values as $name => $type) {
			switch ($type) {
			case 'int':
				if (isset($input[$name]) && is_numeric($input[$name]))
					$valid[$name] = $input[$name];
				break;
			case 'email':
				$valid[$name] = implode(',', $email_list);
				break;
			}
		}
		// recalculate interval
		$valid['interval'] = SpectrOMDBCleanup::calculate_interval($input['frequency'], $input['time']);

		if ($bad_email)
			add_settings_error(SpectrOMDBCleanup::SETTINGS_NAME, 'invalid-email', __('You have entered an invalid e-mail address.', 'spectrom-dbcleanup'));
		if (0 === count($email_list))
			add_settings_error(SpectrOMDBCleanup::SETTINGS_NAME, 'invalid-email', __('To receive Reports, you need to enter at least one email address.', 'spectrom-dbcleanup'));

		return ($valid);
	}

	/**
	 * Returns frequencies definition
	 * @return array $setting Frequencies definition or dictionary
	 */
	private function _get_setting_frequencies()
	{
		$setting = array(
			1 => __('Every Day', 'spectrom-dbcleanup'),
			2 => __('Every Two Days', 'spectrom-dbcleanup'),
			3 => __('Every Three Days', 'spectrom-dbcleanup'),
			5 => __('Every Five Days', 'spectrom-dbcleanup'),
			7 => __('Every Seven Days', 'spectrom-dbcleanup'),
			14 => __('Every Fourteen Days', 'spectrom-dbcleanup'),
		);
		return ($setting);
	}

	/**
	 * Returns time of day definition
	 * @return array $setting Dictionary for time of the day settings
	 */
	private function _get_setting_times()
	{
		$setting = array();
		for ($i = 0; $i < 24; $i++)
			$setting[$i] = date('g:00 a', strtotime(sprintf('%02d:00', $i)));
		return ($setting);
	}
}

SpectrOMDBCleanupAdmin::get_instance();

// EOF
