<?php

/**
 * @see Zend_Gdata_Extension_Property
 */
require_once 'Zend/Gdata/Analytics/Extension/Property.php';

/**
 * @category   Zend
 * @package    Zend_Gdata
 * @subpackage Analytics
 */
class Zend_Gdata_Analytics_Extension_Metric extends Zend_Gdata_Analytics_Extension_Property
{
    protected $_rootNamespace = 'ga';
    protected $_rootElement = 'metric';
    protected $_value = null;
    protected $_name = null;

	protected function takeAttributeFromDOM($attribute)
    {
        switch ($attribute->localName) {
	        case 'name':
	        	$this->_name = $attribute->nodeValue;
		        break;
	        case 'value':
	            $this->_value = $attribute->nodeValue;
	            break;
	        default:
	            parent::takeAttributeFromDOM($attribute);
        }
    }
}
?>