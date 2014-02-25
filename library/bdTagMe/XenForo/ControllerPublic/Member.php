<?php

class bdTagMe_XenForo_ControllerPublic_Member extends XFCP_bdTagMe_XenForo_ControllerPublic_Member
{

	public function actionIndex()
	{
		$ug = $this->_input->filterSingle('ug', XenForo_Input::STRING);

		if (empty($ug))
		{
			// this is not our request, let the parent handle it
			return parent::actionIndex();
		}

		return $this->responseReroute('XenForo_ControllerPublic_Member', 'bdtagme-usergroup-tagged');
	}

	public function actionBdtagmeUsergroupTagged()
	{
		$userGroupId = $this->_input->filterSingle('ug', XenForo_Input::STRING);

		$engine = bdTagMe_Engine::getInstance();
		$taggableUserGroups = $engine->getTaggableUserGroups();

		if (!isset($taggableUserGroups[$userGroupId]))
		{
			// hmm, the requested user group is not taggable...
			return $this->responseNoPermission();
		}
		$userGroup = $taggableUserGroups[$userGroupId];

		if (isset($userGroup['userIds']))
		{
			$users = $this->getModelFromCache('XenForo_Model_User')->getUsersByIds(array_keys($userGroup['userIds']));
		}
		else
		{
			$users = array();
		}

		$viewParams = array(
			'users' => $users,
			'userGroup' => $userGroup,
		);

		return $this->responseView('bdTagMe_ViewPublic_Member_Tagged_UserGroup', 'bdtagme_members_tagged_user_group', $viewParams);
	}

	public function actionBdtagmeFind()
	{
		$response = parent::actionFind();

		if ($response instanceof XenForo_ControllerResponse_View)
		{
			$users = &$response->params['users'];
			$q = utf8_strtolower($this->_input->filterSingle('q', XenForo_Input::STRING));

			if (!empty($q) AND bdTagMe_Option::get('groupTag'))
			{
				$userGroups = bdTagMe_Engine::getInstance()->getTaggableUserGroups();

				$userGroupTitlesToLower = array();
				foreach ($userGroups as $userGroup)
				{
					$userGroupTitlesToLower[$userGroup['user_group_id']] = utf8_strtolower($userGroup['title']);
				}

				foreach ($userGroupTitlesToLower as $userGroupId => $userGroupTitleToLower)
				{
					if (strpos($userGroupTitleToLower, $q) === 0)
					{
						// run extra check to eliminate users with matching username with this user group
						foreach (array_keys($users) as $userId)
						{
							if (utf8_strtolower($users[$userId]['username']) == $userGroupTitleToLower)
							{
								unset($users[$userId]);
							}
						}

						array_unshift($users, array(
							'user_id' => -1,
							'username' => $userGroups[$userGroupId]['title'],
							'gravatar' => bdTagMe_Option::get('userGroupGravatar'),
						));
					}
				}
			}
		}

		return $response;
	}

}
