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

namespace Pimcore\Bundle\AdminBundle\Security\Authentication\Token;

use Symfony\Component\Security\Http\Authenticator\Token\PostAuthenticationToken;

/**
 * Test stand-in for the token the classic admin UI issues while two-factor authentication is
 * pending. It is a plain PostAuthenticationToken subclass there, and the bundle is not a core
 * dependency, so the class is declared here under its real name. Load it only when the real
 * class is not available.
 */
class TwoFactorRequiredToken extends PostAuthenticationToken
{
}
