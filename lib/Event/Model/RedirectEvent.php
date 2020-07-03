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

namespace Pimcore\Event\Model;

use Pimcore\Event\Traits\ArgumentsAwareTrait;
use Pimcore\Model\Redirect;
use Symfony\Component\EventDispatcher\Event;

class RedirectEvent extends Event
{
    use ArgumentsAwareTrait;

    /**
     * @var Redirect
     */
    protected $redirect;

    /**
     * @param Redirect $redirect
     * @param array $arguments additional parameters (e.g. "versionNote" for the version note)
     */
    public function __construct(Redirect $redirect, array $arguments = [])
    {
        $this->redirect = $redirect;
        $this->arguments = $arguments;
    }

    /**
     * @return Redirect
     */
    public function getRedirect(): Redirect
    {
        return $this->redirect;
    }

    /**
     * @param Redirect $redirect
     */
    public function setRedirect(Redirect $redirect): void
    {
        $this->redirect = $redirect;
    }
}
