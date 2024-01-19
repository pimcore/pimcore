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

namespace Pimcore\Maintenance\Tasks;

use Pimcore\Maintenance\TaskInterface;
use Pimcore\Maintenance\Tasks\DataObject\ConcreteTaskHelperInterface;

/**
 * @internal
 */
class CleanupFieldcollectionTablesTask implements TaskInterface
{
    public function __construct(private ConcreteTaskHelperInterface $helper)
    {
    }

    public function execute(): void
    {
        $this->helper->cleanupCollectionTable();
    }
}
