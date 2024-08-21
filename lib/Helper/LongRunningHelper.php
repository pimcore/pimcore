<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Helper;

use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ConnectionRegistry;
use Exception;
use LogicException;
use Monolog\Handler\HandlerInterface;
use Pimcore\Cache\RuntimeCache;
use Psr\Log\LoggerAwareTrait;

final class LongRunningHelper
{
    use LoggerAwareTrait;

    protected ConnectionRegistry $connectionRegistry;

    /**
     * @var string[]
     */
    protected array $pimcoreRuntimeCacheProtectedItems = [
        'Config_system',
        'pimcore_admin_user',
        'Config_website',
        'pimcore_error_document',
        'pimcore_site',
        'Pimcore_Db',
    ];

    protected array $monologHandlers = [];

    /**
     * @var string[]
     */
    protected array $tmpFilePaths = [];

    /**
     * LongRunningHelper constructor.
     *
     */
    public function __construct(ConnectionRegistry $connectionRegistry)
    {
        $this->connectionRegistry = $connectionRegistry;
    }

    public function cleanUp(array $options = []): void
    {
        $this->cleanupDoctrine();
        $this->cleanupMonolog();
        $this->cleanupPimcoreRuntimeCache($options);
        $this->triggerPhpGarbageCollector();
    }

    protected function cleanupDoctrine(): void
    {
        try {
            foreach ($this->connectionRegistry->getConnections() as $name => $connection) {
                if (!($connection instanceof Connection)) {
                    throw new LogicException('Expected only instances of Connection');
                }
                if ($connection->isTransactionActive() === false) {
                    $connection->close();
                }
            }
        } catch (Exception $e) {
            // connection couldn't be established, this is e.g. the case when Pimcore isn't installed yet
        }
    }

    protected function triggerPhpGarbageCollector(): void
    {
        gc_enable();
        $collectedCycles = gc_collect_cycles();

        $this->logger->debug(sprintf('PHP garbage collector collected %d cycles', $collectedCycles));
    }

    protected function cleanupPimcoreRuntimeCache(array $options = []): void
    {
        $options = $this->resolveOptions(__METHOD__, $options);

        $protectedItems = $this->pimcoreRuntimeCacheProtectedItems;

        if (isset($options['keepItems']) && is_array($options['keepItems']) && count($options['keepItems']) > 0) {
            $protectedItems = array_merge($protectedItems, $options['keepItems']);
        }

        RuntimeCache::clear($protectedItems);
    }

    public function addPimcoreRuntimeCacheProtectedItems(array $items): void
    {
        $this->pimcoreRuntimeCacheProtectedItems = array_merge($this->pimcoreRuntimeCacheProtectedItems, $items);
        $this->pimcoreRuntimeCacheProtectedItems = array_unique($this->pimcoreRuntimeCacheProtectedItems);
    }

    public function removePimcoreRuntimeCacheProtectedItems(array $items): void
    {
        foreach ($this->pimcoreRuntimeCacheProtectedItems as $item) {
            $key = array_search($item, $this->pimcoreRuntimeCacheProtectedItems);
            if ($key !== false) {
                unset($this->pimcoreRuntimeCacheProtectedItems[$key]);
            }
        }
    }

    protected function cleanupMonolog(): void
    {
        foreach ($this->monologHandlers as $handler) {
            $handler->close();
        }
    }

    /**
     * @internal
     *
     */
    public function addMonologHandler(HandlerInterface $handler): void
    {
        $this->monologHandlers[] = $handler;
    }

    protected function resolveOptions(string $method, array $options): array
    {
        $name = preg_replace('@[^\:]+\:\:cleanup@', '', $method);
        $name = lcfirst($name);
        if (isset($options[$name])) {
            return $options[$name];
        }

        return [];
    }

    /**
     * @internal
     * Register a temp file which will be deleted on next call of cleanUp()
     */
    public function addTmpFilePath(string $tmpFilePath): void
    {
        $this->tmpFilePaths[] = $tmpFilePath;
    }

    public function deleteTemporaryFiles(): void
    {
        foreach ($this->tmpFilePaths as $tmpFilePath) {
            @unlink($tmpFilePath);
        }
        $this->tmpFilePaths = [];
    }
}
