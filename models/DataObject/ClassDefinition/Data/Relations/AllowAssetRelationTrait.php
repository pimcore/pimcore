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

namespace Pimcore\Model\DataObject\ClassDefinition\Data\Relations;

use Pimcore\Logger;
use Pimcore\Model\Asset;

/**
 * @internal
 */
trait AllowAssetRelationTrait
{
    /**
     * Checks if an asset is an allowed relation
     *
     *
     *
     * @internal
     */
    protected function allowAssetRelation(Asset $asset): bool
    {
        if ($asset->getId() <= 0) {
            return false;
        }

        $allowedAssetTypes = $this->getAssetTypes();
        $allowed = true;
        if (!$this->getAssetsAllowed()) {
            $allowed = false;
        } elseif (count($allowedAssetTypes) > 0) {
            //check for allowed asset types
            $allowedTypes = array_column($allowedAssetTypes, 'assetTypes');

            $allowed = in_array($asset->getType(), $allowedTypes, true);
        } else {
            //don't check if no allowed asset types set
        }

        Logger::debug('checked object relation to target asset [' . $asset->getId() . '] in field [' . $this->getName() . '], allowed:' . $allowed);

        return $allowed;
    }
}
