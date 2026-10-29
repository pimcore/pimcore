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

namespace Pimcore\Model\DataObject\Classificationstore\Engine;

use Pimcore\Model\DataObject\Classificationstore\StoreConfig;
use Pimcore\Model\DataObject\Exception\ClassificationStoreEngineNotAvailableException;
use Pimcore\Model\DataObject\Exception\UnknownClassificationStoreEngineException;

/**
 * Maps the engine column of a classification store to the engine that owns its values.
 *
 * An unresolvable value never falls back to the default engine: a store that has already been
 * partially migrated would read back empty values and write EAV rows on the next save.
 *
 * @internal
 */
final class EngineResolver
{
    private const DEFAULT_ENGINE = 'eav';

    /**
     * Engine names this release knows about but does not ship yet.
     */
    private const UNAVAILABLE_ENGINES = ['document'];

    /**
     * @var array<string, EngineInterface>|null
     */
    private ?array $registeredEngines = null;

    /**
     * @var array<int, EngineInterface>
     */
    private array $storeEngines = [];

    /**
     * @param iterable<string, EngineInterface> $engines
     */
    public function __construct(private readonly iterable $engines)
    {
    }

    /**
     * @return array<string, EngineInterface> engine name => engine
     */
    public function getEngines(): array
    {
        if ($this->registeredEngines === null) {
            $this->registeredEngines = is_array($this->engines) ? $this->engines : iterator_to_array($this->engines);
        }

        return $this->registeredEngines;
    }

    public function getDefaultEngine(): EngineInterface
    {
        return $this->getEngines()[self::DEFAULT_ENGINE];
    }

    /**
     * Resolves the engine of a store, or the default engine when no store is configured.
     *
     * The store is looked up at most once per process, as StoreConfig reads straight through its Dao.
     *
     * @throws UnknownClassificationStoreEngineException
     * @throws ClassificationStoreEngineNotAvailableException
     */
    public function resolveForStore(?int $storeId): EngineInterface
    {
        if ($storeId === null) {
            return $this->getDefaultEngine();
        }

        if (!isset($this->storeEngines[$storeId])) {
            $this->storeEngines[$storeId] = $this->resolve(StoreConfig::getById($storeId)?->getEngine(), $storeId);
        }

        return $this->storeEngines[$storeId];
    }

    /**
     * Resolves an engine value that the caller has already read, naming the store it came from.
     *
     * @throws UnknownClassificationStoreEngineException
     * @throws ClassificationStoreEngineNotAvailableException
     */
    public function resolve(?string $engine, int $storeId): EngineInterface
    {
        if ($engine === null || $engine === self::DEFAULT_ENGINE) {
            return $this->getDefaultEngine();
        }

        $engines = $this->getEngines();
        if (isset($engines[$engine])) {
            return $engines[$engine];
        }

        if (in_array($engine, self::UNAVAILABLE_ENGINES, true)) {
            throw new ClassificationStoreEngineNotAvailableException($storeId, $engine);
        }

        throw new UnknownClassificationStoreEngineException($storeId, $engine);
    }
}
