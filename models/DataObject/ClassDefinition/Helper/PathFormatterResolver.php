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
 * @category   Pimcore
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\DataObject\ClassDefinition\Helper;

use Pimcore\Logger;
use Pimcore\Model\DataObject\ClassDefinition\PathFormatterInterface;

class PathFormatterResolver
{
    public static $formatterCache = [];

    /**
     * @param $formatterClass
     *
     * @return PathFormatterInterface
     */
    public static function resolvePathFormatter($formatterClass): ?PathFormatterInterface
    {
        if ($formatterClass) {
            $formatter = null;

            if (isset(self::$formatterCache[$formatterClass])) {
                return self::$formatterCache[$formatterClass];
            }
            if (substr($formatterClass, 0, 1) == '@') {
                $serviceName = substr($formatterClass, 1);
                try {
                    $formatter = \Pimcore::getKernel()->getContainer()->get($serviceName);
                } catch (\Exception $e) {
                    Logger::error($e);
                }
            } else {
                $formatter = new $formatterClass;
            }

            if ($formatter instanceof PathFormatterInterface) {
                return $formatter;
            }
        }

        return null;
    }
}
