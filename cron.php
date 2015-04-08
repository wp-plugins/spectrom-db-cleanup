<?php
/**
 * Performs Cron tasks
 * @package SpectrOMDBCleanup
 * @author SpectrOM
 */

class SpectrOMDBCleanupCron
{
	private static $_instance = NULL;
	private $_plugin = NULL;
	private $wpdb = NULL;

	private $_start_opt = 0;
	private $_end_opt = 0;

	private $table_data = NULL;				// holds information from SHOW TABLE STATUS

	private $_start_benchmarks = NULL;
	private $_end_benchmarks = NULL;

	private $_postsremoved = -1;
	private $_postmetaremoved = -1;
	private $_commentsremoved = -1;
	private $_commentmetaremoved = -1;
	private $_transientsremoved = -1;
	private $_usermetaremoved = -1;
	private $_table_results = array();		// holds results of the OPTIMIZE TABLE statements

	/**
	 * class contructor, setup all actions and filters
	 */
	private function __construct($plugin)
	{
		$this->_plugin = $plugin;
		$this->do_cleanup();

		$this->generate_report();
	}

	/**
	 * return singleton instance of SpectrOMDBCleanupCron
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
	 * Generate report and send email
	 */
	private function generate_report()
	{
		$db_cleanup = array();
		$html = ('0' == $this->_plugin->get_option('plain_emails', '0')) ? TRUE : FALSE;

		$report1 = __('There were %1$s %2$s that were removed from %3$s.', 'spectrom-dbcleanup');
		$report2 = __('There were %1$s %2$s records that were removed.', 'spectrom-dbcleanup');
		$bench = __('Benchmark time of %1$s table: %2$s.', 'spectrom-dbcleanup');
		$time = __('Cleanup took a total of %1$s seconds.', 'spectrom-dbcleanup');
		$newline = "\r\n";
		if ($html)
			$newline = '<br/>';

		// compose cleanup report data
		if (-1 !== $this->_postsremoved)
			$db_cleanup[] = sprintf($report1,
				$this->_postsremoved,			__('Posts', 'spectrom-dbcleanup'),		__('the Trash', 'spectrom-dbcleanup'));

		if (-1 !== $this->_postmetaremoved)
			$db_cleanup[] = sprintf($report1,
				$this->_postmetaremoved,		__('records', 'spectrom-dbcleanup'),	__('postmeta', 'spectrom-dbcleanup'));

		if (-1 !== $this->_commentsremoved)
			$db_cleanup[] = sprintf($report1,
				$this->_commentsremoved,		__('Comments', 'spectrom-dbcleanup'),	__('the Trash', 'spectrom-dbcleanup'));

		if (-1 !== $this->_commentmetaremoved)
			$db_cleanup[] = sprintf($report2,
				$this->_commentmetaremoved,	__('commentmeta', 'spectrom-dbcleanup'));

		if (-1 !== $this->_usermetaremoved)
			$db_cleanup[] = sprintf($report2,
				$this->_usermetaremoved,		__('User meta', 'spectrom-dbcleanup'));

		if (-1 !== $this->_transientsremoved)
			$db_cleanup[] = sprintf($report2,
				$this->_transientsremoved,		__('Transient', 'spectrom-dbcleanup'));


		// if no database clean options are enabled, the Database Cleanup section of the report is to be omitted.
		$sitename = get_bloginfo('name') . ' - ' . get_bloginfo('wpurl');
		$subject = sprintf(__('SpectrOM DB Cleanup Report for %1$s', 'spectrom-dbcleanup'), $sitename);
		$report = $subject;

		// add benchmark information to the report
		$report .= $newline . $newline . __('Benchmarking Report Before Cleanup:', 'spectrom-dbcleanup') . $newline;
		$report .= sprintf($bench, __('Posts', 'spectrom-dbcleanup'), number_format($this->_start_benchmarks['posts_time'], 8)) . $newline;
		$report .= sprintf($bench, __('Comments', 'spectrom-dbcleanup'), number_format($this->_start_benchmarks['comments_time'], 8)) . $newline;
		$report .= sprintf($bench, __('Options', 'spectrom-dbcleanup'), number_format($this->_start_benchmarks['options_time'], 8)) . $newline;

		// add database cleanup information to the report
		if (0 !== count($db_cleanup)) {
			$report .= $newline . $newline . __('Database Cleanup.', 'spectrom-dbcleanup') . $newline;
			$report .= implode($newline, $db_cleanup);
		}

		$report .= $newline . $newline . __('The following tables were optimized:', 'spectrom-dbcleanup') . $newline;

		if ($html) {
			$start = '<tr><td>';
			$between = '</td><td>';
			$between_rj = '</td><td align="right">';
			$end = '</td></tr>';

			$report .= '<br/><table>';
			$report .= $start . __('Table Name', 'spectrom-dbcleanup') .
					$between . __('Engine', 'spectrom-dbcleanup') .
					$between . __('Records', 'spectrom-dbcleanup') .
					$between . __('Row Length', 'spectrom-dbcleanup') .
					$between . __('Collation', 'spectrom-dbcleanup') .
					$between . __('Optmization Results', 'spectrom-dbcleanup') . $end;
		} else {
			$start = "\t";
			$between = $between_rj = "\t\t";
			$end = '';
		}

		foreach ($this->_table_results as $table_name => $result) {
			$show_data = $this->get_table_status($table_name);
			if ($result)
				$report .= "\r\n" .
					$start . $table_name .
					$between . $show_data['Engine'] .
					$between_rj . number_format($show_data['Rows'], 0) .
					$between_rj . number_format($show_data['Data_length'], 0) .
					$between . $show_data['Collation'] .
					$between . implode('; ', $result) .
					$end;
		}

		$headers = array();
		if ($html) {
			$report .= '</table>';
			$headers[] = 'text/html; charset=iso-8859-1';
			add_filter('wp_mail_content_type', array(__CLASS__, 'html_content_type'));
		}

		// add benchmark information to the report
		$report .= $newline . $newline . __('Benchmarking Report After Cleanup:', 'spectrom-dbcleanup') . $newline;
		$report .= sprintf($bench, __('Posts', 'spectrom-dbcleanup'), number_format($this->_end_benchmarks['posts_time'], 8)) . $newline;
		$report .= sprintf($bench, __('Comments', 'spectrom-dbcleanup'), number_format($this->_end_benchmarks['comments_time'], 8)) . $newline;
		$report .= sprintf($bench, __('Options', 'spectrom-dbcleanup'), number_format($this->_end_benchmarks['options_time'], 8)) . $newline;
		
		$report .= $newline . sprintf($time, number_format(($this->_end_opt - $this->_start_opt), 4)) . $newline;

		// done compiling report

		$emails = $this->_plugin->get_option('emails', get_option('admin_email'));

		// send report to email address
		wp_mail($emails, $subject, $report, $headers);

		remove_filter('wp_mail_content_type', array(__CLASS__, 'html_content_type'));
	}

