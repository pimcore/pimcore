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

namespace Pimcore\Security\Hasher\Factory;

use ReflectionClass;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;

/**
 * @internal
 */
abstract class AbstractHasherFactory implements PasswordHasherFactoryInterface
{
    /**
     * Hasher class name to build
     *
     */
    protected string $className;

    /**
     * Arguments passed to hasher constructor
     *
     * @var array
     */
    protected mixed $arguments = [];

    protected ?ReflectionClass $reflector = null;

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

    protected function buildPasswordHasher(ReflectionClass $reflectionClass): PasswordHasherInterface
    {
        /** @var PasswordHasherInterface $hasher */
        $hasher = $reflectionClass->newInstanceArgs($this->arguments);

        return $hasher;
    }

    protected function getReflector(): ReflectionClass
    {
        if (null === $this->reflector) {
            $this->reflector = new ReflectionClass($this->className);
        }

        return $this->reflector;
    }
}
