<?php

class bdTagMe_Listener {
	public static function load_class($class, array &$extend) {
		// load_class_datawriter is still kept separated to do legacy support
		// I don't want to break people's site when this upgrade this add-on
		static $classes = array(
			'XenForo_BbCode_Formatter_Base',
		
			'XenForo_DataWriter_DiscussionMessage_Post',
			'XenForo_DataWriter_DiscussionMessage_ProfilePost',
			'XenForo_DataWriter_ProfilePostComment',
		
			'XenForo_Model_Post',
			'XenForo_Model_ProfilePost',
		
			'XenForo_ViewPublic_Member_Post',
		);
		
		if (in_array($class, $classes)) {
			$extend[] = 'bdTagMe_' . $class;
		}
	}
	
	public static function init_dependencies(XenForo_Dependencies_Abstract $dependencies, array $data) {
		bdTagMe_Template_Helper::injectMethods();
	}
	
	public static function template_create($templateName, array &$params, XenForo_Template_Abstract $template) {
		static $preloaded = false;
		if (!$preloaded) {
			$template->preloadTemplate('bdtagme_tag');
			
			bdTagMe_Template_Helper::saveTemplate($template);
			
			$preloaded = true;
		}
		
		switch ($templateName) {
			case 'account_alert_preferences':
				$template->preloadTemplate('bdtagme_account_alerts_messages_in_threads');
				$template->preloadTemplate('bdtagme_account_alerts_messages_on_profile_pages');
				break;
				
			case 'editor':
			case 'PAGE_CONTAINER':
				$template->preloadTemplate('bdtagme_' . $templateName);
				break;
		}
	}
	
	public static function template_hook($hookName, &$contents, array $hookParams, XenForo_Template_Abstract $template) {
		switch ($hookName) {
			case 'account_alerts_messages_in_threads':
			case 'account_alerts_messages_on_profile_pages':
				$ourTemplate = $template->create('bdtagme_' . $hookName, $template->getParams());
				$contents .= $ourTemplate->render();
				break;
			case 'editor_tinymce_init':
				$search = 'plugins: plugins';
				$replace = 'plugins: plugins + \',-xenforo_bdtagme\'';
				
				$contents = str_replace($search, $replace, $contents);
				break;
		}
	}
	
	public static function template_post_render($templateName, &$content, array &$containerData, XenForo_Template_Abstract $template) {
		switch ($templateName) {
			case 'editor':
			case 'PAGE_CONTAINER':
				$ourTemplate = $template->create('bdtagme_' . $templateName, $template->getParams());
				$content .= $ourTemplate->render();
				break;
		}
	}
	
	public static function file_health_check(XenForo_ControllerAdmin_Abstract $controller, array &$hashes) {
		$ourHashes = bdTagMe_FileSums::getHashes();
		
		foreach ($ourHashes as $filePath => $hash) {
			$hashes[$filePath] = $hash;
		}
	}
}