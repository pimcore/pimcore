<?php

declare(strict_types=1);

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

use Symfony\Component\HttpFoundation\Session\SessionInterface;

class SimpleAdminSessionHandler extends AbstractAdminSessionHandler
{
    public function __construct(SessionInterface $session)
    {
        $this->session = $session;
    }

    /**
     * @inheritDoc
     */
    public function getOption(string $name)
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function getSessionName(): string
    {
        return $this->session->getName();
    }

    /**
     * @inheritDoc
     */
    public function loadSession(): SessionInterface
    {
        return $this->session;
    }

    /**
     * @inheritDoc
     */
    public function writeClose()
    {
        $this->session->save();
    }
}
