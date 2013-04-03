<?php

class bdTagMe_Engine {
	
	const ERROR_NO_PERMISSION_TO_TAG = 'no_permission_to_tag';
	const ERROR_TOO_MANY_TAGGED = 'too_many_tagged';
	const SIMPLE_CACHE_KEY_TAGGABLE_USER_GROUPS = 'bdTagMe_taggableUserGroups';
	
	// PLEASE UPDATE THE SYMBOL AND REGEX IF YOU CHANGE IT HERE. THE 3 PLACES ARE:
	// xenforo/js/bdTagMe/full/frontend.js
	// xenforo/js/bdTagMe/full/tinymce_plugin.js
	// xenforo/library/bdTagMe/Engine.php
	// normally, there is [ and ] in the regular expression
	// but they have been removed to specifically support [ and ] in username
	// if this regular expression is being reused to detect non-word characters
	// you should consider to add [ and ] into the mix!
	const SYMBOL = '@';
	const REGEX = '/[\s\(\)\.,!\?:;@\\\\{}]/';
	
	protected $_foundTagged = array();
	
	public function notifyTaggedUsers($uniqueId,
		$contentType, $contentId,
		$alertAction, array $ignoredUserIds = array(), XenForo_Model $someRandomModel = null,
		array $viewingUser = null) {
		// this method is kept for legacy support reason...
		$someRandomModel->standardizeViewingUserReference($viewingUser);
		
		return $this->notifyTaggedUsers2($uniqueId,
			$contentType, $contentId, $viewingUser['user_id'], $viewingUser['username'],
			$alertAction, $ignoredUserIds, $someRandomModel,
			$viewingUser);
	}
	
	public function notifyTaggedUsers2($uniqueId,
		$contentType, $contentId, $contentUserId, $contentUserName,
		$alertAction, array $ignoredUserIds = array(), XenForo_Model $someRandomModel = null) {
		if (!empty($this->_foundTagged[$uniqueId])) {
			if ($someRandomModel != null) {
				/* @var $userModel XenForo_Model_User */
				$userModel = $someRandomModel->getModelFromCache('XenForo_Model_User');
			} else {
				/* @var $userModel XenForo_Model_User */
				$userModel = XenForo_Model::create('XenForo_Model_User');
			}
			$taggedUsers = array();
			$neededUserIds = array();
			
			foreach ($this->_foundTagged[$uniqueId] as &$entity) {
				switch ($entity['entity_type']) {
					case 'user':
						$taggedUsers[$entity['user']['user_id']] = &$entity['user'];
						break;
					case 'user_group':
						if (isset($entity['user_group']['userIds'])) {
							$neededUserIds = array_merge($neededUserIds, $entity['user_group']['userIds']);
						}
						break;
				}
			}
			
			// filter $neededUserIds a bit, try to identify user in $taggedUsers if possible
			$neededUserIds = array_unique($neededUserIds);
			foreach (array_keys($neededUserIds) as $i) {
				$neededUserId = $neededUserIds[$i];
				if (isset($taggedUsers[$neededUserId])) {
					unset($neededUserIds[$i]);
				}
			}
			if (!empty($neededUserIds)) {
				// okie, we still need some users now
				// use the user model to get them
				$taggedUsers = array_merge(
					$taggedUsers,
					$userModel->getUsersByIds($neededUserIds, array(
						// as noted in _getEntitiesByPortions, we need to request
						// option and profile for these users too
						'join' => XenForo_Model_User::FETCH_USER_OPTION | XenForo_Model_User::FETCH_USER_PROFILE
					))
				);
			}
			
			// sondh@2012-10-16
			// support sending alerts in batches
			$alertInBatch = bdTagMe_Option::get('alertInBatch');
			if (count($taggedUsers) < 2) {
				$alertInBatch = false;
			}
			
			if ($alertInBatch) {
				$alertModel =  $someRandomModel->getModelFromCache('XenForo_Model_Alert');
				$alertModel->bdTagMe_beginBatch();
			}
			
			foreach ($taggedUsers as &$taggedUser) {
				if ($taggedUser['user_id'] == $contentUserId) continue; // it's stupid to notify one's self
				
				if (!$userModel->isUserIgnored($taggedUser, $contentUserId)
					AND !in_array($taggedUser['user_id'], $ignoredUserIds)
					AND XenForo_Model_Alert::userReceivesAlert($taggedUser, $contentType, $alertAction)
				) {
					XenForo_Model_Alert::alert(
						$taggedUser['user_id'],
						$contentUserId, $contentUserName,
						$contentType, $contentId,
						$alertAction
					);
				}
			}
			
			if ($alertInBatch) {
				$alertModel->bdTagMe_commitBatch();
			}
			
			return true;
		}
		
		return false;
	}
	
