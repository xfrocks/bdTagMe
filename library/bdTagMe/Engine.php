<?php

class bdTagMe_Engine {
	
	const ERROR_TOO_MANY_TAGGED = 'too_many_tagged';
	
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
		if (isset($this->_foundTagged[$uniqueId])) {
			/* @var $userModel XenForo_Model_User */
			$userModel = $someRandomModel->getModelFromCache('XenForo_Model_User');
			
			foreach ($this->_foundTagged[$uniqueId] as &$taggedUser) {
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
			
			return true;
		}
		
		return false;
	}
	
	public function searchTextForTagged($uniqueId, &$message, array $options = array(), &$errorInfo = null) {
		// prepare options
		$defaultOptions = array(
			'max'           => 10,       // maximum tags in a message
			'mode'          => 'custom', // working mode (url | custom | facebookAlike | dontChange)
			'modeCustomTag' => 'USER',   // custom mode's tag
			'removePrefix'  => true,     // remove prefix when render
		);
		$options = XenForo_Application::mapMerge($defaultOptions, $options);
		$this->_validateOptions($options);

		// array to store all tagged users
		$tagged = array();
		
		$foundPortions = $this->_searchTextForPortions($message, $options);
		
		if (!empty($foundPortions)) {
			$users = $this->_getUsersByPortions($foundPortions);
			
			if (!empty($users)) {
				foreach ($foundPortions as $offset => $portion) {
					$userName = $this->_getBestMatchedUserNameForPortion($message, $portion, $offset, $users);
					
					if (!empty($userName)) {
						$this->_replacePortionInText($message, $userName, $offset, $users[$userName], $options);
						$tagged[$userName] = $users[$userName];
					}
				}
			}
		}
		
		if (!empty($options['max']) AND count($tagged) > $options['max']) {
			// a limit is set and this message has exceeded that limit
			$errorInfo = array(
				self::ERROR_TOO_MANY_TAGGED,
				array(
					'max' => $max,
					'count' => count($tagged),
				)
			);
			return false;
		}
		
		$this->_foundTagged[$uniqueId] = $tagged;
		
		return true;
	}
	
	protected function _searchTextForPortions(&$message, array &$options) {
		$offset = 0;
		$found = array();
		
		do {
			// PLEASE UPDATE THE REGULAR EXPRESSION IN JAVASCRIPT IF YOU CHANGE IT HERE (3 PLACES)
			// normally, there is [ and ] in the regular expression
			// but they have been removed to specifically support [ and ] in username
			// if this regular expression is being reused to detect non-word characters
			// you should consider to add [ and ] into the mix!
			if ($matched = preg_match(
				'/(\s|^|\])@([^\s\(\)\.,!\?:;@\\\\{}]+)/',
				$message,
				$matches,
				PREG_OFFSET_CAPTURE,
				$offset
			)) {
				$offset = $matches[2][1];
				
				if (!$this->_isBetweenUrlTags($message, $offset)
					AND !$this->_isBetweenPlainTags($message, $offset) // since 1.5.2
				) {
					// only saves the portion if it is valid
					// 1. not in between URL tags
					// 2. (to be added)
					
					$text = strtolower(trim($matches[2][0])); // normalize it a little bit
					
					// we removed [ and ] from the 2nd group in the regular expression
					// that will cause problem if somebody use [b]@username[/b]
					// we will now once again look for the '[' character and try to remove it
					$stupidCharacterPos = strpos($text, '[');
					if ($stupidCharacterPos !== false AND $stupidCharacterPos > 0) {
						// important: only remove it if it's not the first character!
						// this is very important because if we remove it when it's the first character
						// all those "[GANGSER] username" will become un-tag-able
						$text = substr($text, 0, $stupidCharacterPos);
					}
					
					$found[$offset] = $text; // please note: this offset doesn't include the prefix '@'
				}
			}
		} while ($matched);
		// it's easier to process found portions backward
		// (the offset of them won't be changed after search and replace for example)
		// so we are doing it here
		$found = array_reverse($found, true);
		
		return $found;
	}
	
	protected function _replacePortionInText(&$message, $portion, $offset, &$user, array &$options) {
		switch ($options['mode']) {
			case 'url':
				$replacement = "[URL='{$user['link']}']{$user['username']}[/URL]";
				
				if ($options['removePrefix']) {
					// removes prefix (subtract 1 from the offset)
					$message = substr($message, 0, $offset - 1)
								. $replacement
								. substr($message, $offset + strlen($portion));
				} else {
					// keeps the prefix
					$message = substr($message, 0, $offset)
								. $replacement
								. substr($message, $offset + strlen($portion));
				}
				break;
				
			case 'custom':
				$replacement = "[{$options['modeCustomTag']}={$user['user_id']}]{$user['username']}[/{$options['modeCustomTag']}]";
				$message = substr($message, 0, $offset - 1) . $replacement . substr($message, $offset + strlen($portion));
				break;
				
			case 'facebookAlike':
				$escaped = $this->_escapeFacebookAlike($user['username']);
				$replacement = "@[{$user['user_id']}:{$escaped}]";
				$message = substr($message, 0, $offset - 1) . $replacement . substr($message, $offset + strlen($portion));
				break;
				
			// case 'dontChange':
				// oops, nothing to do here
				// break;
		}
	}
	
