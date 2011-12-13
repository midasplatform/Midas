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
 * TaskController
 * 
 */
class Scheduler_WorkflowController extends Scheduler_AppController
{
  public $_moduleModels=array();
  public $_moduleComponents=array('Ezc');

  /**
   * @method initAction()
   *  Index Action (first action when we access the application)
   */
  function init()
    {      

    } // end method indexAction

  /** create workflow */
  function createAction()
    {
    $definition = $this->ModuleComponent->Ezc->initWorkflowDefinitionStorage();
    // Load latest version of workflow named "Test".
    
    $workflow = new ezcWorkflow( 'Test' );
    $input = new ezcWorkflowNodeInput(
      array( 'item1' => new ezcWorkflowConditionIsObject, 'item2' => new ezcWorkflowConditionIsObject )
    );
    $workflow->startNode->addOutNode( $input );

    $split = new ezcWorkflowNodeParallelSplit();
    $input->addOutNode($split);
    $nodeExec1 = new ezcWorkflowNodeAction( 'Process A' );
    $nodeExec2 = new ezcWorkflowNodeAction( 'Process A' );
    $nodeExec1->addInNode($split);
    $nodeExec2->addInNode($split);

    $disc = new ezcWorkflowNodeDiscriminator();
    $disc->addInNode( $nodeExec1 );
    $disc->addInNode( $nodeExec2 );
    
    
    $processB = new ezcWorkflowNodeAction( 'Process B' );
    $disc->addOutNode($processB );
    
    $processB->addOutNode( $workflow->endNode);

    
    $this->_createGraph($workflow);

    // Save workflow definition to database.
   // $definition->save( $workflow );
    } 
    
  /** create graph */
  private function _createGraph($workflow)
    {
    $visitor = new ezcWorkflowVisitorVisualization;
    $workflow->accept( $visitor );
    $modulesConfig=Zend_Registry::get('configsModules');
    $command = $modulesConfig['scheduler']->dot;
    $dotFile = $this->getTempDirectory().'/graphviz_workflow_'.$workflow->__get('id').'.dot';
    $image = $this->getTempDirectory().'/graphviz_workflow_'.$workflow->__get('id').'.png';
    if(file_exists($dotFile))
      {
      unlink($dotFile);
      }
    if(file_exists($image))
      {
      unlink($image);
      }
    file_put_contents($dotFile, (string) $visitor);
    
    exec('"'.$command.'" -Tpng -o "'.$image.'" "'.$dotFile.'"');
    if(file_exists($dotFile))
      {
      unlink($dotFile);
      }
    if(!file_exists($image))
      {
      throw new Zend_Exception('Unable to create Graphviz');
      }
    return $image;
    }
    
}//end class