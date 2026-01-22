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

namespace Pimcore\Routing\Dynamic;

use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 *
 * Value object containing properties needed while matching document routes.
 */
final class DynamicRequestContext
{
    private Request $request;

    private string $path;

    private string $originalPath;

    public function __construct(Request $request, string $path, string $originalPath)
    {
        $this->request = $request;
        $this->path = $path;
        $this->originalPath = $originalPath;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function setRequest(Request $request): void
    {
        $this->request = $request;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function setPath(string $path): void
    {
        $this->path = $path;
    }

    public function getOriginalPath(): string
    {
        return $this->originalPath;
    }

    public function setOriginalPath(string $originalPath): void
    {
        $this->originalPath = $originalPath;
    }
}
