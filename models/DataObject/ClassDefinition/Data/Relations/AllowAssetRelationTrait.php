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

namespace Pimcore\Model\DataObject\ClassDefinition\Data\Relations;

use Pimcore\Logger;
use Pimcore\Model\Asset;

trait AllowAssetRelationTrait
{
    /**
     * Checks if an asset is an allowed relation
     *
     * @param Asset $asset
     *
     * @return bool
     */
    protected function allowAssetRelation($asset)
    {
        $allowedAssetTypes = $this->getAssetTypes();
        $allowedTypes = [];
        $allowed = true;
        if (!$this->getAssetsAllowed()) {
            $allowed = false;
        } elseif ($this->getAssetsAllowed() and is_array($allowedAssetTypes) and count($allowedAssetTypes) > 0) {
            //check for allowed asset types
            foreach ($allowedAssetTypes as $t) {
                if (is_array($t) && array_key_exists('assetTypes', $t)) {
                    $t = $t['assetTypes'];
                }

                if ($t) {
                    if (is_string($t)) {
                        $allowedTypes[] = $t;
                    } elseif (is_array($t) && count($t) > 0) {
                        if (isset($t['assetTypes'])) {
                            $allowedTypes[] = $t['assetTypes'];
                        } else {
                            $allowedTypes[] = $t;
                        }
                    }
                }
            }
            if (!in_array($asset->getType(), $allowedTypes)) {
                $allowed = false;
            }
        } else {
            //don't check if no allowed asset types set
        }

        Logger::debug('checked object relation to target asset [' . $asset->getId() . '] in field [' . $this->getName() . '], allowed:' . $allowed);

        return $allowed;
    }
}
