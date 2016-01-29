<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @category   Pimcore
 * @package    Property
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace Pimcore\Model\Tool\Newsletter\Config;

use Pimcore\Model;

class Listing extends Model\Listing\JsonListing
{

    /**
     * @var array
     */
    public $newsletter = array();

    /**
     * @return array
     */
    public function getNewsletter()
    {
        return $this->newsletter;
    }

    /**
     * @param $newsletter
     * @return $this
     */
    public function setNewsletter($newsletter)
    {
        $this->newsletter = $newsletter;
        return $this;
    }
}
