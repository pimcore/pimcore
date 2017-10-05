<?php

declare(strict_types=1);

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

namespace Pimcore\Migrations\Configuration;

use Symfony\Component\OptionsResolver\OptionsResolver;

final class MigrationSetConfiguration
{
    /**
     * @var string
     */
    private $identifier;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $namespace;

    /**
     * @var string
     */
    private $directory;

    /**
     * @var OptionsResolver
     */
    private static $configResolver;

    public function __construct(string $identifier, string $name, string $namespace, string $directory)
    {
        $this->identifier = $identifier;
        $this->name       = $name;
        $this->namespace  = $namespace;
        $this->directory  = $directory;
    }

    public static function fromConfig(array $config): self
    {
        if (null === self::$configResolver) {
            self::$configResolver = new OptionsResolver();
            self::configureConfigResolver(self::$configResolver);
        }

        $resolvedConfig = self::$configResolver->resolve($config);

        return new self(
            $resolvedConfig['identifier'],
            $resolvedConfig['name'],
            $resolvedConfig['namespace'],
            $resolvedConfig['directory']
        );
    }

    private static function configureConfigResolver(OptionsResolver $resolver)
    {
        $keys = ['identifier', 'name', 'namespace', 'directory'];

        $resolver->setRequired($keys);
        foreach ($keys as $key) {
            $resolver->setAllowedTypes($key, 'string');
        }
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getNamespace(): string
    {
        return $this->namespace;
    }

    public function getDirectory(): string
    {
        return $this->directory;
    }
}
