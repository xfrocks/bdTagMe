<?php

class bdTagMe_Template_Helper {
	
	protected static $_helperBodyTextOriginalCallback = array('XenForo_Template_Helper_Core', 'helperBodyText');
	protected static $_helperSnippetOriginalCallback = array('XenForo_Template_Helper_Core', 'helperSnippet');
	
	protected static $_templateObject = false;
	
	public static function injectMethods() {
		// {xen:helper bodytext, $string}
		self::$_helperBodyTextOriginalCallback = XenForo_Template_Helper_Core::$helperCallbacks['bodytext'];
		if (self::$_helperBodyTextOriginalCallback[0] == 'self') {
			self::$_helperBodyTextOriginalCallback[0] = 'XenForo_Template_Helper_Core';
		}
		XenForo_Template_Helper_Core::$helperCallbacks['bodytext'] = array(__CLASS__, 'helperBodyText');
		
		// {xen:helper snippet, $string, $maxLength, $options}
		self::$_helperSnippetOriginalCallback = XenForo_Template_Helper_Core::$helperCallbacks['snippet'];
		if (self::$_helperSnippetOriginalCallback[0] == 'self') {
			self::$_helperSnippetOriginalCallback[0] = 'XenForo_Template_Helper_Core';
		}
		XenForo_Template_Helper_Core::$helperCallbacks['snippet'] = array(__CLASS__, 'helperSnippet');
		
		// {xen:helper bdTagMe_option, $key}
		XenForo_Template_Helper_Core::$helperCallbacks['bdtagme_option'] = array(__CLASS__, 'helperOption');
	}
	
	public static function helperBodyText($string) {
		$string = call_user_func(self::$_helperBodyTextOriginalCallback, $string);
		
		$engine = bdTagMe_Engine::getInstance();
		$string = $engine->renderFacebookAlike($string);
		
		return $string;
	}
	
	public static function helperSnippet($string, $maxLength = 0, array $options = array()) {
		$string = call_user_func(self::$_helperSnippetOriginalCallback, $string, $maxLength, $options);
		
		$engine = bdTagMe_Engine::getInstance();
		$string = $engine->renderFacebookAlike($string, array('plaintext' => true));
		
		return $string;
	}
	
	public static function helperOption($key) {
		return bdTagMe_Option::get($key);
	}
	
	public static function saveTemplate(XenForo_Template_Abstract $template) {
		if (self::$_templateObject === false) {
			self::$_templateObject = $template;
		}
	}
	
	public static function createTemplate($templateName) {
		if (self::$_templateObject === false) {
			// out of luck :(
			return new XenForo_Template_Public($templateName);
		} else {
			return self::$_templateObject->create($templateName, self::$_templateObject->getParams());
		}
	}
}