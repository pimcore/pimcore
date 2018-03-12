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
use Wa72\HtmlPageDom\HtmlPageCrawler;

class CodeInjector
{
    const SELECTOR_BODY = 'body';
    const SELECTOR_HEAD = 'head';

    const POSITION_BEGINNING = 'beginning';
    const POSITION_END = 'end';
    const REPLACE = 'replace';

    /**
     * @deprecated Use REPLACE instead
     */
    const POSITION_REPLACE = self::REPLACE;

    private static $presetSelectors = [
        self::SELECTOR_HEAD,
        self::SELECTOR_BODY,
    ];

    private static $validPositions = [
        self::POSITION_BEGINNING,
        self::POSITION_END,
        self::REPLACE,
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
        $result  = $this->injectIntoHtml($content, $code, $selector, $position, $response->getCharset());

        $response->setContent($result);
    }

    public function injectIntoHtml(string $html, string $code, string $selector, string $position, string $charset = 'UTF-8'): string
    {
        if (!in_array($position, self::$validPositions)) {
            throw new \InvalidArgumentException(sprintf(
                'Invalid position. Supported positions are: %s',
                implode(', ', self::$validPositions)
            ));
        }

        if (in_array($selector, self::$presetSelectors, true)) {
            return $this->injectIntoPresetSelector($html, $code, $selector, $position);
        } else {
            return $this->injectIntoDomSelector($html, $code, $selector, $position, $charset);
        }
    }

    private function injectIntoPresetSelector(string $html, string $code, string $selector, string $position): string
    {
        $startTagPattern = '<\s*?' . $selector . '\b[^>]*>';
        $endTagPattern   = '</' . $selector . '\b[^>]*>';

        // temporary placeholder to use in preg_replace as we can't be sure the code breaks our replacement
        $injectTpl = sprintf('----%s----', uniqid('INJECT:', true));

        if (self::REPLACE === $position) {
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

    private function injectIntoDomSelector(string $html, string $code, string $selector, string $position, string $charset): string
    {
        try {
            $dom = $this->createDomDocument($html, $charset);
        } catch (\Throwable $e) {
            return $html;
        }

        $crawler = new HtmlPageCrawler($dom);

        /** @var HtmlPageCrawler $element */
        $element = $crawler->filter($selector)->first();
        if (0 === $element->count()) {
            return $html;
        }

        if (self::REPLACE === $position) {
            $element->setInnerHtml($code);
        } elseif (self::POSITION_BEGINNING === $position) {
            $element->prepend($code);
        } elseif (self::POSITION_END === $position) {
            $element->append($code);
        }

        return trim($crawler->saveHTML());
    }

    /**
     * Extracted from Symfony\Component\DomCrawler\Crawler::addContent(). This is the same logic,
     * but it passes additional libxml options to loadHTML to avoid changing the HTML content.
     *
     * @param string $content
     * @param string $charset
     *
     * @return \DOMDocument
     */
    private function createDomDocument(string $content, string $charset): \DOMDocument
    {
        $internalErrors  = libxml_use_internal_errors(true);
        $disableEntities = libxml_disable_entity_loader(true);

        $dom = new \DOMDocument('1.0', $charset);
        $dom->validateOnParse = true;

        set_error_handler(function () {
            throw new \Exception();
        });

        // Convert charset to HTML-entities to work around bugs in DOMDocument::loadHTML()
        $content = mb_convert_encoding($content, 'HTML-ENTITIES', $charset);

        restore_error_handler();

        if ('' !== trim($content)) {
            @$dom->loadHTML($content, LIBXML_HTML_NODEFDTD | LIBXML_HTML_NOIMPLIED);
        }

        libxml_use_internal_errors($internalErrors);
        libxml_disable_entity_loader($disableEntities);

        return $dom;
    }
}
