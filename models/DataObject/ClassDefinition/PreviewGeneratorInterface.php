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

namespace Pimcore\Model\DataObject\ClassDefinition;

use Pimcore\Model\DataObject\Concrete;

interface PreviewGeneratorInterface
{
    public const PARAMETER_SITE = 'site';

    public const PARAMETER_LOCALE = 'locale';

    public function generatePreviewUrl(Concrete $object, array $params): string;

    /**
     * @return array[
     *  [
     *  'name' => string,
     *  'label' => string,
     *  'values' => [
     *     string => string,
     *  ]
     *  ]
     * ]
     *
     */
    public function getPreviewConfig(Concrete $object): array;
}
