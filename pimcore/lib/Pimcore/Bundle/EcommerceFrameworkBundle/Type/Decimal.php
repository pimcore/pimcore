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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\Type;

class Decimal
{
    const INTEGER_NUMBER_REGEXP = '/^([+\-]?)\d+$/';
    const DECIMAL_NUMBER_REGEXP = '/^([+\-]?)(\d+)\.(\d+)$/';

    /**
     * @var int
     */
    protected static $defaultScale = 4;

    /**
     * @var int
     */
    private $amount;

    /**
     * Precision after comma - actual amount will be amount divided by 10^scale
     *
     * @var int
     */
    private $scale;

    /**
     * Builds a value from an integer. The integer amount here must be the final value with
     * conversion factor already applied.
     *
     * @param int $amount
     * @param int $scale
     */
    protected function __construct(int $amount, int $scale)
    {
        $this->amount = $amount;
        $this->scale  = $scale;
    }

    /**
     * Sets the global default scale to be used
     *
     * @param int $scale
     */
    public static function setDefaultScale(int $scale)
    {
        static::validateScale($scale);
        static::$defaultScale = $scale;
    }

    /**
     * Validates scale not being negative
     *
     * @param int $scale
     */
    private static function validateScale(int $scale)
    {
        if ($scale < 0) {
            throw new \DomainException('Scale must be greater or equal than 0');
        }
    }

    /**
     * Asserts that an integer value didn't become something else
     * (after some arithmetic operation).
     *
     * Adapted from moneyphp/money PhpCalculator
     *
     * @param $amount
     *
     * @throws \OverflowException  If integer overflow occured
     * @throws \UnderflowException If integer underflow occured
     */
    private static function validateIntegerBounds($amount)
    {
        if ($amount > (PHP_INT_MAX - 1)) {
            throw new \OverflowException('The maximum allowed integer (PHP_INT_MAX) was reached');
        } elseif ($amount < (~PHP_INT_MAX + 1)) {
            throw new \UnderflowException('The minimum allowed integer (PHP_INT_MAX) was reached');
        }
    }

    /**
     * Round value to int value if needed
     *
     * @param $value
     * @param int|null $roundingMode
     *
     * @return int
     */
    private static function toIntValue($value, int $roundingMode = null): int
    {
        if (!is_int($value)) {
            $value = round($value, 0, $roundingMode ?? PHP_ROUND_HALF_EVEN);
            $value = (int)$value;
        }

        return $value;
    }

    /**
     * Creates a value. If an integer is passed, its value will be used without any conversions. Any
     * other value (float, string) will be converted to int with the given scale. If a Decimal is
     * passed, it will be converted to the given scale if necessary. Example:
     *
     * input: 15
     * scale: 4
     * amount: 15 * 10^4 = 150000, scale: 4
     *
     * @param int|float|string|self $amount
     * @param int|null $scale
     * @param int|null $roundingMode
     *
     * @return self
     * @throws \TypeError
     */
    public static function create($amount, int $scale = null, int $roundingMode = null)
    {
        if (is_string($amount)) {
            return static::fromString($amount, $scale, $roundingMode);
        } elseif (is_numeric($amount)) {
            return static::fromNumeric($amount, $scale, $roundingMode);
        } elseif ($amount instanceof self) {
            return static::fromDecimal($amount, $scale);
        } else {
            throw new \TypeError(
                'Expected (int, float, string, self), but received ' .
                (is_object($amount) ? get_class($amount) : gettype($amount))
            );
        }
    }

    /**
     * Creates a value from an raw integer input. No value conversions will be done.
     *
     * @param int $amount
     * @param int|null $scale
     *
     * @return self
     */
    public static function fromRawValue(int $amount, int $scale = null): self
    {
        $scale = $scale ?? static::$defaultScale;
        static::validateScale($scale);

        return new static($amount, $scale);
    }