	protected function _isBetweenUrlTags(&$message, $position) {
		// found the nearest [URL before the position
		$posOpen = strripos($message, '[URL', $position - strlen($message));
		
		if ($posOpen !== false) {
			// there is an open tag before us, checks for close tag
			$posClose = stripos($message, '[/URL]', $posOpen);
			
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
		$posOpen = strripos($message, '[PLAIN', $position - strlen($message));
		
		if ($posOpen !== false) {
			// there is an open tag before us, checks for close tag
			$posClose = stripos($message, '[/PLAIN]', $posOpen);
			
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
	
	protected function _getUsersByPortions(array $portions) {
		$db = XenForo_Application::get('db');
		$users = array();
		
		if (!empty($portions)) {
			$conditions = array();
			foreach ($portions as $portion) {
				$conditions[] = 'username LIKE ' . XenForo_Db::quoteLike($portion, 'r');
			}

			$records = $db->fetchAll(
				'SELECT user.*, user_option.*, user_profile.*
				FROM `xf_user` AS user
				INNER JOIN `xf_user_option` AS user_option ON (user_option.user_id = user.user_id)
				INNER JOIN `xf_user_profile` AS user_profile ON (user_profile.user_id = user.user_id)
				WHERE ' . implode(' OR ', $conditions)
			);
			
			if (!empty($records)) {
				foreach ($records as $record) {
					$record['link'] = XenForo_Link::buildPublicLink('full:members', $record);
					$users[strtolower($record['username'])] = $record;
				}
			}
		}
		
		return $users;
	}
	
	protected function _getBestMatchedUserNameForPortion(&$message, $portion, $offset, array &$users) {
		$userName = '';
		$userNameLength = 0;
		$tmpLength = 0;
		
		// one-word username
		if (isset($users[$portion])) {
			$userName = $portion;
			$userNameLength = strlen($userName);
		}
		
		// multi-word username
		foreach ($users as $tmpUserName => &$user) {
			if (strpos($tmpUserName, $portion) === 0) {
				// we found a match, check if the length is better
				$tmpLength = strlen($tmpUserName);
				
				$portionInMessage = strtolower(substr($message, $offset, $tmpLength));
				if ($portionInMessage !== $tmpUserName) {
					// the username doesn't match the message
					// of course we can't accept it
					// since 1.4.1
					continue;
				}
				
				if ($tmpLength > $userNameLength) {
					// the length is good, change it now
					$userName = $tmpUserName;
					$userNameLength = $tmpLength;
				} elseif ($tmpLength == $userNameLength) {
					// additional checks here?
					// ideas: higher message_count, is_follower, etc.
					// TODO: think about this!
				}
			}
		}
		
		return $userName;
	}
	
	protected function _validateOptions(array &$options) {
		if ($options['mode'] == 'custom' && empty($options['modeCustomTag'])) {
			$options['mode'] = 'url';
		}
	}
	
	public function renderFacebookAlike($message, array $options = array()) {
		$rendered = $message;
		$offset = 0;
		$taggedUsers = array();
		
		// looks for portions in the message
		do {
			if ($matched = preg_match(
				'/@\[(\d+?):(([^\\\\\\]]|\\\\\\\\|\\\\])+?)\]/', // LOL, I'm so good at this kind of stuff!
				$rendered,
				$matches,
				PREG_OFFSET_CAPTURE,
				$offset
			)) {
				$offset = $matches[0][1];
				$fullMatched = $matches[0][0];
				$userId = $matches[1][0];
				$userName = $this->_unEscapeFacebookAlike($matches[2][0]);
				
				if (is_numeric($userId) AND !empty($userName)) {
					$taggedUsers[$offset] = array('user_id' => $userId, 'username' => $userName, 'fullMatched' => $fullMatched);
				}
				
				$offset++; // keep us from matching the same thing all over again
			}
		} while ($matched);
		
		if (!empty($taggedUsers)) {
			// starts render the portions
			$taggedUsers = array_reverse($taggedUsers, true);
			
			foreach ($taggedUsers as $offset => $taggedUser) {
				if (empty($options['plaintext'])) {
					$template = bdTagMe_Template_Helper::createTemplate('bdtagme_tag');
					$template->setParam('userName', $taggedUser['username']);
					$template->setParam('link', XenForo_Link::buildPublicLink('members', $taggedUser));
					$template->setParam('removePrefix', bdTagMe_Option::get('removePrefix'));
					$replacement = $template->render();
				} else {
					$replacement = (bdTagMe_Option::get('removePrefix') ? '' : '@') . $taggedUser['username'];
				}
				
				$rendered = substr($rendered, 0, $offset)
							. $replacement
							. substr($rendered, $offset + strlen($taggedUser['fullMatched']));
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
	
	protected static $_instance = false;
	
	/**
	 * @return bdTagMe_Engine
	 */
	public static final function getInstance() {
		if (self::$_instance === false) {
			self::$_instance = new bdTagMe_Engine();
		}
		
		return self::$_instance;
	}
}