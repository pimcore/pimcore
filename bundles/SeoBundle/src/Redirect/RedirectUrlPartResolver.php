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

namespace Pimcore\Bundle\SeoBundle\Redirect;

use InvalidArgumentException;
use Pimcore\Bundle\SeoBundle\Model\Redirect;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
class RedirectUrlPartResolver
{
    private Request $request;

    private array $parts = [];

    /**
     * RedirectUrlPartResolver constructor.
     *
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function getRequestUriPart(string $type): string
    {
        if (isset($this->parts[$type])) {
            return $this->parts[$type];
        }

        $part = null;
        switch ($type) {
            case Redirect::TYPE_ENTIRE_URI:
                $part = $this->request->getUri();

                break;

            case Redirect::TYPE_PATH_QUERY:
                $part = $this->request->getRequestUri();

                break;

            case Redirect::TYPE_AUTO_CREATE:
            case Redirect::TYPE_PATH:
                $part = $this->request->getPathInfo();

                break;
        }

        if (null === $part) {
            throw new InvalidArgumentException(sprintf('Unsupported request URI part type "%s"', $type));
        }

        $this->parts[$type] = urldecode($part);

        return $this->parts[$type];
    }
}
