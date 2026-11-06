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

namespace Pimcore\Tests\Support\PHPStan;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\CollectedDataNode;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * DDL commits implicitly on MySQL/MariaDB, which desynchronizes DBAL's transaction nesting level
 * when the migration runs inside a transaction.
 *
 * @implements Rule<CollectedDataNode>
 */
final class TransactionalMigrationWithDdlRule implements Rule
{
    public function __construct(
        private readonly ReflectionProvider $reflectionProvider
    ) {
    }

    public function getNodeType(): string
    {
        return CollectedDataNode::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $nonTransactionalClasses = [];
        foreach ($node->get(NonTransactionalMigrationCollector::class) as $classNames) {
            foreach ($classNames as $className) {
                $nonTransactionalClasses[$className] = true;
            }
        }

        $errors = [];
        $reportedClasses = [];
        foreach ($node->get(MigrationDdlCollector::class) as $file => $ddlCalls) {
            foreach ($ddlCalls as [$className, $line, $reason]) {
                if (isset($reportedClasses[$className])
                    || $this->isNonTransactional($className, $nonTransactionalClasses)
                ) {
                    continue;
                }

                $reportedClasses[$className] = true;
                $errors[] = RuleErrorBuilder::message(sprintf(
                    'Migration %s runs DDL (%s) but is transactional. DDL commits implicitly on MySQL/MariaDB, override isTransactional() to return false.',
                    $className,
                    $reason
                ))
                    ->file($file)
                    ->line($line)
                    ->identifier('pimcore.transactionalMigrationWithDdl')
                    ->build();
            }
        }

        return $errors;
    }

    /**
     * @param array<string, true> $nonTransactionalClasses
     */
    private function isNonTransactional(string $className, array $nonTransactionalClasses): bool
    {
        if (!$this->reflectionProvider->hasClass($className)) {
            return false;
        }

        $classReflection = $this->reflectionProvider->getClass($className);
        foreach ([$classReflection, ...$classReflection->getParents()] as $class) {
            if (isset($nonTransactionalClasses[$class->getName()])) {
                return true;
            }
        }

        return false;
    }
}
