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

namespace Pimcore\Tests\Unit\CoreBundle\DependencyInjection;

use Codeception\Test\Unit;
use Pimcore\Bundle\CoreBundle\DependencyInjection\Compiler\WorkflowPass;
use Pimcore\Helper\SymfonyExpression\ReadOnlyWorkflowExpressionLanguage;
use Pimcore\Workflow\Manager;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\Workflow\EventListener\ExpressionLanguage;
use Symfony\Component\Workflow\Registry;

/**
 * Workflow expressions must be evaluated with read-only stand-ins (GHSA-93jv-p24f-gcpp), also when
 * the application enables Symfony framework workflows: FrameworkBundle then defines
 * `workflow.security.expression_language` itself and core's own definition is not loaded.
 */
class WorkflowPassExpressionLanguageTest extends Unit
{
    public function testFrameworkWorkflowExpressionLanguageIsMadeReadOnly(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('pimcore.workflow', []);
        $container->setDefinition(Manager::class, new Definition(Manager::class));
        // as registered by FrameworkBundle's workflow.php when framework workflows are enabled
        $container->setDefinition('workflow.registry', new Definition(Registry::class));
        $container->setDefinition(
            'workflow.security.expression_language',
            new Definition(ExpressionLanguage::class)
        );

        (new WorkflowPass())->process($container);

        $this->assertSame(
            ReadOnlyWorkflowExpressionLanguage::class,
            $container->getDefinition('workflow.security.expression_language')->getClass()
        );
    }

    public function testRegistryWithoutExpressionLanguageGetsTheReadOnlyOne(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('pimcore.workflow', []);
        $container->setDefinition(Manager::class, new Definition(Manager::class));
        // a bundle providing the registry without FrameworkBundle's workflow services
        $container->setDefinition('workflow.registry', new Definition(Registry::class));

        (new WorkflowPass())->process($container);

        $this->assertSame(
            ReadOnlyWorkflowExpressionLanguage::class,
            $container->getDefinition('workflow.security.expression_language')->getClass()
        );
    }

    public function testCoreWorkflowExpressionLanguageIsReadOnly(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('pimcore.workflow', []);
        $container->setDefinition(Manager::class, new Definition(Manager::class));

        (new WorkflowPass())->process($container);

        $this->assertSame(
            ReadOnlyWorkflowExpressionLanguage::class,
            $container->getDefinition('workflow.security.expression_language')->getClass()
        );
    }
}
