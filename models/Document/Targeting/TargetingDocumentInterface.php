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

namespace Pimcore\Model\Document\Targeting;

/**
 *
 * @method string getTargetGroupEditablePrefix(int $targetGroupId) - not implementing it is deprecated since v6.7 and will throw exception in 7.
 * @method string getTargetGroupEditableName(int $targetGroupId = null) - not implementing it is deprecated since v6.7 and will throw exception in 7.
 * @method bool hasTargetGroupSpecificEditables() - not implementing it is deprecated since v6.7 and will throw exception in 7.
 * @method array getTargetGroupSpecificEditableNames() - not implementing it is deprecated since v6.7 and will throw exception in 7.
 */
interface TargetingDocumentInterface
{
    // this was kept "persona" for BC reasons and is one of the
    // few parts where the term "persona" refers to a "target group"
    const TARGET_GROUP_EDITABLE_PREFIX = 'persona_-';
    const TARGET_GROUP_EDITABLE_SUFFIX = '-_';
    /**
     * @deprecated since v6.7 and will be removed in 7. Use TARGET_GROUP_EDITABLE_PREFIX instead
     */
    const TARGET_GROUP_ELEMENT_PREFIX = 'persona_-';

    /**
     * @deprecated since v6.7 and will be removed in 7. Use TARGET_GROUP_EDITABLE_SUFFIX instead
     */
    const TARGET_GROUP_ELEMENT_SUFFIX = '-_';

    /**
     * Build target group element prefix for a given target group or for
     * the configured one if $targetGroupId is null and there is a configured
     * target group.
     *
     * @param int|null $targetGroupId
     *
     * @return string
     *
     * @deprecated since v6.7 and will be removed in 7. Use getTargetGroupEditablePrefix() instead.
     */
    public function getTargetGroupElementPrefix(int $targetGroupId = null): string;

    /**
     * Adds target group prefix to element name if it is not already prefixed and
     * if a target group is set.
     *
     * @param string $name
     *
     * @return string
     *
     * @deprecated since v6.7 and will be removed in 7. Use getTargetGroupEditableName() instead.
     */
    public function getTargetGroupElementName(string $name): string;

    /**
     * Sets the target group to use
     *
     * @param int $useTargetGroup
     */
    public function setUseTargetGroup(int $useTargetGroup = null);

    /**
     * Returns the target group to use
     *
     * @return int|null
     */
    public function getUseTargetGroup();

    /**
     * Checks if the document has targeting specific elements
     *
     * @return bool
     *
     * @deprecated since v6.7 and will be removed in 7. Use hasTargetGroupSpecificEditables() instead.
     */
    public function hasTargetGroupSpecificElements(): bool;

    /**
     * Returns targeting specific element names
     *
     * @return array
     *
     * @deprecated since v6.7 and will be removed in 7. Use getTargetGroupSpecificEditableNames() instead.
     */
    public function getTargetGroupSpecificElementNames(): array;
}
