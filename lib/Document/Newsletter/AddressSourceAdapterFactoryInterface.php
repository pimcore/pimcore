<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Document\Newsletter;

interface AddressSourceAdapterFactoryInterface
{
    /**
     * Configures and creates the AddressSourceAdapterInterface
     *
     * @param array $params
     *
     * @return AddressSourceAdapterInterface
     */
    public function create($params);
}
