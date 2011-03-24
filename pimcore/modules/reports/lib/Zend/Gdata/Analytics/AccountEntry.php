<?php
/**
 * @see Zend_Gdata_Entry
 */
require_once 'Zend/Gdata/Entry.php';

require_once 'Zend/Gdata/Analytics/Extension/Property.php';
require_once 'Zend/Gdata/Analytics/Extension/Metric.php';
require_once 'Zend/Gdata/Analytics/Extension/Dimension.php';
require_once 'Zend/Gdata/Analytics/Extension/TableId.php';

/**
 * Represents a Calendar entry in the Calendar data API meta feed of a user's
 * calendars.
 *
 * @category   Zend
 * @package    Zend_Gdata
 * @subpackage Calendar
 * @copyright  Copyright (c) 2005-2009 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Gdata_Analytics_AccountEntry extends Zend_Gdata_Entry
{

	protected $_accountId;
	protected $_accountName;
	protected $_profileId;
	protected $_webPropertyId;
	protected $_currency;
	protected $_timezone;
	protected $_tableId;

	public function __construct($element = null)
    {
        $this->registerAllNamespaces(Zend_Gdata_Analytics::$namespaces);
        parent::__construct($element);
    }

    /**
     * @param DOMElement $child
     */
    protected function takeChildFromDOM($child)
    {
        $absoluteNodeName = $child->namespaceURI . ':' . $child->localName;
        switch ($absoluteNodeName){
        	case $this->lookupNamespace('ga') . ':' . 'property';
	            $property = new Zend_Gdata_Analytics_Extension_Property();
	            $property->transferFromDOM($child);
	            $this->{$property->getName()} = $property;
            break;
        	case $this->lookupNamespace('ga') . ':' . 'tableId';
	            $tableId = new Zend_Gdata_Analytics_Extension_TableId();
	            $tableId->transferFromDOM($child);
	            $this->_tableId = $tableId;
            break;
        	default:
            	parent::takeChildFromDOM($child);
            break;
        }
    }
}
?>