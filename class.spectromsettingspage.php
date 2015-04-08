<?php

if (!class_exists('SpectrOMSettingsPage')) {
class SpectrOMSettingsPage
{
	public function __construct()
	{
		echo '<div class="spectrom-page-wrap" xstyle="border:1px solid red">';
			echo '<div class="spectrom-mkt-right" xstyle="border:1px solid black">';
			echo '<div class="spectrom-logo"></div>';
			echo __('By: ', 'spectrom'), '<a href="http://SpectrOMtech.com" alt="SpectrOM Technologies" title="SpectrOM Technologies" target="_spectrom">SpectrOMtech.com</a><br/>';
			echo '</div>';

			echo '<div class="spectrom-page">';
			do_action('spectrom_page');
			echo '</div>';
			echo '&nbsp;';

			echo '<div style="clear:both"></div>';
		echo '</div>';
	}
}
} // class_exists

// EOF