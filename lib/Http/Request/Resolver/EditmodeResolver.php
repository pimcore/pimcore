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

namespace Pimcore\Http\Request\Resolver;

use Pimcore\Http\RequestHelper;
use Pimcore\Security\User\UserLoader;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class EditmodeResolver extends AbstractRequestResolver implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    const ATTRIBUTE_EDITMODE = '_editmode';

    protected UserLoader $userLoader;

    protected RequestHelper $requestHelper;

    private bool $forceEditmode = false;

    public function __construct(RequestStack $requestStack, UserLoader $userLoader, RequestHelper $requestHelper)
    {
        $this->userLoader = $userLoader;
        $this->requestHelper = $requestHelper;

        parent::__construct($requestStack);
    }

    public function setForceEditmode(bool $forceEditmode): static
    {
        $this->forceEditmode = $forceEditmode;

        return $this;
    }

    public function isEditmode(Request $request = null): bool
    {
        if ($this->forceEditmode) {
            $this->logger->debug('Resolved editmode to true as force editmode is set');

            return true;
        }

        if (null === $request) {
            $request = $this->getCurrentRequest();
        }

        // try to read attribute from request - this allows sub-requests to define their
        // own editmode state
        if ($request->attributes->has(static::ATTRIBUTE_EDITMODE)) {
            return $request->attributes->get(static::ATTRIBUTE_EDITMODE);
        }

        $logData = [
            'param' => false,
            'adminRequest' => false,
            'user' => false,
        ];

        // read editmode from request params
        $result = false;
        if ($request->query->has('pimcore_editmode')) {
            $logData['param'] = true;
            $result = true;
        }

        if ($result) {
            // editmode is only allowed for logged in users
            if (!$this->requestHelper->isFrontendRequestByAdmin($request)) {
                $result = false;
            } else {
                $logData['adminRequest'] = true;
            }

            $user = $this->userLoader->getUser();
            if (!$user) {
                $result = false;
            } else {
                $logData['user'] = true;
            }
        }

        $this->logger->debug('Resolved editmode to {editmode}', [
            'editmode' => $result ? 'true' : 'false',
            'params' => $logData,
        ]);

        $request->attributes->set(static::ATTRIBUTE_EDITMODE, $result);

        return $result;
    }
}
