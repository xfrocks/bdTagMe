<?php

class bdTagMe_XenForo_Model_Alert extends XFCP_bdTagMe_XenForo_Model_Alert {
	
	protected static $_bdTagMe_inBatchMode = false;
	protected static $_bdTagMe_batchQueue = array();
	
	public function bdTagMe_beginBatch() {
		self::$_bdTagMe_inBatchMode = true;
	}
	
	public function bdTagMe_commitBatch() {
		if (self::$_bdTagMe_inBatchMode) {
			$db = $this->_getDb();
			
			// sondh@2012-10-17
			// support sending massive amount of alerts at once
			$queueIndex = 0;
			$perBatch = 200; // TODO: option?
			$queueItemsCount = count(self::$_bdTagMe_batchQueue);
			
			while ($queueIndex < $queueItemsCount) {
				$dbValues = array();
				$dbBind = array();
				$alertedUserIds = array();
				
				$maxI = min($queueIndex + $perBatch, $queueItemsCount);
				for ($i = $queueIndex; $i < $maxI; $i++) {
					$alertRequest = self::$_bdTagMe_batchQueue[$i];
					if (count($alertRequest) == 7) {
						$dbValues[] = '(?, ?, ?, ?, ?, ?, ?, ?)';									// 8 columns
						
						$dbBind[] = $alertRequest[0]; 												// alerted_user_id
						$dbBind[] = $alertRequest[1]; 												// user_id
						$dbBind[] = $alertRequest[2]; 												// username
						$dbBind[] = $alertRequest[3]; 												// content_type
						$dbBind[] = $alertRequest[4]; 												// content_id
						$dbBind[] = $alertRequest[5]; 												// action
						$dbBind[] = XenForo_Application::$time; 									// event_date
						$dbBind[] = is_array($alertRequest[6]) ? serialize($alertRequest[6]) : ''; 	// extra_data
						
						$alertedUserIds[] = $alertRequest[0];
					}
				}
				
				if (!empty($dbValues) AND !empty($dbBind) AND !empty($alertedUserIds)) {
					$db->query(
						'INSERT INTO `xf_user_alert`
						(alerted_user_id, user_id, username, content_type, content_id, action, event_date, extra_data)
						VALUES
						' . implode(', ', $dbValues),
						$dbBind
					);
					
					// update alert counter for users
					// TODO: make sure this work correctly when a user receive more than one alert in a batch
					$alertedUserIds = array_unique($alertedUserIds);
					$db->query(
						'UPDATE xf_user SET
						alerts_unread = alerts_unread + 1
						WHERE user_id IN (' . $db->quote($alertedUserIds) . ')'
					);
				}
				
				$queueIndex = $maxI;
			}
			
			self::$_bdTagMe_inBatchMode = false;
			self::$_bdTagMe_batchQueue = array();
			
			return true;
		}
		
		return false;
	}
	
	public function alertUser($alertUserId, $userId, $username, $contentType, $contentId, $action, array $extraData = null) {
		if (self::$_bdTagMe_inBatchMode) {
			// schedule sending alert for later commit
			self::$_bdTagMe_batchQueue[] = func_get_args();
			
			return true;
		}
		
		return parent::alertUser($alertUserId, $userId, $username, $contentType, $contentId, $action, $extraData);
	}

}