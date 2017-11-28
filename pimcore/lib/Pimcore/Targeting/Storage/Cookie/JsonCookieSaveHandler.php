<?php

declare(strict_types=1);

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

namespace Pimcore\Targeting\Storage\Cookie;

class JsonCookieSaveHandler extends AbstractCookieSaveHandler
{
    /**
     * @inheritdoc
     */
    protected function parseData(string $scope, string $name, $data): array
    {
        if (null === $data) {
            return [];
        }

        $json = json_decode($data, true);
        if (is_array($json)) {
            return $json;
        }

        return [];
    }

    /**
     * @inheritdoc
     */
    protected function prepareData(string $scope, string $name, $expire, $data)
    {
        if (empty($data)) {
            return null;
        }

        return json_encode($data);
    }
}
