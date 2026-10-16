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

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\Migrations\AbstractMigration;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp\Concat;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\InterpolatedStringPart;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\InterpolatedString;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\Fieldcollection;
use Pimcore\Model\DataObject\Objectbrick;

/**
 * Collects calls inside migrations that run DDL, which commits implicitly on MySQL/MariaDB.
 *
 * @implements Collector<MethodCall, array{class-string, int, string}>
 */
final class MigrationDdlCollector implements Collector
{
    private const array SCHEMA_CHANGING_METHODS = [
        Schema::class => [
            'createnamespace', 'createtable', 'renametable', 'droptable', 'createsequence', 'dropsequence',
        ],
        Table::class => [
            'setprimarykey', 'addprimarykeyconstraint', 'adduniqueconstraint', 'addindex', 'dropprimarykey',
            'dropindex', 'adduniqueindex', 'renameindex', 'addcolumn', 'renamecolumn', 'modifycolumn',
            'dropcolumn', 'addforeignkeyconstraint', 'addoption', 'removeforeignkey', 'dropforeignkey',
            'removeuniqueconstraint', 'dropuniqueconstraint', 'setcomment',
        ],
        Column::class => [
            'setoptions', 'settype', 'settypename', 'setlength', 'setprecision', 'setscale', 'setunsigned',
            'setfixed', 'setnotnull', 'setdefault', 'setplatformoptions', 'setplatformoption',
            'setcolumndefinition', 'setautoincrement', 'setcomment', 'setvalues',
        ],
    ];

    private const array TABLE_CREATING_DEFINITIONS = [
        ClassDefinition::class,
        Objectbrick\Definition::class,
        Fieldcollection\Definition::class,
    ];

    private const array SQL_METHODS = [
        AbstractMigration::class => ['addsql'],
        Connection::class => ['executestatement', 'executequery'],
    ];

    private const string DDL_PATTERN = '/^\s*(ALTER|CREATE|DROP|RENAME|TRUNCATE)\s+(?!TEMPORARY\b)/i';

    public function getNodeType(): string
    {
        return MethodCall::class;
    }

    public function processNode(Node $node, Scope $scope): ?array
    {
        $classReflection = $scope->getClassReflection();
        if ($classReflection === null || !$classReflection->isSubclassOf(AbstractMigration::class)) {
            return null;
        }

        if (!$node->name instanceof Identifier) {
            return null;
        }

        $reason = $this->findDdlReason($node, $node->name->toLowerString(), $scope->getType($node->var), $scope);
        if ($reason === null) {
            return null;
        }

        return [$classReflection->getName(), $node->getStartLine(), $reason];
    }

    private function findDdlReason(MethodCall $node, string $methodName, Type $callerType, Scope $scope): ?string
    {
        foreach (self::SCHEMA_CHANGING_METHODS as $className => $methodNames) {
            if (in_array($methodName, $methodNames, true) && $this->isInstanceOf($callerType, $className)) {
                return sprintf('%s::%s()', $className, $node->name);
            }
        }

        if ($methodName === 'save') {
            foreach (self::TABLE_CREATING_DEFINITIONS as $className) {
                if ($this->isInstanceOf($callerType, $className)) {
                    return sprintf('%s::save()', $className);
                }
            }
        }

        foreach (self::SQL_METHODS as $className => $methodNames) {
            if (!in_array($methodName, $methodNames, true) || !$this->isInstanceOf($callerType, $className)) {
                continue;
            }

            $sql = $node->getArgs()[0]->value ?? null;
            foreach ($sql === null ? [] : $this->getLeadingStrings($sql, $scope) as $leadingString) {
                if (preg_match(self::DDL_PATTERN, $leadingString, $matches) === 1) {
                    return sprintf('%s statement', strtoupper($matches[1]));
                }
            }
        }

        return null;
    }

    private function isInstanceOf(Type $type, string $className): bool
    {
        return (new ObjectType($className))->isSuperTypeOf($type)->yes();
    }

    /**
     * @return string[]
     */
    private function getLeadingStrings(Expr $expr, Scope $scope): array
    {
        if ($expr instanceof Concat) {
            return $this->getLeadingStrings($expr->left, $scope);
        }

        if ($expr instanceof FuncCall
            && $expr->name instanceof Name
            && $expr->name->toLowerString() === 'sprintf'
            && isset($expr->getArgs()[0])
        ) {
            return $this->getLeadingStrings($expr->getArgs()[0]->value, $scope);
        }

        if ($expr instanceof InterpolatedString && $expr->parts[0] instanceof InterpolatedStringPart) {
            return [$expr->parts[0]->value];
        }

        return array_map(
            static fn ($constantString) => $constantString->getValue(),
            $scope->getType($expr)->getConstantStrings()
        );
    }
}
