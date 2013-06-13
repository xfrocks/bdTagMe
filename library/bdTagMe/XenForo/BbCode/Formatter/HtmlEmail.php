<?php

class bdTagMe_XenForo_BbCode_Formatter_HtmlEmail extends XFCP_bdTagMe_XenForo_BbCode_Formatter_HtmlEmail {
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
		return sprintf('%s%s',
				(bdTagMe_Option::get('removePrefix') ? '' : '@'),
				htmlentities($this->stringifyTree($tag['children'])));
	}
}