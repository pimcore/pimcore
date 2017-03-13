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

return [
    'Pimcore\Model\Document\*' => DI\object('Pimcore\Model\Document\*'),
    'Pimcore\Model\Asset\*' => DI\object('Pimcore\Model\Asset\*'),
    'Pimcore\Model\Object\*\Listing' => DI\object('Pimcore\Model\Object\*\Listing'),
    'Pimcore\Model\Object\Data\*' => DI\object('Pimcore\Model\Object\Data\*'),
    'Pimcore\Model\Object\*' => DI\object('Pimcore\Model\Object\*'),

    \Pimcore\Image\Adapter::class => DI\factory([\Pimcore\Image::class, 'create']),
];
