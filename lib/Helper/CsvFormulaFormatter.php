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

class CsvFormulaFormatter extends \League\Csv\EscapeFormula
{
    public function unEscapeField(string $field): string
    {
        if (isset($field[0], $field[1])
            && $field[0] === $this->getEscape()
            && in_array($field[1], $this->getSpecialCharacters())
        ) {
            return ltrim($field, $field[0]);
        }

        return $field;
    }
}
