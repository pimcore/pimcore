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

namespace Pimcore\Bundle\XliffBundle\Escaper;

use DOMElement;
use Symfony\Component\DomCrawler\Crawler;

class Xliff12Escaper
{
    const SELFCLOSING_TAGS = ['area', 'base', 'br', 'col', 'command', 'embed', 'hr', 'img', 'input', 'keygen', 'link', 'meta', 'param', 'source', 'track', 'wbr'];

    public function escapeXliff(string $content): string
    {
        $count = 1;
        $openTags = [];
        $final = [];

        // remove nasty device control characters
        $content = preg_replace('/[\x00-\x09\x0B\x0C\x0E-\x1F\x7F]/', '', $content);

        if (!preg_match_all('/<([^>]+)>([^<]+)?/', $content, $matches)) {
            // return original content if it doesn't contain HTML tags
            return $this->encodeData($content);
        }

        // Handle text before the first HTML tag
        $firstTagPosition = strpos($content, '<');
        $preText = ($firstTagPosition > 0) ? $this->encodeData(substr($content, 0, $firstTagPosition)) : '';

        foreach ($matches[0] as $match) {
            $parts = explode('>', $match);
            $parts[0] .= '>';
            foreach ($parts as $part) {
                if (!empty(trim($part)) || trim($part) === '0') {
                    if (preg_match("/<([a-z0-9\/]+)/", $part, $tag)) {
                        $tagName = str_replace('/', '', $tag[1]);
                        if (in_array($tagName, self::SELFCLOSING_TAGS)) {
                            $part = '<ph id="' . $count . '">' . $this->encodeData($part) . '</ph>';

                            $count++;
                        } elseif (!str_contains($tag[1], '/')) {
                            $openTags[$count] = ['tag' => $tagName, 'id' => $count];
                            $part = '<bpt id="' . $count . '">' . $this->encodeData($part) . '</bpt>';

                            $count++;
                        } else {
                            $closingTag = array_pop($openTags);
                            $part = '<ept id="' . $closingTag['id'] . '">' . $this->encodeData($part) . '</ept>';
                        }
                    } else {
                        $part = $this->encodeData($part);
                    }

                    if (!empty($part)) {
                        $final[] = $part;
                    }
                }
            }
        }

        $content = $preText . implode('', $final);

        return $content;
    }

    public function unescapeXliff(string $content): string
    {
        $content = $this->parseInnerXml($content);

        if (preg_match("/<\/?(bpt|ept|ph)/", $content)) {
            $xml = new Crawler($content);
            $els = $xml->filter('bpt, ept, ph');
            /** @var DOMElement $el */
            foreach ($els as $el) {
                $content = html_entity_decode($el->textContent, ENT_COMPAT, 'UTF-8');
                $el->ownerDocument->textContent = $content;
            }
            $content = $xml->text();
        } else {
            $content = html_entity_decode(trim($content));
        }

        return $content;
    }

    private function parseInnerXml(string $content): string
    {
        $node = simplexml_load_string($content, null, LIBXML_NOCDATA);

        if (empty($node->children())) {
            return (string) $node;
        }

        $content = $node->asXML();

        $content = preg_replace("/<\?xml version=\"\d\.\d\"\?>\s?/i", '', $content);
        $content = preg_replace("/<\/?(target|mrk)([^>.]+)?>\s?/i", '', $content);
        // we have to do this again but with html entities because of CDATA content
        $content = preg_replace("/&lt;\/?(target|mrk)((?!&gt;).)*&gt;/i", '', $content);

        return $content;
    }

    private function encodeData(string $data): string
    {
        return htmlspecialchars($data, ENT_QUOTES | ENT_SUBSTITUTE | ENT_DISALLOWED, 'UTF-8', true);
    }
}
