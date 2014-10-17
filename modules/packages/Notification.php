<?php
/*=========================================================================
 MIDAS Server
 Copyright (c) Kitware SAS. 26 rue Louis Guérin. 69100 Villeurbanne, FRANCE
 All rights reserved.
 More information http://www.kitware.com

 Licensed under the Apache License, Version 2.0 (the "License");
 you may not use this file except in compliance with the License.
 You may obtain a copy of the License at

         http://www.apache.org/licenses/LICENSE-2.0.txt

 Unless required by applicable law or agreed to in writing, software
 distributed under the License is distributed on an "AS IS" BASIS,
 WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 See the License for the specific language governing permissions and
 limitations under the License.
=========================================================================*/

require_once BASE_PATH.'/modules/api/library/APIEnabledNotification.php';

/** Notification manager for the packages module */
class Packages_Notification extends ApiEnabled_Notification
{
    public $moduleName = 'packages';
    public $_moduleComponents = array('Api');
    public $_moduleModels = array('Project');
    public $_models = array('Community');

    /** init notification process */
    public function init()
    {
        $baseUrl = Zend_Controller_Front::getInstance()->getBaseUrl();
        $this->moduleWebroot = $baseUrl.'/'.$this->moduleName;
        $this->webroot = $baseUrl;

        $this->addCallBack('CALLBACK_CORE_GET_LEFT_LINKS', 'getLeftLinks');
        $this->addCallBack('CALLBACK_CORE_COMMUNITY_DELETED', 'communityDeleted');
        $this->addCallBack('CALLBACK_CORE_ITEM_DELETED', 'itemDeleted');
        $this->addCallBack('CALLBACK_CORE_ITEM_VIEW_ACTIONMENU', 'getItemMenuLink');
        $this->addCallBack('CALLBACK_CORE_COMMUNITY_MANAGE_FORM', 'communityManageForm');
        $this->addCallBack('CALLBACK_CORE_GET_COMMUNITY_VIEW_TABS', 'communityViewTabs');
        $this->addCallBack('CALLBACK_CORE_EDIT_COMMUNITY_INFO', 'communityEditInfo');
        $this->enableWebAPI($this->moduleName);
    }

    /**
     * Add the link to this module to the left side list
     */
    public function getLeftLinks()
    {
        $enabledProjects = $this->Packages_Project->getAllEnabled();

        $list = array();
        foreach ($enabledProjects as $project) {
            $list[$project->name.' Packages'] = array(
                $this->webroot.'/community/'.$project->getCommunityId().'#Packages',
                $this->webroot.'/modules/'.$this->moduleName.'/public/images/package.png',
            );
        }

        return $list;
    }

    /**
     * If this community is a project with packages, show the packages tab
     */
    public function communityViewTabs($args)
    {
        $community = $args['community'];
        $project = $this->Packages_Project->getByCommunityId($community->getKey());
        if ($project && $project->getEnabled()) {
            return array('Packages' => $this->moduleWebroot.'/view/project?projectId='.$project->getKey());
        }

        return null;
    }

    /**
     * When a community is deleted, we must delete its associated project
     */
    public function communityDeleted($args)
    {
        $community = $args['community'];

        $projectModel = MidasLoader::loadModel('Project', $this->moduleName);
        $project = $projectModel->getByCommunityId($community->getKey());
        if ($project) {
            $projectModel->delete($project);
        }
    }

    /**
     * When an item is deleted, we must delete associated package/extension records
     */
    public function itemDeleted($args)
    {
        $itemDao = $args['item'];

        $packageModel = MidasLoader::loadModel('Package', $this->moduleName);
        $package = $packageModel->getByItemId($itemDao->getKey());
        if ($package) {
            $packageModel->delete($package);
        }

        $extensionModel = MidasLoader::loadModel('Extension', $this->moduleName);
        $extension = $extensionModel->getByItemId($itemDao->getKey());
        if ($extension) {
            $extensionModel->delete($extension);
        }
    }

    /**
     * Render the checkbox to allow a community to be a project
     */
    public function communityManageForm($args)
    {
        $community = $args['community'];
        $project = $this->Packages_Project->getByCommunityId($community->getKey());

        $html = '<input type="checkbox" name="packages_project" ';
        if ($project && $project->getEnabled() == 1) {
            $html .= 'checked="checked"';
        }
        $html .= '/ >This community hosts packages for a project';

        return array('Packages' => $html);
    }

    /**
     * Used to set the project flag on a community
     */
    public function communityEditInfo($args)
    {
        $community = $args['community'];
        $params = $args['params'];

        $enabled = isset($params['packages_project']);
        $this->Packages_Project->setEnabled($community, $enabled);
    }

    /**
     * Add link to the right hand menu in the item view if the item is a package
     */
    public function getItemMenuLink($params)
    {
        $item = $params['item'];
        $itemModel = MidasLoader::loadModel('Item');
        $packageModel = MidasLoader::loadModel('Package', $this->moduleName);
        $package = $packageModel->getByItemId($item->getKey());

        if (!$itemModel->policyCheck($item, $this->userSession->Dao, MIDAS_POLICY_WRITE)
        ) {
            return '';
        }
        if ($package) {
            $type = 'package';
            $id = $package->getKey();
        } else {
            $extensionModel = MidasLoader::loadModel('Extension', $this->moduleName);
            $extension = $extensionModel->getByItemId($item->getKey());
            if ($extension) {
                $type = 'extension';
                $id = $extension->getKey();
            } else {
                return '';
            }
        }

        $baseUrl = Zend_Controller_Front::getInstance()->getBaseUrl();

        return '<li><a href="'.$baseUrl.'/'.$this->moduleName.'/'.$type.'/manage?id='.$id.'"><img alt="" src="'.$baseUrl.'/modules/'.$this->moduleName.'/public/images/package_go.png" /> '.$this->t(
            'Manage '.$type
        ).'</a></li>';
    }
}
