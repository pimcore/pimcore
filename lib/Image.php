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

namespace Pimcore;

use Exception;
use Pimcore;
use Pimcore\Image\AdapterInterface;

final class Image
{
    /**
     *
     * @throws Exception
     */
    public static function getInstance(): AdapterInterface
    {
        return Pimcore::getContainer()->get(AdapterInterface::class);
    }
}