	/**
	 * Filter callback for setting the content type
	 * @param string $content_type Default content type passed in
	 * @return string The modified content type
	 */
	public static function html_content_type($content_type)
	{
		return ('text/html; charset=iso-8859-1');
	}

	/**
	 * Perform cleanup process
	 * @return array of data that the report is generated from
	 */
	private function do_cleanup()
	{
		$this->_start_opt = microtime(TRUE);

		global $wpdb;
		$this->wpdb = $wpdb;

		$this->_start_benchmarks = $this->_perform_benchmark();

		$this->_remove_posts();
		$this->_remove_comments();
		$this->_remove_usermeta();
		$this->_remove_transients();

		// perform a “SHOW TABLES LIKE '{$wpdb->prefix}%’” to get a list of the database tables in the current database.
		$sql = "SHOW TABLES LIKE '{$wpdb->prefix}%'";
		$rows = $wpdb->get_results($sql, ARRAY_N);
		if ($rows)
			foreach ($rows as $row)
				$table_names[] = $row[0];
		// add the users and usermeta tables, in case of a multisite install
		if (!in_array($wpdb->users, $table_names))
			$table_names[] = $wpdb->users;
		if (!in_array($wpdb->usermeta, $table_names))
			$table_names[] = $wpdb->usermeta;

		// for each table returned from the SHOW TABLES command, run a “OPTIMIZE TABLE `{$table_name}” command. The return value from this command will be saved and built into an email report.
		foreach ($table_names as $table_name) {
			$sql = "OPTIMIZE TABLE `{$table_name}`";
			$rows = $wpdb->get_results($sql);
			$this->_table_results[$table_name] = array();
			if (is_array($rows)) {
				foreach ($rows as $row) {
					// Note, performing an OPTIMIZE TABLE on InnoDB type tables will result in the following message returned from the MySQL server: “Table does not support optimize, doing recreate + analyze instead”. This error is not to be reported to the user in the summary email sent below. Any other error messages are to be reported.
					if ('Table does not support optimize, doing recreate + analyze instead' === $row->Msg_text)
						continue;
					$this->_table_results[$table_name][] = strtoupper($row->Msg_type) . ': ' . $row->Msg_text;
				}
			} else {
				global $EZSQL_ERROR;
				$this->_table_results[$table_name][] = sprintf(__('Error running optimize: %1$s', 'spectrom-dbcleanup'),
					$EZSQL_ERROR[count($EZSQL_ERROR) - 1]['error_str']);
			}
		}

		// get ending benchmarks
		$this->_end_benchmarks = $this->_perform_benchmark();

		// get detailed table data
		$sql = 'SHOW TABLE STATUS';
		$this->table_data = $wpdb->get_results($sql, ARRAY_A);

		$this->_end_opt = microtime(TRUE);
	}

