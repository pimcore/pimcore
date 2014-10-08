<?php
//require_once 'PHPUnit/Framework.php';


class SingleTest extends Test_SuiteBase {


    public static function suite() {
        $suite = new SingleTest('Inheritance');
        $suite->addTestSuite('TestSuite_Inheritance_AllTests');

        return $suite;
    }
}

