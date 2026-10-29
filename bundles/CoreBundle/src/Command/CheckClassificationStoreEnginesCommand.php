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

namespace Pimcore\Bundle\CoreBundle\Command;

use Pimcore\Console\AbstractCommand;
use Pimcore\Model\DataObject\Classificationstore\Engine\EngineResolver;
use Pimcore\Model\DataObject\Classificationstore\StoreConfig;
use Pimcore\Model\DataObject\Exception\ClassificationStoreEngineNotAvailableException;
use Pimcore\Model\DataObject\Exception\UnknownClassificationStoreEngineException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal
 */
#[AsCommand(
    name: 'pimcore:classificationstore:check-engines',
    description: 'Check that every Classification Store resolves to a registered storage engine'
)]
class CheckClassificationStoreEnginesCommand extends AbstractCommand
{
    public function __construct(private readonly EngineResolver $engineResolver)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $errors = [];

        foreach ($this->loadStoreConfigs() as $storeConfig) {
            try {
                $this->engineResolver->resolve($storeConfig->getEngine(), (int) $storeConfig->getId());
            } catch (UnknownClassificationStoreEngineException|ClassificationStoreEngineNotAvailableException $e) {
                // the exception names the store by id and the offending value; the name is what an
                // operator recognises the store by
                $errors[] = sprintf('%s (store name: "%s")', $e->getMessage(), (string) $storeConfig->getName());
            }
        }

        if ($errors === []) {
            $this->io->success('All classification stores resolve to a registered engine.');

            return self::SUCCESS;
        }

        foreach ($errors as $error) {
            $this->io->error($error);
        }

        return self::FAILURE;
    }

    /**
     * @return StoreConfig[]
     */
    protected function loadStoreConfigs(): array
    {
        return (new StoreConfig\Listing())->load();
    }
}
