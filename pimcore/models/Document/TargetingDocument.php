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
        $prefix = '';

        if (!$targetGroupId) {
            $targetGroupId = $this->getUseTargetGroup();
        }

        if ($targetGroupId) {
            $prefix = self::TARGET_GROUP_ELEMENT_PREFIX . $targetGroupId . self::TARGET_GROUP_ELEMENT_SUFFIX;
        }

        return $prefix;
    }

    /**
     * @inheritdoc
     */
    public function getTargetGroupElementName(string $name): string
    {
        if (!$this->getUseTargetGroup()) {
            return $name;
        }

        $prefix = $this->getTargetGroupElementPrefix();
        if (!preg_match('/^' . preg_quote($prefix, '/') . '/', $name)) {
            $name = $prefix . $name;
        }

        return $name;
    }

    /**
     * @inheritDoc
     */
    public function hasTargetGroupSpecificElements(): bool
    {
        return $this->getDao()->hasTargetGroupSpecificElements();
    }

    /**
     * @inheritDoc
     */
    public function getTargetGroupSpecificElementNames(): array
    {
        return $this->getDao()->getTargetGroupSpecificElementNames();
    }

    /**
     * Set an element with the given key/name
     *
     * @param string $name
     * @param Tag $data
     *
     * @return PageSnippet
     */
    public function setElement($name, $data)
    {
        if ($this->getUseTargetGroup()) {
            $name = $this->getTargetGroupElementName($name);
            $data->setName($name);
        }

        return parent::setElement($name, $data);
    }

    /**
     * Get an element with the given key/name
     *
     * @param string $name
     *
     * @return Tag
     */
    public function getElement($name)
    {
        // check if a target group is requested for this page, if yes deliver a different version of the element (prefixed)
        if ($this->getUseTargetGroup()) {
            $targetGroupElementName = $this->getTargetGroupElementName($name);

            if ($this->hasElement($targetGroupElementName)) {
                $name = $targetGroupElementName;
            } else {
                // if there's no dedicated content for this target group, inherit from the "original" content (unprefixed)
                // and mark it as inherited so it is clear in the ui that the content is not specific to the selected target group
                // replace all occurrences of the target group prefix, this is needed because of block-prefixes
                $inheritedName    = str_replace($this->getTargetGroupElementPrefix(), '', $name);
                $inheritedElement = parent::getElement($inheritedName);

                if ($inheritedElement) {
                    $inheritedElement = clone $inheritedElement;
                    $inheritedElement->setDao(null);
                    $inheritedElement->setName($targetGroupElementName);
                    $inheritedElement->setInherited(true);

                    $this->setElement($targetGroupElementName, $inheritedElement);

                    return $inheritedElement;
                }
            }
        }

        // delegate to default
        return parent::getElement($name);
    }

    public function __sleep()
    {
        $finalVars  = [];
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
