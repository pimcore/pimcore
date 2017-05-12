<?php

namespace Pimcore\Tests\Helper;

use Codeception\Module;
use Pimcore\Bundle\EcommerceFrameworkBundle\Tools\Installer;

class Ecommerce extends Module
{

    public function _beforeSuite($settings = [])
    {
        parent::_beforeSuite($settings);

        //install ecommerce framework
        $installer = new Installer();
        $installer->install();
    }
}
