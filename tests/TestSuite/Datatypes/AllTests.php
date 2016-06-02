<?php
//require_once 'PHPUnit/Framework.php';



class TestSuite_Datatypes_AllTests extends Test_SuiteBase
{
    public static function suite()
    {
        $suite = new TestSuite_Datatypes_AllTests('Datatypes');

        $tests = ['TestSuite_Datatypes_KeyValueTest', 'TestSuite_Datatypes_LocalizedFieldTest'];

        $success = shuffle($tests);
        print("Created the following execution order:\n");

        foreach ($tests as $test) {
            print("    - " . $test . "\n");
            $suite->addTestSuite($test);
        }

        return $suite;
    }
}
