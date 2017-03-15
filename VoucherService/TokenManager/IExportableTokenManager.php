<?php

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
