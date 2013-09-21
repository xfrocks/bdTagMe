<?php

class bdTagMe_Engine
{

	const SIMPLE_CACHE_KEY_TAGGABLE_USER_GROUPS = 'bdTagMe_taggableUserGroups';

	const OPTION_MAX_TAGGED_USERS = 'maxTaggedUsers';

	public function notifyTaggedUsers3($contentType, $contentId, $contentUserId, $contentUserName, $alertAction, $taggedUsers, array $noAlertUserIds = array(), array $noEmailUserIds = array(), XenForo_Model $someRandomModel = null, array $options = array())
	{
		$options = array_merge(array(self::OPTION_MAX_TAGGED_USERS => bdTagMe_Option::get('max')), $options);

		if ($someRandomModel != null)
		{
			/* @var $userModel XenForo_Model_User */
			$userModel = $someRandomModel->getModelFromCache('XenForo_Model_User');
		}
		else
		{
			/* @var $userModel XenForo_Model_User */
			$userModel = XenForo_Model::create('XenForo_Model_User');
		}

		$neededUserIds = array();
		$taggableUserGroups = $this->getTaggableUserGroups();

		foreach ($taggedUsers as $taggedUserId => $taggedUserName)
		{
			if (is_numeric($taggedUserId))
			{
				$neededUserIds[] = $taggedUserId;
			}
			else
			{
				if (strpos($taggedUserId, 'ug_') === 0)
				{
					$userGroupId = substr($taggedUserId, 3);

					if (isset($taggableUserGroups[$userGroupId]))
					{
						$neededUserIds += array_keys($taggableUserGroups[$userGroupId]['userIds']);
					}
				}
			}
		}

		if (!empty($neededUserIds))
		{
			if ($options[self::OPTION_MAX_TAGGED_USERS] > 0)
			{
				// there are limit of maximum tagged users
				$neededUserIds = array_slice($neededUserIds, 0, $options[self::OPTION_MAX_TAGGED_USERS], true);
			}

			$users = $userModel->getUsersByIds($neededUserIds, array('join' => XenForo_Model_User::FETCH_USER_OPTION | XenForo_Model_User::FETCH_USER_PROFILE,
				// 'nodeIdPermissions' => $thread['node_id']
			));
		}
		else
		{
			$users = array();
		}

		foreach ($users as $user)
		{
			if ($user['user_id'] == $contentUserId)
			{
				// it's stupid to notify one's self
				continue;
			}

			if (!$userModel->isUserIgnored($user, $contentUserId))
			{
				$shouldAlert = true;
				if (!XenForo_Model_Alert::userReceivesAlert($user, $contentType, $alertAction))
				{
					$shouldAlert = false;
				}
				if (in_array($user['user_id'], $noAlertUserIds) || in_array($user['user_id'], $noEmailUserIds))
				{
					$shouldAlert = false;
				}

				if ($shouldAlert)
				{
					XenForo_Model_Alert::alert($user['user_id'], $contentUserId, $contentUserName, $contentType, $contentId, $alertAction);
				}

				if (bdTagMe_Option::get('alertEmail') && !empty($user['bdtagme_email']))
				{
					$shouldEmail = true;
					if (in_array($user['user_id'], $noEmailUserIds))
					{
						$shouldEmail = false;
					}

					if ($shouldEmail)
					{
						$viewLink = $someRandomModel->getModelFromCache('XenForo_Model_Alert')->bdTagMe_getContentLink($contentType, $contentId);

						if (!empty($viewLink))
						{
							$mail = XenForo_Mail::create('bdtagme_tagged', array(
								'sender' => array(
									'user_id' => $contentUserId,
									'username' => $contentUserName
								),
								'receiver' => $user,
								'contentType' => $contentType,
								'contentId' => $contentId,
								'viewLink' => $viewLink,
							), $user['language_id']);

							$mail->enableAllLanguagePreCache();
							$mail->queue($user['email'], $user['username']);
						}
					}
				}
			}
		}

		return true;
	}

	public function getTaggableUserGroups()
	{
		$userGroups = XenForo_Application::getSimpleCacheData(self::SIMPLE_CACHE_KEY_TAGGABLE_USER_GROUPS);
		if (empty($userGroups))
			$userGroups = array();

		return $userGroups;
	}

