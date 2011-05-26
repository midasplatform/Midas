<?php
/*=========================================================================
MIDAS Server
Copyright (c) Kitware SAS. 20 rue de la Villette. All rights reserved.
69328 Lyon, FRANCE.

See Copyright.txt for details.
This software is distributed WITHOUT ANY WARRANTY; without even
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR
PURPOSE.  See the above copyright notices for more information.
=========================================================================*/

/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   Zend
 * @package    Zend_View
 * @subpackage Helper
 * @copyright  Copyright (c) 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
 * @version    $Id: Partial.php 20096 2010-01-06 02:05:09Z bkarwin $
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
/** Zend_View_Helper_Abstract.php */
// require_once 'Zend/View/Helper/Abstract.php';

/**
 * Helper for rendering a template fragment in its own variable scope.
 *
 * @package    Zend_View
 * @subpackage Helper
 * @copyright  Copyright (c) 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_View_Helper_Element extends Zend_View_Helper_Abstract
  {
  /** element */
  public function element($name = null, $module = null, $model = null)
    {
    $view = $this->view;

    if ((null !== $module) && is_string($module))
      {
      // require_once 'Zend/Controller/Front.php';
      $moduleDir = Zend_Controller_Front::getInstance()->getControllerDirectory($module);
      if (null === $moduleDir)
        {
        // require_once 'Zend/View/Helper/Partial/Exception.php';
        $e = new Zend_View_Helper_Partial_Exception('Cannot render partial; module does not exist');
        $e->setView($this->view);
        throw $e;
        }
      $viewsDir = dirname($moduleDir) . '/views';
      $view->addBasePath($viewsDir);
      }
    elseif ((null == $model) && (null !== $module)
    && (is_array($module) || is_object($module)))
      {
      $model = $module;
      }

    if (!empty($model))
      {
      if (is_array($model))
        {
        $view->assign($model);
        }
      elseif (is_object($model))
        {
        if (null !== ($objectKey = $this->getObjectKey()))
          {
          $view->assign($objectKey, $model);
          }
        elseif (method_exists($model, 'toArray'))
          {
          $view->assign($model->toArray());
          }
        else
          {
          $view->assign(get_object_vars($model));
          }
        }
      }

    return $view->render("element/".$name.".phtml");
    }

  }
