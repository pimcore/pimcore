<?php
//require_once 'PHPUnit/Framework.php';


class SingleTest extends Test_SuiteBase {


    public static function suite() {
        $suite = new SingleTest('Models');
        $suite->addTest(TestSuite_Rest_AllTests::suite());

        return $suite;
    }
}

