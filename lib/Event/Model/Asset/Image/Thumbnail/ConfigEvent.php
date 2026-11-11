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

namespace Pimcore\Event\Model\Asset\Image\Thumbnail;

use Pimcore\Model\Asset\Image\Thumbnail\Config;
use Symfony\Contracts\EventDispatcher\Event;

final class ConfigEvent extends Event
{
    public function __construct(private readonly Config $config)
    {
    }

    public function getConfig(): Config
    {
        return $this->config;
    }
}