    /**
     * Creates a value from a string input. If possible, the integer will be created with
     * string operations (e.g. adding zeroes), otherwise it will fall back to fromNumeric().
     *
     * @param string $amount
     * @param int|null $scale
     * @param int|null $roundingMode
     *
     * @return Decimal
     */
    public static function fromString(string $amount, int $scale = null, int $roundingMode = null): self
    {
        $scale = $scale ?? static::$defaultScale;
        static::validateScale($scale);

        $result = null;

        if (1 === preg_match(self::INTEGER_NUMBER_REGEXP, $amount, $captures)) {
            // no decimals -> add zeroes until we have the expected amount
            // e.g. 1234, scale 4 = 12340000
            $result = (int)($amount . str_repeat('0', $scale));
        } elseif (1 === preg_match(self::DECIMAL_NUMBER_REGEXP, $amount, $captures)) {
            // decimal part is lower/equals than scale - add zeroes as needed and concat it with the integer part
            // e.g. 123.45 at scale 4 -> 123 (integer) . 4500 (zero padded decimal part) => 1234500
            if (strlen($captures[3]) <= $scale) {
                $fractionalPart = str_pad($captures[3], $scale, '0', STR_PAD_RIGHT);
                $result         = (int)($captures[1] . $captures[2] . $fractionalPart);
            }
        }

        if (null !== $result) {
            static::validateIntegerBounds($result);

            return new static($result, $scale);
        }

        // default to numeric - this will also apply rounding as we
        // fall back to floats here
        return static::fromNumeric($amount, $scale, $roundingMode);
    }

    /**
     * Creates a value from a numeric input. The given amount will be converted to int
     * with the given scale. Please note that this implicitely rounds the amount to the
     * next integer, so precision depends on the given scale.
     *
     * @param int|float|string $amount
     * @param int|null $scale
     * @param int|null $roundingMode
     *
     * @return Decimal
     */
    public static function fromNumeric($amount, int $scale = null, int $roundingMode = null): self
    {
        if (!is_numeric($amount)) {
            throw new \InvalidArgumentException('Value is not numeric');
        }

        $scale = $scale ?? static::$defaultScale;
        static::validateScale($scale);

        $result = $amount * pow(10, $scale);
        static::validateIntegerBounds($result);

        $result = static::toIntValue($result, $roundingMode);

        return new static($result, $scale);
    }

    /**
     * Creates a value from another price value. If the scale matches the given scale,
     * the input value will be returned, otherwise the scale will be converted and a
     * new object will be returned. Please note that this will potentially imply precision
     * loss when converting to a lower scale.
     *
     * @param Decimal $amount
     * @param int|null $scale
     *
     * @return Decimal
     */
    public static function fromDecimal(Decimal $amount, int $scale = null): self
    {
        $scale = $scale ?? static::$defaultScale;
        static::validateScale($scale);

        // object is identical - creating a new object is not necessary
        if ($amount->scale === $scale) {
            return $amount;
        }

        return $amount->withScale($scale);
    }

    /**
     * Create a zero value object
     *
     * @param int|null $scale
     *
     * @return Decimal
     */
    public static function zero(int $scale = null): self
    {
        return static::fromRawValue(0, $scale);
    }

    /**
     * Returns the used scale factor
     *
     * @return int
     */
    public function getScale(): int
    {
        return $this->scale;
    }

    /**
     * Returns the internal representation value
     *
     * WARNING: use this with caution as the represented value depends on the scale!
     *
     * @return int
     */
    public function asRawValue(): int
    {
        return $this->amount;
    }

    /**
     * Returns a numeric representation
     *
     * @return int|float
     */
    public function asNumeric()
    {
        return $this->amount / pow(10, $this->scale);
    }

    /**
     * Returns a string representation. Digits default to the scale. If $digits is passed,
     * the string will be truncated to the given amount of digits without any rounding.
     *
     * @param int|null $digits
     *
     * @return string
     */
    public function asString(int $digits = null): string
    {
        $string = strval($this->amount);
        $amount = null;

        if ($this->scale === 0) {
            $amount = $string;
        } elseif (strlen($string) <= $this->scale) {
            $fractionalPart = str_pad($string, $this->scale, '0', STR_PAD_LEFT);

            $amount = '0.' . $fractionalPart;
        } else {
            $fractionalOffset = strlen($string) - $this->scale;
            $integerPart      = substr($string, 0, $fractionalOffset);
            $fractionalPart   = substr($string, $fractionalOffset);

            $amount = $integerPart . '.' . $fractionalPart;
        }

        if (null !== $digits) {
            $amount = $this->truncateDecimalString($amount, $digits);
        }

        return $amount;
    }

