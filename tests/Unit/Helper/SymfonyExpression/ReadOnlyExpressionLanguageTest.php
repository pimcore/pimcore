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

namespace Pimcore\Tests\Unit\Helper\SymfonyExpression;

use ArrayObject;
use Closure;
use Codeception\Test\Unit;
use LogicException;
use Pimcore\Helper\SymfonyExpression\ReadOnlyExpressionLanguage;
use Pimcore\Helper\SymfonyExpression\ReadOnlyValueAdapterInterface;
use Pimcore\Helper\SymfonyExpression\ReadOnlyWorkflowExpressionLanguage;
use Pimcore\Model\User;
use Pimcore\Security\User\User as SecurityUser;
use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;
use Symfony\Component\ExpressionLanguage\SyntaxError;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Expressions only get read-only stand-ins of the objects they are evaluated with
 * (GHSA-93jv-p24f-gcpp), in every expression language core provides for stored expressions.
 */
class ReadOnlyExpressionLanguageTest extends Unit
{
    public function testScalarsAndArraysAreUnchanged(): void
    {
        $expressionLanguage = new ReadOnlyExpressionLanguage();

        $this->assertSame(
            'a-2',
            $expressionLanguage->evaluate('items[0] ~ "-" ~ count', ['items' => ['a', 'b'], 'count' => 2])
        );
        $this->assertTrue($expressionLanguage->evaluate('"b" in items', ['items' => ['a', 'b']]));
    }

    public function testMethodsOfArbitraryObjectsAreNotCallable(): void
    {
        $this->expectException(SyntaxError::class);

        (new ReadOnlyExpressionLanguage())->evaluate('value.offsetSet("x", 1)', ['value' => new ArrayObject()]);
    }

    public function testResultIsTheOriginalObject(): void
    {
        $value = new ArrayObject(['x' => 1]);

        $this->assertSame($value, (new ReadOnlyExpressionLanguage())->evaluate('value', ['value' => $value]));
    }

    public function testConstantIsNotAvailable(): void
    {
        $this->expectException(SyntaxError::class);

        (new ReadOnlyExpressionLanguage())->evaluate('constant("PHP_VERSION")');
    }

    public function testFunctionsReceiveTheOriginalObjects(): void
    {
        $value = new ArrayObject(['x' => 1]);
        $provider = new class() implements ExpressionFunctionProviderInterface {
            public function getFunctions(): array
            {
                return [
                    new ExpressionFunction(
                        'countOf',
                        static fn (): string => '',
                        static fn (array $variables, ArrayObject $value): int => count($value)
                            + count($variables['value'])
                    ),
                ];
            }
        };

        $this->assertSame(
            2,
            (new ReadOnlyExpressionLanguage([$provider]))->evaluate('countOf(value)', ['value' => $value])
        );
    }

    public function testCompilingIsNotSupported(): void
    {
        $this->expectException(LogicException::class);

        (new ReadOnlyExpressionLanguage())->compile('object.getDao()', ['object']);
    }

    public function testFunctionResultsAreWrappedAlsoInsideArrays(): void
    {
        $user = new User();
        $user->setAdmin(false);
        $provider = new class($user) implements ExpressionFunctionProviderInterface {
            public function __construct(private readonly User $user)
            {
            }

            public function getFunctions(): array
            {
                return [
                    new ExpressionFunction('currentUser', static fn (): string => '', fn (): User => $this->user),
                    new ExpressionFunction('users', static fn (): string => '', fn (): array => ['a' => [$this->user]]),
                ];
            }
        };
        $expressionLanguage = new ReadOnlyExpressionLanguage([$provider]);

        $this->assertFalse($expressionLanguage->evaluate('users()["a"][0].isAdmin()'));

        foreach (['currentUser().setAdmin(true)', 'users()["a"][0].setAdmin(true)'] as $expression) {
            try {
                $expressionLanguage->evaluate($expression);
                $this->fail(sprintf('`%s` must be rejected', $expression));
            } catch (SyntaxError) {
            }
        }

        $this->assertFalse($user->isAdmin());
    }

    public function testValueAdapterExposesItsGettersOnly(): void
    {
        $adapter = new class() implements ReadOnlyValueAdapterInterface {
            public function supports(object $value): bool
            {
                return $value instanceof ArrayObject;
            }

            /**
             * @return array<string, Closure(array<mixed>): mixed>
             */
            public function getGetters(object $value): array
            {
                return [
                    'getcount' => static fn (): int => $value->count(),
                    // returns the live object: it has to come back as a stand-in
                    'getself' => static fn (): ArrayObject => $value,
                ];
            }
        };

        $expressionLanguage = new ReadOnlyExpressionLanguage([], [$adapter]);
        $value = new ArrayObject([1, 2, 3]);

        $this->assertSame(3, $expressionLanguage->evaluate('value.getCount()', ['value' => $value]));
        $this->assertSame(3, $expressionLanguage->evaluate('value.getSelf().getCount()', ['value' => $value]));

        $this->expectException(SyntaxError::class);
        $expressionLanguage->evaluate('value.getSelf().exchangeArray([])', ['value' => $value]);
    }

    public function testWorkflowGuardFunctionsGetTheOriginalSubject(): void
    {
        $subject = new ArrayObject();
        $authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_EDITOR', $this->identicalTo($subject))
            ->willReturn(true);

        $this->assertTrue((new ReadOnlyWorkflowExpressionLanguage())->evaluate(
            'is_granted("ROLE_EDITOR", subject)',
            ['subject' => $subject, 'auth_checker' => $authorizationChecker]
        ));
    }

    public function testWorkflowUserIsReadOnly(): void
    {
        $user = new User();
        $user->setId(7);
        $user->setName('editor');
        $user->setAdmin(false);
        $securityUser = new SecurityUser($user);
        $token = new UsernamePasswordToken($securityUser, 'pimcore_admin', ['ROLE_PIMCORE_USER']);

        $expressionLanguage = new ReadOnlyWorkflowExpressionLanguage();
        $variables = ['user' => $securityUser, 'token' => $token, 'subject' => new ArrayObject()];

        $this->assertSame('editor', $expressionLanguage->evaluate('user.getUserIdentifier()', $variables));
        $this->assertFalse($expressionLanguage->evaluate('user.getUser().isAdmin()', $variables));
        $this->assertSame(7, $expressionLanguage->evaluate('token.getUser().getUser().getId()', $variables));
        $this->assertSame(['ROLE_PIMCORE_USER'], $expressionLanguage->evaluate('token.getRoleNames()', $variables));

        foreach ([
            'user.getUser().setAdmin(true)',
            'user.getUser().save()',
            'token.setUser(null)',
            'user.getPassword()',
        ] as $expression) {
            try {
                $expressionLanguage->evaluate($expression, $variables);
                $this->fail(sprintf('`%s` must be rejected', $expression));
            } catch (SyntaxError) {
            }
        }

        $this->assertFalse($user->isAdmin());
    }
}
