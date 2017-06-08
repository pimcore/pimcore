<?php

namespace Pimcore\Tests\Helper;

use Codeception\Module;

class Ecommerce extends Module
{
    public function _beforeSuite($settings = [])
    {
        /** @var Pimcore $pimcoreModule */
        $pimcoreModule = $this->getModule('\\' . Pimcore::class);

        // install ecommerce framework
        $installer = $pimcoreModule->getContainer()->get('pimcore.ecommerceframework.installer');
        $installer->install();
    }
}
