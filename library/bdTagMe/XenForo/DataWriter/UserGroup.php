<?php

class bdTagMe_XenForo_DataWriter_UserGroup extends XFCP_bdTagMe_XenForo_DataWriter_UserGroup
{

    protected function _postSave()
    {
        if (isset($GLOBALS['bdTagMe_XenForo_ControllerAdmin_UserGroup#actionSave'])) {
            /** @var bdTagMe_XenForo_ControllerAdmin_UserGroup $controller */
            $controller = $GLOBALS['bdTagMe_XenForo_ControllerAdmin_UserGroup#actionSave'];

            $controller->bdTagMe_actionSave($this);
        }

        parent::_postSave();
    }

    protected function _postDelete()
    {
        $engine = bdTagMe_Engine::getInstance();
        $engine->setTaggableUserGroup($this->getMergedData(), false, $this);

        parent::_postDelete();
    }

}
