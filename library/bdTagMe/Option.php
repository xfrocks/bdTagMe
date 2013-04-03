<?php

class bdTagMe_Option {
	public static function get($key) {
		$options = XenForo_Application::get('options');
		
		switch ($key) {
			case 'max':
				// sondh@2012-10-14
				// since 1.6, this add-on dropped the system wide option bdtagme_max
				// and use permission system to determine the maximum number of tag a user can use
				// so all request for the old max option will be processed here and return
				// the permission value of the current visitor
				$visitor = XenForo_Visitor::getInstance();
				return $visitor->hasPermission('general', 'bdtagme_max');
				break;
			case 'groupTag':
				$visitor = XenForo_Visitor::getInstance();
				return $visitor->hasPermission('general', 'bdtagme_groupTag');
				break;
			case 'modeCustomTag': return $options->get('bdtagme_mode_custom_tag'); // legacy support
			case 'removePrefix': return $options->get('bdtagme_remove_prefix'); // legacy support
		}
		
		return $options->get('bdtagme_' . $key);
	}
}