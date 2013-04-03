<?php

class bdTagMe_DataWriter_DiscussionMessagePost extends XFCP_bdTagMe_DataWriter_DiscussionMessagePost {
	private $taggeds = array();
	
	public function set($field, $value, $tableName = '', array $options = null) {
		if ($field == 'message') {
			$offset = 0;
			$found = array();
			
			do {
				if ($matched = preg_match('/(\s|^)@([^\s\(\)\[\]\.,!\?:;]+)/', $value, $matches, PREG_OFFSET_CAPTURE,$offset)) {
					$offset = $matches[2][1];
					$text = strtolower($matches[2][0]);
					$found["$offset"] = $text;
				}
			} while ($matched);
			
			if (!empty($found)) {
				$db = XenForo_Application::get('db');
				$sql = "
					SELECT user_id, username
					FROM xf_user
					WHERE
					" . implode(' OR ',array_map(create_function('$a','$a = mysql_escape_string($a); return "username LIKE \'$a%\'";'),$found)) . " 
				";
				$records = $db->fetchAll($sql);
				
				if (!empty($records)) {
					$mapped = array();
					foreach ($records as $record) {
						$record['link'] = XenForo_Link::buildPublicLink('full:members',$record);
						$mapped[strtolower($record['username'])] = $record;
					}
					
					// start altering the message
					foreach (array_reverse($found, true) as $offset => $text) {
						$offset = intval($offset);
						// first we have to look for URL tags
						$open = strripos($value,'[URL',$offset - strlen($value));
						if ($open !== false) {
							// there is an open tag before us
							// check for close tag
							$close = stripos($value,'[/URL]',$open);
							if ($close === false) {
								// no close tag, good for us anyway
							} else if ($close < $offset) {
								// it's closed before us, good
							} else {
								// ignore this one as it's inside a URL tag
								continue;
							}
						} else {
							// no URL tag, so far so good
						}
						
						// 1 word username
						if (isset($mapped[$text])) {
							$target = $text;
						} else {
							$target = '';
						}
						// multi-words username
						foreach ($mapped as $username => $tmp) {
							$text = strtolower(substr($value,$offset,strlen($username)));
							if ($text == $username AND strlen($username) > strlen($target)) {
								// we found it
								$target = $text;
							}
						}
						
						// start replacing
						if (!empty($target)) {
							$tagged =& $mapped[$target];
							$link = $tagged['link'];
							$username = $tagged['username'];
							$replacement = "[URL='{$link}']{$username}[/URL]";
							
							// since 1.0: remove "@" or not
							if (XenForo_Application::get('options')->bdtagme_remove_prefix) {
								// remove prefix
								$value = substr($value, 0, $offset - 1) . $replacement . substr($value, $offset + strlen($target));
							} else {
								// keep the prefix
								$value = substr($value, 0, $offset) . $replacement . substr($value, $offset + strlen($target));
							}
							
							$this->taggeds[] = $tagged;
						}
					}
				}
			}
			
			$max = XenForo_Application::get('options')->bdtagme_max;
			if ($max > 0) {
				// a limitation was set
				if (count($this->taggeds) > $max) {
					// the poster exceeded the limit
					$this->error(new XenForo_Phrase('bdtagme_you_can_only_tag_x_people_in_a_post', array(
						'max' => $max,
						'count' => count($this->taggeds),
					)), 'message');
				}
			}
		}
		parent::set($field,$value,$tableName,$options);
	}
	
	protected function _postSaveAfterTransaction() {
		parent::_postSaveAfterTransaction();
		
		// pushes alerts
		if (!empty($this->taggeds)) {
			$post = $this->getMergedData();
			foreach ($this->taggeds as $tagged) {
				if ($tagged['user_id'] == $post['post_id']) continue; // it's stupid to send alert to myself
				if (XenForo_Model_Alert::userReceivesAlert($tagged, 'post', 'quote')) {
					XenForo_Model_Alert::alert($tagged['user_id'],
						$post['user_id'], $post['username'],
						'post', $post['post_id'],
						'tagged'
					);
				}
			}
		}
	}
}