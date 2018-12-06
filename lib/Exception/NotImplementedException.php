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

namespace Pimcore\Exception;

class NotImplementedException extends \Exception
{
    /**
     * Generates: Intentionally not implemented: {$feature}.
     *
     * @param string $feature
     * @param int $code
     * @param \Exception|null $previous
     */
    public function __construct($feature, $code = 0, \Exception $previous = null)
    {
        parent::__construct("Intentionally not implemented: {$feature}", $code, $previous);
    }
}
