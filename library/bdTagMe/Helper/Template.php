<?php

class bdTagMe_Helper_Template
{
    /** @var callable|null */
    protected static $_defaultBodyText = null;

    public static function helperSnippetSetup()
    {
        self::$_defaultBodyText = XenForo_Template_Helper_Core::$helperCallbacks['bodytext'];
        if (self::$_defaultBodyText[0] === 'self') {
            self::$_defaultBodyText[0] = 'XenForo_Template_Helper_Core';
        }

        XenForo_Template_Helper_Core::$helperCallbacks['bodytext'] = array(
            __CLASS__,
            'helperBodyText'
        );
    }

    public static function helperBodyText()
    {
        $args = func_get_args();

        if (self::$_defaultBodyText !== null) {
            $string = call_user_func_array(self::$_defaultBodyText, $args);
        } else {
            $string = array_shift($args);
        }

        $string = self::linkTaggedUserGroup($string);

        return $string;
    }

    public static function linkTaggedUserGroup($string)
    {
        $string = preg_replace_callback('#(?<=^|\s|[\](,]|--|@)@\[ug_(\d+):(\'|"|&quot;|)(.*)\\2\]#iU', array(
            'self',
            '_linkTaggedUserGroupCallback'
        ), $string);

        return $string;
    }

    protected static function _linkTaggedUserGroupCallback(array $matches)
    {
        $userGroupId = intval($matches[1]);
        $userGroupTitle = htmlspecialchars($matches[3], null, 'utf-8', false);

        $link = XenForo_Link::buildPublicLink('full:members', array(), array('ug' => $userGroupId));

        return '<a href="' . htmlspecialchars($link) . '" class="username ug" data-usergroup="' . $userGroupId . ', ' . $userGroupTitle . '">' . $userGroupTitle . '</a>';
    }

}
