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

use Pimcore\Config\LocationAwareConfigRepository;

/**
 * @internal
 */
class SystemConfig
{
    public static function getConfigDataByKey(LocationAwareConfigRepository $repository, string $key): array
    {
        $config = [];
        $configKey = $repository->loadConfigByKey(($key));

        if (isset($configKey[0])) {
            $config = $configKey[0];
            $config['writeable'] = $repository->isWriteable();
        }

        return $config;
    }
}
