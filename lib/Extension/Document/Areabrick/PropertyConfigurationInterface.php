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


use Pimcore\Model\Document\Tag\Area\Info;

Interface PropertyConfigurationInterface extends PropertySupportInterface
{
    /**
     * @param Info $info
     * @param array $params contains the custom brick params defined in areablock option `params` or `globalParams`
     *
     * @return array
     */
    public function configureProperties(Info $info, array $params = []): array;
}

