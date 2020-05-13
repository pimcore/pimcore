<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\CoreBundle\DependencyInjection\Compiler;

use Pimcore\Workflow\Manager;
use Pimcore\Workflow\Transition;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Security\Core\Authorization\ExpressionLanguage;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Workflow;
use Symfony\Component\Workflow\Exception\LogicException;

class WorkflowPass implements CompilerPassInterface
{
    /**
     * @inheritDoc
     */
    public function process(ContainerBuilder $container)
    {
        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__.'/../../Resources/config')
        );

        $workflowManagerDefinition = $container->getDefinition(Manager::class);

        if (!$container->hasDefinition('workflow.registry')) {
            $loader->load('services_symfony_workflow.yml');
        }

        $config = $container->getParameter('pimcore.workflow');

        foreach ($config as $workflowName => $workflowConfig) {
            if (!$workflowConfig['enabled']) {
                continue;
            }

            $type = $workflowConfig['type'] ?? 'workflow';

            $workflowManagerDefinition->addMethodCall(
                'registerWorkflow',
                [
                    $workflowName,
                    [
                        'label' => $workflowConfig['label'],
                        'priority' => $workflowConfig['priority'],
                        'type' => $type,
                    ],
                ]
            );

            $transitions = [];
            foreach ($workflowConfig['transitions'] as $transitionName => $transitionConfig) {
                if ('workflow' === $type) {
                    $transitions[] = new Definition(
                        Transition::class,
                        [
                            $transitionName,
                            $transitionConfig['from'],
                            $transitionConfig['to'],
                            $transitionConfig['options'],
                        ]
                    );
                } elseif ('state_machine' === $type) {
                    foreach ($transitionConfig['from'] as $from) {
                        foreach ($transitionConfig['to'] as $to) {
                            $transitions[] = new Definition(
                                Transition::class,
                                [$transitionName, $from, $to, $transitionConfig['options']]
                            );
                        }
                    }
                }
            }

            $places = [];
            foreach ($workflowConfig['places'] as $place => $placeConfig) {
                $places[] = $place;

                $workflowManagerDefinition->addMethodCall('addPlaceConfig', [$workflowName, $place, $placeConfig]);
            }

            foreach ($workflowConfig['globalActions'] ?? [] as $action => $actionConfig) {
                $workflowManagerDefinition->addMethodCall('addGlobalAction', [$workflowName, $action, $actionConfig]);
            }

            $markingStoreType = $workflowConfig['marking_store']['type'] ?? null;
            $markingStoreService = $workflowConfig['marking_store']['service'] ?? null;
            if (is_null($markingStoreService) && is_null($markingStoreType)) {
                $markingStoreType = 'state_table';
            }

            // Create a Definition
            $definitionDefinition = new Definition(Workflow\Definition::class);
            $definitionDefinition->setPublic(false);
            $definitionDefinition->addArgument($places);
            $definitionDefinition->addArgument($transitions);
            $definitionDefinition->addTag(
                'workflow.definition',
                [
                    'name' => $workflowName,
                    'type' => $type,
                    'marking_store' => $markingStoreType,
                ]
            );

            if (isset($workflowConfig['initial_markings']) && $workflowConfig['initial_markings'] !== []) {
                $definitionDefinition->addArgument($workflowConfig['initial_markings']);
            } elseif (isset($workflowConfig['initial_place'])) {
                $definitionDefinition->addArgument($workflowConfig['initial_place']);
            }

            // Create MarkingStore
            if (!is_null($markingStoreType)) {
                $markingStoreDefinition = new ChildDefinition('workflow.marking_store.'.$markingStoreType);

                if ($markingStoreType === 'state_table' || $markingStoreType === 'data_object_splitted_state') {
                    $markingStoreDefinition->addArgument($workflowName);
                }

                if ($markingStoreType === 'data_object_splitted_state') {
                    $markingStoreDefinition->addArgument($places);
                }

                foreach ($workflowConfig['marking_store']['arguments'] ?? [] as $argument) {
                    $markingStoreDefinition->addArgument($argument);
                }
            } elseif (!is_null($markingStoreService)) {
                $markingStoreDefinition = new Reference($markingStoreService);
            }

            // Create Workflow
            $workflowId = sprintf('%s.%s', $type, $workflowName);
            $workflowDefinition = new ChildDefinition(sprintf('%s.abstract', $type));
            $workflowDefinition->replaceArgument(0, new Reference(sprintf('%s.definition', $workflowId)));
            if (isset($markingStoreDefinition)) {
                $workflowDefinition->replaceArgument(1, $markingStoreDefinition);
            }
            $workflowDefinition->replaceArgument(3, $workflowName);

            // Store to container
            $container->setDefinition($workflowId, $workflowDefinition);
            $container->setDefinition(sprintf('%s.definition', $workflowId), $definitionDefinition);

            $registryDefinition = $container->getDefinition('workflow.registry');
            // Add workflow to Registry
            if ($workflowConfig['supports']) {
                foreach ((array)$workflowConfig['supports'] as $supportedClassName) {
                    $strategyDefinition = new Definition(
                        Workflow\SupportStrategy\ClassInstanceSupportStrategy::class,
                        [$supportedClassName]
                    );
                    $strategyDefinition->setPublic(false);
                    $registryDefinition->addMethodCall('add', [new Reference($workflowId), $strategyDefinition]);
                }
            } elseif (isset($workflowConfig['support_strategy'])) {
                $supportStrategyType = $workflowConfig['support_strategy']['type'] ?? null;

                if (!is_null($supportStrategyType)) {
                    $supportStrategyDefinition = new ChildDefinition('workflow.support_strategy.'.$supportStrategyType);

                    foreach ($workflowConfig['support_strategy']['arguments'] ?? [] as $argument) {
                        $supportStrategyDefinition->addArgument($argument);
                    }
                    $registryDefinition->addMethodCall('add', [new Reference($workflowId), $supportStrategyDefinition]);
                } elseif (isset($workflowConfig['support_strategy']['service'])) {
                    $registryDefinition->addMethodCall(
                        'add',
                        [new Reference($workflowId), new Reference($workflowConfig['support_strategy']['service'])]
                    );
                }
            }

            // Enable the AuditTrail
            if ($workflowConfig['audit_trail']['enabled']) {
                $listener = new Definition(Workflow\EventListener\AuditTrailListener::class);
                $listener->setPrivate(true);
                $listener->addTag('monolog.logger', ['channel' => 'workflow']);
                $listener->addTag(
                    'kernel.event_listener',
                    ['event' => sprintf('workflow.%s.leave', $workflowName), 'method' => 'onLeave']
                );
                $listener->addTag(
                    'kernel.event_listener',
                    ['event' => sprintf('workflow.%s.transition', $workflowName), 'method' => 'onTransition']
                );
                $listener->addTag(
                    'kernel.event_listener',
                    ['event' => sprintf('workflow.%s.enter', $workflowName), 'method' => 'onEnter']
                );
                $listener->addArgument(new Reference('logger'));
                $container->setDefinition(sprintf('%s.listener.audit_trail', $workflowId), $listener);
            }

            // Add Guard Listener
            $guard = new Definition(Workflow\EventListener\GuardListener::class);
            $guard->setPrivate(true);
            $configuration = [];
            foreach ($workflowConfig['transitions'] as $transitionName => $config) {
                if (!isset($config['guard'])) {
                    continue;
                }

                if (!class_exists(ExpressionLanguage::class)) {
                    throw new LogicException(
                        'Cannot guard workflows as the ExpressionLanguage component is not installed.'
                    );
                }

                if (!class_exists(Security::class)) {
                    throw new LogicException('Cannot guard workflows as the Security component is not installed.');
                }

                $eventName = sprintf('workflow.%s.guard.%s', $workflowName, $transitionName);
                $guard->addTag('kernel.event_listener', ['event' => $eventName, 'method' => 'onTransition']);
                $configuration[$eventName] = $config['guard'];
            }
            if ($configuration) {
                $guard->setArguments(
                    [
                        $configuration,
                        new Reference('workflow.security.expression_language'),
                        new Reference('security.token_storage'),
                        new Reference('security.authorization_checker'),
                        new Reference('security.authentication.trust_resolver'),
                        new Reference('security.role_hierarchy'),
                        new Reference('validator', ContainerInterface::NULL_ON_INVALID_REFERENCE),
                    ]
                );

                $container->setDefinition(sprintf('%s.listener.guard', $workflowId), $guard);
                $container->setParameter('workflow.has_guard_listeners', true);
            }
        }
    }
}
