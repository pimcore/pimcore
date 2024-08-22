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

use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

final class Session
{
    /**
     * @param callable(AttributeBagInterface, SessionInterface):mixed $func
     */
    public static function useBag(SessionInterface $session, callable $func, string $namespace = 'pimcore_admin'): mixed
    {
        $bag = $session->getBag($namespace);

        if ($bag instanceof AttributeBagInterface) {
            return $func($bag, $session);
        }

        throw new InvalidArgumentException(sprintf('The Bag "%s" is not a AttributeBagInterface.', $namespace));
    }

    public static function getSessionBag(
        SessionInterface $session,
        string $namespace = 'pimcore_admin'
    ): ?AttributeBagInterface {
        $bag = $session->getBag($namespace);
        if ($bag instanceof AttributeBagInterface) {
            return $bag;
        }

        return null;
    }
}
