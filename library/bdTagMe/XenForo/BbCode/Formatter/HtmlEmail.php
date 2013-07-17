<?php

class bdTagMe_XenForo_BbCode_Formatter_HtmlEmail extends XFCP_bdTagMe_XenForo_BbCode_Formatter_HtmlEmail
{
	public function getTags()
	{
		$tags = parent::getTags();

		$tags['usergroup'] = array(
			'hasOption' => true,
			'stopSmilies' => true,
			'callback' => array(
				$this,
				'bdTagMe_renderTagUserGroup'
			)
		);

		return $tags;
	}

	public function bdTagMe_renderTagUserGroup(array $tag, array $rendererStates)
	{
		$content = $this->renderSubTree($tag['children'], $rendererStates);
		if ($content === '')
		{
			return '';
		}

		$userGroupId = intval($tag['option']);
		if (!$userGroupId)
		{
			return $content;
		}

		$link = XenForo_Link::buildPublicLink('full:members', '', array('ug' => $userGroupId));

		return $this->_wrapInHtml('<a href="' . htmlspecialchars($link) . '" class="OverlayTrigger">', '</a>', $content);
	}

}
