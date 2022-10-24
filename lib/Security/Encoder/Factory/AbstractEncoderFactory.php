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

namespace Pimcore\Security\Encoder\Factory;

use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;

/**
 * @internal
 *
 * @deprecated
 */
abstract class AbstractEncoderFactory implements EncoderFactoryInterface
{
    /**
     * Encoder class name to build
     *
     * @var string
     */
    protected string $className;

    /**
     * Arguments passed to encoder constructor
     *
     * @var array
     */
    protected mixed $arguments = [];

    protected ?\ReflectionClass $reflector = null;

    /**
     * @param string $className
     * @param mixed|null $arguments
     */
    public function __construct(string $className, mixed $arguments = null)
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

    protected function buildEncoder(\ReflectionClass $reflectionClass): PasswordEncoderInterface
    {
        /** @var PasswordEncoderInterface $encoder */
        $encoder = $reflectionClass->newInstanceArgs($this->arguments);

        return $encoder;
    }

    protected function getReflector(): \ReflectionClass
    {
        if (null === $this->reflector) {
            $this->reflector = new \ReflectionClass($this->className);
        }

        return $this->reflector;
    }
}
