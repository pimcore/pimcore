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

use Pimcore\Http\Response\CodeInjector;
use Pimcore\Model\Tool\Targeting\Rule;
use Pimcore\Targeting\Model\VisitorInfo;
use Symfony\Component\HttpFoundation\Response;

class CodeSnippet implements ActionHandlerInterface, ResponseTransformingActionHandlerInterface
{
    /**
     * @var CodeInjector
     */
    private $codeInjector;

    public function __construct(CodeInjector $codeInjector)
    {
        $this->codeInjector = $codeInjector;
    }

    /**
     * @inheritDoc
     */
    public function apply(VisitorInfo $visitorInfo, array $action, Rule $rule = null)
    {
        $code = $action['code'] ?? '';
        $selector = $action['selector'] ?? '';
        $position = $action['position'] ?? '';

        if (empty($code) || empty($selector) || empty($position)) {
            return;
        }

        $visitorInfo->addAction([
            'type' => 'codesnippet',
            'scope' => VisitorInfo::ACTION_SCOPE_RESPONSE,
            'code' => $code,
            'selector' => $selector,
            'position' => $position,
        ]);
    }

    public function transformResponse(VisitorInfo $visitorInfo, Response $response, array $actions)
    {
        foreach ($actions as $action) {
            $this->codeInjector->inject(
                $response,
                $action['code'],
                $action['selector'],
                $action['position']
            );
        }
    }
}
