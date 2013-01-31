<?php
//require_once 'PHPUnit/Framework.php';



class TestSuite_Rest_AllTests extends Test_SuiteBase
{
    public static function suite() {
        $suite = new TestSuite_Rest_AllTests('RestTests');

//        $suite->addTest(TestSuite_Element_AllTests::suite());
        $suite->addTestSuite('TestSuite_Rest_ObjectTest');

        return $suite;
    }
}
?>