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


namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\VoucherService\TokenManager;

interface IExportableTokenManager
{
    const FORMAT_CSV   = 'csv';
    const FORMAT_PLAIN = 'plain';

    /**
     * Export tokens to CSV
     *
     * @param array $params
     * @return string
     */
    public function exportCsv(array $params);

    /**
     * Export tokens to plain text list
     *
     * @param array $params
     * @return string
     */
    public function exportPlain(array $params);
}
