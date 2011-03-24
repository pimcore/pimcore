<?php

/**
 * @see Zend_Gdata_Extension_Metric
 */
require_once 'Zend/Gdata/Analytics/Extension/Metric.php';

/**
 * @category   Zend
 * @package    Zend_Gdata
 * @subpackage Analytics
 */
class Zend_Gdata_Analytics_Extension_Dimension extends Zend_Gdata_Analytics_Extension_Metric
{
    protected $_rootNamespace = 'ga';
    protected $_rootElement = 'dimension';
    protected $_value = null;
    protected $_name = null;
}
?>