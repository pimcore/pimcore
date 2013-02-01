<?php
//require_once 'PHPUnit/Framework.php';


class AllTests extends Test_SuiteBase {


    public static function suite() {
        $suite = new AllTests('Models');
        $suite->addTest(TestSuite_Rest_AllTests::suite());
        return $suite;
    }}

?>
