<?php
//require_once 'PHPUnit/Framework.php';


class SingleTest extends Test_SuiteBase
{
    public static function suite()
    {
        $suite = new SingleTest('Classificationstore');
        $suite->addTestSuite('TestSuite_Classificationstore_AllTests');

        return $suite;
    }
}
