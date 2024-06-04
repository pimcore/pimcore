<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under following license:
 * - Pimcore Commercial License (PCL)
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     PCL
 */

namespace Pimcore\Bundle\GenericExecutionEngineBundle\DependencyInjection;

use Exception;
use Pimcore\Bundle\GenericExecutionEngineBundle\Agent\JobExecutionAgentInterface;
use Pimcore\Bundle\GenericExecutionEngineBundle\Configuration\ExecutionContextInterface;
use Pimcore\Bundle\GenericExecutionEngineBundle\EventSubscriber\JobExecutionSubscriber;
use Pimcore\Bundle\GenericExecutionEngineBundle\Utils\Enums\ErrorHandlingMode;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class PimcoreGenericExecutionEngineExtension extends Extension
{
    /**
     * @throws Exception
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../../config'));
        $loader->load('services.yaml');

        $definition = $container->getDefinition(ExecutionContextInterface::class);
        $definition->setArgument('$contexts', $config['execution_context'] ?? []);

        $definition = $container->getDefinition(JobExecutionAgentInterface::class);
        $definition->setArgument(
            '$errorHandlingMode',
            $config['error_handling'] ?? ErrorHandlingMode::CONTINUE_ON_ERROR->value
        );
    }
}
