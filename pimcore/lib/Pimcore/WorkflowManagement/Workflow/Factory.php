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

namespace Pimcore\WorkflowManagement\Workflow;

use Pimcore\Model\Workflow;

class Factory
{

    /**
     *
     * @param $config
     * @return Workflow
     * @throws \Exception
     */
    public static function getWorkflowFromConfig($config)
    {
        if (!is_array($config)) {
            throw new \Exception('Workflow json configuration could not be created, invalid configuration array given');
        }

        return Workflow::getById($config['id']);
    }
}
