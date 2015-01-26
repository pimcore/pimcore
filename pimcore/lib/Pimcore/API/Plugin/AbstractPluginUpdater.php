<?php
/**
 * Pimcore
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.pimcore.org/license
 *
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\API\Plugin;

use Pimcore\API\AbstractAPI;
use Pimcore\Resource;

abstract class AbstractPluginUpdater {

    /**
     * runs all Revision Updates
     */
    const RUN_ALL_UPDATES = 'All';

    /**
     * @var int Revision number
     */
    protected $revision;

    /**
     * @var \Pimcore\Resource
     */
    protected $db;

    public function __construct($revision){
        $this->revision = $revision;
        $this->db = \Pimcore\Resource::getConnection();
    }

    /**
     * define updates which shoud be performed evertytime (e.g. Translation updates...)
     */
    public function runDefaultUpdates(){}

    /**
     * Runs all revision updates
     */
    public function updateRevisionAll(){
        foreach(get_class_methods(get_class($this)) as $method){
            if(stripos($method,'updateRevision') !== false && $method != __FUNCTION__){
                $this->$method();
            }
        }
    }

    /**
     * performs the updates
     */
    public function run(){
        $this->runDefaultUpdates();
        $method = 'updateRevision'.$this->revision;
        if(method_exists($this,$method)){
            $this->$method();
        }
    }
}