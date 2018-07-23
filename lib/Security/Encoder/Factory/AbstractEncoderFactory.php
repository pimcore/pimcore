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

namespace Pimcore\Security\Encoder\Factory;

use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;

abstract class AbstractEncoderFactory implements EncoderFactoryInterface
{
    /**
     * Encoder class name to build
     *
     * @var string
     */
    protected $className;

    /**
     * Arguments passed to encoder constructor
     *
     * @var array
     */
    protected $arguments = [];

    /**
     * @var \ReflectionClass
     */
    protected $reflector;

    /**
     * @param string $className
     * @param array|mixed $arguments
     */
    public function __construct($className, $arguments = null)
    {
        $this->className = $className;

        if ($arguments) {
            if (!is_array($arguments)) {
                $arguments = [$arguments];
            }
        } else {
            $arguments = [];
        }

        $this->arguments = $arguments;
    }

    /**
     * @param \ReflectionClass $reflectionClass
     *
     * @return PasswordEncoderInterface
     */
    protected function buildEncoder(\ReflectionClass $reflectionClass)
    {
        /** @var PasswordEncoderInterface $encoder */
        $encoder = $reflectionClass->newInstanceArgs($this->arguments);

        return $encoder;
    }

    /**
     * @return \ReflectionClass
     */
    protected function getReflector()
    {
        if (null === $this->reflector) {
            $this->reflector = new \ReflectionClass($this->className);
        }

        return $this->reflector;
    }
}
