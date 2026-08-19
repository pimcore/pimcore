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

use Exception;
use Pimcore\Workflow\Manager;

/**
 * Reference {@see BundleUsageProviderInterface} for a core capability: workflows are "used" when at
 * least one workflow is configured. Purely structural and content-never (a boolean derived from the
 * count) - it self-resets to false if all workflows are removed, so the bundle/capability owns the
 * definition of "used". Bundles (Data Hub, Portal Engine, …) add their own provider the same way.
 *
 * Also the reference for the null case: if the workflow manager cannot be consulted we do not know
 * whether workflows are in use, and saying `false` there would be a fabricated adoption gap.
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

    public function isUsed(): ?bool
    {
        try {
            return $this->workflowManager->getAllWorkflows() !== [];
        } catch (Exception) {
            return null;
        }
    }
}
