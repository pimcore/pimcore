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

use DateTimeInterface;
use IntlDateFormatter;
use NumberFormatter;
use RuntimeException;

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
     * @var IntlDateFormatter[]
     */
    protected array $dateFormatters = [];

    protected ?NumberFormatter $numberFormatter = null;

    /**
     * @var NumberFormatter[]
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
     * @throws RuntimeException
     */
    protected function buildDateTimeFormatters(string $format): IntlDateFormatter
    {
        return match ($format) {
            self::DATE_SHORT => IntlDateFormatter::create(
                $this->getLocale(),
                IntlDateFormatter::SHORT,
                IntlDateFormatter::NONE
            ),
            self::DATE_MEDIUM => IntlDateFormatter::create(
                $this->getLocale(),
                IntlDateFormatter::MEDIUM,
                IntlDateFormatter::NONE
            ),
            self::DATE_LONG => IntlDateFormatter::create(
                $this->getLocale(),
                IntlDateFormatter::LONG,
                IntlDateFormatter::NONE
            ),
            self::DATETIME_SHORT => IntlDateFormatter::create(
                $this->getLocale(),
                IntlDateFormatter::SHORT,
                IntlDateFormatter::SHORT
            ),
            self::DATETIME_MEDIUM => IntlDateFormatter::create(
                $this->getLocale(),
                IntlDateFormatter::MEDIUM,
                IntlDateFormatter::MEDIUM
            ),
            self::DATETIME_LONG => IntlDateFormatter::create(
                $this->getLocale(),
                IntlDateFormatter::LONG,
                IntlDateFormatter::LONG
            ),
            self::TIME_SHORT => IntlDateFormatter::create(
                $this->getLocale(),
                IntlDateFormatter::NONE,
                IntlDateFormatter::SHORT
            ),
            self::TIME_MEDIUM => IntlDateFormatter::create(
                $this->getLocale(),
                IntlDateFormatter::NONE,
                IntlDateFormatter::MEDIUM
            ),
            self::TIME_LONG => IntlDateFormatter::create(
                $this->getLocale(),
                IntlDateFormatter::NONE,
                IntlDateFormatter::LONG
            ),
            default => throw new RuntimeException("Invalid format '{$format}' for date formatter."),
        };
    }

    /**
     * formats given datetime in given format
     *
     * @return false|string
     */
    public function formatDateTime(DateTimeInterface|int|string $dateTime, string $format = self::DATETIME_MEDIUM): bool|string
    {
        $formatter = $this->dateFormatters[$format] ??= $this->buildDateTimeFormatters($format);

        return $formatter->format($dateTime);
    }

    /**
     * formats given value as number based on current locale
     *
     * @return false|string
     */
    public function formatNumber(float|int $value): bool|string
    {
        $this->numberFormatter ??= new NumberFormatter($this->getLocale(), NumberFormatter::DECIMAL);

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
            $formatter = new NumberFormatter($this->getLocale(), NumberFormatter::CURRENCY);

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
