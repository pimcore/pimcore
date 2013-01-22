<?php
//require_once 'PHPUnit/Framework.php';



class Webservice_AllTests extends PHPUnit_Framework_TestSuite
{

 
    public static function suite()
    {

        $suite = new Webservice_AllTests('WebserviceTests');
        $suite->addTestSuite('Webservice_AssetTest');
        $suite->addTestSuite('Webservice_DocumentTest');
        $suite->addTestSuite('Webservice_ObjectTest');
        
        return $suite;  
    }
}
?>