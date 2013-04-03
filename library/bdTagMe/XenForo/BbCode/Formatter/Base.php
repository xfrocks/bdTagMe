<?php

class bdTagMe_XenForo_BbCode_Formatter_Base extends XFCP_bdTagMe_XenForo_BbCode_Formatter_Base {
	public function getTags() {
		if ($this->_tags !== null) {
			return $this->_tags;
		}
		
		$tags = parent::getTags();
		
		$bbCodeTag = bdTagMe_Option::get('modeCustomTag');
		if (!empty($bbCodeTag)) {
			$bbCodeTag = strtolower($bbCodeTag);
			$tags[$bbCodeTag] = array(
				'hasOption' => true,
				'plainChildren' => true,
				'callback' => array($this, 'bdTagMe_renderCustom'),
			);
		}
		
		return $tags;
	}
	
	public function bdTagMe_renderCustom(array $tag, array $rendererStates) {
		$userName = $this->stringifyTree($tag['children']);
		$userId = intval($tag['option']);

		if (empty($userId)) {
			// for some reaons, the user id is missing
			// in that case, just return the user name...
			return $userName;
		} else {
			if (!empty($this->_view)) {
				// added check to make sure the view exists before we use it
				// in some odd cases, the formatter may be created without a valid view...
				$template = $this->_view->createTemplateObject('bdtagme_tag', array(
					'userId' => $userId,
					'userName' => $userName,
					'link' => XenForo_Link::buildPublicLink('members', array('user_id' => $userId, 'username' => $userName)),
					'removePrefix' => bdTagMe_Option::get('removePrefix'),
				));
				return $template->render();				
			} else {
				return ''
					. (bdTagMe_Option::get('removePrefix') ? '' : '@')
					. '<a href="'
					. XenForo_Link::buildPublicLink('members', array('user_id' => $userId, 'username' => $userName))
					. '" class="username">' . htmlentities($userName) . '</a>';
			}
		}
	}
}