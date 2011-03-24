<?php
/**
 * Pimcore
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.pimcore.org/license
 *
 * @category   Pimcore
 * @package    Object_Class
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

interface Object_Class_Data_Interface{

    /**
        * converts object data to a simple string value or CSV Export
        * @abstract
        * @param Object_Abstract $object
        * @return string
        */
      public function getForCsvExport($object);

       /**
        * fills object field data values from CSV Import String
        * @abstract
        * @param string $importValue
        * @param Object_Abstract $abstract
        * @return Object_Class_Data
        */
      public function getFromCsvImport($importValue);




}
 
