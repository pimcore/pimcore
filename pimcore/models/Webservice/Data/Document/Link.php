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
 * @package    Webservice
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Webservice\Data\Document;

use Pimcore\Model;

class Link extends Model\Webservice\Data\Document
{

    /**
     * @var integer
     */
    public $internal;

    /**
     * @var string
     */
    public $internalType;

    /**
     * @var string
     */
    public $direct;

    /**
     * @var string
     */
    public $linktype;

    /**
     * @var string
     */
    public $target;

    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $type;

    /**
     * @var string
     */
    public $href;


    /**
     * @var string
     */
    public $parameters;

    /**
     * @var string
     */
    public $anchor;

    /**
     * @var string
     */
    public $title;

    /**
     * @var string
     */
    public $accesskey;

    /**
     * @var string
     */
    public $rel;

    /**
     * @var string
     */
    public $tabindex;
}
