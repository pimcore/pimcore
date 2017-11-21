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

namespace Pimcore\Targeting\ActionHandler;

use Pimcore\Model\Tool\Targeting\Rule;
use Pimcore\Targeting\Model\VisitorInfo;

class CodeSnippet implements ActionHandlerInterface
{
    /**
     * @inheritDoc
     */
    public function apply(VisitorInfo $visitorInfo, array $action, Rule $rule = null)
    {
        $code     = $action['code'] ?? '';
        $selector = $action['selector'] ?? '';
        $position = $action['position'] ?? '';

        if (empty($code) || empty($selector) || empty($position)) {
            return;
        }

        $visitorInfo->addAction([
            'type'     => 'code_snippet',
            'scope'    => 'frontend',
            'code'     => $code,
            'selector' => $selector,
            'position' => $position
        ]);
    }
}