	public function issueDwError(XenForo_DataWriter $dw, $messageField, $errorInfo) {
		switch ($errorInfo[0]) {
			case self::ERROR_NO_PERMISSION_TO_TAG:
				$dw->error(new XenForo_Phrase('bdtagme_you_have_no_permission_to_tag'));
				break;
			case self::ERROR_TOO_MANY_TAGGED:
				$dw->error(
					new XenForo_Phrase('bdtagme_you_can_only_tag_x_people', $errorInfo[1]),
					$messageField
				);
				break;
		}
	}
	
	public function searchTextForTagged($uniqueId, &$message, array $options = array(), &$errorInfo = null) {
		// prepare options
		$defaultOptions = array(
			'max'           		=> 10,			// maximum tags in a message, -1 means unlimited
			'mode'          		=> 'custom',	// working mode (url | custom | facebookAlike | dontChange)
			'modeCustomTag' 		=> 'USER',		// custom mode's tag
			'removePrefix'  		=> true,		// remove prefix when render
		
			// sondh@2012-11-05
			// added to support large sites
			'maxUsersPerPortion' 	=> 0,			// maximum number of users to query from db (0 means no limit)			
		);
		$options = XenForo_Application::mapMerge($defaultOptions, $options);
		$this->_validateOptions($options);
		
		// array to store all tagged users
		$tagged = array();
		$taggedUserIds = array();
		
		$foundPortions = $this->_searchTextForPortions($message, $options);
		
		if (!empty($foundPortions)) {
			$entities = $this->_getEntitiesByPortions($foundPortions, $options);
			
			if (!empty($entities)) {
				foreach ($foundPortions as $offset => $portion) {
					$entitySafeText = $this->_getBestMatchedEntitySafeTextForPortion($message, $portion, $offset, $entities);
					
					if (!empty($entitySafeText)) {
						$this->_replacePortionInText($message, $entitySafeText, $offset, $entities[$entitySafeText], $options);
						$tagged[$entitySafeText] = $entities[$entitySafeText];
						
						switch ($tagged[$entitySafeText]['entity_type']) {
							case 'user':
								$taggedUserIds[] = $tagged[$entitySafeText]['user']['user_id'];
								break;
							case 'user_group':
								if (isset($tagged[$entitySafeText]['user_group']['userIds'])) {
									$taggedUserIds = array_merge($taggedUserIds, $tagged[$entitySafeText]['user_group']['userIds']);
								}
								break;
						}
					}
				}
			}
		}
		
		$taggedUserIds = array_unique($taggedUserIds);
		$taggedUsersCount = count($taggedUserIds);
		
		if ($options['max'] != -1 AND $taggedUsersCount > $options['max']) {
			// a limit is set and this message has exceeded that limit
			
			// check for permission to tag
			if ($options['max'] === 0) {
				$errorInfo = array(self::ERROR_NO_PERMISSION_TO_TAG);
				return false;
			} 
			
			$errorInfo = array(
				self::ERROR_TOO_MANY_TAGGED,
				array(
					'max' => $options['max'],
					'count' => $taggedUsersCount,
				)
			);
			return false;
		}
		
		if (isset($this->_foundTagged[$uniqueId])) {
			// this message has been processed before?
			// probably a conflict with some other add-ons
			// we will just merge the data
			// this fix was suggested by Julio Franco@xenforo.com
			// TODO: skip processing?
			$this->_foundTagged[$uniqueId] = array_merge($this->_foundTagged[$uniqueId], $tagged);
		} else {
			$this->_foundTagged[$uniqueId] = $tagged;
		}
		
		return true;
	}
	
