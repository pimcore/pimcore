<?php

declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\GeneratorBundle\Model;

use Sensio\Bundle\GeneratorBundle\Model\Bundle as BaseBundle;

class Bundle extends BaseBundle
{
    /**
     * @var BaseBundle
     */
    private $bundle;

    /**
     * @param BaseBundle $bundle
     */
    public function __construct(BaseBundle $bundle)
    {
        $this->bundle = $bundle;
    }

    public function getNamespace()
    {
        return $this->bundle->getNamespace();
    }

    public function getName()
    {
        return $this->bundle->getName();
    }

    public function getConfigurationFormat()
    {
        return $this->bundle->getConfigurationFormat();
    }

    public function isShared()
    {
        return $this->bundle->isShared();
    }

    public function getTargetDirectory()
    {
        return $this->bundle->getTargetDirectory();
    }

    public function getBasename()
    {
        return $this->bundle->getBasename();
    }

    public function getExtensionAlias()
    {
        return $this->bundle->getExtensionAlias();
    }

    public function getServicesConfigurationFilename()
    {
        return $this->bundle->getServicesConfigurationFilename();
    }

    public function getRoutingConfigurationFilename()
    {
        return $this->bundle->getRoutingConfigurationFilename();
    }

    public function getBundleClassName()
    {
        return $this->bundle->getBundleClassName();
    }

    public function setTestsDirectory($testsDirectory)
    {
        $this->bundle->setTestsDirectory($testsDirectory);
    }

    public function getTestsDirectory()
    {
        return $this->bundle->getTestsDirectory();
    }

    public function shouldGenerateDependencyInjectionDirectory()
    {
        return true;
    }
}
