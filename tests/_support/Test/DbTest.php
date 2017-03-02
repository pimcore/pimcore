<?php

namespace Pimcore\Tests\Test;

use Pimcore\Tests\BasicsTester;
use Pimcore\Tests\Util\TestHelper;

abstract class DbTest extends AbstractTest
{
    /**
     * @return BasicsTester
     */
    protected function getTester()
    {
        return $this->tester;
    }

    /**
     * @inheritDoc
     */
    protected function setUp()
    {
        parent::setUp();

        if ($this->needsDb()) {
            TestHelper::checkDbSupport();
        }

        foreach ($this->needsTestClasses() as $testClass => $jsonFile) {
            $this->getTester()->createClass($testClass, $jsonFile);
        }
    }

    protected function needsTestClasses()
    {
        return ['unittest' => 'class-import.json'];
    }

    protected function needsDb()
    {
        if (count($this->needsTestClasses()) > 0) {
            return true;
        }

        return false;
    }
}
