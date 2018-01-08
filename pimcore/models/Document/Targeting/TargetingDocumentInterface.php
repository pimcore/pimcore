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

interface TargetingDocumentInterface
{
    // this was kept "persona" for BC reasons and is one of the
    // few parts where the term "persona" refers to a "target group"
    const TARGET_GROUP_ELEMENT_PREFIX = 'persona_-';
    const TARGET_GROUP_ELEMENT_SUFFIX = '-_';

    /**
     * Build target group element prefix for a given target group or for
     * the configured one if $targetGroupId is null and there is a configured
     * target group.
     *
     * @param int|null $targetGroupId
     *
     * @return string
     */
    public function getTargetGroupElementPrefix(int $targetGroupId = null): string;

    /**
     * Adds target group prefix to element name if it is not already prefixed and
     * if a target group is set.
     *
     * @param string $name
     *
     * @return string
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
     */
    public function hasTargetGroupSpecificElements(): bool;

    /**
     * Returns targeting specific element names
     *
     * @return array
     */
    public function getTargetGroupSpecificElementNames(): array;
}
