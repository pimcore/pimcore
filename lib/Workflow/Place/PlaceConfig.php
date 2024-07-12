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

namespace Pimcore\Workflow\Place;

use Pimcore\Helper\ContrastColor;
use Pimcore\Workflow\ExpressionService;
use Symfony\Component\Workflow\WorkflowInterface;

class PlaceConfig
{
    private string $place;

    private array $placeConfigArray;

    private ExpressionService $expressionService;

    private string $workflowName;

    public function __construct(string $place, array $placeConfigArray, ExpressionService $expressionService, string $workflowName)
    {
        $this->place = $place;
        $this->placeConfigArray = $placeConfigArray;
        $this->expressionService = $expressionService;
        $this->workflowName = $workflowName;
    }

    public function getLabel(): string
    {
        return $this->placeConfigArray['label'] ?? $this->place;
    }

    public function getTitle(): string
    {
        return $this->placeConfigArray['title'] ?? '';
    }

    public function getColor(): string
    {
        return $this->placeConfigArray['color'] ?? '#bfdadc';
    }

    public function getColorInverted(): bool
    {
        return $this->placeConfigArray['colorInverted'] ?? false;
    }

    public function getBackgroundColor(): string
    {
        return $this->getColorInverted() ? ContrastColor::getContrastColor($this->getColor()) : $this->getColor();
    }

    public function getFontColor(): string
    {
        return $this->getColorInverted() ? $this->getColor() : ContrastColor::getContrastColor($this->getColor());
    }

    public function getBorderColor(): string
    {
        return $this->getColor();
    }

    public function isVisibleInHeader(): bool
    {
        return $this->placeConfigArray['visibleInHeader'];
    }

    public function getObjectLayout(WorkflowInterface $workflow, object $subject): ?string
    {
        return $this->getPermissions($workflow, $subject)['objectLayout'] ?? null;
    }

    public function getPlace(): string
    {
        return $this->place;
    }

    public function getWorkflowName(): string
    {
        return $this->workflowName;
    }

    public function getPlaceConfigArray(): array
    {
        return $this->placeConfigArray;
    }

    public function getPermissions(WorkflowInterface $workflow, object $subject): array
    {
        foreach ($this->placeConfigArray['permissions'] ?? [] as $permission) {
            $condition = $permission['condition'] ?? false;
            if ($condition && !$this->expressionService->evaluateExpression($workflow, $subject, $condition)) {
                continue;
            }

            return $permission;
        }

        return [];
    }

    public function getUserPermissions(WorkflowInterface $workflow, object $subject): array
    {
        $result = [];

        foreach ($this->getPermissions($workflow, $subject) as $permission => $value) {
            if (in_array($permission, ['save', 'publish', 'unpublish', 'delete', 'view', 'rename', 'settings', 'versions', 'properties'])) {
                $result[$permission] = $value;
            } elseif (in_array($permission, ['lEdit', 'lView'])) {
                $result[$permission] = implode(',', $value);
            } elseif ($permission === 'modify') {
                $result['save'] = $value;
                $result['publish'] = $value;
                $result['unpublish'] = $value;
                $result['delete'] = $value;
                $result['rename'] = $value;
            }
        }

        return $result;
    }
}
