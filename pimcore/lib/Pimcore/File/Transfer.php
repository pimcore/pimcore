<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Christian Kogler
 * Date: 01.06.13
 * Time: 12:47
 */

class Pimcore_File_Transfer extends Zend_File_Transfer{

    public function __construct($adapter = 'Pimcore_File_Transfer_Adapter_Http', $direction = false, $options = array())
    {
        parent::__construct($adapter,$direction,$options);
    }

}