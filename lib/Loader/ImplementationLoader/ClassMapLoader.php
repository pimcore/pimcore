<?php

declare(strict_types = 1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Loader\ImplementationLoader;

/**
 * Loads implementations from a fixed name => className map
 *
 * @internal
 */
class ClassMapLoader extends AbstractClassNameLoader
{
    protected array $classMap = [];

    public function __construct(array $classMap = [])
    {
        foreach ($classMap as $source => $target) {
            $this->classMap[$this->normalizeName($source)] = $this->normalizeName($target);
        }
    }

    public function supports(string $name): bool
    {
        return isset($this->classMap[$this->normalizeName($name)]);
    }

    public function getClassMap(): array
    {
        return $this->classMap;
    }

    protected function getClassName(string $name): string
    {
        return $this->classMap[$this->normalizeName($name)];
    }

    /**
     * Strip leading slashes from class names
     */
    private function normalizeName(string $name): string
    {
        return ltrim($name, '\\');
    }
}
