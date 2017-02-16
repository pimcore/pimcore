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
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\API\Plugin;

use Pimcore\API\AbstractAPI;
use Pimcore\Db;

abstract class AbstractPluginUpdater
{
    /**
     * runs all Revision Updates
     */
    const RUN_ALL_UPDATES = 'All';

    /**
     * @var int Revision number
     */
    protected $revision;

    /**
     * @var \Pimcore\Db
     */
    protected $db;

    /**s 
     * @param $revision
     */
    public function __construct($revision)
    {
        $this->revision = $revision;
        $this->db = \Pimcore\Db::getConnection();
    }

    /**
     * define updates which shoud be performed evertytime (e.g. Translation updates...)
     */
    public function runDefaultUpdates()
    {
    }

    /**
     * Runs all revision updates
     */
    public function updateRevisionAll()
    {
        foreach (get_class_methods(get_class($this)) as $method) {
            if (stripos($method, 'updateRevision') !== false && $method != __FUNCTION__) {
                $this->$method();
            }
        }
    }

    /**
     * performs the updates
     */
    public function run()
    {
        $this->runDefaultUpdates();
        $method = 'updateRevision'.$this->revision;
        if (method_exists($this, $method)) {
            $this->$method();
        }
    }
}
