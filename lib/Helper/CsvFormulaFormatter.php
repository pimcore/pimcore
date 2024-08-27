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

namespace Pimcore\Helper;

use League\Csv\EscapeFormula;

/**
 * @deprecated and will be removed in Pimcore 12. Use \League\Csv\EscapeFormula instead.
 */
class CsvFormulaFormatter extends EscapeFormula
{
    public function unEscapeField(mixed $field): string
    {
        trigger_deprecation(
            'pimcore/pimcore',
            '11.1.0',
            sprintf('The "%s" class is deprecated, use "%s" instead.', __CLASS__, EscapeFormula::class)
        );

        if (isset($field[0], $field[1])
            && $field[0] === $this->getEscape()
            && in_array($field[1], $this->getSpecialCharacters())
        ) {
            return ltrim($field, $field[0]);
        }

        return $field;
    }
}
