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

namespace Pimcore\Tests\Unit\Workflow;

use Pimcore\Bundle\CoreBundle\DependencyInjection\Compiler\WorkflowPass;
use Pimcore\Bundle\CoreBundle\DependencyInjection\Configuration;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Tests\Support\Test\TestCase;
use Pimcore\Workflow\EventSubscriber\ChangePublishedStateSubscriber;
use Pimcore\Workflow\ExpressionService;
use Pimcore\Workflow\GlobalAction;
use Pimcore\Workflow\Manager;
use Pimcore\Workflow\Place\PlaceConfig;
use Pimcore\Workflow\Transition;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

/**
 * A place-level changePublishedState is inherited by the transitions and global actions which lead to
 * that place, and is resolved when the container is compiled. These tests pin that resolution, so that
 * Transition::getChangePublishedState() - which is what consumers outside of the Manager read - reports
 * the value which will actually be applied.
 */
final class ChangePublishedStateTest extends TestCase
{
    private const WORKFLOW_NAME = 'product_workflow';

    /**
     * Processes the given workflow config through the real configuration tree - so that the pass sees
     * exactly the shape it sees in production, defaults included - and runs WorkflowPass over it.
     */
    private function compile(array $workflowConfig): ContainerBuilder
    {
        $processed = (new Processor())->processConfiguration(new Configuration(), [[
            'workflows' => [
                self::WORKFLOW_NAME => $workflowConfig + [
                    'type' => 'state_machine',
                    'supports' => [Concrete::class],
                    // WorkflowPass reads the label unconditionally although it has no default.
                    'label' => 'Product workflow',
                ],
            ],
        ]]);

        $container = new ContainerBuilder();
        $container->setDefinition(Manager::class, new Definition(Manager::class));
        // Prevents the pass from loading the Symfony workflow service definitions, which are
        // irrelevant here and would pull in the rest of the container.
        $container->setDefinition('workflow.registry', new Definition());
        $container->setParameter('pimcore.workflow', $processed['workflows']);

        (new WorkflowPass())->process($container);

        return $container;
    }

    /**
     * @return array<string, string> transition name => effective changePublishedState
     */
    private function compileTransitions(array $workflowConfig): array
    {
        $container = $this->compile($workflowConfig);
        $type = $workflowConfig['type'] ?? 'state_machine';
        $definition = $container->getDefinition(sprintf('%s.%s.definition', $type, self::WORKFLOW_NAME));

        $result = [];
        foreach ($definition->getArgument(1) as $transitionDefinition) {
            $arguments = $transitionDefinition->getArguments();
            $transition = new Transition($arguments[0], $arguments[1], $arguments[2], $arguments[3]);
            $result[$transition->getName()] = $transition->getChangePublishedState();
        }

        return $result;
    }

    private function globalActionFrom(ContainerBuilder $container, string $action): GlobalAction
    {
        foreach ($container->getDefinition(Manager::class)->getMethodCalls() as [$method, $arguments]) {
            if ($method === 'addGlobalAction' && $arguments[1] === $action) {
                return new GlobalAction(
                    $arguments[1],
                    $arguments[2],
                    $this->createStub(ExpressionService::class),
                    self::WORKFLOW_NAME
                );
            }
        }

        $this->fail(sprintf('global action "%s" was not registered', $action));
    }

    public function testTransitionInheritsTheStateOfItsTargetPlace(): void
    {
        $transitions = $this->compileTransitions([
            'places' => [
                'open' => [],
                'accepted' => ['changePublishedState' => ChangePublishedStateSubscriber::FORCE_PUBLISHED],
            ],
            'transitions' => [
                'accept' => ['from' => ['open'], 'to' => ['accepted'], 'options' => []],
            ],
        ]);

        $this->assertSame(
            [ChangePublishedStateSubscriber::FORCE_PUBLISHED],
            array_values($transitions)
        );
    }

    public function testAnExplicitTransitionStateIsNotOverwritten(): void
    {
        $transitions = $this->compileTransitions([
            'places' => [
                'accepted' => ['changePublishedState' => ChangePublishedStateSubscriber::FORCE_PUBLISHED],
            ],
            'transitions' => [
                'accept' => [
                    'from' => ['open'],
                    'to' => ['accepted'],
                    'options' => ['changePublishedState' => ChangePublishedStateSubscriber::SAVE_VERSION],
                ],
            ],
        ]);

        $this->assertSame(
            [ChangePublishedStateSubscriber::SAVE_VERSION],
            array_values($transitions)
        );
    }

