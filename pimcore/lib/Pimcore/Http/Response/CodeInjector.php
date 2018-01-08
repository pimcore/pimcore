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

namespace Pimcore\Http\Response;

use Pimcore\Http\ResponseHelper;
use Symfony\Component\HttpFoundation\Response;

class CodeInjector
{
    const SELECTOR_BODY = 'body';
    const SELECTOR_HEAD = 'head';

    const POSITION_BEGINNING = 'beginning';
    const POSITION_END = 'end';
    const POSITION_REPLACE = 'replace';

    private static $validSelectors = [
        self::SELECTOR_HEAD,
        self::SELECTOR_BODY,
    ];

    private static $validPositions = [
        self::POSITION_BEGINNING,
        self::POSITION_END,
        self::POSITION_REPLACE
    ];

    /**
     * @var ResponseHelper
     */
    private $responseHelper;

    public function __construct(ResponseHelper $responseHelper)
    {
        $this->responseHelper = $responseHelper;
    }

    public function inject(Response $response, string $code, string $selector = self::SELECTOR_BODY, string $position = self::POSITION_END)
    {
        if (empty($code)) {
            return;
        }

        if (!$this->responseHelper->isHtmlResponse($response)) {
            return;
        }

        $content = $response->getContent();
        $result  = $this->injectIntoHtml($content, $code, $selector, $position);

        $response->setContent($result);
    }

    public function injectIntoHtml(string $html, string $code, string $selector, string $position): string
    {
        if (!in_array($selector, self::$validSelectors)) {
            throw new \InvalidArgumentException(sprintf(
                'Invalid selector. Supported selectors are: %s',
                implode(', ', self::$validSelectors)
            ));
        }

        if (!in_array($position, self::$validPositions)) {
            throw new \InvalidArgumentException(sprintf(
                'Invalid position. Supported positions are: %s',
                implode(', ', self::$validPositions)
            ));
        }

        $startTagPattern = '<\s*?' . $selector . '\b[^>]*>';
        $endTagPattern   = '</' . $selector . '\b[^>]*>';

        // temporary placeholder to use in preg_replace as we can't be sure the code breaks our replacement
        $injectTpl = sprintf('----%s----', uniqid('INJECT:', true));

        if (self::POSITION_REPLACE === $position) {
            $html = preg_replace(
                '#(' . $startTagPattern . ')(.*?)(' . $endTagPattern . ')#s',
                '${1}' . $injectTpl . '${3}',
                $html
            );
        } elseif (self::POSITION_BEGINNING === $position) {
            $html = preg_replace(
                '#(' . $startTagPattern . ')#s',
                '${1}' . $injectTpl,
                $html
            );
        } elseif (self::POSITION_END === $position) {
            $html = preg_replace(
                '#(' . $endTagPattern . ')#s',
                $injectTpl . '${1}',
                $html
            );
        }

        // replace placeholder with actual code
        $html = str_replace($injectTpl, $code, $html);

        return $html;
    }
}
