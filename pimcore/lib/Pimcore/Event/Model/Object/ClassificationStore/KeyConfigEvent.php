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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Event\Model\Object\ClassificationStore;

use Pimcore\Model\Object\Classificationstore\KeyConfig;
use Symfony\Component\EventDispatcher\Event;

class KeyConfigEvent extends Event {

    /**
     * @var KeyConfig
     */
    protected $keyConfig;

    /**
     * DocumentEvent constructor.
     * @param KeyConfig $keyConfig
     */
    function __construct(KeyConfig $keyConfig)
    {
        $this->keyConfig = $keyConfig;
    }

    /**
     * @return KeyConfig
     */
    public function getKeyConfig()
    {
        return $this->keyConfig;
    }

    /**
     * @param KeyConfig $keyConfig
     */
    public function setKeyConfig($keyConfig)
    {
        $this->keyConfig = $keyConfig;
    }
}
