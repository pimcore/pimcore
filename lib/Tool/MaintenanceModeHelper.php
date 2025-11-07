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

namespace Pimcore\Tool;

use Doctrine\DBAL\Connection;
use Exception;
use InvalidArgumentException;
use Pimcore;
use Pimcore\Cache;
use Pimcore\Event\SystemEvents;
use Pimcore\Model\Tool\TmpStore;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpFoundation\RequestStack;

class MaintenanceModeHelper implements MaintenanceModeHelperInterface
{
    protected const ENTRY_ID = 'maintenance_mode';

    protected const OFF = 'OFF';

    public function __construct(protected RequestStack $requestStack, protected Connection $db)
    {
    }

    public function activate(string $sessionId): void
    {
        if (empty($sessionId)) {
            $sessionId = $this->requestStack->getSession()->getId();
        }

        if (empty($sessionId)) {
            throw new InvalidArgumentException('Pass sessionId to activate the maintenance mode');
        }

        $this->addEntry($sessionId);

        Pimcore::getEventDispatcher()->dispatch(new GenericEvent(), SystemEvents::MAINTENANCE_MODE_ACTIVATE);
    }

    public function deactivate(): void
    {
        $this->removeEntry();

        Pimcore::getEventDispatcher()->dispatch(new GenericEvent(), SystemEvents::MAINTENANCE_MODE_DEACTIVATE);
    }

    public function isActive(?string $matchSessionId = null): bool
    {
        if ($maintenanceModeEntry = $this->getEntry()) {
            if ($matchSessionId === null || $matchSessionId !== $maintenanceModeEntry) {
                return true;
            }
        }

        return false;
    }

    protected function addEntry(string $sessionId): void
    {
        Cache::save($sessionId, self::ENTRY_ID, lifetime: null, force: true);
        TmpStore::add(self::ENTRY_ID, $sessionId);
    }

    protected function getEntry(): ?string
    {
        $tmpStore = null;

        try {
            $entryId = Cache::load(self::ENTRY_ID);
            if ($entryId) {
                // If the entry is set to OFF, we return null to indicate that maintenance mode is not active
                return $entryId === self::OFF ? null : $entryId;
            }
        } catch (Exception $exception) {
            // The cache entry is not set, we try to load it from the database
            try {
                if (!$this->db->isConnected()) {
                    $this->db->getNativeConnection();
                }
                $tmpStore = TmpStore::get(self::ENTRY_ID);
            } catch (Exception $e) {
                return null;
            }
        }

        $entryValue = null;
        if ($tmpStore instanceof TmpStore) {
            $entryValue = $tmpStore->getData();
        }
        // We set the cache entry to OFF if it isn't set, to avoid unnecessary database calls in the future
        Cache::save($entryValue ?? self::OFF, self::ENTRY_ID, lifetime: null);

        return $entryValue;
    }

    protected function removeEntry(): void
    {
        try {
            Cache::save(self::OFF, self::ENTRY_ID, lifetime: null, force: true);
            TmpStore::delete(self::ENTRY_ID);
        } catch (Exception $e) {
            //nothing to log as the tmp doesn't exist
        }
    }
}
