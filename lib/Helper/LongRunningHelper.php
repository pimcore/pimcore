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

namespace Pimcore\Helper;

use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ConnectionRegistry;
use Monolog\Handler\HandlerInterface;
use Psr\Log\LoggerAwareTrait;

class LongRunningHelper
{
    use LoggerAwareTrait;

    protected $connectionRegistry;
    protected $pimcoreRuntimeCacheProtectedItems = [
        'Config_system',
        'pimcore_admin_user',
        'Config_website',
        'pimcore_editmode',
        'pimcore_error_document',
        'pimcore_site',
        'Pimcore_Db',
    ];
    protected $monologHandlers = [];

    /**
     * LongRunningHelper constructor.
     *
     * @param ConnectionRegistry $connectionRegistry
     */
    public function __construct(ConnectionRegistry $connectionRegistry)
    {
        $this->connectionRegistry = $connectionRegistry;
    }

    /**
     * @param array $options
     */
    public function cleanUp($options = [])
    {
        $this->cleanupDoctrine();
        $this->cleanupMonolog();
        $this->cleanupPimcoreRuntimeCache($options);
        $this->triggerPhpGarbageCollector();
    }

    protected function cleanupDoctrine()
    {
        try {
            foreach ($this->connectionRegistry->getConnections() as $name => $connection) {
                if (!($connection instanceof Connection)) {
                    throw new \LogicException('Expected only instances of Connection');
                }
                if ($connection->isTransactionActive() === false) {
                    $connection->close();
                }
            }
        } catch (\Exception $e) {
            // connection couldn't be established, this is e.g. the case when Pimcore isn't installed yet
        }
    }

    protected function triggerPhpGarbageCollector()
    {
        gc_enable();
        $collectedCycles = gc_collect_cycles();

        $this->logger->debug(sprintf('PHP garbage collector collected %d cycles', $collectedCycles));
    }

    /**
     * @param array $options
     */
    protected function cleanupPimcoreRuntimeCache($options = [])
    {
        $options = $this->resolveOptions(__METHOD__, $options);

        $protectedItems = $this->pimcoreRuntimeCacheProtectedItems;

        if (isset($options['keepItems']) && is_array($options['keepItems']) && count($options['keepItems']) > 0) {
            $protectedItems = array_merge($protectedItems, $options['keepItems']);
        }

        \Pimcore\Cache\Runtime::clear($protectedItems);
    }

    /**
     * @param array $items
     */
    public function addPimcoreRuntimeCacheProtectedItems(array $items)
    {
        $this->pimcoreRuntimeCacheProtectedItems = array_merge($this->pimcoreRuntimeCacheProtectedItems, $items);
        $this->pimcoreRuntimeCacheProtectedItems = array_unique($this->pimcoreRuntimeCacheProtectedItems);
    }

    /**
     * @param array $items
     */
    public function removePimcoreRuntimeCacheProtectedItems(array $items)
    {
        foreach ($this->pimcoreRuntimeCacheProtectedItems as $item) {
            $key = array_search($item, $this->pimcoreRuntimeCacheProtectedItems);
            if ($key !== false) {
                unset($this->pimcoreRuntimeCacheProtectedItems[$key]);
            }
        }
    }

    public function cleanupMonolog()
    {
        foreach ($this->monologHandlers as $handler) {
            $handler->close();
        }
    }

    /**
     * @param HandlerInterface $handler
     */
    public function addMonologHandler(HandlerInterface $handler)
    {
        $this->monologHandlers[] = $handler;
    }

    /**
     * @param string $method
     * @param array $options
     *
     * @return array
     */
    protected function resolveOptions(string $method, array $options)
    {
        $name = preg_replace('@[^\:]+\:\:cleanup@', '', $method);
        $name = lcfirst($name);
        if (isset($options[$name])) {
            return $options[$name];
        }

        return [];
    }
}
