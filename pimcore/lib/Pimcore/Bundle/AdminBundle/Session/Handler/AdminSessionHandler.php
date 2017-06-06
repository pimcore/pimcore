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

namespace Pimcore\Bundle\AdminBundle\Session\Handler;

use Pimcore\Bundle\AdminBundle\Session\AdminSessionStorageFactory;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorage;

class AdminSessionHandler extends AbstractAdminSessionHandler implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * contains how many sessions are currently open, this is important, because writeClose() must not be called if
     * there is still an open session, this is especially important if something doesn't use the method use() but get()
     * so the session isn't closed automatically after the action is done
     */
    private $openedSessions = 0;

    /**
     * when using mod_php, session_start() always adds an Set-Cookie header when called,
     * this is the case in self::get(), so depending on how often self::get() is called the more
     * header will get added to the response, so we clean them up in Pimcore::outputBufferEnd()
     * to avoid problems with (reverse-)proxies such as Varnish who do not like too much Set-Cookie headers
     *
     * @var bool
     */
    private $sessionCookieCleanupNeeded = false;

    /**
     * @var array
     */
    private $foreignSessionStack = [];

    /**
     * @var NativeSessionStorage
     */
    private $storage;

    /**
     * @var AdminSessionStorageFactory
     */
    private $storageFactory;

    /**
     * @var array
     */
    private $validSessionOptions;

    /**
     * @param SessionInterface $session
     * @param NativeSessionStorage $storage
     * @param AdminSessionStorageFactory $storageFactory
     */
    public function __construct(
        SessionInterface $session,
        NativeSessionStorage $storage,
        AdminSessionStorageFactory $storageFactory
    ) {
        $this->session        = $session;
        $this->storage        = $storage;
        $this->storageFactory = $storageFactory;
    }

    /**
     * @inheritdoc
     */
    public function getOption(string $name)
    {
        return $this->storageFactory->getOption($name);
    }

    /**
     * @inheritdoc
     */
    public function getSessionName(): string
    {
        return $this->getOption('name');
    }

    /**
     * @inheritdoc
     */
    public function loadSession(): SessionInterface
    {
        $sessionName = $this->getSessionName();

        $this->logger->debug('Opening admin session {name}', ['name' => $sessionName]);

        $initSession = session_status() !== PHP_SESSION_ACTIVE;
        if ($this->backupForeignSession()) {
            $initSession = true;
        }

        if ($initSession) {
            $this->storageFactory->initializeStorage($this->storage);

            if (isset($_COOKIE[$sessionName])) {
                $this->session->setId($_COOKIE[$sessionName]);
            }

            // only set the session id if the cookie isn't present, otherwise Set-Cookie is always in the headers
            if (array_key_exists($sessionName, $_REQUEST) && !empty($_REQUEST[$sessionName]) && (!array_key_exists($sessionName, $_COOKIE) || empty($_COOKIE[$sessionName]))) {
                // get session work with session-id via get (since SwfUpload doesn't support cookies)
                $this->session->setId($_REQUEST[$sessionName]);
            }
        }

        $this->openedSessions++;

        $this->logger->debug('Admin session {name} was successfully opened. Open admin sessions: {count}', [
            'name'  => $sessionName,
            'count' => $this->openedSessions
        ]);

        return $this->session;
    }

    /**
     * @inheritdoc
     */
    public function writeClose()
    {
        $this->openedSessions--;

        if ($this->openedSessions === 0) {
            $this->session->save();

            $this->logger->debug('Admin session {name} was written and closed', [
                'name' => $this->getSessionName()
            ]);

            $this->restoreForeignSession();
        } else {
            $this->logger->debug('Not writing/closing session admin session {name} as there are still {count} open sessions', [
                'name'  => $this->getSessionName(),
                'count' => $this->openedSessions
            ]);
        }
    }

    /**
     * Backs up currently open foreign sessions
     *
     * @return bool
     */
    private function backupForeignSession(): bool
    {
        $sessionName      = session_name();
        $adminSessionName = $this->getSessionName();

        $this->logger->debug('Current session name is {name}', [
            'name'             => $sessionName,
            'adminSessionName' => $adminSessionName
        ]);

        if ($sessionName === $adminSessionName) {
            return false;
        }

        $sessionId = null;
        if (session_status() === PHP_SESSION_ACTIVE) {
            $sessionId = session_id();
        }

        $options = ini_get_all('session', false);

        $stackCount = count($this->foreignSessionStack) + 1;
        if (null !== $sessionId) {
            $this->logger->debug('Backing up active foreign session {name} with ID {id}. Total count on stack: {count}', [
                'name'  => $sessionName,
                'id'    => $sessionId,
                'count' => $stackCount
            ]);
        } else {
            $this->logger->debug('Backing up foreign session {name}. Total count on stack: {count}.', [
                'name'  => $sessionName,
                'count' => $stackCount
            ]);
        }

        array_push($this->foreignSessionStack, [
            'id'      => $sessionId,
            'name'    => $sessionName,
            'options' => $options
        ]);

        if ($sessionId) {
            @session_write_close();
        }

        return true;
    }

    /**
     * Restores previously backed up sessions
     *
     * @return bool
     */
    private function restoreForeignSession(): bool
    {
        if (empty($this->foreignSessionStack)) {
            return false;
        }

        $data      = array_pop($this->foreignSessionStack);
        $sessionId = $data['id'];

        $stackCount = count($this->foreignSessionStack);
        if ($sessionId) {
            $this->logger->debug('Restoring active foreign session {name} with ID {id}. Remaining count on stack: {count}', [
                'name'  => $data['name'],
                'id'    => $sessionId,
                'count' => $stackCount
            ]);
        } else {
            $this->logger->debug('Restoring foreign session {name}. Remaining count on stack: {count}', [
                'name'  => $data['name'],
                'count' => $stackCount
            ]);
        }

        $this->session->save(); // session_write_close
        session_name($data['name']);

        if ($sessionId) {
            session_id($sessionId);

            // TODO handle $sessionCookieCleanupNeeded?
            @session_start();
        }

        return true;
    }
}
