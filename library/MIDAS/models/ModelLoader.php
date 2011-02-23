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
  public function loadModels($models)
    {
    if (is_string($models))
      {
      $this->loadModel($models);
      }
    elseif (is_array($models))
      {
      foreach ($models as $model)
        {
        $this->loadModel($model);
        }
      }
    }

  /**
   * \fn public  loadModel()
   * \brief Loads a model
   */
  public function loadModel($model)
    {
    $databaseType = Zend_Registry::get('configDatabase')->database->type;
    $models = Zend_Registry::get('models');
    if (!isset($models[$model]))
      {
      Zend_Loader::loadClass($model . 'Model', BASE_PATH.'/application/models/' . $databaseType);
      $name = $model . 'Model';
      if (class_exists($name))
        {
        $models[$model] = new $name;
        Zend_Registry::set('models', $models);
        }
      else
        {
        throw new Zend_Exception('Unable to load class ' . $name);
        }
      }
    return $models[$model];
    }

  }

?>