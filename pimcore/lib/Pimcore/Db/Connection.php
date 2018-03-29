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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Db;

use Doctrine\DBAL\Cache\QueryCacheProfile;
use Pimcore\Db;
use Pimcore\Db\ZendCompatibility\Expression;
use Pimcore\Db\ZendCompatibility\QueryBuilder;
use Pimcore\Db\ZendCompatibility\QueryBuilder as ZendDbCompatibleQueryBuilder;
use Pimcore\Model\Element\ValidationException;

class Connection extends \Doctrine\DBAL\Connection
{
    use PimcoreExtensionsTrait;
}
