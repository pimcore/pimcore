<?php

namespace Pimcore\Tests\Test;

use Pimcore\Tests\ModelTester;

/**
 * @property ModelTester $tester
 */
abstract class ModelTestCase extends TestCase
{
    /**
     * @inheritDoc
     */
    protected function setUp()
    {
        parent::setUp();

        if ($this->needsDb()) {
            $testClasses = $this->needsTestClasses();
            if (is_array($testClasses)) {
                foreach ($testClasses as $testClass => $jsonFile) {
                    $this->tester->createClass($testClass, $jsonFile);
                }
            }
        }
    }

    /**
     * Determine if the test needs test classes
     *
     * @return array|bool
     */
    protected function needsTestClasses()
    {
        return ['unittest' => 'class-import.json'];
    }

    /**
     * @inheritdoc
     */
    protected function needsDb()
    {
        $testClasses = $this->needsTestClasses();

        if (is_array($testClasses) && count($testClasses) > 0) {
            return true;
        }

        return false;
    }
}
