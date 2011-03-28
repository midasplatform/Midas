<?php
class JsonComponent extends AppComponent
{ 
    static private $_instance = null;
    private function __construct() {
    }
    static public function getInstance() {
        if (!self::$_instance instanceof self) {
           self::$_instance = new self();
        }
        return self::$_instance;
    }
 
    /**
     * Decodes the given $encodedValue string which is
     * encoded in the JSON format
     *
     * @param string $encodedValue Encoded in JSON format
     * @param boolean
     * @return mixed
     */
    static public function decode($encodedValue,
            $objectDecodeType = true)
    {
        $char = substr($encodedValue,0,1);
        $isValue = false;
        if ($char != "{") {
            $isValue = true;
            $encodedValue = '{"toto":'.$encodedValue.'}';
        }
        $tab = json_decode($encodedValue, $objectDecodeType);
        if ($isValue) {
            $tab = $tab["toto"];
        }
        if (is_array($tab)) {
            self::getInstance()->utf8_decode_array($tab);
        }
        else {
            $tab = utf8_decode($tab);
        }
        return $tab;
    }
 
 
    /**
     * Encode the mixed $valueToEncode into the JSON format
     *
     * @param mixed $valueToEncode
     * @return string JSON encoded object
     */
    static public function encode($valueToEncode)
    {
        if (!is_array($valueToEncode)) {
            $valueToEncode = utf8_encode($valueToEncode);
        }
        else {
            self::getInstance()->utf8_encode_array($valueToEncode);
        }
        // return encoded string
        return json_encode($valueToEncode);
    }
 
    private function utf8_encode_array(&$tab) {
        array_walk ($tab, array($this,'_utf8_encode_array'));
    }
    private function utf8_decode_array(&$tab) {
        array_walk ($tab, array($this,'_utf8_decode_array'));
    }
 
    private function _utf8_encode_array (&$array, $key) {
        if(is_array($array)) {
            array_walk ($array, array($this,'_utf8_encode_array'));
        } else {
            $array = @utf8_encode($array);
        }
    }
 
    private function _utf8_decode_array (&$array, $key) {
        if(is_array($array)) {
            array_walk ($array, array($this,'_utf8_decode_array'));
        } else {
            $array = utf8_decode($array);
        }
    }
    
}
?>