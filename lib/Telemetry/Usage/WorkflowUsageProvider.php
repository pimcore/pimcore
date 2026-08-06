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

namespace Pimcore\Telemetry\Usage;

use Pimcore\Workflow\Manager;
use Throwable;

/**
 * Reference {@see BundleUsageProviderInterface} for a core capability: workflows are "used" when at
 * least one workflow is configured. Purely structural and content-never (a boolean derived from the
 * count) - it self-resets to false if all workflows are removed, so the bundle/capability owns the
 * definition of "used". Bundles (Data Hub, Portal Engine, …) add their own provider the same way.
 *
 * @internal
 */
final readonly class WorkflowUsageProvider implements BundleUsageProviderInterface
{
    public function __construct(
        private Manager $workflowManager,
    ) {
    }

    public function getBundleKey(): string
    {
        return 'workflow';
    }

    public function isUsed(): bool
    {
        try {
            return $this->workflowManager->getAllWorkflows() !== [];
        } catch (Throwable) {
            return false;
        }
    }
}
