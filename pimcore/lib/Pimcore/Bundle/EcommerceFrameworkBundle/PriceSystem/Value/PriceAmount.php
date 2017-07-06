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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\Value;

class PriceAmount
{
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
     * Builds a price amount from an integer. The integer amount here must be the final value
     * with conversion factor already applied.
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
            $value = (int)round($value, 0, $roundingMode ?? PHP_ROUND_HALF_UP);
        }

        return $value;
    }

    /**
     * Creates a value. If an integer is passed, its value will be used without any conversions. Any
     * other value (float, string) will be converted to int with the given scale. If a PriceAmount is
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
        if (is_numeric($amount)) {
            return static::fromNumeric($amount, $scale, $roundingMode);
        } elseif ($amount instanceof self) {
            return static::fromPriceAmount($amount, $scale);
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
     * Creates a value from a numeric input. The given amount will be converted to int
     * with the given scale. Please note that this implicitely rounds the amount to the
     * next integer, so precision depends on the given scale.
     *
     * @param int|float|string $amount
     * @param int|null $scale
     * @param int|null $roundingMode
     *
     * @return PriceAmount
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
     * @param PriceAmount $amount
     * @param int|null $scale
     *
     * @return PriceAmount
     */
    public static function fromPriceAmount(PriceAmount $amount, int $scale = null): self
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
     * @return PriceAmount
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
     * Returns a string representation. Amount of digits defaults to the scale
     *
     * @param int|null $digits
     *
     * @return string
     */
    public function asString(int $digits = null): string
    {
        $digits = $digits ?? $this->scale;

        return number_format($this->asNumeric(), $digits, '.', '');
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
     * @return PriceAmount
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
     * @param PriceAmount $other
     *
     * @todo Assert same scale before comparing?
     *
     * @return bool
     */
    public function equals(PriceAmount $other): bool
    {
        return $other->scale === $this->scale && $other->amount === $this->amount;
    }

    /**
     * Compares a value to another one
     *
     * @param PriceAmount $other
     *
     * @return int
     */
    public function compare(PriceAmount $other): int
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
     * @param PriceAmount $other
     *
     * @return bool
     */
    public function greaterThan(PriceAmount $other): bool
    {
        return $this->compare($other) === 1;
    }

    /**
     * Compares this >= other
     *
     * @param PriceAmount $other
     *
     * @return bool
     */
    public function greaterThanOrEqual(PriceAmount $other): bool
    {
        return $this->compare($other) >= 0;
    }

    /**
     * Compares this < other
     *
     * @param PriceAmount $other
     *
     * @return bool
     */
    public function lessThan(PriceAmount $other): bool
    {
        return $this->compare($other) === -1;
    }

    /**
     * Compares this <= other
     *
     * @param PriceAmount $other
     *
     * @return bool
     */
    public function lessThanOrEqual(PriceAmount $other): bool
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
     * @return PriceAmount
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
     * @param PriceAmount|int|float|string $other
     *
     * @return PriceAmount
     */
    public function add($other): self
    {
        if (!$other instanceof PriceAmount) {
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
     * @param PriceAmount|int|float|string $other
     *
     * @return PriceAmount
     */
    public function sub($other): self
    {
        if (!$other instanceof PriceAmount) {
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
     * a PriceAmount is passed, its float representation will be used for calculations.
     *
     * @param int|float|PriceAmount $other
     * @param int|null $roundingMode
     *
     * @return PriceAmount
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
     * a PriceAmount is passed, its float representation will be used for calculations.
     *
     * @param int|float|PriceAmount $other
     * @param int|null $roundingMode
     *
     * @return PriceAmount
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
     * @example PriceAmount::create(5)->toAdditiveInverse() = -5
     * @example PriceAmount::create(-5)->toAdditiveInverse() = 5
     *
     * @return PriceAmount
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
     * @return PriceAmount
     */
    public function toPercentage($percentage, int $roundingMode = null): self
    {
        $percentage = $this->getScalarOperand($percentage);

        return $this->mul(($percentage / 100), $roundingMode);
    }

    /**
     * Calculate a discounted amount
     *
     * @example PriceAmount::create(100)->discount(15) = 85
     *
     * @param $discount
     * @param int|null $roundingMode
     *
     * @return PriceAmount
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
     * @example PriceAmount::create(100)->percentageOf(PriceAmount::create(50)) = 200
     * @example PriceAmount::create(50)->percentageOf(PriceAmount::create(100)) = 50
     *
     * @param PriceAmount $other
     *
     * @return int|float
     */
    public function percentageOf(PriceAmount $other)
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
     * @example PriceAmount::create(30)->discountPercentageOf(PriceAmount::create(100)) = 70
     *
     * @param PriceAmount $other
     *
     * @return int|float
     */
    public function discountPercentageOf(PriceAmount $other)
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
     * @param int|float|PriceAmount $operand
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

    private function assertSameScale(PriceAmount $other, string $message = null)
    {
        if ($other->scale !== $this->scale) {
            $message = $message ?? 'Can\'t operate on amounts with different scales. Please convert both amounts to the same scale before proceeding.';

            throw new \DomainException($message);
        }
    }
}
