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

namespace Pimcore\Event\Admin\Login;

use Pimcore\Event\Traits\RequestAwareTrait;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Request;

class LoginCredentialsEvent extends Event
{
    use RequestAwareTrait;

    /**
     * @var array
     */
    protected $credentials;

    /**
     * @param Request $request
     * @param array $credentials
     */
    public function __construct(Request $request, array $credentials)
    {
        $this->request     = $request;
        $this->credentials = $credentials;
    }

    /**
     * @return array
     */
    public function getCredentials()
    {
        return $this->credentials;
    }

    /**
     * @param array $credentials
     */
    public function setCredentials(array $credentials)
    {
        $this->credentials = $credentials;
    }
}
