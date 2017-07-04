<?php

namespace Pimcore\Tests\Helper;

use Codeception\Lib\ModuleContainer;
use Codeception\Module;

class Ecommerce extends Module
{
    /**
     * @inheritDoc
     */
    public function __construct(ModuleContainer $moduleContainer, $config = null)
    {
        $this->config = array_merge($this->config, [
            'run_installer' => true
        ]);

        parent::__construct($moduleContainer, $config);
    }

    public function _beforeSuite($settings = [])
    {
        if ($this->config['run_installer']) {
            /** @var Pimcore $pimcoreModule */
            $pimcoreModule = $this->getModule('\\' . Pimcore::class);

            // install ecommerce framework
            $installer = $pimcoreModule->getContainer()->get('pimcore.ecommerceframework.installer');
            $installer->install();
        }
    }
}
