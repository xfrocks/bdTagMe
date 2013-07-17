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
		if (!empty($user['user_group_id']))
		{
			$prefix = XenForo_Application::getOptions()->userTagKeepAt ? '@' : '';

			if ($replaceStyle == 'bb')
			{
				return $prefix . '[USERGROUP=' . $user['user_group_id'] . ']' . $user['username'] . '[/USERGROUP]';
			}
			else
			{
				return $prefix . $user['username'];
			}
		}

		return parent::_replaceTagUserMatch($user, $replaceStyle);
	}

}
