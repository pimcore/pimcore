<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Model\Tool\Email\Log;

use Pimcore\Model;

/**
 * @internal
 *
 * @method \Pimcore\Model\Tool\Email\Log\Listing\Dao getDao()
 * @method Model\Tool\Email\Log[] load()
 * @method Model\Tool\Email\Log|false current()
 * @method int getTotalCount()
 */
class Listing extends Model\Listing\AbstractListing
{
    /**
     * @return Model\Tool\Email\Log[]
     */
    public function getEmailLogs(): array
    {
        return $this->getData();
    }

    /**
     * Sets EmailLog entries
     *
     *
     * @return $this
     */
    public function setEmailLogs(array $emailLogs): static
    {
        return $this->setData($emailLogs);
    }
}
