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

namespace Pimcore\Model\DataObject\Traits;

use Pimcore\Model\DataObject\ClassDefinition;

/**
 * @internal
 */
trait ClassSavedTrait
{
    /**
     * @param mixed $containerDefinition
     * @param array $params
     */
    public function preSave($containerDefinition, $params = [])
    {
        // nothing to do
    }

    /**
     * @param mixed $containerDefinition
     * @param array $params
     */
    public function postSave($containerDefinition, $params = [])
    {
        if ($containerDefinition instanceof ClassDefinition) {
            $this->classSaved($containerDefinition);
        }
    }
}
