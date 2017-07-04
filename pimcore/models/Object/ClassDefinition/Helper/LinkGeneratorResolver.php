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
 * @package    Object|Class
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Object\ClassDefinition\Helper;

use Pimcore\Logger;
use Pimcore\Model\Object\ClassDefinition\LinkGeneratorInterface;


class LinkGeneratorResolver
{

    public static $generatorCache = [];

    public static function resolveGenerator($generatorClass)
    {
        if ($generatorClass) {
            if (isset(self::$generatorCache[$generatorClass])) {
                return self::$generatorCache[$generatorClass];
            }
            if (substr($generatorClass, 0, 1) == '@') {
                $serviceName = substr($generatorClass, 1);
                try {
                    $generator = \Pimcore::getKernel()->getContainer()->get($serviceName);
                } catch (\Exception $e) {
                    Logger::error($e);
                }
            } else {
                $generator = new $generatorClass;
            }

            if ($generator instanceof LinkGeneratorInterface) {
                return $generator;
            }
        }

        return null;
    }
}
