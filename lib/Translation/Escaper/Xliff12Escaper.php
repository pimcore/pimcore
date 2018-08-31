<?php
/**
 * Created by PhpStorm.
 * User: mmoser
 * Date: 03/05/2018
 * Time: 18:15
 */

namespace Pimcore\Translation\Escaper;


class Xliff12Escaper
{
    const SELFCLOSING_TAGS = ['area', 'base', 'br', 'col', 'command', 'embed', 'hr', 'img', 'input', 'keygen', 'link', 'meta', 'param', 'source', 'track', 'wbr'];

    /**
     * @param string $content
     *
     * @return string
     */
    public function escapeXliff(string $content): string
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
            return $this->toCData($content);
        }

        // Handle text before the first HTML tag
        $firstTagPosition = strpos($content, '<');
        $preText = ($firstTagPosition > 0) ? $this->toCData(substr($content, 0, $firstTagPosition)) : '';

        foreach ($matches[0] as $match) {
            $parts = explode('>', $match);
            $parts[0] .= '>';
            foreach ($parts as $part) {
                $part = trim($part);
                if (!empty($part)) {
                    if (preg_match("/<([a-z0-9\/]+)/", $part, $tag)) {
                        $tagName = str_replace('/', '', $tag[1]);
                        if (in_array($tagName, self::SELFCLOSING_TAGS)) {
                            $part = '<ph id="' . $count . '">' . $this->toCData($part) . '</ph>';

                            $count++;
                        } elseif (strpos($tag[1], '/') === false) {
                            $openTags[$count] = ['tag' => $tagName, 'id' => $count];
                            $part = '<bpt id="' . $count . '">' . $this->toCData($part) . '</bpt>';

                            $count++;
                        } else {
                            $closingTag = array_pop($openTags);
                            $part = '<ept id="' . $closingTag['id'] . '">' . $this->toCData($part) . '</ept>';
                        }
                    } else {
                        $part = str_replace($replacement, ['<', '>'], $part);
                        $part = $this->toCData($part);
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

    /**
     * @param string $content
     *
     * @return string
     */
    public function unescapeXliff(string $content): string
    {
        $content = preg_replace("/<\/?(target|mrk)([^>.]+)?>/i", '', $content);
        // we have to do this again but with html entities because of CDATA content
        $content = preg_replace("/&lt;\/?(target|mrk)((?!&gt;).)*&gt;/i", '', $content);

        if (preg_match("/<\/?(bpt|ept)/", $content)) {
            include_once(PIMCORE_PATH . '/lib/simple_html_dom.php');
            $xml = str_get_html($content);
            if ($xml) {
                $els = $xml->find('bpt,ept,ph');
                foreach ($els as $el) {
                    $content = html_entity_decode($el->innertext, null, 'UTF-8');
                    $el->outertext = $content;
                }
            }
            $content = $xml->save();
        }

        return $content;
    }

    private function toCData(string $data): string
    {
        return sprintf('<![CDATA[%s]]>', $data);
    }
}