	// optimization methods

	/**
	 * utility method for removing old/trashed post data
	 */
	private function _remove_posts()
	{
		// if the 'remove_posts’ option has a value of 1 (enabled) then the plugin will remove posts.
		if ('1' === $this->_plugin->get_option('remove_posts', '0')) {
			// get a starting count of post records.
			$count_post_start = $this->get_table_count($this->wpdb->posts);

			// remove any posts that are set to a post_status='trash'
			$sql = "DELETE FROM `{$this->wpdb->posts}`
					WHERE `post_status` = 'trash'";
			$ret = $this->wpdb->query($sql);

			// get an ending count of post records.
			$count_post_end = $this->get_table_count($this->wpdb->posts);

			$this->_postsremoved = ($count_post_start - $count_post_end);

			// get a starting count of postmeta records.
			$count_postmeta_start = $this->get_table_count($this->wpdb->postmeta);

			// remove any postmeta records that do not have a valid post_id value that corresponds to an existing post ID.
			$sql = "DELETE `{$this->wpdb->postmeta}`
					FROM `{$this->wpdb->postmeta}`
					LEFT JOIN `{$this->wpdb->posts}` ON `{$this->wpdb->posts}`.`ID` = `{$this->wpdb->postmeta}`.`post_id`
					WHERE `{$this->wpdb->posts}`.`ID` IS NULL";
			$ret = $this->wpdb->query($sql);

			// get an ending count of postmeta records.
			$count_postmeta_end = $this->get_table_count($this->wpdb->postmeta);

			$this->_postmetaremoved = ($count_postmeta_start - $count_postmeta_end);
		}
	}

	/**
	 * Utility method for removing old/trashed commnet data
	 */
	private function _remove_comments()
	{
		// if the 'remove_comments’ option has a value of 1 (enabled) then the plugin will remove comment records.
		if ('1' === $this->_plugin->get_option('remove_comments', '0')) {
			// get a starting count of comment records.
			$count_comments_start = $this->get_table_count($this->wpdb->comments);

			// remove any comments that have a 'comment_approved’ value of 'post-trashed’
			$sql = "DELETE FROM `{$this->wpdb->comments}`
					WHERE `comment_approved` IN ('post-trashed', 'trash')";
			$ret = $this->wpdb->query($sql);

			// remove comments that do not have an associated post
			$sql = "DELETE `{$this->wpdb->comments}`
					FROM `{$this->wpdb->comments}`
					LEFT JOIN `{$this->wpdb->posts}` ON `{$this->wpdb->posts}`.`ID` = `{$this->wpdb->comments}`.`comment_post_ID`
					WHERE `{$this->wpdb->posts}`.`ID` IS NULL";
			$ret = $this->wpdb->query($sql);

			// get an ending count of comment records.
			$count_comments_end = $this->get_table_count($this->wpdb->comments);

			$this->_commentsremoved = ($count_comments_start - $count_comments_end);

			// get a starting count of commentmeta records.
			$count_commentmeta_start = $this->get_table_count($this->wpdb->commentmeta);

			// remove any commentmeta records that do not have a valid comment_parent value that corresponds to an existing comment_ID, or any records that do not have a 'comment_post_ID’ value corresponding to an existing post ID.
			$sql = "DELETE `{$this->wpdb->commentmeta}`
					FROM `{$this->wpdb->commentmeta}`
					LEFT JOIN `{$this->wpdb->comments}` ON `{$this->wpdb->comments}`.`comment_ID` = `{$this->wpdb->commentmeta}`.`comment_id`
					WHERE `{$this->wpdb->comments}`.`comment_ID` IS NULL";
			$ret = $this->wpdb->query($sql);

			// get an ending count of commentmeta records.
			$count_commentmeta_end = $this->get_table_count($this->wpdb->commentmeta);

			$this->_commentmetaremoved = ($count_commentmeta_start - $count_commentmeta_end);
		}
	}

