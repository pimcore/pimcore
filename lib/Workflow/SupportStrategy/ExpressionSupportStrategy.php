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

namespace Pimcore\Workflow\SupportStrategy;

use Pimcore\Workflow\ExpressionService;
use Symfony\Component\Workflow\SupportStrategy\WorkflowSupportStrategyInterface;
use Symfony\Component\Workflow\WorkflowInterface;

/**
 * @author Andreas Kleemann <akleemann@inviqa.com>
 */
class ExpressionSupportStrategy implements WorkflowSupportStrategyInterface
{
    private ExpressionService $expressionService;

    /**
     * @var string|string[]
     */
    private string|array $className;

    private string $expression;

    /**
     * ExpressionSupportStrategy constructor.
     *
     * @param string|string[] $className a FQCN
     */
    public function __construct(ExpressionService $expressionService, array|string $className, string $expression)
    {
        $this->expressionService = $expressionService;
        $this->className = $className;
        $this->expression = $expression;
    }

    public function supports(WorkflowInterface $workflow, object $subject): bool
    {
        if (!$this->supportsClass($subject)) {
            return false;
        }

        $ret = $this->expressionService->evaluateExpression($workflow, $subject, $this->expression);

        return filter_var($ret, FILTER_VALIDATE_BOOL) ? (bool)$ret : false;
    }

    private function supportsClass(object $subject): bool
    {
        if (is_string($this->className)) {
            return $subject instanceof $this->className;
        }

        foreach ($this->className as $className) {
            if ($subject instanceof $className) {
                return true;
            }
        }

        return false;
    }

    public function getClassName(): array|string
    {
        return $this->className;
    }
}
