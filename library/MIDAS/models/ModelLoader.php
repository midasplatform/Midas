<?php
/**
 * \class ModelLoader
 * \brief Create Model object depending of the database type
 */
class MIDAS_ModelLoader
  {
  /**
   * \fn public  loadModels()
   * \brief Loads models (array or string)
   */
  public function loadModels($models, $module = '')
    {
    if(is_string($models))
      {
      $this->loadModel($models, $module);
      }
    elseif(is_array($models))
      {
      foreach($models as $model)
        {
        $this->loadModel($model, $module);
        }
      }
    }

  /**
   * \fn public  loadModel()
   * \brief Loads a model
   */
  public function loadModel($model, $module = '')
    {

    $databaseType = Zend_Registry::get('configDatabase')->database->type;
    $models = Zend_Registry::get('models');
    if(!isset($models[$module.$model]))
      {
      if($module == "")
        {    
        include_once BASE_PATH."/core/models/" . $databaseType."/".$model.'Model.php';
        $name = $model . 'Model';
        }
      else
        {
        include_once BASE_PATH."/modules/".$module."/models/" . $databaseType."/".$model.'Model.php';
        $name = ucfirst($module).'_'.$model . 'Model';
        }
      if(class_exists($name))
        {
        $models[$module.$model] = new $name;
        Zend_Registry::set('models', $models);
        }
      else
        {
        throw new Zend_Exception('Unable to load class ' . $name);
        }
      }
    return $models[$module.$model];
    }
  }
