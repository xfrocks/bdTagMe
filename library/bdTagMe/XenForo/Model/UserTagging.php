<?php

class bdTagMe_XenForo_Model_UserTagging extends XFCP_bdTagMe_XenForo_Model_UserTagging
{
	protected function _getTagMatchUsers(array $matches)
	{
		$usersByMatch = parent::_getTagMatchUsers($matches);

		if (bdTagMe_Option::get('groupTag'))
		{
			$engine = bdTagMe_Engine::getInstance();
			$taggableUserGroups = $engine->getTaggableUserGroups();

			$matchesToLower = array();
			foreach ($matches as $key => $match)
			{
				$matchesToLower[$key] = utf8_strtolower($match[1][0]);
			}

			$userGroupTitlesToLower = array();
			foreach ($taggableUserGroups as $taggableUserGroup)
			{
				$userGroupTitlesToLower[$taggableUserGroup['user_group_id']] = utf8_strtolower($taggableUserGroup['title']);
			}

			foreach ($userGroupTitlesToLower as $userGroupId => $userGroupTitleToLower)
			{
				foreach ($matchesToLower as $matchKey => $matchToLower)
				{
					if (strpos($userGroupTitleToLower, $matchToLower) === 0)
					{
						$userGroupInfo = array(
							'user_id' => 'ug_' . $userGroupId,
							'username' => $taggableUserGroups[$userGroupId]['title'],
							'lower' => strtolower($taggableUserGroups[$userGroupId]['title']),
							'user_group_id' => $userGroupId,
						);
						$usersByMatch[$matchKey][$userGroupInfo['user_id']] = $userGroupInfo;
					}
				}
			}
		}

		return $usersByMatch;
	}

	protected function _replaceTagUserMatch(array $user, $replaceStyle)
	{
		$prefix = XenForo_Application::getOptions()->userTagKeepAt ? '@' : '';

		if (!empty($user['user_group_id']))
		{
			// user group tagging
			if ($replaceStyle == 'bb')
			{
				return call_user_func_array('sprintf', array(
					'%s[USERGROUP=%s]%s[/USERGROUP]',
					$prefix,
					$user['user_group_id'],
					$user['username'],
				));
			}
			elseif ($replaceStyle == 'facebookAlike')
			{
				return call_user_func_array('sprintf', array(
					'%s[usergroup,%s:%s]',
					$prefix,
					$user['user_group_id'],
					bdTagMe_Engine::escapeFacebookAlike($user['username']),
				));
			}
			else
			{
				return $prefix . $user['username'];
			}
		}
		else
		{
			// user tagging
			if ($replaceStyle == 'facebookAlike')
			{
				return call_user_func_array('sprintf', array(
					'%s[%s:%s]',
					$prefix,
					$user['user_id'],
					bdTagMe_Engine::escapeFacebookAlike($user['username']),
				));
			}
		}

		return parent::_replaceTagUserMatch($user, $replaceStyle);
	}

}
