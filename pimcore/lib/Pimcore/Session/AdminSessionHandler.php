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

namespace Pimcore\Session;

use Pimcore\Bundle\PimcoreAdminBundle\Session\AdminSessionStorageFactory;
use Pimcore\Session\Attribute\LockableAttributeBag;
use Pimcore\Session\Attribute\LockableAttributeBagInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorage;

class AdminSessionHandler implements LoggerAwareInterface
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
     * @var bool
     */
    private $sessionCookieCleanupNeeded = false;

    /**
     * @var array
     */
    private $foreignSessionStack = [];

    /**
     * @var SessionInterface
     */
    private $session;

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
    )
    {
        $this->session        = $session;
        $this->storage        = $storage;
        $this->storageFactory = $storageFactory;
    }

    /**
     * @param string $name
     *
     * @return mixed|null
     */
    public function getOption($name)
    {
        return $this->storageFactory->getOption($name);
    }

    /**
     * @return string
     */
    public function getSessionName()
    {
        return $this->getOption('name');
    }

    /**
     * Use the admin session and immediately close it after usage. The callable gets the session as its first argument.
     *
     * @param callable $callable
     *
     * @return mixed
     */
    public function useSession(callable $callable)
    {
        $session = $this->loadSession();

        $result = call_user_func_array($callable, [$session]);

        $this->writeClose();

        return $result;
    }

    /**
     * Use an attribute bag and close the session immediately after usage. The callable gets the attribute bag and the session
     * as arguments.
     *
     * @param callable $callable
     * @param string $name
     *
     * @return mixed
     */
    public function useSessionAttributeBag(callable $callable, $name = 'pimcore_admin')
    {
        $session      = $this->loadSession();
        $attributeBag = $this->loadAttributeBag($name, $session);

        $result = call_user_func_array($callable, [$attributeBag, $session]);

        $this->writeClose();

        return $result;
    }

    /**
     * Loads an attribute bag, optionally lock it if supported and close the session.
     *
     * @param string $name
     *
     * @return AttributeBagInterface
     */
    public function getReadOnlyAttributeBag($name = 'pimcore_admin')
    {
        $bag = $this->useSessionAttributeBag(function (AttributeBagInterface $bag) {
            if ($bag instanceof LockableAttributeBagInterface) {
                $bag->lock();
            }

            return $bag;
        }, $name);

        return $bag;
    }

    /**
     * Returns the session ID
     *
     * @see SessionInterface::getId()
     *
     * @return string
     */
    public function getSessionId()
    {
        return $this->useSession(function (SessionInterface $session) {
            return $session->getId();
        });
    }

    /**
     * @param int|null $lifetime
     *
     * @return bool
     */
    public function invalidate($lifetime = null)
    {
        return $this->session->invalidate($lifetime);
    }

    /**
     * Regenerates the session ID
     *
     * @see SessionInterface::migrate()
     *
     * @return bool
     */
    public function regenerateId()
    {
        return $this->useSession(function (SessionInterface $session) {
            return $session->migrate(true);
        });
    }

    /**
     * Loads the admin session and backs up a currently open foreign session. You MUST call writeClose() after usage
     * to make sure the admin session is written and foreign sessions are restored.
     *
     * @return SessionInterface
     */
    public function loadSession()
    {
        $initSession = session_status() !== PHP_SESSION_ACTIVE;
        if ($this->backupForeignSession()) {
            $initSession = true;
        }

        $sessionName = $this->getSessionName();

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

        return $this->session;
    }

    /**
     * Directly loads an attribute bag from the session. You MUST call writeClose() after usage
     * to make sure the admin session is written and foreign sessions are restored.
     *
     * @param $name
     * @param SessionInterface|null $session
     *
     * @return LockableAttributeBag|\Symfony\Component\HttpFoundation\Session\SessionBagInterface
     */
    public function loadAttributeBag($name, SessionInterface $session = null)
    {
        if (null === $session) {
            $session = $this->loadSession();
        }

        try {
            $attributeBag = $session->getBag($name);
        } catch (\Exception $e) {
            // requested bag doesn't exist, we create a default attribute bag
            $attributeBag = new LockableAttributeBag($name);
            $session->registerBag($attributeBag);
        }

        if ($attributeBag instanceof LockableAttributeBagInterface) {
            $attributeBag->unlock();
        }

        return $attributeBag;
    }

    /**
     * Saves the session if it it the last admin session which was opened and restore a foreign session if there is one
     * available.
     */
    public function writeClose()
    {
        $this->openedSessions--;

        if ($this->openedSessions === 0) {
            $this->session->save();
            $this->restoreForeignSession();
        }
    }

    /**
     * Check if the request has a cookie or a param matching the session name.
     *
     * @param Request $request
     * @param bool    $checkRequestParams
     *
     * @return bool
     */
    public function requestHasSessionId(Request $request, $checkRequestParams = false)
    {
        $sessionName = $this->getSessionName();

        $properties = ['cookies'];

        if ($checkRequestParams) {
            $properties[] = 'request';
            $properties[] = 'query';
        }

        foreach ($properties as $property) {
            if ($request->$property->has($sessionName) && !empty($request->$property->get($sessionName))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get session ID from request cookie/param
     *
     * @param Request $request
     * @param bool    $checkRequestParams
     *
     * @return string
     */
    public function getSessionIdFromRequest(Request $request, $checkRequestParams = false)
    {
        if (static::requestHasSessionId($request, $checkRequestParams)) {
            $sessionName = static::getSessionName();

            if ($sessionId = $request->cookies->get($sessionName)) {
                return $sessionId;
            }

            if ($checkRequestParams) {
                if ($sessionId = $request->request->get($sessionName)) {
                    return $sessionId;
                }

                if ($sessionId = $request->query->get($sessionName)) {
                    return $sessionId;
                }
            }
        }

        throw new \RuntimeException('Failed to get session ID from request');
    }

    /**
     * Backs up currently open foreign sessions
     *
     * @return bool
     */
    private function backupForeignSession()
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
    private function restoreForeignSession()
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
            @session_start();
        }

        return true;
    }
}