	protected function _searchTextForPortions(&$message, array &$options) {
		$offset = 0;
		$found = array();
		
		while(1) {
			// sondh@2012-10-17
			// switched from using preg_match to utf8_strpos
			// because preg_match doesn't report unicode offset properly
			$foundLength = 0; // reset
			$symbolOffset = utf8_strpos($message, self::SYMBOL, $offset);
			
			if ($symbolOffset === false) {
				// could not find any symbol
				break; // this will end the while(1) loop
			}
			
			// check the previous character
			$prevCharIsGood = true;
			if ($symbolOffset > 0) {
				$prevChar = utf8_substr($message, $symbolOffset - 1, 1);
				if (!preg_match(self::REGEX, $prevChar)) {
					// the previous character is a text-character
					// this portion may be part of an email address or something
					$prevCharIsGood = false;
				}
			}
			
			if ($prevCharIsGood) {
				// okie, look for the portion (after the symbol) now...
				$messageLengthMinus1 = utf8_strlen($message) - 1;
				while ($symbolOffset + $foundLength < $messageLengthMinus1) {
					// look for as many text-character as possible
					$tmpChar = utf8_substr($message, $symbolOffset + $foundLength + 1, 1);
					if (!preg_match(self::REGEX, $tmpChar)) {
						// this is a text-character, accept it and continue looking
						$foundLength++;
					} else {
						// non text character, stop looking
						break;
					}
				}
			}
			
			$offset = $symbolOffset + 1; // this is the offset of the first character of the portion
			
			if ($foundLength > 0) {
				if (!$this->_isBetweenUrlTags($message, $offset)
					AND !$this->_isBetweenPlainTags($message, $offset) // since 1.5.2
				) {
					// only saves the portion if it is valid
					// 1. not in between URL tags
					// 2. (to be added)
					
					$portion = utf8_strtolower(utf8_trim(utf8_substr($message, $offset, $foundLength)));
					
					// we removed [ and ] from the 2nd group in the regular expression
					// that will cause problem if somebody use [b]@username[/b]
					// we will now once again look for the '[' character and try to remove it
					$stupidCharacterPos = utf8_strpos($portion, '[');
					if ($stupidCharacterPos !== false AND $stupidCharacterPos > 0) {
						// important: only remove it if it's not the first character!
						// this is very important because if we remove it when it's the first character
						// all those "[GANGSER] username" will become un-tag-able
						// sondh@2012-10-17: this is soooo complicated, took me a while to read
						// the comment and understood what I meant to say!
						$portion = utf8_substr($portion, 0, $stupidCharacterPos);
					}
					
					$found[$offset] = $portion;
				}
			}
		}
		
		// it's easier to process found portions backward
		// (the offset of them won't be changed after search and replace for example)
		// so we are doing it here
		$found = array_reverse($found, true);
		
		return $found;
	}
	
	protected function _replacePortionInText(&$message, $portion, $offset, &$entity, array &$options) {
		switch ($options['mode']) {
			case 'url':
				if ($entity['entity_type'] == 'user') {
					$link = XenForo_Link::buildPublicLink('canonical:members', $entity['user']);
					
					$replacement = "[URL='{$link}']{$entity['entity_text']}[/URL]";
				} else {
					$replacement = $entity['entity_text'];
				}
				
				if ($options['removePrefix']) {
					// removes prefix (subtract 1 from the offset)
					$message = utf8_substr($message, 0, $offset - 1)
								. $replacement
								. utf8_substr($message, $offset + utf8_strlen($portion));
				} else {
					// keeps the prefix
					$message = utf8_substr($message, 0, $offset)
								. $replacement
								. utf8_substr($message, $offset + utf8_strlen($portion));
				}
				break;
				
			case 'custom':
				$replacement = "[{$options['modeCustomTag']}={$entity['entity_id']}]{$entity['entity_text']}[/{$options['modeCustomTag']}]";
				$message = utf8_substr($message, 0, $offset - 1) . $replacement . utf8_substr($message, $offset + utf8_strlen($portion));
				break;
				
			case 'facebookAlike':
				$escaped = $this->_escapeFacebookAlike($entity['entity_text']);
				$replacement = "@[{$entity['entity_id']}:{$escaped}]";
				$message = utf8_substr($message, 0, $offset - 1) . $replacement . utf8_substr($message, $offset + utf8_strlen($portion));
				break;
				
			// case 'dontChange':
				// oops, nothing to do here
				// break;
		}
	}
	
