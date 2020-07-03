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

namespace Pimcore\Targeting\DataProvider;

use Pimcore\Targeting\Model\VisitorInfo;
use Pimcore\Targeting\Storage\TargetingStorageInterface;

class TargetingStorage implements DataProviderInterface
{
    const PROVIDER_KEY = 'targeting_storage';

    /**
     * @var TargetingStorageInterface
     */
    private $storage;

    public function __construct(TargetingStorageInterface $storage)
    {
        $this->storage = $storage;
    }

    /**
     * @inheritDoc
     */
    public function load(VisitorInfo $visitorInfo)
    {
        $visitorInfo->set(self::PROVIDER_KEY, $this->storage);
    }
}
