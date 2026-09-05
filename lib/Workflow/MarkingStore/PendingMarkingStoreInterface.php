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

namespace Pimcore\Workflow\MarkingStore;

use Pimcore\Model\Element\ElementInterface;
use Symfony\Component\Workflow\MarkingStore\MarkingStoreInterface;

/**
 * A marking store that persists the marking independently of the subject (like the
 * state_table store) can implement this interface to keep a marking pending on the
 * subject when the subject is only going to be saved as a version (draft).
 *
 * The pending marking is part of the subject's version data, so it lives and dies
 * with the draft: discarding the draft drops it, publishing the draft persists it
 * (see persistPendingMarking()).
 */
interface PendingMarkingStoreInterface extends MarkingStoreInterface
{
    /**
     * Context key the workflow manager sets to true when the subject is only going to be
     * saved as a version after the transition (changePublishedState: save_version).
     * setMarking() must then keep the marking pending on the subject instead of persisting it.
     */
    public const CONTEXT_SAVE_VERSION = 'pimcore_save_version';

    /**
     * Persist the marking that is pending on the subject (if any) and remove it from the subject.
     * Called when the subject is fully saved.
     */
    public function persistPendingMarking(ElementInterface $subject): void;
}
