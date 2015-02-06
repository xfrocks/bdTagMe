<?php

class bdTagMe_XenForo_Route_Prefix_Members extends XFCP_bdTagMe_XenForo_Route_Prefix_Members
{

    public function match($routePath, Zend_Controller_Request_Http $request, XenForo_Router $router)
    {
        $ourAction = 'tagged';

        if (strpos($routePath, $ourAction . '/') === 0) {
            // this is our request
            $action = $ourAction;
            $request->setParam('entity_id', str_replace('/', ',', trim(substr($routePath, strlen($ourAction)), '/')));
            return $router->getRouteMatch('XenForo_ControllerPublic_Member', $action, 'members');
        }

        return parent::match($routePath, $request, $router);
    }

    public function buildLink($originalPrefix, $outputPrefix, $action, $extension, $data, array &$extraParams)
    {
        if ($action == 'tagged' AND isset($data['entity_id'])) {
            // this is our link
            $newAction = $action . '/' . str_replace(',', '/', $data['entity_id']);
            return XenForo_Link::buildBasicLink($outputPrefix, $newAction, $extension);
        }

        return parent::buildLink($originalPrefix, $outputPrefix, $action, $extension, $data, $extraParams);
    }

}
