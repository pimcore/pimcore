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

use Doctrine\Migrations\AbstractMigration;
use PhpParser\Node;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Return_;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;

/**
 * Collects migrations whose isTransactional() unconditionally returns false.
 *
 * @implements Collector<ClassMethod, class-string>
 */
final class NonTransactionalMigrationCollector implements Collector
{
    public function getNodeType(): string
    {
        return ClassMethod::class;
    }

    public function processNode(Node $node, Scope $scope): ?string
    {
        $classReflection = $scope->getClassReflection();
        if ($classReflection === null
            || !$classReflection->isSubclassOf(AbstractMigration::class)
            || $node->name->toLowerString() !== 'istransactional'
        ) {
            return null;
        }

        $statements = $node->stmts ?? [];
        if (count($statements) !== 1
            || !$statements[0] instanceof Return_
            || $statements[0]->expr === null
            || !$scope->getType($statements[0]->expr)->isFalse()->yes()
        ) {
            return null;
        }

        return $classReflection->getName();
    }
}
