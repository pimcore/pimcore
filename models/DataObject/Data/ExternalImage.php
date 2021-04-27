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

namespace Pimcore\Model\DataObject\Data;

use Pimcore\Model\DataObject\OwnerAwareFieldInterface;
use Pimcore\Model\DataObject\Traits\OwnerAwareFieldTrait;

class ExternalImage implements OwnerAwareFieldInterface
{
    use OwnerAwareFieldTrait;

    /** @var string|null */
    protected $url;

    /**
     * @param string|null $url
     */
    public function __construct($url = null)
    {
        $this->url = $url;
        $this->markMeDirty();
    }

    /**
     * @return string|null
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param string|null $url
     */
    public function setUrl($url)
    {
        $this->url = $url;
        $this->markMeDirty();
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (is_null($this->url)) ? '' : $this->url;
    }
}
