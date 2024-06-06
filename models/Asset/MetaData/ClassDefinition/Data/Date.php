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

namespace Pimcore\Model\Asset\MetaData\ClassDefinition\Data;

use Carbon\Carbon;
use Pimcore\Tool\UserTimezone;

class Date extends Data
{
    public function getDataFromEditMode(mixed $data, array $params = []): mixed
    {
        return $this->normalize($data, $params);
    }

    public function normalize(mixed $value, array $params = []): mixed
    {
        if ($value && !is_numeric($value)) {
            $value = strtotime($value);
        }

        return $value;
    }

    public function getVersionPreview(mixed $value, array $params = []): string
    {
        if (!$value) {
            return '';
        }

        $date = Carbon::createFromTimestamp((int) $value);

        return UserTimezone::applyTimezone($date)->format('Y-m-d');
    }
}
