<?php

class bdTagMe_Listener
{
	public static function load_class($class, array &$extend)
	{
		// load_class_datawriter is still kept separated to do legacy support
		// I don't want to break people's site when this upgrade this add-on
		static $classes = array(
			'bdAlerts_Model_Mail',

			'XenForo_BbCode_Formatter_Base',
			'XenForo_BbCode_Formatter_HtmlEmail',
			'XenForo_BbCode_Formatter_Text',

			'XenForo_ControllerAdmin_UserGroup',
			'XenForo_ControllerPublic_Account',
			'XenForo_ControllerPublic_Member',

			'XenForo_DataWriter_DiscussionMessage_Post',
			'XenForo_DataWriter_DiscussionMessage_ProfilePost',
			'XenForo_DataWriter_ProfilePostComment',
			'XenForo_DataWriter_User',
			'XenForo_DataWriter_UserGroup',

			'XenForo_Model_Alert',
			'XenForo_Model_ForumWatch',
			'XenForo_Model_Post',
			'XenForo_Model_ProfilePost',
			'XenForo_Model_User',
			'XenForo_Model_UserTagging',

			'XenForo_Route_Prefix_Members',
		);

		if (in_array($class, $classes))
		{
			$extend[] = 'bdTagMe_' . $class;
		}
	}

	public static function init_dependencies(XenForo_Dependencies_Abstract $dependencies, array $data)
	{
		bdTagMe_Helper_Template::helperSnippetSetup();
	}

	public static function template_create($templateName, array &$params, XenForo_Template_Abstract $template)
	{
		switch ($templateName)
		{
			case 'account_alert_preferences':
				$template->preloadTemplate('bdtagme_account_alerts_messages_on_profile_pages');
				break;
			case 'account_contact_details':
				$template->preloadTemplate('bdtagme_account_contact_details_messaging');
				break;

			case 'PAGE_CONTAINER':
				if (!bdTagMe_Option::get('skipGlobalJs'))
				{
					$template->preloadTemplate('bdtagme_' . $templateName);
				}
				break;
		}
	}

	public static function template_hook($hookName, &$contents, array $hookParams, XenForo_Template_Abstract $template)
	{
		switch ($hookName)
		{
			case 'account_alerts_messages_on_profile_pages':
			case 'account_contact_details_messaging':
				$ourTemplate = $template->create('bdtagme_' . $hookName, $template->getParams());
				$contents .= $ourTemplate->render();
				break;

			case 'body':
				if (!bdTagMe_Option::get('skipGlobalJs'))
				{
					$ourTemplate = $template->create('bdtagme_PAGE_CONTAINER', $template->getParams());
					$contents .= $ourTemplate->render();
				}
				break;
		}
	}

	public static function file_health_check(XenForo_ControllerAdmin_Abstract $controller, array &$hashes)
	{
		$hashes += bdTagMe_FileSums::getHashes();
	}

}
