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
