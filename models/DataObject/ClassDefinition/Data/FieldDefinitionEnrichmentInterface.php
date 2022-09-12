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

namespace Pimcore\Model\DataObject\ClassDefinition\Data;

use Pimcore\Model\DataObject\ClassDefinition\Data;

interface FieldDefinitionEnrichmentInterface
{
    /**
     * If in admin mode this method can be implemented to change the fielddefinition whenever
     * getFieldDefinition() get called on the data type.
     * One example purpose is to populate or change dynamic settings like the options for select and multiselect fields.
     * The context param contains contextual information about the container, the field name, etc ...
     *
     * @param array $context
     *
     * @return $this
     */
    public function enrichFieldDefinition(/** array */ $context = []) /** : static */;
}
