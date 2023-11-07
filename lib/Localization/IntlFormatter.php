<?php
declare(strict_types=1);

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
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Localization;

/**
 * Formatting service for dates, times and numbers
 */
class IntlFormatter
{
    const DATE_SHORT = 'date_short';

    const DATE_MEDIUM = 'date_medium';

    const DATE_LONG = 'date_long';

    const DATETIME_SHORT = 'datetime_short';

    const DATETIME_MEDIUM = 'datetime_medium';

    const DATETIME_LONG = 'datetime_long';

    const TIME_SHORT = 'time_short';

    const TIME_MEDIUM = 'time_medium';

    const TIME_LONG = 'time_long';

    protected ?string $locale = null;

    private LocaleServiceInterface $localeService;

    /**
     * @var \IntlDateFormatter[]
     */
    protected array $dateFormatters = [];

    protected ?\NumberFormatter $numberFormatter = null;

    /**
     * @var \NumberFormatter[]
     */
    protected array $currencyFormatters = [];

    /**
     * ICU DecimalFormat definition per locale for currencies
     *
     * @var string[]
     */
    protected array $currencyFormats = [];

    public function __construct(LocaleServiceInterface $locale)
    {
        $this->localeService = $locale;
    }

    public function getLocale(): string
    {
        if ($this->locale === null) {
            $this->locale = $this->localeService->findLocale();
        }

        return $this->locale;
    }

    public function setLocale(string $locale): void
    {
        $this->locale = $locale;

        //reset formatters
        $this->dateFormatters = [];
        $this->numberFormatter = null;
        $this->currencyFormatters = [];
    }

    public function getCurrencyFormat(string $locale): string
    {
        return $this->currencyFormats[$locale];
    }

    public function setCurrencyFormat(string $locale, string $currencyFormat): void
    {
        $this->currencyFormats[$locale] = $currencyFormat;
    }

    /**
     *
     *
     * @throws \RuntimeException
     */
    protected function buildDateTimeFormatters(string $format): \IntlDateFormatter
    {
        switch ($format) {
            case self::DATE_SHORT:
                return \IntlDateFormatter::create(
                    $this->getLocale(),
                    \IntlDateFormatter::SHORT,
                    \IntlDateFormatter::NONE
                );
            case self::DATE_MEDIUM:
                return \IntlDateFormatter::create(
                    $this->getLocale(),
                    \IntlDateFormatter::MEDIUM,
                    \IntlDateFormatter::NONE
                );
            case self::DATE_LONG:
                return \IntlDateFormatter::create(
                    $this->getLocale(),
                    \IntlDateFormatter::LONG,
                    \IntlDateFormatter::NONE
                );
            case self::DATETIME_SHORT:
                return \IntlDateFormatter::create(
                    $this->getLocale(),
                    \IntlDateFormatter::SHORT,
                    \IntlDateFormatter::SHORT
                );
            case self::DATETIME_MEDIUM:
                return \IntlDateFormatter::create(
                    $this->getLocale(),
                    \IntlDateFormatter::MEDIUM,
                    \IntlDateFormatter::MEDIUM
                );
            case self::DATETIME_LONG:
                return \IntlDateFormatter::create(
                    $this->getLocale(),
                    \IntlDateFormatter::LONG,
                    \IntlDateFormatter::LONG
                );
            case self::TIME_SHORT:
                return \IntlDateFormatter::create(
                    $this->getLocale(),
                    \IntlDateFormatter::NONE,
                    \IntlDateFormatter::SHORT
                );
            case self::TIME_MEDIUM:
                return \IntlDateFormatter::create(
                    $this->getLocale(),
                    \IntlDateFormatter::NONE,
                    \IntlDateFormatter::MEDIUM
                );
            case self::TIME_LONG:
                return \IntlDateFormatter::create(
                    $this->getLocale(),
                    \IntlDateFormatter::NONE,
                    \IntlDateFormatter::LONG
                );
            default:
                throw new \RuntimeException("Invalid format '$format'' for date formatter.");
        }
    }

    /**
     * formats given datetime in given format
     *
     *
     */
    public function formatDateTime(\DateTimeInterface|int|string $dateTime, string $format = self::DATETIME_MEDIUM): bool|string
    {
        if (isset($this->dateFormatters[$format])) {
            $formatter = $this->dateFormatters[$format];
        } else {
            $formatter = $this->buildDateTimeFormatters($format);
            $this->dateFormatters[$format] = $formatter;
        }

        return $formatter->format($dateTime);
    }

    /**
     * formats given value as number based on current locale
     *
     *
     */
    public function formatNumber(float|int $value): bool|string
    {
        if (empty($this->numberFormatter)) {
            $this->numberFormatter = new \NumberFormatter($this->getLocale(), \NumberFormatter::DECIMAL);
        }

        return $this->numberFormatter->format($value);
    }

    /**
     * formats given value as currency string with given currency based on current locale
     *
     *
     */
    public function formatCurrency(float $value, string $currency, string $pattern = 'default'): string
    {
        if (empty($this->currencyFormatters[$pattern])) {
            $formatter = new \NumberFormatter($this->getLocale(), \NumberFormatter::CURRENCY);

            if ($pattern !== 'default') {
                $formatter->setPattern($pattern);
            } elseif ($this->currencyFormats[$this->getLocale()] ?? null) {
                $formatter->setPattern($this->currencyFormats[$this->getLocale()]);
            }

            $this->currencyFormatters[$pattern] = $formatter;
        }

        return $this->currencyFormatters[$pattern]->formatCurrency($value, $currency);
    }
}
