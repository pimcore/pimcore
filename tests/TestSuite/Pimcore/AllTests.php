<?php
//require_once 'PHPUnit/Framework.php';

use Symfony\Component\Finder\Finder;

class TestSuite_Pimcore_AllTests extends Test_SuiteBase
{
    public static function suite()
    {
        $suite = new static();

        $tests = [];

        $finder = new Finder();
        $finder
            ->files()
            ->in(__DIR__)
            ->name('*Test.php');

        foreach ($finder as $testFile) {
            $relativePath = str_replace(__DIR__, '', $testFile->getRealPath());
            $relativePath = preg_replace('/^\//', '', $relativePath);

            $className = str_replace('/', '\\', $relativePath);
            $className = preg_replace('/\.php$/', '', $className);
            $className = '\\TestSuite\\Pimcore\\' . $className;

            if (class_exists($className)) {
                $reflector = new ReflectionClass($className);

                if ($reflector->isInstantiable() && $reflector->isSubclassOf(\PHPUnit_Framework_TestCase::class)) {
                    $tests[] = $className;
                }
            }
        }

        foreach ($tests as $test) {
            print("    - " . $test . "\n");
            $suite->addTestSuite($test);
        }

        return $suite;
    }
}
