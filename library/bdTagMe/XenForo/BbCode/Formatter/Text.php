<?php

class bdTagMe_XenForo_BbCode_Formatter_Text extends XFCP_bdTagMe_XenForo_BbCode_Formatter_Text
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
        return $this->renderSubTree($tag['children'], $rendererStates);
    }

}