	/**
	 * Utility method for removing orphaned usermeta data
	 */
	private function _remove_usermeta()
	{
		// if the 'remove_usermeta’ option has a value of 1 (enabled) then the plugin will remove any orphaned usermeta records
		if ('1' === $this->_plugin->get_option('remove_usermeta', '0')) {
			// get a starting count of usermeta records.
			$count_usermeta_start = $this->get_table_count($this->wpdb->usermeta);

			// remove any usermeta records where the user_id value does not have a corresponding existing user ID.
			$sql = "DELETE `{$this->wpdb->usermeta}`
					FROM `{$this->wpdb->usermeta}`
					LEFT JOIN `{$this->wpdb->users}` ON `{$this->wpdb->users}`.`ID` = `{$this->wpdb->usermeta}`.`user_id`
					WHERE `{$this->wpdb->users}`.`ID` IS NULL";
			$ret = $this->wpdb->query($sql);

			// get an ending count of usermeta records.
			$count_usermeta_end = $this->get_table_count($this->wpdb->usermeta);

			$this->_usermetaremoved = ($count_usermeta_start - $count_usermeta_end);
		}
	}

	/**
	 * Utility method for removing expired transient data
	 */
	private function _remove_transients()
	{
		if ('1' === $this->_plugin->get_option('remove_expired_transients', '0')) {
			$count_sql = "SELECT COUNT(*) as `cnt`
					FROM `{$this->wpdb->options}`
					WHERE `option_name` LIKE '%_transient_%'";
			$count_transients_start = intval($this->wpdb->get_var($count_sql));

			$expire = strtotime('now') - (60 * 60 * 24);
			$sql = "SELECT REPLACE(`option_name`, 'timeout_', '') AS `trans_name`
					FROM `{$this->wpdb->options}`
					WHERE (`option_name` like '_transient_timeout_%' OR `option_name` LIKE '_site_transient_timeout_%')
						AND `option_value` < {$expire}";
			$res = $this->wpdb->get_results($sql, ARRAY_A);
			$keys = array();
			foreach ($res as $id => $row)
				$keys[] = $row['trans_name'];

			$add_keys = array();
			foreach ($keys as $key)
				$add_keys[] = str_replace('_transient_', '_transient_timeout_', $key);
			$all_keys = array_merge($keys, $add_keys);
			$sql = "DELETE
					FROM `{$this->wpdb->options}`
					WHERE `option_name` IN('" . implode('\',\'', $all_keys) . "')";
			$this->wpdb->query($sql);

			$count_transients_end = intval($this->wpdb->get_var($count_sql));
			$this->_transientsremoved = ($count_transients_start - $count_transients_end);
		}
	}

	// benchmarking operations