	public function setTaggableUserGroup(array $userGroup, $isTaggable, XenForo_DataWriter_UserGroup $dw)
	{
		$taggableUserGroups = $this->getTaggableUserGroups();
		$isChanged = false;

		if ($isTaggable)
		{
			// get users and update into the taggable list
			$taggableUserGroups[$userGroup['user_group_id']] = array(
				'user_group_id' => $userGroup['user_group_id'],
				'title' => $userGroup['title'],
				'userIds' => $dw->getModelFromCache('XenForo_Model_User')->bdTagMe_getUserIdsByUserGroupId($userGroup['user_group_id']),
			);
			$isChanged = true;
		}
		else
		{
			// unset this user group if needed
			foreach (array_keys($taggableUserGroups) as $taggableUserGroupId)
			{
				if ($taggableUserGroupId == $userGroup['user_group_id'])
				{
					unset($taggableUserGroups[$taggableUserGroupId]);
					$isChanged = true;
				}
			}
		}

		if ($isChanged)
		{
			XenForo_Application::setSimpleCacheData(self::SIMPLE_CACHE_KEY_TAGGABLE_USER_GROUPS, $taggableUserGroups);
		}
	}

	public function updateTaggableUserGroups(array $userGroupIds, XenForo_DataWriter_User $dw)
	{
		$taggableUserGroups = $this->getTaggableUserGroups();
		$isChanged = false;

		foreach ($taggableUserGroups as &$taggableUserGroup)
		{
			if (in_array($taggableUserGroup['user_group_id'], $userGroupIds))
			{
				// this user group need to be updated
				$taggableUserGroup['userIds'] = $dw->getModelFromCache('XenForo_Model_User')->bdTagMe_getUserIdsByUserGroupId($taggableUserGroup['user_group_id']);
				$isChanged = true;
			}
		}

		if ($isChanged)
		{
			XenForo_Application::setSimpleCacheData(self::SIMPLE_CACHE_KEY_TAGGABLE_USER_GROUPS, $taggableUserGroups);
		}
	}

	public function renderFacebookAlike($message)
	{
		$rendered = $message;
		$offset = 0;
		$entities = array();

		do
		{
			// LOL, I'm so good at this kind of stuff!
			if ($matched = preg_match('/@\[(([a-z_]+,)?(\d+)):(([^\\\\\\]]|\\\\\\\\|\\\\])+)\]/', $rendered, $matches, PREG_OFFSET_CAPTURE, $offset))
			{
				$offset = $matches[0][1];
				$fullMatched = $matches[0][0];
				$entityId = $matches[1][0];
				$entityText = self::unEscapeFacebookAlike($matches[4][0]);

				if (!empty($entityText))
				{
					if (is_numeric($entityId))
					{
						$entities[$offset] = array(
							'entity_type' => 'user',
							'entity_id' => $entityId,
							'entity_text' => $entityText,
							'fullMatched' => $fullMatched,
						);
					}
					else
					{
						$parts = explode(',', $entityId);
						if (count($parts) == 2)
						{
							switch ($parts[0])
							{
								case 'usergroup':
									$entities[$offset] = array(
										'entity_type' => $parts[0],
										'entity_id' => $entityId,
										'entity_text' => $entityText,
										'fullMatched' => $fullMatched,
									);
									break;
								default:
								// do not process unknown entity type
							}
						}
					}
				}

				// prevent us from matching the same thing all over again
				$offset++;
			}
		}
		while ($matched);

		if (!empty($entities))
		{
			// starts render the portions
			$entities = array_reverse($entities, true);

			$prefix = XenForo_Application::getOptions()->userTagKeepAt ? '@' : '';

			foreach ($entities as $offset => $entity)
			{
				$replacement = $prefix . htmlentities($entity['entity_text']);

				$rendered = substr($rendered, 0, $offset) . $replacement . substr($rendered, $offset + strlen($entity['fullMatched']));
			}
		}

		return $rendered;
	}

	public static function escapeFacebookAlike($string)
	{
		return str_replace(array(
			'\\',
			']'
		), array(
			'\\\\',
			'\\]'
		), $string);
	}

	public static function unEscapeFacebookAlike($string)
	{
		return str_replace(array(
			'\\]',
			'\\\\'
		), array(
			']',
			'\\'
		), $string);
	}

	/**
	 * @return bdTagMe_Engine
	 */
	public static function getInstance()
	{
		static $instance = false;

		if ($instance === false)
		{
			$instance = new bdTagMe_Engine();
			// TODO: support code event listeners?
		}

		return $instance;
	}

	private function __construct()
	{
	}

	private function __clone()
	{
	}

}
