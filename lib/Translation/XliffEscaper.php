<?php
/**
 * Created by PhpStorm.
 * User: mmoser
 * Date: 03/05/2018
 * Time: 18:15
 */

namespace Pimcore\Translation;


class XliffEscaper
{
    const SELFCLOSING_TAGS = ['area', 'base', 'br', 'col', 'command', 'embed', 'hr', 'img', 'input', 'keygen', 'link', 'meta', 'param', 'source', 'track', 'wbr'];

    /**
     * @param string $content
     *
     * @return string
     */
    public function escapeXliff($content)
    {
        $count = 1;
        $openTags = [];
        $final = [];

        // remove nasty device control characters
        $content = preg_replace('/[\x00-\x09\x0B\x0C\x0E-\x1F\x7F]/', '', $content);

        $replacement = ['%_%_%lt;%_%_%', '%_%_%gt;%_%_%'];
        $content = str_replace(['&lt;', '&gt;'], $replacement, $content);
        $content = html_entity_decode($content, null, 'UTF-8');

        if (!preg_match_all('/<([^>]+)>([^<]+)?/', $content, $matches)) {
            // return original content if it doesn't contain HTML tags
            return '<![CDATA[' . $content . ']]>';
        }

        // Handle text before the first HTML tag
        $firstTagPosition = strpos($content, '<');
        $preText = ($firstTagPosition > 0) ? '<![CDATA[' . substr($content, 0, $firstTagPosition) . ']]>' : '';

        foreach ($matches[0] as $match) {
            $parts = explode('>', $match);
            $parts[0] .= '>';
            foreach ($parts as $part) {
                $part = trim($part);
                if (!empty($part)) {
                    if (preg_match("/<([a-z0-9\/]+)/", $part, $tag)) {
                        $tagName = str_replace('/', '', $tag[1]);
                        if (in_array($tagName, self::SELFCLOSING_TAGS)) {
                            $part = '<ph id="' . $count . '"><![CDATA[' . $part . ']]></ph>';

                            $count++;
                        } elseif (strpos($tag[1], '/') === false) {
                            $openTags[$count] = ['tag' => $tagName, 'id' => $count];
                            $part = '<bpt id="' . $count . '"><![CDATA[' . $part . ']]></bpt>';

                            $count++;
                        } else {
                            $closingTag = array_pop($openTags);
                            $part = '<ept id="' . $closingTag['id'] . '"><![CDATA[' . $part . ']]></ept>';
                        }
                    } else {
                        $part = str_replace($replacement, ['<', '>'], $part);
                        $part = '<![CDATA[' . $part . ']]>';
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
}
