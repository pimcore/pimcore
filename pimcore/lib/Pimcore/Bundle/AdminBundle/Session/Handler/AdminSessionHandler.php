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

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class AdminSessionHandler extends AbstractAdminSessionHandler implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * Contains how many sessions are currently open, this is important, because writeClose() must not be called if
     * there is still an open session, this is especially important if something doesn't use the method use() but get()
     * so the session isn't closed automatically after the action is done
     */
    private $openedSessions = 0;

    /**
     * @inheritdoc
     */
    public function loadSession(): SessionInterface
    {
        $sessionName = $this->getSessionName();

        $this->logger->debug('Opening admin session {name}', ['name' => $sessionName]);

        if (!$this->session->isStarted()) {
            $this->session->start();
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

        if (0 === $this->openedSessions) {
            $this->session->save();

            $this->logger->debug('Admin session {name} was written and closed', [
                'name' => $this->getSessionName()
            ]);
        } else {
            $this->logger->debug('Not writing/closing session admin session {name} as there are still {count} open sessions', [
                'name'  => $this->getSessionName(),
                'count' => $this->openedSessions
            ]);
        }
    }
}