	protected function _isBetweenUrlTags(&$message, $position) {
		// found the nearest [URL before the position
		$posOpen = self::utf8_strripos($message, '[URL', $position - utf8_strlen($message));
		
		if ($posOpen !== false) {
			// there is an open tag before us, checks for close tag
			$posClose = self::utf8_stripos($message, '[/URL]', $posOpen);
			
			if ($posClose === false) {
				// no close tag (?!)
			} else if ($posClose < $position) {
				// there is one but it's also before us
				// that means we are not in between them
			} else {
				// this position is in between 2 URL tags!!!
				return true;
			}
		} else {
			// no URL tag so far
		}
		
		return false;
	}
	
	protected function _isBetweenPlainTags(&$message, $position) {
		// this method is just a simple copy pasta of _isBetweenUrlTags. LOL
		// since 1.5.2
		// found the nearest [PLAIN before the position
		$posOpen = self::utf8_strripos($message, '[PLAIN', $position - utf8_strlen($message));
		
		if ($posOpen !== false) {
			// there is an open tag before us, checks for close tag
			$posClose = self::utf8_stripos($message, '[/PLAIN]', $posOpen);
			
			if ($posClose === false) {
				// no close tag (?!)
			} else if ($posClose < $position) {
				// there is one but it's also before us
				// that means we are not in between them
			} else {
				// this position is in between 2 PLAIN tags!!!
				return true;
			}
		} else {
			// no PLAIN tag so far
		}
		
		return false;
	}
	
	protected function _getEntitiesByPortions(array $portions, array &$options) {
		$db = XenForo_Application::get('db');
		$entities = array();
		
		if (!empty($portions)) {
			$userGroups = $this->getTaggableUserGroups();
			foreach ($userGroups as $userGroup) {
				$tmp = array(
					'entity_type' => 'user_group',
					'entity_id' => 'user_group,' . $userGroup['user_group_id'],
					'entity_text' => $userGroup['title'],
					'entity_safe_text' => utf8_strtolower($userGroup['title']),
					'user_group' => $userGroup,
				);
				
				$entities[$tmp['entity_safe_text']] = $tmp;
			}
			
			$conditions = array();
			foreach ($portions as $portion) {
				$conditions[] = 'username LIKE ' . XenForo_Db::quoteLike($portion, 'r');
			}

			// we have to do manual request here because our conditions use OR operator
			// the query is similar to XenForo_Model_User::getUsersByIds with fetch options
			// similar to join = XenForo_Model_User::FETCH_USER_PROFILE | XenForo_Model_User::FETCH_USER_OPTION
			$records = $db->fetchAll(
				'SELECT user.*, user_option.*, user_profile.*
				FROM `xf_user` AS user
				INNER JOIN `xf_user_option` AS user_option ON (user_option.user_id = user.user_id)
				INNER JOIN `xf_user_profile` AS user_profile ON (user_profile.user_id = user.user_id)
				WHERE ' . implode(' OR ', $conditions)
				. (
					$options['maxUsersPerPortion'] > 0 ?
					'
						AND user_state = \'valid\'
					ORDER BY last_activity DESC
					LIMIT ' . ($options['maxUsersPerPortion'] * count($portions)) . '
					'
					:''
				)
			);
			
			if (!empty($records)) {
				foreach ($records as $record) {
					$tmp = array(
						'entity_type' => 'user',
						'entity_id' => $record['user_id'],
						'entity_text' => $record['username'],
						'entity_safe_text' => utf8_strtolower($record['username']),
						'user' => $record,
					);
					
					if (!isset($entities[$tmp['entity_safe_text']])) {
						// do not overwrite existing entity
						$entities[$tmp['entity_safe_text']] = $tmp;
					}
				}
			}
		}
		
		return $entities;
	}
	
