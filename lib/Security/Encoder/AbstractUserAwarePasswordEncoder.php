<?php

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

namespace Pimcore\Security\Encoder;

use Symfony\Component\Security\Core\Encoder\BasePasswordEncoder;
use Symfony\Component\Security\Core\Exception\RuntimeException;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @internal
 */
abstract class AbstractUserAwarePasswordEncoder extends BasePasswordEncoder implements UserAwarePasswordEncoderInterface
{
    /**
     * @var UserInterface|null
     */
    protected $user;

    /**
     * {@inheritdoc}
     */
    public function setUser(UserInterface $user)
    {
        if ($this->user) {
            throw new RuntimeException('User was already set and can\'t be overwritten');
        }

        $this->user = $user;
    }

    /**
     * {@inheritdoc}
     */
    public function getUser()
    {
        if (!$this->user) {
            throw new RuntimeException('No user was set');
        }

        return $this->user;
    }
}
