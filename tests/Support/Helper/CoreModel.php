<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Tests\Support\Helper;

use Pimcore\Bundle\SeoBundle\Installer;
use Pimcore\Bundle\SeoBundle\Model\Redirect;
use Pimcore\Tests\Support\Util\Autoloader;

class CoreModel extends Model
{
    public function _beforeSuite(array $settings = []): void
    {
        parent::_beforeSuite($settings);
        $this->installSeoBundle();
        $this->installSimpleBackendSearchBundle();
    }

    private function installSeoBundle(): void
    {
        /** @var Pimcore $pimcoreModule */
        $pimcoreModule = $this->getModule('\\'.Pimcore::class);

        $this->debug('[PimcoreSeoBundle] Running SeoBundle installer');

        // install ecommerce framework
        $installer = $pimcoreModule->getContainer()->get(Installer::class);
        $installer->install();

        //explicitly load installed classes so that the new ones are used during tests
        Autoloader::load(Redirect::class);
    }

    private function installSimpleBackendSearchBundle(): void
    {
        /** @var Pimcore $pimcoreModule */
        $pimcoreModule = $this->getModule('\\'.Pimcore::class);

        $this->debug('[PimcoreSimpleBackendSearchBundle] Running SimpleBackendSearchBundle installer');

        $installer = $pimcoreModule->getContainer()->get(\Pimcore\Bundle\SimpleBackendSearchBundle\Installer::class);
        $installer->install();
    }
}