	protected function _getBestMatchedEntitySafeTextForPortion(&$message, $portion, $offset, array &$entities) {
		$foundSafeText = '';
		$foundLength = 0;
		$tmpLength = 0;
		
		// one-word text
		if (isset($entities[$portion])) {
			$foundSafeText = $portion;
			$foundLength = utf8_strlen($foundSafeText);
		}
		
		// multi-word text
		foreach ($entities as $tmpSafeText => &$entity) {
			if (utf8_strpos($tmpSafeText, $portion) === 0) {
				// we found a match, check if the length is better
				$tmpLength = utf8_strlen($tmpSafeText);
				
				$portionInMessage = utf8_strtolower(utf8_substr($message, $offset, $tmpLength));
				if ($portionInMessage !== $tmpSafeText) {
					// the safe text doesn't match the message
					// of course we can't accept it
					// since 1.4.1
					continue;
				}
				
				if ($tmpLength > $foundLength) {
					// the length is good, change it now
					$foundSafeText = $tmpSafeText;
					$foundLength = $tmpLength;
				} elseif ($tmpLength == $foundLength) {
					// additional checks here?
					// ideas: higher message_count, is_follower, etc.
					// TODO: think about this!
				}
			}
		}
		
		return $foundSafeText;
	}
	
	protected function _validateOptions(array &$options) {
		if ($options['mode'] == 'custom' && empty($options['modeCustomTag'])) {
			$options['mode'] = 'url';
		}
		
		$options['max'] = intval($options['max']);
	}
	
