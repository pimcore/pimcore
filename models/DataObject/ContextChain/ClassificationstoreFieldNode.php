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
 * @package    Object
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\DataObject\ContextChain;

use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use Pimcore\Model\DataObject\Classificationstore\Service;

class ClassificationstoreFieldNode extends AbstractNode
{

    /** @var int */
    protected $groupId;

    /** @var int */
    protected $keyId;

    /** @var string */
    protected $language;

    /**
     * @internal
     *
     * ClassificationstoreFieldNode constructor.
     * @param int $groupId
     * @param int $keyId
     * @param string $language
     */
    public function __construct($groupId, $keyId, $language = null)
    {
        $this->groupId = $groupId;
        $this->keyId = $keyId;
        $this->language = $language;
    }

    /**
     * @return int
     */
    public function getGroupId(): int
    {
        return $this->groupId;
    }

    /**
     * @return int
     */
    public function getKeyId(): int
    {
        return $this->keyId;
    }

    /**
     * @return string
     */
    public function getLanguage(): string
    {
        return $this->language;
    }

    /**
     * @return \Pimcore\Model\DataObject\ClassDefinition\Data|null
     * @throws \Exception
     */
    public function getFieldDefinition() {
        $keyConfig = KeyConfig::getById($this->keyId);
        if ($keyConfig) {
            return Service::getFieldDefinitionFromKeyConfig($keyConfig);
        }
        return null;
    }

}
