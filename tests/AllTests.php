<?php

class AllTests extends Test_SuiteBase
{
    public static function suite()
    {
        $suite = new AllTests('Models');
        $suite->addTest(TestSuite_Basics_AllTests::suite());
        $suite->addTest(TestSuite_Datatypes_AllTests::suite());
        $suite->addTest(TestSuite_Inheritance_AllTests::suite());
        $suite->addTest(TestSuite_Rest_AllTests::suite());
        $suite->addTest(TestSuite_Classificationstore_AllTests::suite());
        $suite->addTest(TestSuite_Pimcore_AllTests::suite());

        return $suite;
    }
}
