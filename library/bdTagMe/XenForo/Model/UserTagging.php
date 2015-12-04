<?php

class bdTagMe_XenForo_Model_UserTagging extends XFCP_bdTagMe_XenForo_Model_UserTagging
{
    protected $_bdTagMe_plainReplacements = array();

    public function getTaggedUsersInMessage($message, &$newMessage, $replaceStyle = 'bb')
    {
        $filteredMessage = $message;
        $this->_bdTagMe_plainReplacements = null;

        if ($replaceStyle == 'bb') {
            $filteredMessage = preg_replace_callback('#\[usergroup=[^\]]*](.*)\[/usergroup]#siU', array(
                $this,
                '_bdTagMe_plainReplaceHandler'
            ), $filteredMessage);
        }

        $response = parent::getTaggedUsersInMessage($filteredMessage, $newMessage, $replaceStyle);

        if ($this->_bdTagMe_plainReplacements) {
            $newMessage = strtr($newMessage, $this->_bdTagMe_plainReplacements);
            $this->_bdTagMe_plainReplacements = null;
        }

        return $response;
    }

    protected function _bdTagMe_plainReplaceHandler(array $match)
    {
        if (!is_array($this->_bdTagMe_plainReplacements)) {
            $this->_bdTagMe_plainReplacements = array();
        }

        $placeholder = "\x1A_bdTagMe_" . count($this->_bdTagMe_plainReplacements) . "\x1A";

        $this->_bdTagMe_plainReplacements[$placeholder] = $match[0];

        return $placeholder;
    }

    protected function _getTagMatchUsers(array $matches)
    {
        $usersByMatch = parent::_getTagMatchUsers($matches);

        if (bdTagMe_Option::get('groupTag')) {
            $engine = bdTagMe_Engine::getInstance();
            $taggableUserGroups = $engine->getTaggableUserGroups();

            $matchesToLower = array();
            foreach ($matches as $key => $match) {
                $matchesToLower[$key] = utf8_strtolower($match[1][0]);
            }

            $userGroupTitlesToLower = array();
            foreach ($taggableUserGroups as $taggableUserGroup) {
                $userGroupTitlesToLower[$taggableUserGroup['user_group_id']] = utf8_strtolower($taggableUserGroup['title']);
            }

            $changedMatchKeys = array();
            foreach ($userGroupTitlesToLower as $userGroupId => $userGroupTitleToLower) {
                foreach ($matchesToLower as $matchKey => $matchToLower) {
                    if (strpos($userGroupTitleToLower, $matchToLower) === 0) {
                        $userGroupInfo = array(
                            'user_id' => 'ug_' . $userGroupId,
                            'username' => $taggableUserGroups[$userGroupId]['title'],
                            'lower' => $userGroupTitleToLower,
                            'user_group_id' => $userGroupId,
                        );
                        $usersByMatch[$matchKey][$userGroupInfo['user_id']] = $userGroupInfo;
                        $changedMatchKeys[$matchKey] = true;
                    }
                }
            }

            foreach (array_keys($changedMatchKeys) as $matchKey) {
                uasort($usersByMatch[$matchKey], array(__CLASS__, 'sortByLowerLength'));
            }
        }

        return $usersByMatch;
    }

    protected function _replaceTagUserMatch(array $user, $replaceStyle)
    {
        $prefix = XenForo_Application::getOptions()->get('userTagKeepAt') ? '@' : '';

        if (!empty($user['user_group_id'])) {
            // user group tagging
            if ($replaceStyle == 'bb') {
                return call_user_func_array('sprintf', array(
                    '[USERGROUP=%2$d]%1$s%3$s[/USERGROUP]',
                    $prefix,
                    $user['user_group_id'],
                    $user['username'],
                ));
            }
        }

        return parent::_replaceTagUserMatch($user, $replaceStyle);
    }

    public static function sortByLowerLength($a, $b)
    {
        $aLen = strlen($a['lower']);
        $bLen = strlen($b['lower']);

        if ($aLen === $bLen) {
            return 0;
        } else {
            return $aLen < $bLen ? 1 : -1;
        }
    }

}
