<?php

namespace OnlineShop\Framework\VoucherService\TokenManager;

interface IExportableTokenManager
{
    const FORMAT_CSV = 'csv';

    /**
     * Export tokens
     *
     * @param \Zend_View $view
     * @param array $params
     * @param string $format
     * @return mixed
     */
    public function exportTokens(\Zend_View $view, $params, $format = self::FORMAT_CSV);
}
