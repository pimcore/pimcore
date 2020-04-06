<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @category   Pimcore
 * @package    Object
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\DataObject\Data;

use Pimcore\Model\DataObject\OwnerAwareFieldInterface;
use Pimcore\Model\DataObject\Traits\OwnerAwareFieldTrait;

class ExternalImage implements OwnerAwareFieldInterface
{
    use OwnerAwareFieldTrait;

    /** @var string */
    protected $url;

    /**
     * ExternalImage constructor.
     *
     * @param string|null $url
     */
    public function __construct($url = null)
    {
        $this->url = $url;
        $this->markMeDirty();
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param string $url
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
