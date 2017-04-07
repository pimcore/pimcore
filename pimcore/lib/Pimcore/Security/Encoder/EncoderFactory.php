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

namespace Pimcore\Security\Encoder;

use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Password encoding and verification for Pimcore objects and admin users is implemented on the user object itself.
 * Therefore the encoder needs the user object when encoding or verifying a password. This factory decorates the core
 * factory and allows to delegate building the encoder to a type specific factory which then is able to create a
 * dedicated encoder for a user object.
 *
 * If the given user is not configured to be handled by one of the encoder factories, the normal framework encoder
 * logic applies.
 */
class EncoderFactory implements EncoderFactoryInterface
{
    /**
     * @var EncoderFactoryInterface
     */
    protected $frameworkFactory;

    /**
     * @var EncoderFactoryInterface[]
     */
    protected $encoderFactories = [];

    /**
     * @param EncoderFactoryInterface $frameworkFactory
     * @param array $encoderFactories
     */
    public function __construct(EncoderFactoryInterface $frameworkFactory, array $encoderFactories = [])
    {
        $this->frameworkFactory = $frameworkFactory;
        $this->encoderFactories = $encoderFactories;
    }

    /**
     * @inheritDoc
     */
    public function getEncoder($user)
    {
        if ($encoder = $this->getEncoderFromFactory($user)) {
            return $encoder;
        }

        // fall back to default implementation
        return $this->frameworkFactory->getEncoder($user);
    }

    /**
     * Returns the password encoder factory to use for the given account.
     *
     * @param UserInterface|string $user A UserInterface instance or a class name
     *
     * @return PasswordEncoderInterface|null
     */
    private function getEncoderFromFactory($user)
    {
        $factoryKey = null;

        if ($user instanceof EncoderFactoryAwareInterface && (null !== $factoryName = $user->getEncoderFactoryName())) {
            if (!array_key_exists($factoryName, $this->encoderFactories)) {
                throw new \RuntimeException(sprintf('The encoder factory "%s" was not configured.', $factoryName));
            }

            $factoryKey = $factoryName;
        } else {
            foreach ($this->encoderFactories as $class => $factory) {
                if ((is_object($user) && $user instanceof $class) || (!is_object($user) && (is_subclass_of($user, $class) || $user == $class))) {
                    $factoryKey = $class;
                    break;
                }
            }
        }

        if (null !== $factoryKey) {
            $factory = $this->encoderFactories[$factoryKey];
            $encoder = $factory->getEncoder($user);

            if (!$encoder) {
                throw new \RuntimeException(sprintf('Failed to fetch encoder from factory "%s".', $factoryKey));
            }

            return $encoder;
        }
    }
}
