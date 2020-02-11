<?php
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

namespace Pimcore\Extension\Bundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

abstract class AbstractPimcoreBundle extends Bundle implements PimcoreBundleInterface
{
    /**
     * @inheritDoc
     */
    public function getNiceName()
    {
        return $this->getName();
    }

    /**
     * @inheritDoc
     */
    public function getDescription()
    {
        return '';
    }

    /**
     * @inheritDoc
     */
    public function getVersion()
    {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function getInstaller()
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getAdminIframePath()
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getJsPaths()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getCssPaths()
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getEditmodeJsPaths()
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getEditmodeCssPaths()
    {
        return [];
    }
}
