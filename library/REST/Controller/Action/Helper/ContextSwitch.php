<?php
/**
 * ContextSwitch
 *
 * extends default context switch and adds AMF3, XML, PHP serialization
 */
class REST_Controller_Action_Helper_ContextSwitch extends Zend_Controller_Action_Helper_ContextSwitch
{
    protected $_autoSerialization = true;

    // TODO: run through Zend_Serializer::factory()
    protected $_availableAdapters = array(
        'json'  => 'Zend_Serializer_Adapter_Json',
        'xml'   => 'REST_Serializer_Adapter_Xml',
        'php'   => 'Zend_Serializer_Adapter_PhpSerialize',
        'html'  => 'Zend_Serializer_Adapter_Json',
    );

    protected $_rest_contexts = array(
        'json' => array(
            'suffix'    => 'json',
            'headers'   => array(
                'Content-Type' => 'application/json'
            ),

            'options' => array(
                'autoDisableLayout' => true,
            ),

            'callbacks' => array(
                'init' => 'initAbstractContext',
                'post' => 'restContext'
            ),
        ),

        'xml' => array(
            'suffix'    => 'xml',
            'headers'   => array(
                'Content-Type' => 'application/xml'
            ),

            'options' => array(
                'autoDisableLayout' => true,
            ),

            'callbacks' => array(
                'init' => 'initAbstractContext',
                'post' => 'restContext'
            ),
        ),

        'php' => array(
            'suffix'    => 'php',
            'headers'   => array(
                'Content-Type' => 'application/x-httpd-php'
            ),

            'options' => array(
                'autoDisableLayout' => true,
            ),

            'callbacks' => array(
                'init' => 'initAbstractContext',
                'post' => 'restContext'
            )
        ),

        'html' => array(
            'suffix'    => 'html',
            'headers'   => array(
                'Content-Type' => 'text/html; Charset=UTF-8'
            ),

            'options' => array(
                'autoDisableLayout' => false,
            ),
          
            'callbacks' => array(
                'init' => 'initAbstractContext',
                'post' => 'restContext'
            )
        )
    );

    public function __construct($options = null)
    {
        if ($options instanceof Zend_Config) {
            $this->setConfig($options);
        } elseif (is_array($options)) {
            $this->setOptions($options);
        }

        if (empty($this->_contexts)) {
            $this->addContexts($this->_rest_contexts);
        }

        $this->init();
    }

    public function getAutoDisableLayout()
    {
        $context = $this->_actionController->getRequest()->getParam($this->getContextParam());
        return $this->_rest_contexts[$context]['options']['autoDisableLayout'];
    }

    public function initAbstractContext()
    {
        if (!$this->getAutoSerialization()) {
            return;
        }

        $viewRenderer = Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer');
        $view = $viewRenderer->view;

        if ($view instanceof Zend_View_Interface) {
            $viewRenderer->setNoRender(true);
        }
    }

    public function restContext()
    {
        if (!$this->getAutoSerialization()) {
            return;
        }

        $view = Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer')->view;

        if ($view instanceof Zend_View_Interface) {
            if (method_exists($view, 'getVars')) {
                $vars = $view->getVars();
                if (isset($vars['apiresults'])) {
                    $data = $vars['apiresults'];

                    if (count($data) !== 0) {
                        $serializer = new $this->_availableAdapters[$this->_currentContext];
                        $body = $serializer->serialize($data);

                        if ($this->_currentContext == 'xml') {
                            $stylesheet = $this->getRequest()->getHeader('X-XSL-Stylesheet');

                            if ($stylesheet !== false and !empty($stylesheet)) {
                                $body = str_replace('<?xml version="1.0"?>', sprintf('<?xml version="1.0"?><?xml-stylesheet type="text/xsl" href="%s"?>', $stylesheet), $body);
                            }
                        }
                        
                        if ($this->_currentContext == 'json') {
                            $callback = $this->getRequest()->getParam('jsonp-callback', false);

                            if ($callback !== false and !empty($callback)) {
                                $body = sprintf('%s(%s)', $callback, $body);
                            }
                        }
                        
                        if ($this->_currentContext == 'html') {
                           $body = $this->prettyPrint($body, array("format" => "html"));
                        }

                        $this->getResponse()->setBody($body);
                    }
                }
            }
        }
    }

    public function setAutoSerialization($flag)
    {
        $this->_autoSerialization = (bool) $flag;
        return $this;
    }

    public function getAutoSerialization()
    {
        return $this->_autoSerialization;
    }
    
    
    /**
     * This function is based on the below Zend patches with minor customized changes
     * Refs:
     * http://framework.zend.com/issues/browse/ZF-9577
     * http://framework.zend.com/issues/browse/ZF-10185
     * 
     * Pretty-print JSON string
     *
     * Use 'format' option to select output format - currently html and txt supported, txt is default
     * Use 'indent' option to override the indentation string set in the format - by default for the 'txt' format it's a tab
     *
     * @param string $json Original JSON string
     * @param array $options Encoding options
     * @return string
     */
    public function prettyPrint($json, $options = array())
    {
        $tokens = preg_split('|([\{\}\]\[,])|', $json, -1, PREG_SPLIT_DELIM_CAPTURE);
        $result = "";
        $indent = 0;

        $format= "txt";

        $ind = "\t";

        if(isset($options['format'])) {
            $format = $options['format'];
        }

        switch ($format):
            case 'html':
                $line_break = "<br />";
                $line_break_length = 6;
                $ind = "&nbsp;&nbsp;&nbsp;&nbsp;";
                break;
            default:
            case 'txt':
                $line_break = "\n";
                $line_break_length = 2;
                $ind = "\t";
                break;
        endswitch;

        //override the defined indent setting with the supplied option
        if(isset($options['indent'])) {
            $ind = $options['indent'];
        }
        
        $inLiteral = false; 
        foreach($tokens as $token) {
            if($token == "") continue;

            $prefix = str_repeat($ind, $indent);
            if (!$inLiteral && ($token == '{' || $token == '[')) {
                $indent++;
                if($result != "" && substr($result, strlen($result)-$line_break_length) == $line_break) {
                    $result .= $prefix;
                }
                $result .= "$token$line_break";
            } elseif (!$inLiteral && ($token == '}' || $token == ']')) {
                $indent--;
                $prefix = str_repeat($ind, $indent);
                $result .= "$line_break$prefix$token";
            } elseif (!$inLiteral && $token == ',') {
                $result .= "$token$line_break" ;
            } else {
                $result .= ( $inLiteral ? '' : $prefix ) . $token;
                // Count # of unescaped double-quotes in token, subtract # of
                // escaped double-quotes and if the result is odd then we are
                // inside a string literal
                if ((substr_count($token, "\"")-substr_count($token, "\\\"")) % 2 != 0) {
                    $inLiteral = !$inLiteral;
                }
            }
        }
        return $result;
   }
}
