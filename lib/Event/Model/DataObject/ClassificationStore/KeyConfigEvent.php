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

namespace Pimcore\Event\Model\DataObject\ClassificationStore;

use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use Symfony\Contracts\EventDispatcher\Event;

class KeyConfigEvent extends Event
{
    protected KeyConfig $keyConfig;

    /**
     * DocumentEvent constructor.
     *
     */
    public function __construct(KeyConfig $keyConfig)
    {
        $this->keyConfig = $keyConfig;
    }

    public function getKeyConfig(): KeyConfig
    {
        return $this->keyConfig;
    }

    public function setKeyConfig(KeyConfig $keyConfig): void
    {
        $this->keyConfig = $keyConfig;
    }
}
