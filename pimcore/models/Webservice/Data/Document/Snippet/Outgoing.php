<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @category   Pimcore
 * @package    Webservice
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace Pimcore\Model\Webservice\Data\Document\Snippet;

use Pimcore\Model;

class Outgoing extends Model\Webservice\Data\Document\Snippet {


    /**
     * @var string
     */
    public $path;

    /**
     * @var integer
     */
    public $creationDate;

    /**
     * @var integer
     */
    public $modificationDate;

    /**
     * @var integer
     */
    public $userModification;

    /**
     * @var Model\Webservice\Data\Document\Listing\Item[]
     */
    public $childs;


}
