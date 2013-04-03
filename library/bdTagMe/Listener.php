<?php

class bdTagMe_Listener {
	public static function load_class_datawriter($class, array &$extend) {
		if ($class == 'XenForo_DataWriter_DiscussionMessage_Post') {
			$extend[] = 'bdTagMe_DataWriter_DiscussionMessagePost';
		}
	}
	
	public static function load_class($class, array &$extend) {
		// load_class_datawriter is still kept separated to do legacy support
		// I don't want to break people's site when this upgrade this add-on
		static $classes = array(
			'XenForo_BbCode_Formatter_Base',
		);
		
		if (in_array($class, $classes)) {
			$extend[] = 'bdTagMe_' . $class;
		}
	}
	
	public static function template_create($templateName, array &$params, XenForo_Template_Abstract $template) {
		static $preloaded = false;
		if (!$preloaded) {
			$template->preloadTemplate('bdtagme_tag');
			$preloaded = true;
		}
		
		if ($templateName == 'account_alert_preferences') {
			$template->preloadTemplate('bdtagme_account_alert_preferences');
		}
	}
	
	public static function template_hook($hookName, &$contents, array $hookParams, XenForo_Template_Abstract $template) {
		if ($hookName == 'account_alerts_messages_in_threads') {
			$ourTemplate = $template->create('bdtagme_account_alert_preferences', $template->getParams());
			$contents .= $ourTemplate->render();
		}
	}
}