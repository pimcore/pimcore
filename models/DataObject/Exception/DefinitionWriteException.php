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

namespace Pimcore\Model\DataObject\Exception;

use Exception;

class DefinitionWriteException extends Exception
{
    public function __construct()
    {
        parent::__construct(sprintf('Definitions in %s folder cannot be overwritten', PIMCORE_CUSTOM_CONFIGURATION_DIRECTORY));
    }
}
