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

namespace Pimcore\Routing\Dynamic;

use Symfony\Component\HttpFoundation\Request;

/**
 * Value object containing properties needed while matching document routes.
 */
class DynamicRequestContext
{
    /**
     * @var Request
     */
    private $request;

    /**
     * @var string
     */
    private $path;

    /**
     * @var string
     */
    private $originalPath;

    /**
     * @param Request $request
     * @param string $path
     * @param string $originalPath
     */
    public function __construct(Request $request, string $path, string $originalPath)
    {
        $this->request = $request;
        $this->path = $path;
        $this->originalPath = $originalPath;
    }

    /**
     * @return Request
     */
    public function getRequest(): Request
    {
        return $this->request;
    }

    /**
     * @param Request $request
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @param string $path
     */
    public function setPath(string $path)
    {
        $this->path = $path;
    }

    /**
     * @return string
     */
    public function getOriginalPath(): string
    {
        return $this->originalPath;
    }

    /**
     * @param string $originalPath
     */
    public function setOriginalPath(string $originalPath)
    {
        $this->originalPath = $originalPath;
    }
}
