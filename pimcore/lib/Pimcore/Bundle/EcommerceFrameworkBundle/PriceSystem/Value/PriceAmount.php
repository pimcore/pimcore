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
    private static $defaultScale = 4;

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
    public function __construct(int $amount, int $scale)
    {
        static::validateScale($scale);

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

    private static function validateScale(int $scale)
    {
        if ($scale < 0) {
            throw new \DomainException('Scale must be greater or equal than 0');
        }
    }

    /**
     * Round value to int value if needed
     *
     * @param $value
     *
     * @return int
     */
    private static function toIntValue($value): int
    {
        if (!is_int($value)) {
            $value = (int)round($value);
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
     *
     * @return self
     * @throws \TypeError
     */
    public static function create($amount, int $scale = null)
    {
        if (is_numeric($amount)) {
            return static::fromNumeric($amount, $scale);
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
        return new static($amount, $scale ?? static::$defaultScale);
    }

    /**
     * Creates a value from a numeric input. The given amount will be converted to int
     * with the given scale. Please note that this implicitely rounds the amount to the
     * next integer, so precision depends on the given scale.
     *
     * @param int|float|string $amount
     * @param int|null $scale
     *
     * @return PriceAmount
     */
    public static function fromNumeric($amount, int $scale = null): self
    {
        if (!is_numeric($amount)) {
            throw new \InvalidArgumentException('Value is not numeric');
        }

        $scale = $scale ?? static::$defaultScale;

        $result = $amount * pow(10, $scale);
        $result = static::toIntValue($result);

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

        // object is identical - creating a new object is not necessary
        if ($amount->scale === $scale) {
            return $amount;
        }

        return $amount->withScale($scale);
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
     *
     * @return PriceAmount
     */
    public function withScale(int $scale): self
    {
        // no need to create a new object as output would be identical
        if ($scale === $this->scale) {
            return $this;
        }

        $this->validateScale($scale);

        $diff = $scale - $this->scale;

        $result = $this->amount * pow(10, $diff);
        $result = static::toIntValue($result);

        return new static($result, $scale);
    }

    /**
     * Adds another price amount
     *
     * @param PriceAmount $other
     *
     * @return PriceAmount
     */
    public function add(PriceAmount $other): self
    {
        $this->compareScale($other);

        return new static($this->amount + $other->amount, $this->scale);
    }

    /**
     * Subtracts another price amount
     *
     * @param PriceAmount $other
     *
     * @return PriceAmount
     */
    public function sub(PriceAmount $other): self
    {
        $this->compareScale($other);

        return new static($this->amount - $other->amount, $this->scale);
    }

    /**
     * Multiplies by the given factor. This does NOT have to be a price amount, but can be
     * a simple scalar factor (e.g. 2) as multiplying prices is rarely needed. However, if
     * a PriceAmount is passed, its float representation will be used for calculations.
     *
     * @param int|float|PriceAmount $other
     *
     * @return PriceAmount
     */
    public function mul($other): self
    {
        $operand = $this->getScalarOperand($other);

        $result = $this->amount * $operand;
        $result = static::toIntValue($result);

        return new static($result, $this->scale);
    }

    /**
     * Divides by the given divisor. This does NOT have to be a price amount, but can be
     * a simple scalar factor (e.g. 2) as dividing prices is rarely needed. However, if
     * a PriceAmount is passed, its float representation will be used for calculations.
     *
     * @param int|float|PriceAmount $other
     *
     * @return PriceAmount
     * @throws \DivisionByZeroError
     */
    public function div($other): self
    {
        $operand = $this->getScalarOperand($other);
        $epsilon = pow(10, -1 * $this->scale);

        if (abs(0 - $operand) < $epsilon) {
            throw new \DivisionByZeroError('Division by zero is not allowed');
        }

        $result = $this->amount / $operand;
        $result = static::toIntValue($result);

        return new static($result, $this->scale);
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

    private function compareScale(PriceAmount $other)
    {
        if ($other->scale !== $this->scale) {
            throw new \DomainException('Can\'t operate on amounts with different scales. Please convert both amounts to the same scale before proceeding.');
        }
    }
}
