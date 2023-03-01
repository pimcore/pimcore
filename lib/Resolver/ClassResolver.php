<?php
declare(strict_types = 1);

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


namespace Pimcore\Resolver;

/**
 * Core class resolver returning FQCN
 *
 * @internal
 */
class ClassResolver implements ResolverInterface
{
    public const TYPE_DOCUMENTS = 'documents';
    public const TYPE_ASSETS = 'assets';

    private array $map;

    public function __construct(array $classes = [])
    {
        $this->map = $classes;
    }

    public function resolve(string $class, string $type): ?string
    {
        return $this->map[$type][$class] ?? null;
    }
}
