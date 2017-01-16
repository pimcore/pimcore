<?php
//require_once 'PHPUnit/Framework.php';

class TestSuite_Pimcore_AllTests extends Test_SuiteBase
{
    public static function suite()
    {
        $suite = new static();

        $tests = [
            \TestSuite\Pimcore\MailTest::class,
            \TestSuite\Pimcore\Cache\Core\CoreHandlerTest::class
        ];

        foreach ($tests as $test) {
            print("    - " . $test . "\n");
            $suite->addTestSuite($test);
        }

        return $suite;
    }
}
