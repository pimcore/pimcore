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
use Pimcore\Model\Document\Tag\Area\Info;

Interface EditableDialogBoxInterface
{
    /**
     * @param Info $info
     * @param array $context contains the context information about the brick (index, key, name, parent-name, ...)
     * @param array $options contains the original configuration of the areablock this brick is in
     *
     * @return array
     */
    public function getEditableDialogBoxConfiguration(Document $info, array $context, array $options): EditableDialogBoxConfiguration;
}
