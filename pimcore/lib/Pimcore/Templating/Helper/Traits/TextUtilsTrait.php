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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Templating\Helper\Traits;

trait TextUtilsTrait
{
    /**
     * @param string $string
     * @param int|null $length
     * @param string $suffix
     *
     * @return string
     */
    public function normalizeString($string, $length = null, $suffix = '')
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

    /**
     * @param string $string
     *
     * @return string
     */
    public function getStringAsOneLine($string)
    {
        $string = str_replace("\r\n", ' ', $string);
        $string = str_replace("\n", ' ', $string);
        $string = str_replace("\r", ' ', $string);
        $string = str_replace("\t", '', $string);
        $string = preg_replace('#[ ]+#', ' ', $string);

        return $string;
    }

    /**
     * @param string $string
     * @param int $length
     *
     * @return string
     */
    public function truncateString($string, $length)
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
