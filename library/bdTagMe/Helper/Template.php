<?php

class bdTagMe_Helper_Template
{
	protected static $_defaultBodyText = false;

	public static function helperSnippetSetup()
	{
		self::$_defaultBodyText = XenForo_Template_Helper_Core::$helperCallbacks['bodytext'];
		if (self::$_defaultBodyText[0] === 'self')
		{
			self::$_defaultBodyText[0] = 'XenForo_Template_Helper_Core';
		}

		XenForo_Template_Helper_Core::$helperCallbacks['bodytext'] = array(
			__CLASS__,
			'helperBodyText'
		);
	}

	public static function helperBodyText()
	{
		$args = func_get_args();

		$string = array_shift($args);

		$string = bdTagMe_Engine::getInstance()->renderFacebookAlike($string);

		array_unshift($args, $string);

		return call_user_func_array(self::$_defaultBodyText, $args);
	}

}
