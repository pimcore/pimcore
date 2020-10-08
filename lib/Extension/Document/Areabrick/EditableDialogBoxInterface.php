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

namespace Pimcore\Extension\Document\Areabrick;

use Pimcore\Model\Document;
use Pimcore\Model\Document\Editable\Area\Info;

interface EditableDialogBoxInterface
{
    /**
     * @param Document\Editable $area
     * @param Info|null $info
     *
     * @return EditableDialogBoxConfiguration
     */
    public function getEditableDialogBoxConfiguration(Document\Editable $area, ?Info $info): EditableDialogBoxConfiguration;
}
