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

use Pimcore\Bundle\CoreBundle\DependencyInjection\Configuration;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Tests\Support\Test\TestCase;
use Pimcore\Workflow\EventSubscriber\ChangePublishedStateSubscriber;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;

final class WorkflowConfigurationTest extends TestCase
{
    private function processWorkflow(array $workflowConfig): array
    {
        $config = (new Processor())->processConfiguration(new Configuration(), [[
            'workflows' => [
                'product_workflow' => $workflowConfig + [
                    'supports' => [Concrete::class],
                ],
            ],
        ]]);

        return $config['workflows']['product_workflow'];
    }

    public function testPlaceChangePublishedStateDefaultsToNoChange(): void
    {
        $config = $this->processWorkflow([
            'places' => [
                'open' => [],
            ],
            'transitions' => [
                'close' => ['from' => 'open', 'to' => 'open'],
            ],
        ]);

        $this->assertSame(
            ChangePublishedStateSubscriber::NO_CHANGE,
            $config['places']['open']['changePublishedState']
        );
    }

    public function testPlaceAcceptsChangePublishedState(): void
    {
        $config = $this->processWorkflow([
            'places' => [
                'open' => [],
                'closed' => ['changePublishedState' => ChangePublishedStateSubscriber::FORCE_PUBLISHED],
            ],
            'transitions' => [
                'close' => ['from' => 'open', 'to' => 'closed'],
            ],
        ]);

        $this->assertSame(
            ChangePublishedStateSubscriber::FORCE_PUBLISHED,
            $config['places']['closed']['changePublishedState']
        );
    }

    public function testPlaceRejectsInvalidChangePublishedState(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $this->processWorkflow([
            'places' => [
                'closed' => ['changePublishedState' => 'force_deleted'],
            ],
            'transitions' => [
                'close' => ['from' => 'closed', 'to' => 'closed'],
            ],
        ]);
    }
}
