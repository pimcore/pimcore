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

namespace Pimcore\Bundle\SeoBundle\Event\Model;

use Pimcore\Bundle\SeoBundle\Model\Redirect;
use Pimcore\Event\Traits\ArgumentsAwareTrait;
use Symfony\Contracts\EventDispatcher\Event;

class RedirectEvent extends Event
{
    use ArgumentsAwareTrait;

    protected Redirect $redirect;

    /**
     * @param array $arguments additional parameters (e.g. "versionNote" for the version note)
     */
    public function __construct(Redirect $redirect, array $arguments = [])
    {
        $this->redirect = $redirect;
        $this->arguments = $arguments;
    }

    public function getRedirect(): Redirect
    {
        return $this->redirect;
    }

    public function setRedirect(Redirect $redirect): void
    {
        $this->redirect = $redirect;
    }
}
