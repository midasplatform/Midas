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
    $dotFile = BASE_PATH.'/tmp/misc/graphviz_workflow_'.$workflow->__get('id').'.dot';
    $image = BASE_PATH.'/tmp/misc/graphviz_workflow_'.$workflow->__get('id').'.png';
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