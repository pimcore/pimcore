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
declare(strict_types=1);

namespace Pimcore\Bundle\AdminBundle\GDPR\DataProvider;

interface DataProviderInterface
{
    /**
     * Returns sort priority - higher is sorted first
     *
     * @return int
     */
    public function getSortPriority(): int;

    /**
     * Returns name of DataProvider
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Returns JavaScript class name of frontend implementation
     *
     * @return string
     */
    public function getJsClassName(): string;
}
