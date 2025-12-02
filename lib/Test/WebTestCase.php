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

namespace Pimcore\Test;

use Pimcore;
use Symfony\Component\HttpKernel\KernelInterface;

abstract class WebTestCase extends \Symfony\Bundle\FrameworkBundle\Test\WebTestCase
{
    protected static function createKernel(array $options = []): KernelInterface
    {
        $kernel = parent::createKernel($options);

        Pimcore::setKernel($kernel);

        return $kernel;
    }
}
