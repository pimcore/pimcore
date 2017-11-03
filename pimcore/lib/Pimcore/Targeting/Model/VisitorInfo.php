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

namespace Pimcore\Targeting\Model;

use Symfony\Component\HttpFoundation\Request;

class VisitorInfo implements \IteratorAggregate
{
    /**
     * @var Request
     */
    private $request;

    /**
     * @var array
     */
    private $data = [];

    public function __construct(Request $request, array $data = [])
    {
        $this->request = $request;
        $this->data    = $data;
    }

    public static function fromRequest(Request $request): self
    {
        return new static($request);
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function setData(array $data)
    {
        $this->data = $data;
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->data);
    }

    public function has($key): bool
    {
        return isset($this->data[$key]);
    }

    public function get($key, $default = null)
    {
        return $this->data[$key] ?? $default;
    }

    public function set($key, $value)
    {
        $this->data[$key] = $value;
    }
}
