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

namespace Pimcore\Tool;

use Symfony\Component\Finder\SplFileInfo;

class ClassUtils
{
    /**
     * Returns the base name for a class
     *
     * @param string|object $class
     *
     * @return string
     */
    public static function getBaseName($class): string
    {
        return (new \ReflectionClass($class))->getShortName();
    }

    /**
     * Finds the fully qualified class name from a given PHP file by parsing the file content
     *
     * @see http://jarretbyrne.com/2015/06/197/
     *
     * @param \SplFileInfo $file
     *
     * @return string
     */
    public static function findClassName(\SplFileInfo $file): string
    {
        $namespace = '';
        $class = '';

        $gettingNamespace = false;
        $gettingClass = false;

        if (!$file->isReadable() || !file_exists($file->getPathname())) {
            throw new \InvalidArgumentException(sprintf('File %s does not exist or is not readable'));
        }

        $content = '';
        if ($file instanceof SplFileInfo) {
            $content = $file->getContents();
        } else {
            $content = file_get_contents($file->getPathname());
        }

        $content = trim($content);
        if (empty($content)) {
            throw new \RuntimeException(sprintf('Failed to get find class name in file %s as file is empty', $file->getPathname()));
        }

        foreach (token_get_all($content) as $token) {
            // start collecting as soon as we find the namespace token
            if (is_array($token) && $token[0] == T_NAMESPACE) {
                $gettingNamespace = true;
            } elseif (is_array($token) && $token[0] === T_CLASS) {
                $gettingClass = true;
            }

            if ($gettingNamespace) {
                if (is_array($token) && in_array($token[0], [T_STRING, T_NS_SEPARATOR])) {
                    // append to namespace
                    $namespace .= $token[1];
                } elseif ($token === ';') {
                    // namespace done
                    $gettingNamespace = false;
                }
            }

            if ($gettingClass) {
                if (is_array($token) && $token[0] === T_STRING) {
                    $class = $token[1];

                    // all done
                    break;
                }
            }
        }

        if (empty($class)) {
            throw new \RuntimeException(sprintf('Failed to get find class name in file %s', $file->getPathname()));
        }

        return empty($namespace) ? $class : $namespace . '\\' . $class;
    }
}
