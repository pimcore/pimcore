<?php

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
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

/**
 * ----------------------------------------------------------------------------------
 * based on @author ZF1 Zend_Navigation_Page_Uri
 * ----------------------------------------------------------------------------------
 */

/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @copyright  Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

namespace Pimcore\Navigation\Page;

use Pimcore\Navigation\Page;

class Url extends Page
{
    /**
     * Page URI
     *
     * @var string|null
     */
    protected ?string $_uri = null;

    /**
     * Sets page URI
     *
     * @param string|null $uri page URI, must a string or null
     *
     * @return Url fluent interface, returns self
     */
    public function setUri(?string $uri)
    {
        $this->_uri = $uri;

        return $this;
    }

    /**
     * Returns URI
     *
     * @return string|null
     */
    public function getUri(): ?string
    {
        return $this->_uri;
    }

    /**
     * {@inheritdoc}
     */
    public function getHref()
    {
        $uri = $this->getUri();

        $fragment = $this->getFragment();
        if (null !== $fragment) {
            if ('#' === substr($uri, -1)) {
                return $uri . $fragment;
            }

            return $uri . '#' . $fragment;
        }

        return $uri;
    }

    // Public methods:

    /**
     * {@inheritdoc}
     */
    public function toArray(): array
    {
        return array_merge(
            parent::toArray(),
            [
                'uri' => $this->getUri(),
            ]
        );
    }
}
