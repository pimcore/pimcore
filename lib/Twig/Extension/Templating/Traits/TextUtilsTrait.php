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

namespace Pimcore\Twig\Extension\Templating\Traits;

use Pimcore\Tool\Text;

/**
 * @internal
 */
trait TextUtilsTrait
{
    public function normalizeString(string $string, ?int $length = null, string $suffix = ''): string
    {
        $string = strip_tags($string);
        $string = $this->getStringAsOneLine($string);

        if ($length) {
            $truncated = $this->truncateString($string, $length);

            if ($suffix && $truncated !== $string) {
                $truncated .= $suffix;
            }

            $string = $truncated;
        }

        return $string;
    }

    public function getStringAsOneLine(string $string): string
    {
        return Text::getStringAsOneLine($string);
    }

    public function truncateString(string $string, int $length): string
    {
        if ($length < mb_strlen($string)) {
            $text = mb_substr($string, 0, $length);
            if (false !== ($length = mb_strrpos($text, ' '))) {
                $text = mb_substr($text, 0, $length);
            }

            $string = $text;
        }

        return $string;
    }
}