    /**
     * Converts decimal string to the given amount of digits. No rounding is done here - additional digits are
     * just truncated.
     *
     * @param string $amount
     * @param int $digits
     *
     * @return string
     */
    private function truncateDecimalString(string $amount, int $digits): string
    {
        $integerPart    = $amount;
        $fractionalPart = '0';

        if (false !== strpos($amount, '.')) {
            list($integerPart, $fractionalPart) = explode('.', $amount);
        }

        if ($digits === 0) {
            return $integerPart;
        }

        if (strlen($fractionalPart) > $digits) {
            $fractionalPart = substr($fractionalPart, 0, $digits);
        } elseif (strlen($fractionalPart) < $digits) {
            $fractionalPart = str_pad($fractionalPart, $digits, '0', STR_PAD_RIGHT);
        }

        return $integerPart . '.'. $fractionalPart;
    }

    /**
     * Default string representation
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->asString();
    }

    /**
     * Builds a value with the given scale
     *
     * @param int $scale
     * @param int|null $roundingMode
     *
     * @return Decimal
     */
    public function withScale(int $scale, int $roundingMode = null): self
    {
        static::validateScale($scale);

        // no need to create a new object as output would be identical
        if ($scale === $this->scale) {
            return $this;
        }

        $diff = $scale - $this->scale;

        $result = $this->amount * pow(10, $diff);
        static::validateIntegerBounds($result);

        $result = static::toIntValue($result, $roundingMode);

        return new static($result, $scale);
    }

    /**
     * Checks if value is equal to other value
     *
     * @param Decimal $other
     *
     * @todo Assert same scale before comparing?
     *
     * @return bool
     */
    public function equals(Decimal $other): bool
    {
        return $other->scale === $this->scale && $other->amount === $this->amount;
    }

    /**
     * Compares a value to another one
     *
     * @param Decimal $other
     *
     * @return int
     */
    public function compare(Decimal $other): int
    {
        $this->assertSameScale($other, 'Can\'t compare values with different scales. Please convert both values to the same scale.');

        if ($this->amount === $other->amount) {
            return 0;
        }

        return ($this->amount > $other->amount) ? 1 : -1;
    }

    /**
     * Compares this > other
     *
     * @param Decimal $other
     *
     * @return bool
     */
    public function greaterThan(Decimal $other): bool
    {
        return $this->compare($other) === 1;
    }

    /**
     * Compares this >= other
     *
     * @param Decimal $other
     *
     * @return bool
     */
    public function greaterThanOrEqual(Decimal $other): bool
    {
        return $this->compare($other) >= 0;
    }

    /**
     * Compares this < other
     *
     * @param Decimal $other
     *
     * @return bool
     */
    public function lessThan(Decimal $other): bool
    {
        return $this->compare($other) === -1;
    }

    /**
     * Compares this <= other
     *
     * @param Decimal $other
     *
     * @return bool
     */
    public function lessThanOrEqual(Decimal $other): bool
    {
        return $this->compare($other) <= 0;
    }

    /**
     * Checks if amount is zero
     *
     * @return bool
     */
    public function isZero(): bool
    {
        return 0 === $this->amount;
    }

    /**
     * Checks if amount is positive. Not: zero is NOT handled as positive.
     *
     * @return bool
     */
    public function isPositive(): bool
    {
        return $this->amount > 0;
    }

    /**
     * Checks if amount is negative
     *
     * @return bool
     */
    public function isNegative(): bool
    {
        return $this->amount < 0;
    }

    /**
     * Returns the absolute amount
     *
     * @return Decimal
     */
    public function abs(): self
    {
        if ($this->amount < 0) {
            return new static((int)abs($this->amount), $this->scale);
        }

        return $this;
    }

    /**
     * Adds another price amount
     *
     * @param Decimal|int|float|string $other
     *
     * @return Decimal
     */
    public function add($other): self
    {
        if (!$other instanceof Decimal) {
            $other = static::fromNumeric($other, $this->scale);
        }

        $this->assertSameScale($other);

        $result = $this->amount + $other->amount;
        static::validateIntegerBounds($result);

        return new static($result, $this->scale);
    }

    /**
     * Subtracts another price amount
     *
     * @param Decimal|int|float|string $other
     *
     * @return Decimal
     */
    public function sub($other): self
    {
        if (!$other instanceof Decimal) {
            $other = static::fromNumeric($other, $this->scale);
        }

        $this->assertSameScale($other);

        $result = $this->amount - $other->amount;
        static::validateIntegerBounds($result);

        return new static($result, $this->scale);
    }

