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

namespace Pimcore\Helper\SymfonyExpression;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;
use Symfony\Component\Workflow\EventListener\ExpressionLanguage;

/**
 * The workflow expression language (`workflow.security.expression_language`) with read-only
 * stand-ins of the variables: workflow configuration can be edited in the workflow designer, so
 * guards and other workflow expressions must not be able to change the subject, the user or the
 * token (GHSA-93jv-p24f-gcpp). `is_granted()`, `is_valid()` and the other security functions get
 * the original values and keep working.
 */
class ReadOnlyWorkflowExpressionLanguage extends ExpressionLanguage
{
    use ReadOnlyEvaluationTrait;

    /**
     * @param iterable<ExpressionFunctionProviderInterface> $providers
     * @param iterable<ReadOnlyValueAdapterInterface> $valueAdapters
     */
    public function __construct(
        ?CacheItemPoolInterface $cache = null,
        iterable $providers = [],
        iterable $valueAdapters = []
    ) {
        $this->initializeReadOnlyEvaluation($valueAdapters);

        parent::__construct($cache, $providers);
    }
}
