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
 * @category   Pimcore
 * @package    EcommerceFramework
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */


namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model;

class Currency {

    const LEFT = 'left';
    const RIGHT = 'right';
    const NO_SYMBOL = 'none';
    const USE_SYMBOL = 'sign';
    const USE_SHORTNAME = 'shortname';
    const USE_NAME = 'longname';

    /**
     * @var string
     */
    protected $currencyShortName;

    /**
     * @var string
     */
    protected $currencySymbol;

    /**
     * @var string
     */
    protected $currencyName;

    /**
     * @var \Pimcore\Bundle\PimcoreBundle\Service\IntlFormatterService
     */
    protected $formattingService;

    /**
     * @var array
     */
    protected $patternStore = [
        self::NO_SYMBOL => [
            self::LEFT => "#,##0.00",
            self::RIGHT => "#,##0.00"
        ],
        self::USE_SYMBOL => [
            self::LEFT => "¤ #,##0.00",
            self::RIGHT => "#,##0.00 ¤"
        ],
        self::USE_SHORTNAME => [
            self::LEFT => "¤¤ #,##0.00",
            self::RIGHT => "#,##0.00 ¤¤"
        ],
        self::USE_NAME => [
            self::LEFT => "¤¤¤ #,##0.00",
            self::RIGHT => "#,##0.00 ¤¤¤"
        ]
    ];

    /**
     * Currency constructor.
     * @param $currencyShortName string
     */
    public function __construct($currencyShortName)
    {
        $this->currencyShortName = $currencyShortName;
        $this->formattingService = \Pimcore::getContainer()->get("pimcore.locale.intl_formatter");
    }

    /**
     * @param float $value
     * @param string $pattern
     * @return string
     */
    public function toCurrency($value, $pattern = 'default') {

        if(is_array($pattern)) {
            $symbol = $pattern['display'] ? $pattern['display'] : self::USE_SYMBOL;
            $position = $pattern['position'] ? $pattern['position'] : self::RIGHT;

            $pattern = $this->patternStore[$symbol][$position] ? $this->patternStore[$symbol][$position] : 'default';
        }

        return $this->formattingService->formatCurrency($value, $this->currencyShortName, $pattern);
    }

    /**
     * @return string
     */
    public function getShortName() {
        return $this->currencyShortName;
    }

    /**
     * @return string
     */
    public function getSymbol() {
        if(empty($this->currencySymbol)) {
            $result = $this->formattingService->formatCurrency(0, $this->currencyShortName, "¤||");
            $parts = explode("||", $result);
            $this->currencySymbol = $parts[0];
        }
        return $this->currencySymbol;
    }

    /**
     * @return string
     */
    public function getName() {
        if(empty($this->currencyName)) {
            $result = $this->formattingService->formatCurrency(0, $this->currencyShortName, "¤¤¤||");
            $parts = explode("||", $result);
            $this->currencyName = $parts[0];
        }
        return $this->currencyName;
    }

}