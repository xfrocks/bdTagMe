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
	
	public function preLoadTemplates(XenForo_View $view) {
		$view->preLoadTemplate('bdtagme_tag');
		
		return parent::preLoadTemplates($view);
	}
	
	public function bdTagMe_renderCustom(array $tag, array $rendererStates) {
		$entityText = $this->stringifyTree($tag['children']);
		$entityId = $tag['option'];
		
		if (empty($entityId)) {
			// for some reason, the id is missing
			// in that case, just return the text...
			return $entityText;
		} else {
			// IMPORTANT: this kind of processing (user, user_group, etc.)
			// is being done in 2 places bdTagMe_Engine::renderFacebookAlike
			// and bdTagMe_XenForo_BbCode_Formatter_Base::bdTagMe_renderCustom
			// please update both classes if something is changed
			$entity = false;
			if (is_numeric($entityId)) {
				$entity = array(
					'entity_type' => 'user',
					'entity_id' => $entityId,
					'entity_text' => $entityText,
					'entity_link' => XenForo_Template_Helper_Core::callHelper(
						'usernamehtml',
						array(
							array('user_id' => $entityId, 'username' => $entityText),
							'',
							false,
							array('class' => 'bdTagMe_TaggedUser')
						)
					),
				);
			} else {
				$parts = explode(',', $entityId);
				if (count($parts) == 2) {
					switch ($parts[0]) {
						case 'user_group':
							$entity = array(
								'entity_type' => $parts[0],
								'entity_id' => $entityId,
								'entity_text' => $entityText,
							);
							break;
						default:
							// do not process unknown entity type
					}
				}
			}
			
			if ($entity !== false) {
				if (!empty($this->_view)) {
					// added check to make sure the view exists before we use it
					// in some odd cases, the formatter may be created without a valid view...
					$template = $this->_view->createTemplateObject('bdtagme_tag', array(
						'entity' => $entity,
						'removePrefix' => bdTagMe_Option::get('removePrefix'),
					));
					return $template->render();				
				} else {
					return ''
						. (bdTagMe_Option::get('removePrefix') ? '' : '@')
						. htmlentities($entity['entity_text']);
				}
			}
		}
	}
}