	public function renderFacebookAlike($message, array $options = array()) {
		$rendered = $message;
		$offset = 0;
		$entities = array();
		
		// looks for portions in the message
		do {
			if ($matched = preg_match(
				'/@\[(([a-z_]+,)?(\d+)):(([^\\\\\\]]|\\\\\\\\|\\\\])+)\]/', // LOL, I'm so good at this kind of stuff!
				$rendered,
				$matches,
				PREG_OFFSET_CAPTURE,
				$offset
			)) {
				$offset = $matches[0][1];
				$fullMatched = $matches[0][0];
				$entityId = $matches[1][0];
				$entityText = $this->_unEscapeFacebookAlike($matches[4][0]);
				
				if (!empty($entityText)) {
					// IMPORTANT: this kind of processing (user, user_group, etc.)
					// is being done in 2 places bdTagMe_Engine::renderFacebookAlike
					// and bdTagMe_XenForo_BbCode_Formatter_Base::bdTagMe_renderCustom
					// please update both classes if something is changed
					if (is_numeric($entityId)) {
						$entities[$offset] = array(
							'entity_type' => 'user',
							'entity_id' => $entityId,
							'entity_text' => $entityText,
							'fullMatched' => $fullMatched,
						);
					} else {
						$parts = explode(',', $entityId);
						if (count($parts) == 2) {
							switch ($parts[0]) {
								case 'user_group':
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
				
				$offset++; // prevent us from matching the same thing all over again
			}
		} while ($matched);
		
		if (!empty($entities)) {
			// starts render the portions
			$entities = array_reverse($entities, true);
			
			foreach ($entities as $offset => $entity) {
				if (empty($options['plaintext'])) {
					$template = bdTagMe_Template_Helper::createTemplate('bdtagme_tag');
					$template->setParam('entity', $entity);
					$template->setParam('removePrefix', bdTagMe_Option::get('removePrefix'));
					$replacement = $template->render();
				} else {
					$replacement = ''
						. (bdTagMe_Option::get('removePrefix') ? '' : '@')
						. htmlentities($entity['entity_text']);
				}
				
				$rendered = substr($rendered, 0, $offset)
							. $replacement
							. substr($rendered, $offset + strlen($entity['fullMatched']));
			}
		}
		
		return $rendered;
	}
	
	protected function _escapeFacebookAlike($string) {
		return str_replace(array(
			'\\', ']'
		), array(
			'\\\\', '\\]'
		), $string);
	}
	
	protected function _unEscapeFacebookAlike($string) {
		return str_replace(array(
			'\\]', '\\\\'
		), array(
			']', '\\'
		), $string);
	}
	
	public function getTaggableUserGroups() {
		$userGroups = XenForo_Application::getSimpleCacheData(self::SIMPLE_CACHE_KEY_TAGGABLE_USER_GROUPS);
		if (empty($userGroups)) $userGroups = array();
		
		return $userGroups;
	}
	
	public function setTaggableUserGroup(array $userGroup, $isTaggable, XenForo_DataWriter_UserGroup $dw) {
		$taggableUserGroups = $this->getTaggableUserGroups();
		$isChanged = false;
		
		if ($isTaggable) {
			// get users and update into the taggable list
			$taggableUserGroups[$userGroup['user_group_id']] = array(
				'user_group_id' => $userGroup['user_group_id'],
				'title' => $userGroup['title'],
				'userIds' => $dw->getModelFromCache('XenForo_Model_User')->bdTagMe_getUserIdsByUserGroupId($userGroup['user_group_id']),
			);
			$isChanged = true;
		} else {
			// unset this user group if needed
			foreach (array_keys($taggableUserGroups) as $taggableUserGroupId) {
				if ($taggableUserGroupId == $userGroup['user_group_id']) {
					unset($taggableUserGroups[$taggableUserGroupId]);
					$isChanged = true;
				}
			}
		}
		
		if ($isChanged) {
			XenForo_Application::setSimpleCacheData(self::SIMPLE_CACHE_KEY_TAGGABLE_USER_GROUPS, $taggableUserGroups);
		}
	}
	
	public function updateTaggableUserGroups(array $userGroupIds, XenForo_DataWriter_User $dw) {
		$taggableUserGroups = $this->getTaggableUserGroups();
		$isChanged = false;
		
		foreach ($taggableUserGroups as &$taggableUserGroup) {
			if (in_array($taggableUserGroup['user_group_id'], $userGroupIds)) {
				// this user group need to be updated
				$taggableUserGroup['userIds'] = $dw->getModelFromCache('XenForo_Model_User')->bdTagMe_getUserIdsByUserGroupId($taggableUserGroup['user_group_id']);
				$isChanged = true;
			}
		}
		
		if ($isChanged) {
			XenForo_Application::setSimpleCacheData(self::SIMPLE_CACHE_KEY_TAGGABLE_USER_GROUPS, $taggableUserGroups);
		}
	}
	
	public static function utf8_strrpos($haystack, $needle, $offset) {
		if (UTF8_MBSTRING) {
			return mb_strrpos($haystack, $needle, $offset);
		} else {
			return strrpos($haystack, $needle, $offset);
		}
	}
	
	public static function utf8_stripos($haystack, $needle, $offset) {
		if (UTF8_MBSTRING) {
			return mb_stripos($haystack, $needle, $offset);
		} else {
			return stripos($haystack, $needle, $offset);
		}
	}
	
	public static function utf8_strripos($haystack, $needle, $offset) {
		if (UTF8_MBSTRING) {
			return mb_strripos($haystack, $needle, $offset);
		} else {
			return strripos($haystack, $needle, $offset);
		}
	}
	
	/**
	 * @return bdTagMe_Engine
	 */
	public static function getInstance() {
		static $instance = false;
		
		if ($instance === false) {
			$instance = new bdTagMe_Engine();
			// TODO: support code event listeners?
		}
		
		return $instance;
	}
	private function __construct() {}
	private function __clone() {}
}