    /**
     * Multiplies by the given factor. This does NOT have to be a price amount, but can be
     * a simple scalar factor (e.g. 2) as multiplying prices is rarely needed. However, if
     * a Decimal is passed, its float representation will be used for calculations.
     *
     * @param int|float|Decimal $other
     * @param int|null $roundingMode
     *
     * @return Decimal
     */
    public function mul($other, int $roundingMode = null): self
    {
        $operand = $this->getScalarOperand($other);

        $result = $this->amount * $operand;
        static::validateIntegerBounds($result);

        $result = static::toIntValue($result, $roundingMode);

        return new static($result, $this->scale);
    }

    /**
     * Divides by the given divisor. This does NOT have to be a price amount, but can be
     * a simple scalar factor (e.g. 2) as dividing prices is rarely needed. However, if
     * a Decimal is passed, its float representation will be used for calculations.
     *
     * @param int|float|Decimal $other
     * @param int|null $roundingMode
     *
     * @return Decimal
     * @throws \DivisionByZeroError
     */
    public function div($other, int $roundingMode = null): self
    {
        $operand = $this->getScalarOperand($other);
        $epsilon = pow(10, -1 * $this->scale);

        if (abs(0 - $operand) < $epsilon) {
            throw new \DivisionByZeroError('Division by zero is not allowed');
        }

        $result = $this->amount / $operand;
        static::validateIntegerBounds($result);

        $result = static::toIntValue($result, $roundingMode);

        return new static($result, $this->scale);
    }

    /**
     * Returns the additive inverse of a value (e.g. 5 returns -5, -5 returns 5)
     *
     * @example Decimal::create(5)->toAdditiveInverse() = -5
     * @example Decimal::create(-5)->toAdditiveInverse() = 5
     *
     * @return Decimal
     */
    public function toAdditiveInverse(): self
    {
        return $this->mul(-1);
    }

    /**
     * Calculate a percentage amount
     *
     * @param int|float $percentage
     * @param int|null $roundingMode
     *
     * @return Decimal
     */
    public function toPercentage($percentage, int $roundingMode = null): self
    {
        $percentage = $this->getScalarOperand($percentage);

        return $this->mul(($percentage / 100), $roundingMode);
    }

    /**
     * Calculate a discounted amount
     *
     * @example Decimal::create(100)->discount(15) = 85
     *
     * @param $discount
     * @param int|null $roundingMode
     *
     * @return Decimal
     */
    public function discount($discount, int $roundingMode = null): self
    {
        $discount = $this->getScalarOperand($discount);

        return $this->sub(
            $this->toPercentage($discount, $roundingMode)
        );
    }

    /**
     * Get the relative percentage to another value
     *
     * @example Decimal::create(100)->percentageOf(Decimal::create(50)) = 200
     * @example Decimal::create(50)->percentageOf(Decimal::create(100)) = 50
     *
     * @param Decimal $other
     *
     * @return int|float
     */
    public function percentageOf(Decimal $other)
    {
        $this->assertSameScale($other);

        if ($this->equals($other)) {
            return 100;
        }

        return ($this->asRawValue() * 100) / $other->asRawValue();
    }

    /**
     * Get the discount percentage starting from a discounted price
     *
     * @example Decimal::create(30)->discountPercentageOf(Decimal::create(100)) = 70
     *
     * @param Decimal $other
     *
     * @return int|float
     */
    public function discountPercentageOf(Decimal $other)
    {
        $this->assertSameScale($other);

        if ($this->equals($other)) {
            return 0;
        }

        return 100 - $this->percentageOf($other);
    }

    /**
     * Transforms operand into a numeric value used for calculations.
     *
     * @param int|float|Decimal $operand
     *
     * @return float
     */
    private function getScalarOperand($operand)
    {
        if (is_numeric($operand)) {
            return $operand;
        } elseif ($operand instanceof static) {
            return $operand->asNumeric();
        }

        throw new \InvalidArgumentException(sprintf(
            'Value "%s" with type "%s" is no valid operand',
            (is_scalar($operand)) ? $operand : (string)$operand,
            (is_object($operand) ? get_class($operand) : gettype($operand))
        ));
    }

    private function assertSameScale(Decimal $other, string $message = null)
    {
        if ($other->scale !== $this->scale) {
            $message = $message ?? 'Can\'t operate on amounts with different scales. Please convert both amounts to the same scale before proceeding.';

            throw new \DomainException($message);
        }
    }
}