    public function testPlacesTheTransitionDoesNotLeadToAreIgnored(): void
    {
        $transitions = $this->compileTransitions([
            'places' => [
                'open' => ['changePublishedState' => ChangePublishedStateSubscriber::FORCE_UNPUBLISHED],
                'accepted' => [],
            ],
            'transitions' => [
                'accept' => ['from' => ['open'], 'to' => ['accepted'], 'options' => []],
            ],
        ]);

        $this->assertSame(
            [ChangePublishedStateSubscriber::NO_CHANGE],
            array_values($transitions)
        );
    }

    public function testEachStateMachineTransitionInheritsFromItsOwnTargetPlace(): void
    {
        $transitions = $this->compileTransitions([
            'places' => [
                'accepted' => ['changePublishedState' => ChangePublishedStateSubscriber::FORCE_PUBLISHED],
                'rejected' => ['changePublishedState' => ChangePublishedStateSubscriber::FORCE_UNPUBLISHED],
            ],
            'transitions' => [
                'accept' => ['from' => ['open'], 'to' => ['accepted'], 'options' => []],
                'reject' => ['from' => ['open'], 'to' => ['rejected'], 'options' => []],
            ],
        ]);

        $this->assertSame(
            [
                'accept' => ChangePublishedStateSubscriber::FORCE_PUBLISHED,
                'reject' => ChangePublishedStateSubscriber::FORCE_UNPUBLISHED,
            ],
            $transitions
        );
    }

    public function testFirstTargetPlaceDefiningAStateWinsForWorkflowType(): void
    {
        $transitions = $this->compileTransitions([
            'type' => 'workflow',
            'places' => [
                'review' => [],
                'accepted' => ['changePublishedState' => ChangePublishedStateSubscriber::FORCE_PUBLISHED],
                'archived' => ['changePublishedState' => ChangePublishedStateSubscriber::FORCE_UNPUBLISHED],
            ],
            'transitions' => [
                'close' => ['from' => ['review'], 'to' => ['accepted', 'archived'], 'options' => []],
            ],
        ]);

        $this->assertSame(
            [ChangePublishedStateSubscriber::FORCE_PUBLISHED],
            array_values($transitions)
        );
    }

    public function testGlobalActionInheritsTheStateOfThePlaceItMovesTo(): void
    {
        $container = $this->compile([
            'places' => [
                'open' => [],
                'accepted' => ['changePublishedState' => ChangePublishedStateSubscriber::FORCE_PUBLISHED],
            ],
            'transitions' => [
                'accept' => ['from' => ['open'], 'to' => ['accepted'], 'options' => []],
            ],
            'globalActions' => [
                'reset' => ['to' => ['open']],
                'force_accept' => ['to' => ['accepted']],
            ],
        ]);

        $this->assertSame(
            ChangePublishedStateSubscriber::NO_CHANGE,
            $this->globalActionFrom($container, 'reset')->getChangePublishedState()
        );
        $this->assertSame(
            ChangePublishedStateSubscriber::FORCE_PUBLISHED,
            $this->globalActionFrom($container, 'force_accept')->getChangePublishedState()
        );
    }

    public function testGettersDefaultToNoChangeWhenTheOptionIsAbsent(): void
    {
        $placeConfig = new PlaceConfig(
            'accepted',
            [],
            $this->createStub(ExpressionService::class),
            self::WORKFLOW_NAME
        );

        $this->assertSame(
            ChangePublishedStateSubscriber::NO_CHANGE,
            (new Transition('accept', 'open', 'accepted'))->getChangePublishedState()
        );
        $this->assertSame(
            ChangePublishedStateSubscriber::NO_CHANGE,
            (new GlobalAction(
                'reset',
                [],
                $this->createStub(ExpressionService::class),
                self::WORKFLOW_NAME
            ))->getChangePublishedState()
        );
        $this->assertSame(ChangePublishedStateSubscriber::NO_CHANGE, $placeConfig->getChangePublishedState());
    }

    public function testPlaceConfigExposesTheRawConfiguredState(): void
    {
        $placeConfig = new PlaceConfig(
            'accepted',
            ['changePublishedState' => ChangePublishedStateSubscriber::FORCE_PUBLISHED],
            $this->createStub(ExpressionService::class),
            self::WORKFLOW_NAME
        );

        $this->assertSame(
            ChangePublishedStateSubscriber::FORCE_PUBLISHED,
            $placeConfig->getChangePublishedState()
        );
    }
}
