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
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Model\Tool\Email\Log;

use Pimcore\Model;

/**
 * @method \Pimcore\Model\Tool\Email\Log\Listing\Dao getDao()
 * @method Model\Tool\Email\Log[] load()
 * @method Model\Tool\Email\Log current()
 * @method int getTotalCount()
 */
class Listing extends Model\Listing\AbstractListing
{
    /**
     * @var array
     *
     * @deprecated use getter/setter methods or $this->data
     */
    protected $emailLogs = null;

    public function __construct()
    {
        $this->emailLogs = & $this->data;
    }

    /**
     * @return Model\Tool\Email\Log[]
     */
    public function getEmailLogs()
    {
        return $this->getData();
    }

    /**
     * Sets EmailLog entries
     *
     * @param array $emailLogs
     *
     * @return static
     */
    public function setEmailLogs($emailLogs)
    {
        return $this->setData($emailLogs);
    }
}
