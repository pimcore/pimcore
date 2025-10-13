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

        $date = Carbon::createFromTimestamp((int) $value, date_default_timezone_get());

        return UserTimezone::applyTimezone($date)->format('Y-m-d');
    }
}
