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

namespace Pimcore\Tests\Support\Helper;

use Pimcore\Tests\Support\Util\Autoloader;

class CoreModel extends Model
{
    public function _beforeSuite(array $settings = []): void
    {
        parent::_beforeSuite($settings);
    }
}
