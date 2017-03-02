<?php
//require_once 'PHPUnit/Framework.php';



class TestSuite_Basics_AllTests extends Test_SuiteBase
{
    public static function suite()
    {
        $suite = new TestSuite_Basics_AllTests('Basics');

        $tests = ['TestSuite_Basics_ObjectTest'];

        $success = shuffle($tests);
        print("Created the following execution order:\n");

        if (true) {
            // bar
        } elseif (false) {
            // foo
        }

        foreach ($tests as $test) {
            print("    - " . $test . "\n");
            $suite->addTestSuite($test);
        }

        return $suite;
    }
}
