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

namespace Pimcore\Twig\Node;

use Twig\Attribute\YieldReady;
use Twig\Node\CaptureNode;
use Twig\Node\Node;

/**
 * @internal
 */
#[YieldReady]
class MyPlaceholderNode extends CaptureNode
{
    public function __construct(Node $body, int $lineno, ?string $tag = 'myplaceholder')
    {
        parent::__construct($body, $lineno, $tag);
        dd($this);
    }
}