	private function _perform_benchmark()
	{
		$start = microtime(TRUE);

		// get count on post table
		$sql = "SELECT COUNT(*) AS `cnt`
				FROM `{$this->wpdb->posts}`
				LEFT JOIN `{$this->wpdb->posts}` AS `p1` ON `p1`.`ID` = `{$this->wpdb->posts}`.`ID`
				LEFT JOIN `{$this->wpdb->posts}` AS `p2` ON `p2`.`ID` = `p1`.`ID`
				LEFT JOIN `{$this->wpdb->posts}` AS `p3` ON `p3`.`ID` = `p2`.`ID`
				LEFT JOIN `{$this->wpdb->posts}` AS `p4` ON `p4`.`ID` = `p3`.`ID`
				LEFT JOIN `{$this->wpdb->posts}` AS `p5` ON `p5`.`ID` = `p4`.`ID`";
		$this->wpdb->query($sql);
		$end_posts = microtime(TRUE);

		// get count on comment table
		$sql = "SELECT COUNT(*) AS `cnt`
				FROM `{$this->wpdb->comments}`
				LEFT JOIN `{$this->wpdb->comments}` AS `c1` ON `c1`.`comment_ID` = `{$this->wpdb->comments}`.`comment_ID`
				LEFT JOIN `{$this->wpdb->comments}` AS `c2` ON `c2`.`comment_ID` = `c1`.`comment_ID`
				LEFT JOIN `{$this->wpdb->comments}` AS `c3` ON `c3`.`comment_ID` = `c2`.`comment_ID`
				LEFT JOIN `{$this->wpdb->comments}` AS `c4` ON `c4`.`comment_ID` = `c3`.`comment_ID`
				LEFT JOIN `{$this->wpdb->comments}` AS `c5` ON `c5`.`comment_ID` = `c4`.`comment_ID`
				LEFT JOIN `{$this->wpdb->comments}` AS `c6` ON `c6`.`comment_ID` = `c5`.`comment_ID`
				LEFT JOIN `{$this->wpdb->comments}` AS `c7` ON `c7`.`comment_ID` = `c6`.`comment_ID`
				LEFT JOIN `{$this->wpdb->comments}` AS `c8` ON `c8`.`comment_ID` = `c7`.`comment_ID`
				LEFT JOIN `{$this->wpdb->comments}` AS `c9` ON `c9`.`comment_ID` = `c8`.`comment_ID`";
		$this->wpdb->query($sql);
		$end_comments = microtime(TRUE);

		// get count on options table
		$sql = "SELECT COUNT(*) AS `cnt`
				FROM `{$this->wpdb->options}`
				LEFT JOIN `{$this->wpdb->options}` AS `o1` ON `o1`.`option_id` = `{$this->wpdb->options}`.`option_id`
				LEFT JOIN `{$this->wpdb->options}` AS `o2` ON `o2`.`option_id` = `o1`.`option_id`
				LEFT JOIN `{$this->wpdb->options}` AS `o3` ON `o3`.`option_id` = `o2`.`option_id`
				LEFT JOIN `{$this->wpdb->options}` AS `o4` ON `o4`.`option_id` = `o3`.`option_id`
				LEFT JOIN `{$this->wpdb->options}` AS `o5` ON `o5`.`option_id` = `o4`.`option_id`
				LEFT JOIN `{$this->wpdb->options}` AS `o6` ON `o6`.`option_id` = `o5`.`option_id`
				LEFT JOIN `{$this->wpdb->options}` AS `o7` ON `o7`.`option_id` = `o6`.`option_id`
				LEFT JOIN `{$this->wpdb->options}` AS `o8` ON `o8`.`option_id` = `o7`.`option_id`
				LEFT JOIN `{$this->wpdb->options}` AS `o9` ON `o9`.`option_id` = `o8`.`option_id`";
		$this->wpdb->query($sql);
		$end_options = microtime(TRUE);

		$ret = array(
			'posts_time' => ($end_posts - $start),
			'comments_time' => ($end_comments - $end_posts),
			'options_time' => ($end_options - $end_comments),
		);
		return ($ret);
	}

	// some helper functions for runnning the optimization operations

	/**
	 * Perform a SELECT COUNT(*) on a table
	 * @param string $table Name of table to perform count on
	 * @return int The number of records counted in the specified table.
	 */
	private function get_table_count($table)
	{
		$sql = 'SELECT COUNT(*) AS `cnt`
				FROM `' . $table . '`';
		return (intval($this->wpdb->get_var($sql)));
	}

	/**
	 * Find the row from the SHOW TABLE STATUS results for the named table
	 * @param string $table Name of the table to lookup
	 * @return array The row of data from the status report
	 */
	private function get_table_status($table)
	{
		foreach ($this->table_data as $idx => $data) {
			if ($data['Name'] === $table)
				return ($data);
		}
		return (NULL);
/*
 * Name
 * Engine
 * Version
 * Row_format
 * Rows
 * Avg_row_length
 * Data_length
 * Max_data_length
 * Index_length
 * Data_free
 * Auto_increment
 * Create_time
 * Update_time
 * Check_time
 * Collation
 * Checksum
 * Create_options
 * Comment
 */
	}
}

SpectrOMDBCleanupCron::get_instance();

// EOF
