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

namespace Pimcore\Model\Document;

use Pimcore\Model\Document\Targeting\TargetingDocumentInterface;

/**
 * @method \Pimcore\Model\Document\Targeting\TargetingDocumentDaoInterface getDao()
 */
abstract class TargetingDocument extends PageSnippet implements TargetingDocumentInterface
{
    /**
     * @var int
     */
    private $useTargetGroup;

    /**
     * @inheritdoc
     */
    public function setUseTargetGroup(int $useTargetGroup = null)
    {
        $this->useTargetGroup = $useTargetGroup;
    }

    /**
     * @inheritdoc
     */
    public function getUseTargetGroup()
    {
        return $this->useTargetGroup;
    }

    /**
     * @inheritdoc
     */
    public function getTargetGroupElementPrefix(int $targetGroupId = null): string
    {
        return $this->getTargetGroupEditablePrefix($targetGroupId);
    }

    /**
     * @inheritdoc
     */
    public function getTargetGroupEditablePrefix(int $targetGroupId = null): string
    {
        $prefix = '';

        if (!$targetGroupId) {
            $targetGroupId = $this->getUseTargetGroup();
        }

        if ($targetGroupId) {
            $prefix = self::TARGET_GROUP_EDITABLE_PREFIX . $targetGroupId . self::TARGET_GROUP_EDITABLE_SUFFIX;
        }

        return $prefix;
    }

    /**
     * @inheritdoc
     */
    public function getTargetGroupElementName(string $name): string
    {
        return $this->getTargetGroupEditableName($name);
    }

    /**
     * @inheritdoc
     */
    public function getTargetGroupEditableName(string $name): string
    {
        if (!$this->getUseTargetGroup()) {
            return $name;
        }

        $prefix = $this->getTargetGroupEditablePrefix();
        if (!preg_match('/^' . preg_quote($prefix, '/') . '/', $name)) {
            $name = $prefix . $name;
        }

        return $name;
    }

    /**
     * @inheritDoc
     *
     * @deprecated since v6.7 and will be removed in 7. Use hasTargetGroupSpecificEditables() instead.
     */
    public function hasTargetGroupSpecificElements(): bool
    {
        return $this->hasTargetGroupSpecificEditables();
    }

    /**
     * @inheritDoc
     */
    public function hasTargetGroupSpecificEditables(): bool
    {
        return $this->getDao()->hasTargetGroupSpecificEditables();
    }

    /**
     * @inheritDoc
     */
    public function getTargetGroupSpecificElementNames(): array
    {
        return $this->getTargetGroupSpecificEditableNames();
    }

    /**
     * @inheritDoc
     */
    public function getTargetGroupSpecificEditableNames(): array
    {
        return $this->getDao()->getTargetGroupSpecificEditableNames();
    }

    /**
     * Set an element with the given key/name
     *
     * @param string $name
     * @param Editable $data
     *
     * @return PageSnippet
     */
    public function setEditable($name, $data)
    {
        if ($this->getUseTargetGroup()) {
            $name = $this->getTargetGroupEditableName($name);
            $data->setName($name);
        }

        return parent::setEditable($name, $data);
    }

    /**
     * Get an element with the given key/name
     *
     * @param string $name
     *
     * @return Editable|null
     */
    public function getEditable($name)
    {
        // check if a target group is requested for this page, if yes deliver a different version of the element (prefixed)
        if ($this->getUseTargetGroup()) {
            $targetGroupEditableName = $this->getTargetGroupEditableName($name);

            if ($editable = parent::getEditable($targetGroupEditableName)) {
                return $editable;
            } else {
                // if there's no dedicated content for this target group, inherit from the "original" content (unprefixed)
                // and mark it as inherited so it is clear in the ui that the content is not specific to the selected target group
                // replace all occurrences of the target group prefix, this is needed because of block-prefixes
                $inheritedName = str_replace($this->getTargetGroupEditablePrefix(), '', $name);
                $inheritedEditable = parent::getEditable($inheritedName);

                if ($inheritedEditable) {
                    $inheritedEditable = clone $inheritedEditable;
                    $inheritedEditable->setDao(null);
                    $inheritedEditable->setName($targetGroupEditableName);
                    $inheritedEditable->setInherited(true);

                    $this->setEditable($targetGroupEditableName, $inheritedEditable);

                    return $inheritedEditable;
                }
            }
        }

        // delegate to default
        return parent::getEditable($name);
    }

    public function __sleep()
    {
        $finalVars = [];
        $parentVars = parent::__sleep();

        $blockedVars = ['useTargetGroup'];

        foreach ($parentVars as $key) {
            if (!in_array($key, $blockedVars)) {
                $finalVars[] = $key;
            }
        }

        return $finalVars;
    }
}
