<?php

class bdTagMe_Option {
	public static function get($key) {
		$options = XenForo_Application::get('options');
		
		switch ($key) {
			case 'modeCustomTag': return $options->get('bdtagme_mode_custom_tag'); // legacy support
			case 'removePrefix': return $options->get('bdtagme_remove_prefix'); // legacy support
		}
		
		return $options->get('bdtagme_' . $key);
	}
}