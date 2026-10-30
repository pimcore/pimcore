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
use Pimcore\Model\DataObject\Exception\ConflictingClassificationStoreEnginesException;
use Pimcore\Model\DataObject\Exception\UnknownClassificationStoreEngineException;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Maps the engine column of a classification store to the engine that owns its values.
 *
 * An unresolvable value never falls back to the default engine: a store that has already been
 * partially migrated would read back empty values and write EAV rows on the next save.
 *
 * Which engine a store resolves to is memoized per process; that memo is cleared at the request and
 * messenger-message boundary via the kernel.reset tag (ResetInterface), and on a store configuration
 * write by {@see StoreConfig::save()} / {@see StoreConfig::delete()}.
 *
 * @internal
 */
final class EngineResolver implements ResetInterface
{
    private const DEFAULT_ENGINE = 'eav';

    /**
     * Engine names this release knows about but does not ship yet. Empty since `document` ships, so
     * {@see ClassificationStoreEngineNotAvailableException} is no longer raised from the default
     * configuration - the class is kept, as it carries no `@internal` and catching it is legal.
     *
     * @var string[]
     */
    private const UNAVAILABLE_ENGINES = [];

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
     * The store is looked up at most once per request or messenger message, as StoreConfig reads
     * straight through its Dao - {@see reset()} and {@see invalidateStore()} drop the memo again.
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
     * The one engine behind a group of classification store fields that share their value rows.
     *
     * The value tables key their rows by (object, fieldname), so two containers defining the same
     * classification store field name read and write the same rows and cannot sit on two engines.
     * An empty group means no field definition was found at all, which is the default engine.
     *
     * @param array<string, int|null> $storeIds container-qualified field identity => configured store id
     *
     * @throws UnknownClassificationStoreEngineException
     * @throws ClassificationStoreEngineNotAvailableException
     * @throws ConflictingClassificationStoreEnginesException
     */
    public function resolveForStores(array $storeIds): EngineInterface
    {
        $engines = [];
        $first = null;

        foreach ($storeIds as $identity => $storeId) {
            $engines[$identity] = $this->resolveForStore($storeId);
            $first ??= $engines[$identity];
        }

        if ($first === null) {
            return $this->getDefaultEngine();
        }

        if (count(array_unique(array_map(spl_object_id(...), $engines))) > 1) {
            throw new ConflictingClassificationStoreEnginesException($this->nameEngines($engines));
        }

        return $first;
    }

    /**
     * Drops the memo of which engine each store resolves to, so that a store flipped to another
     * engine is read back from its configuration again.
     *
     * The registered engines are not dropped: they come from the container and cannot change
     * within a process.
     */
    public function reset(): void
    {
        $this->storeEngines = [];
    }

    /**
     * Drops the memo of a single store, for the code that has just changed its configuration.
     */
    public function invalidateStore(int $storeId): void
    {
        unset($this->storeEngines[$storeId]);
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

        // @phpstan-ignore-next-line empty while every known engine ships; holding one back re-fills it
        if (in_array($engine, self::UNAVAILABLE_ENGINES, true)) {
            throw new ClassificationStoreEngineNotAvailableException($storeId, $engine);
        }

        throw new UnknownClassificationStoreEngineException($storeId, $engine);
    }

    /**
     * @param array<string, EngineInterface> $engines
     *
     * @return array<string, string> field identity => engine name
     */
    private function nameEngines(array $engines): array
    {
        $names = [];
        foreach ($engines as $identity => $engine) {
            $name = array_search($engine, $this->getEngines(), true);
            $names[$identity] = $name === false ? $engine::class : $name;
        }

        return $names;
    }
}
