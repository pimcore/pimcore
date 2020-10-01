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

namespace Pimcore\Templating\Helper;

use Symfony\Component\Templating\Helper\Helper;

/**
 * @deprecated
 */
class BreachAttackRandomContent extends Helper
{
    /**
     * @inheritDoc
     */
    public function getName()
    {
        return 'breachAttackRandomContent';
    }

    /**
     * @return string
     */
    public function __invoke()
    {
        $length = 50;
        $randomData = random_bytes($length);

        return '<!--'
            . substr(
                base64_encode($randomData),
                0,
                ord($randomData[$length - 1]) % 32
            )
            . '-->';
    }
}
