<?php

class ProductionDbProfiler extends Zend_Db_Profiler  
{  
    protected $_lastQueryText;  
    protected $_lastQueryType;  
  
    public function queryStart($queryText, $queryType = null)  
    {  
        $this->_lastQueryText = $queryText;  
        $this->_lastQueryType = $queryType;  
  
        return null;  
    }  
  
    public function queryEnd($queryId)  
    {  
        return;  
    }  
  
    public function getQueryProfile($queryId)  
    {  
        return null;  
    }  
  
    public function getLastQueryProfile()  
    {  
        $queryId = parent::queryStart($this->_lastQueryText, $this->_lastQueryType);  
  
        return parent::getLastQueryProfile();  
    }  
}  
?>
