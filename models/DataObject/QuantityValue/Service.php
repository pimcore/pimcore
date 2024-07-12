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

namespace Pimcore\Model\DataObject\QuantityValue;

use Pimcore\Model\Translation;

class Service
{
    public function importDefinitionFromJson(string $json, bool $override = false): bool
    {
        try {
            $unitsArray = json_decode($json, true);
            $baseUnits = array_column($unitsArray, 'baseunit');
            $units = []; //array of units to be imported;
            foreach ($unitsArray as $unitArray) {
                if ($unit = Unit::getById($unitArray['id'])) {
                    if ($override) { // override the existing unit definition
                        $unit->delete();
                    } else { //skip the import if delete flag is not set
                        continue;
                    }
                }
                $unit = new Unit();
                $unit->setValues($unitArray, true);
                // we need to organize the units such that parent row are inserted before child row in db
                // to avoid the foreign key constraint error
                if (in_array($unitArray['id'], $baseUnits)) {
                    array_unshift($units, $unit);
                } else {
                    array_push($units, $unit);
                }
            }
            foreach ($units as $unit) {
                $unit->save();
            }
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    public function generateDefinitionJson(): string|false
    {
        $list = new Unit\Listing();
        $list->setOrderKey(['baseunit', 'factor', 'abbreviation']);
        $list->setOrder(['ASC', 'ASC', 'ASC']);

        $result = [];
        $units = $list->getUnits();
        foreach ($units as &$unit) {
            try {
                if ($unit->getAbbreviation()) {
                    $unit->setAbbreviation(Translation::getByKeyLocalized($unit->getAbbreviation(), Translation::DOMAIN_ADMIN,
                        true, true));
                }
                if ($unit->getLongname()) {
                    $unit->setLongname(Translation::getByKeyLocalized($unit->getLongname(), Translation::DOMAIN_ADMIN, true,
                        true));
                }
                $result[] = $unit->getObjectVars();
            } catch (\Exception $e) {
                return false;
            }
        }

        return json_encode($result, JSON_PRETTY_PRINT);
    }
}
