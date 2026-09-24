<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Model\DataObject\ClassDefinition\Data\QuantityValue;

use Doctrine\DBAL\Connection;

/**
 * Formats an already-validated (is_numeric()) decimal string for use in a SQL condition.
 *
 * @internal
 */
final class FilterValueFormatter
{
    /**
     * A plain (float) cast keeps QuantityValue::getFilterConditionExt()'s output byte-for-byte
     * identical, for any value it can represent without loss (~15 significant digits), to what
     * it returned before this class was introduced - which covers virtually every real
     * quantity-value filter, including one that PHP renders back in scientific notation (e.g.
     * "0.000001" -> "1.0E-6"), a form MySQL/MariaDB accept as a valid bare numeric literal. The
     * quantityValue column supports DECIMAL(65, 30), which a PHP float cannot represent exactly,
     * so higher-precision values are quoted instead of cast to avoid truncation. is_numeric()
     * also accepts exponent notation with very few significant digits (e.g. "1e309"), which can
     * overflow a low digit count to INF - not a valid bare SQL literal - so the cast is only
     * reused when it stays finite.
     *
     * $value must already be validated with is_numeric().
     */
    public static function format(Connection $db, string $value): string
    {
        $significantDigits = strlen(ltrim(str_replace(['-', '+', '.'], '', $value), '0')) ?: 1;

        if ($significantDigits <= 15) {
            $float = (float) $value;

            if (is_finite($float)) {
                return (string) $float;
            }
        }

        return $db->quote($value);
    }
}
