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

namespace Pimcore\Tests\Unit\GenericExecutionEngineBundle\Messenger\Middleware;

use Pimcore\Bundle\GenericExecutionEngineBundle\Entity\JobRun;
use Pimcore\Bundle\GenericExecutionEngineBundle\Extractor\JobRunExtractorInterface;
use Pimcore\Bundle\GenericExecutionEngineBundle\Messenger\Messages\AbstractExecutionEngineMessage;
use Pimcore\Bundle\GenericExecutionEngineBundle\Messenger\Middleware\CoauthorMiddleware;
use Pimcore\Model\Version\CoauthorContext;
use Pimcore\Tests\Support\Test\TestCase;
use RuntimeException;
use stdClass;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Middleware\StackMiddleware;

class CoauthorMiddlewareTest extends TestCase
{
    private CoauthorContext $coauthorContext;

    protected function setUp(): void
    {
        parent::setUp();
        $this->coauthorContext = new CoauthorContext();
    }

    public function testStepRunsWithTheCoauthorStoredInTheJobRun(): void
    {
        $seen = [];

        $this->handle(
            ['_coauthor' => ['type' => 'mcp-server', 'coauthor' => 'pme-test']],
            function () use (&$seen): void {
                $seen[] = [$this->coauthorContext->getType(), $this->coauthorContext->getCoauthor()];
            }
        );

        $this->assertSame([['mcp-server', 'pme-test']], $seen);
    }

    public function testCoauthorIsClearedAfterTheStep(): void
    {
        $this->handle(
            ['_coauthor' => ['type' => 'agent', 'coauthor' => 'general']],
            static function (): void {
            }
        );

        $this->assertFalse($this->coauthorContext->isActive());
        $this->assertNull($this->coauthorContext->getType());
    }

    public function testCoauthorIsClearedWhenTheStepFails(): void
    {
        try {
            $this->handle(
                ['_coauthor' => ['type' => 'agent', 'coauthor' => 'general']],
                static function (): never {
                    throw new RuntimeException('step failed');
                }
            );
            $this->fail('Expected RuntimeException');
        } catch (RuntimeException) {
            // expected
        }

        $this->assertFalse($this->coauthorContext->isActive());
    }

    public function testAJobWithoutAStoredCoauthorStaysUnstamped(): void
    {
        $active = [];

        $this->handle(['other' => 'value'], function () use (&$active): void {
            $active[] = $this->coauthorContext->isActive();
        });
        $this->handle(null, function () use (&$active): void {
            $active[] = $this->coauthorContext->isActive();
        });

        $this->assertSame([false, false], $active);
    }

    /**
     * @dataProvider malformedCoauthors
     */
    public function testAMalformedStoredCoauthorIsIgnored(mixed $stored): void
    {
        $active = [];

        $this->handle(['_coauthor' => $stored], function () use (&$active): void {
            $active[] = $this->coauthorContext->isActive();
        });

        $this->assertSame([false], $active);
    }

    public static function malformedCoauthors(): array
    {
        return [
            'string' => ['agent'],
            'missing coauthor' => [['type' => 'agent']],
            'non string type' => [['type' => 1, 'coauthor' => 'x']],
        ];
    }

    public function testOtherMessagesPassThroughUntouched(): void
    {
        $extractor = $this->createMock(JobRunExtractorInterface::class);
        $extractor->expects($this->never())->method('getJobRun');
        $middleware = new CoauthorMiddleware($extractor, $this->coauthorContext);
        $called = false;

        $middleware->handle(
            new Envelope(new stdClass()),
            new StackMiddleware($this->nextMiddleware(function () use (&$called): void {
                $called = true;
            }))
        );

        $this->assertTrue($called);
    }

    private function handle(?array $jobRunContext, callable $step): void
    {
        $jobRun = new JobRun(1);
        $jobRun->setContext($jobRunContext);
        $extractor = $this->createMock(JobRunExtractorInterface::class);
        $extractor->method('getJobRun')->willReturn($jobRun);

        $message = new class(1, 0) extends AbstractExecutionEngineMessage {
        };

        (new CoauthorMiddleware($extractor, $this->coauthorContext))->handle(
            new Envelope($message),
            new StackMiddleware($this->nextMiddleware($step))
        );
    }

    private function nextMiddleware(callable $step): MiddlewareInterface
    {
        return new class($step) implements MiddlewareInterface {
            public function __construct(private readonly mixed $step)
            {
            }

            public function handle(Envelope $envelope, StackInterface $stack): Envelope
            {
                ($this->step)();

                return $envelope;
            }
        };
    }
}
