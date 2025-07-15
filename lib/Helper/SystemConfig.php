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
