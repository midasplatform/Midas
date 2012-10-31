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

/**
 * Use this component to generate consistent breadcrumb bars for Midas
 */
class BreadcrumbComponent extends AppComponent
{
  /**
   * Build a breadcrumb bar for the header and set it on the view provided
   * @param nodes An ordered list of nodes.  Each node must have a 'type' key whose value is
                  in the set {'community', 'folder', 'item', 'user', 'custom'}.
   * @param view The view in which to set the header
   */
  public function setBreadcrumbHeader($nodes, &$view)
    {
    if(is_string($nodes))
      {
      return $nodes;
      }
    if(!is_array($nodes))
      {
      throw new Zend_Exception('Must pass a string or an array to createBreadcrumbs');
      }
    $view->header = '<ul class="pathBrowser">';
    foreach($nodes as $node)
      {
      if(!isset($node['type']))
        {
        throw new Zend_Exception('Each breadcrumb node must have a type');
        }
      switch($node['type'])
        {
        case 'community':
          $view->header .= $this->_createCommunityBreadcrumb($node, $view);
          break;
        case 'folder':
          $view->header .= $this->_createFolderBreadcrumb($node, $view);
          break;
        case 'item':
          $view->header .= $this->_createItemBreadcrumb($node, $view);
          break;
        case 'user':
          $view->header .= $this->_createUserBreadcrumb($node, $view);
          break;
        case 'custom':
        default:
          $view->header .= $this->_createCustomBreadcrumb($node, $view);
          break;
        }
      }
    $view->header .= '</ul>';
    return $view->header;
    }

  /**
   * Create a community breadcrumb from the node. Node should have the following keys:
   * -object The community dao from which to create the breadcrumb
   * -[link] (bool, default = true) Whether to render as a link or just text
   */
  protected function _createCommunityBreadcrumb($node, &$view)
    {
    if(!isset($node['object']) || !($node['object'] instanceof CommunityDao))
      {
      throw new Zend_Exception('Object must be a community to create community breadcrumb type');
      }
    $name = UtilityComponent::sliceName($node['object']->getName(), 25);
    $str = '<li class="pathCommunity"><img alt="" src="'.$view->coreWebroot.'/public/images/icons/community.png" /><span>';
    if(isset($node['link']) && $node['link'] === false)
      {
      $str .= $name;
      }
    else
      {
      $str .= '<a href="'.$view->webroot.'/community/'.$node['object']->getKey().'#tabs-3">'.$name.'</a>';
      }
    $str .= '</span></li>';
    return $str;
    }

  /**
   * Create a folder breadcrumb from the node. Node should have the following keys:
   * -object The folder dao from which to create the breadcrumb
   * -[link] (bool, default = true) Whether to render as a link or just text
   * -[open] (bool, default = false) Whether the folder icon should be shown as open or closed
   */
  protected function _createFolderBreadcrumb($node, &$view)
    {
    if(!isset($node['object']) || !($node['object'] instanceof FolderDao))
      {
      throw new Zend_Exception('Object must be a folder to create folder breadcrumb type');
      }
    $name = UtilityComponent::sliceName($node['object']->getName(), 25);
    $icon = (isset($node['open']) && $node['open'] === true) ? 'folder_open' : 'directory';

    $str = '<li class="pathFolder"><img alt="" src="'.$view->coreWebroot.'/public/images/FileTree/'.$icon.'.png" /><span>';
    if(isset($node['link']) && $node['link'] === false)
      {
      $str .= $name;
      }
    else
      {
      $str .= '<a href="'.$view->webroot.'/folder/'.$node['object']->getKey().'">'.$name.'</a>';
      }
    $str .= '</span></li>';
    return $str;
    }

  /**
   * Create a user breadcrumb from the node. Node should have the following keys:
   * -object The user dao from which to create the breadcrumb
   * -[link] (bool, default = true) Whether to render as a link or just text
   */
  protected function _createUserBreadcrumb($node, &$view)
    {
    if(!isset($node['object']) || !($node['object'] instanceof UserDao))
      {
      throw new Zend_Exception('Object must be a user to create user breadcrumb type');
      }
    $name = UtilityComponent::sliceName($node['object']->getFullName(), 25);
    $str = '<li class="pathUser"><img alt="" src="'.$view->coreWebroot.'/public/images/icons/unknownUser-small.png" /><span>';
    if(isset($node['link']) && $node['link'] === false)
      {
      $str .= $name;
      }
    else
      {
      $str .= '<a href="'.$view->webroot.'/user/'.$node['object']->getKey().'">'.$name.'</a>';
      }
    $str .= '</span></li>';
    return $str;
    }

  /**
   * Create an item breadcrumb from the node. Node should have the following keys:
   * -object The item dao from which to create the breadcrumb
   * -[link] (bool, default = true) Whether to render as a link or just text
   */
  protected function _createItemBreadcrumb($node, &$view)
    {
    if(!isset($node['object']) || !($node['object'] instanceof ItemDao))
      {
      throw new Zend_Exception('Object must be an item to create item breadcrumb type');
      }
    $name = UtilityComponent::sliceName($node['object']->getName(), 25);
    $str = '<li class="pathItem"><img alt="" src="'.$view->coreWebroot.'/public/images/FileTree/file.png" /><span>';
    if(isset($node['link']) && $node['link'] === false)
      {
      $str .= $name;
      }
    else
      {
      $str .= '<a href="'.$view->webroot.'/item/'.$node['object']->getKey().'">'.$name.'</a>';
      }
    $str .= '</span></li>';
    return $str;
    }

  /**
   * Create a custom breadcrumb from the node.  Should have the following keys:
   * -text The text of the breadcrumb
   * -icon The icon of the breadcrumb
   * -[href] The URL to link to.  If not set, will just render text instead of a link.
   * -[maxLength] Number of characters to limit the text to
   */
  protected function _createCustomBreadcrumb($node, &$view)
    {
    if(!isset($node['text']) || !isset($node['icon']))
      {
      throw new Zend_Exception('Custom breadcrumbs must have a text and an icon parameter');
      }
    $text = isset($node['maxLength']) ? UtilityComponent::sliceName($node['text'], (int)$node['maxLength']) : $node['text'];
    $str = '<li class="pathCustom"><img alt="" src="'.$node['icon'].'" /><span>';
    if(isset($node['href']))
      {
      $str .= '<a href="'.$node['href'].'">'.$text.'</a>';
      }
    else
      {
      $str .= $text;
      }
    $str .= '</span></li>';
    return $str;
    }
} // end class