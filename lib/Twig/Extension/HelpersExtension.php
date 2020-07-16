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

namespace Pimcore\Twig\Extension;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\TwigTest;

/**
 * Simple helpers that do not need a dedicated extension
 */
class HelpersExtension extends AbstractExtension
{
    public function getFilters()
    {
        return [
            new TwigFilter('basename', [$this, 'basenameFilter']),
        ];
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('callStatic', function ($class, $method, $args = array()) {
                if (class_exists($class) && method_exists($class, $method)) {
                    return call_user_func_array(array($class, $method), $args);
                }

                return null;
            }),
            new TwigFunction('fileExists', function ($file) {
                return file_exists($file);
            }),
        ];
    }

    public function getTests()
    {
        return [
            new TwigTest('instanceof', function ($object, $class) {
                return is_object($object) && $object instanceof $class;
            }),
        ];
    }

    /**
     * @param string $value
     * @param string $suffix
     *
     * @return string
     */
    public function basenameFilter($value, $suffix = '')
    {
        return basename($value, $suffix);
    }
}
