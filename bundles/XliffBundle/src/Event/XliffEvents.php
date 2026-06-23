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

namespace Pimcore\Bundle\XliffBundle\Event;

final class XliffEvents
{
    /**
     * @Event("Pimcore\Bundle\XliffBundle\Event\Model\TranslationXliffEvent")
     *
     * @var string
     */
    const XLIFF_ATTRIBUTE_SET_EXPORT = 'pimcore.translation.xliff.attribute_set_export';

    /**
     * @Event("Pimcore\Bundle\XliffBundle\Event\Model\TranslationXliffEvent")
     *
     * @var string
     */
    const XLIFF_ATTRIBUTE_SET_IMPORT = 'pimcore.translation.xliff.attribute_set_import';
}
