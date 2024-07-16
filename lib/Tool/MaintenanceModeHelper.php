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

namespace Pimcore\Tool;

use Doctrine\DBAL\Connection;
use Exception;
use InvalidArgumentException;
use Pimcore;
use Pimcore\Event\SystemEvents;
use Pimcore\Model\Tool\TmpStore;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpFoundation\RequestStack;

class MaintenanceModeHelper implements MaintenanceModeHelperInterface
{
    protected const ENTRY_ID = 'maintenance_mode';

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

    public function isActive(string $matchSessionId = null): bool
    {
        try {
            if (!$this->db->isConnected()) {
                $this->db->connect();
            }
        } catch (Exception) {
            return false;
        }

        if ($maintenanceModeEntry = $this->getEntry()) {
            if ($matchSessionId === null || $matchSessionId !== $maintenanceModeEntry) {
                return true;
            }
        }

        return false;
    }

    protected function addEntry(string $sessionId): void
    {
        TmpStore::add(self::ENTRY_ID, $sessionId);
    }

    protected function getEntry(): ?string
    {
        try {
            $tmpStore = TmpStore::get(self::ENTRY_ID);
        } catch (Exception $e) {
            //nothing to log as the tmp doesn't exist
            return null;
        }

        return $tmpStore instanceof TmpStore ? $tmpStore->getData() : null;
    }

    protected function removeEntry(): void
    {
        try {
            TmpStore::delete(self::ENTRY_ID);
        } catch (Exception $e) {
            //nothing to log as the tmp doesn't exist
        }
    }
}
