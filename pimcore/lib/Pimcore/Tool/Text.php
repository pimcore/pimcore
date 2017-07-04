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

namespace Pimcore\Tool;

use Pimcore\Model\Asset;
use Pimcore\Model\Document;
use Pimcore\Model\Element;
use Pimcore\Model\Object\Concrete;

class Text
{
    /**
     * @param string $text
     *
     * @return mixed|string
     */
    public static function removeLineBreaks($text = '')
    {
        $text = str_replace(["\r\n", "\n", "\r", "\t"], ' ', $text);
        $text = preg_replace('#[ ]+#', ' ', $text);

        return $text;
    }

    /**
     * @param $text
     *
     * @return mixed
     */
    public static function wysiwygText($text, $params = [])
    {
        if (empty($text)) {
            return $text;
        }

        $matches = self::getElementsTagsInWysiwyg($text);

        if (count($matches[2]) > 0) {
            for ($i=0; $i < count($matches[2]); $i++) {
                preg_match('/[0-9]+/', $matches[2][$i], $idMatches);
                preg_match('/asset|object|document/', $matches[3][$i], $typeMatches);

                $id = $idMatches[0];
                $type = $typeMatches[0];
                $element = Element\Service::getElementById($type, $id);

                if ($element instanceof Element\ElementInterface) {
                    $path = '';
                    $oldTag = $matches[0][$i];

                    if ($matches[1][$i] == 'a') {
                        $linkAttr = 'href';
                        $path = $element->getFullPath();

                        if ($element instanceof Document) {
                            // get parameters
                            preg_match('/href="([^"]+)*"/', $oldTag, $oldHref);
                            if ($oldHref[1] && (strpos($oldHref[1], '?') !== false || strpos($oldHref[1], '#') !== false)) {
                                $urlParts = parse_url($oldHref[1]);
                                if (array_key_exists('query', $urlParts) && !empty($urlParts['query'])) {
                                    $path .= '?' . $urlParts['query'];
                                }
                                if (array_key_exists('fragment', $urlParts) && !empty($urlParts['fragment'])) {
                                    $path .= '#' . $urlParts['fragment'];
                                }
                            }
                        } elseif ($element instanceof Concrete) {
                            if ($linkGenerator = $element->getClass()->getLinkGenerator()) {
                                $path = $linkGenerator->generate(
                                    $element, $params
                                );
                            } else {
                                // no object path without link generator!
                                $path = '';
                            }
                        }
                    } elseif ($matches[1][$i] == 'img') {
                        $linkAttr = 'src';

                        // only for images
                        if (!$element instanceof Asset\Image) {
                            continue;
                        }

                        $path = $element->getFullPath();

                        // resize image to the given attributes
                        $config = null;

                        preg_match('/width="([^"]+)*"/', $oldTag, $widthAttr);
                        preg_match('/height="([^"]+)*"/', $oldTag, $heightAttr);
                        preg_match('/style="([^"]+)*"/', $oldTag, $styleAttr);

                        if ((isset($widthAttr[1]) && $widthAttr[1]) || (isset($heightAttr[1]) && $heightAttr[1])) {
                            $config = [
                                'width' => intval((isset($widthAttr[1]) ? $widthAttr[1] : null)),
                                'height' => intval((isset($heightAttr[1]) ? $heightAttr[1] : null))
                            ];
                        }

                        if (isset($styleAttr[1]) && $styleAttr[1] && preg_match('/(width|height)/', $styleAttr[1])) {
                            $config = []; // reset the config if it was set already before (attributes)

                            $cleanedStyle = preg_replace('#[ ]+#', '', $styleAttr[1]);
                            $styles = explode(';', $cleanedStyle);
                            foreach ($styles as $style) {
                                if (strpos(trim($style), 'width') === 0) {
                                    if (preg_match('/([0-9]+)(px)/i', $style, $match)) {
                                        $config['width'] = $match[1];
                                    }
                                } elseif (strpos(trim($style), 'height') === 0) {
                                    if (preg_match('/([0-9]+)(px)/i', $style, $match)) {
                                        $config['height'] = $match[1];
                                    }
                                }
                            }
                        }

                        // only create a thumbnail if it is not disabled
                        if (!preg_match('/pimcore_disable_thumbnail="([^"]+)*"/', $oldTag)) {
                            if (!empty($config)) {
                                $path = $element->getThumbnail($config);
                            } elseif ($element->getWidth() > 2000 || $element->getHeight() > 2000) {
                                // if the image is too large, size it down to 2000px this is the max. for wysiwyg
                                $path = $element->getThumbnail([
                                    'width' => 2000,
                                ]);
                            } else {
                                // return the original
                                $path = $element->getFullPath();
                            }
                        }
                    }

                    $pattern = '/'.$linkAttr.'="[^"]*"/';
                    $replacement = $linkAttr . '="' . $path . '"';
                    $newTag = preg_replace($pattern, $replacement, $oldTag);

                    $text = str_replace($oldTag, $newTag, $text);
                } else {
                    // remove the img tag if there is an internal broken link
                    if ($matches[1][$i] == 'img') {
                        $text = str_replace($matches[0][$i], '', $text);
                    }
                }
            }
        }

        return $text;
    }

    /**
     * @deprecated
     * @static
     *
     * @param  array $idMapping e.g. array("asset"=>array(OLD_ID=>NEW_ID),"object"=>array(OLD_ID=>NEW_ID),"document"=>array(OLD_ID=>NEW_ID));
     * @param  string $text html text of wysiwyg field
     *
     * @return mixed
     */
    public static function replaceWysiwygTextRelationIds($idMapping, $text)
    {
        if (!empty($text)) {
            include_once(PIMCORE_PATH . '/lib/simple_html_dom.php');

            $html = str_get_html($text);
            if (!$html) {
                return $text;
            }

            $s = $html->find('a[pimcore_id],img[pimcore_id]');

            foreach ($s as $el) {
                // image
                if ($el->src) {
                    $type = 'asset';
                }

                // link
                if ($el->href) {
                    if ($el->pimcore_type == 'asset') {
                        $type = 'asset';
                    } elseif ($el->pimcore_type == 'document') {
                        $type = 'document';
                    }
                }

                $newId = $idMapping[$type][$el->attr['pimcore_id']];
                if ($newId) {
                    //update id

                    if ($type == 'asset') {
                        $pimcoreElement = Asset::getById($newId);
                    } else {
                        $pimcoreElement = Document::getById($newId);
                    }

                    //TODO

                    $el->pimcore_id = $newId;
                    $el->src = $pimcoreElement->getFullPath();
                } else {
                    //remove relation, not found in mapping
                    $el->pimcore_id = null;
                    $el->src=null;
                }
            }

            $return = $html->save();

            $html->clear();
            unset($html);

            return $return;
        }
    }

    /**
     * @static
     *
     * @param string $text
     *
     * @return array
     */
    public static function getElementsTagsInWysiwyg($text)
    {
        if (!is_string($text) || strlen($text) < 1) {
            return [];
        }

        $hash = 'elements_raw_wysiwyg_text_' . md5($text);
        if (\Pimcore\Cache\Runtime::isRegistered($hash)) {
            return \Pimcore\Cache\Runtime::get($hash);
        }

        //$text = Pimcore_Tool_Text::removeLineBreaks($text);
        preg_match_all("@\<(a|img)[^>]*((?:pimcore_id|pimcore_type)+=\"[0-9]+\")[^>]*((?:pimcore_id|pimcore_type)+=\"[asset|document|object]+\")[^>]*\>@msUi", $text, $matches);

        \Pimcore\Cache\Runtime::set($hash, $matches);

        return $matches;
    }

    /**
     * @static
     *
     * @param $text
     *
     * @return array
     */
    public static function getElementsInWysiwyg($text)
    {
        $hash = 'elements_wysiwyg_text_' . md5($text);
        if (\Pimcore\Cache\Runtime::isRegistered($hash)) {
            return \Pimcore\Cache\Runtime::get($hash);
        }

        $elements = [];
        $matches = self::getElementsTagsInWysiwyg($text);

        if (count($matches[2]) > 0) {
            for ($i=0; $i < count($matches[2]); $i++) {
                preg_match('/[0-9]+/', $matches[2][$i], $idMatches);
                preg_match('/asset|object|document/', $matches[3][$i], $typeMatches);

                $id = $idMatches[0];
                $type = $typeMatches[0];

                $element = Element\Service::getElementById($type, $id);

                if ($id && $type && $element instanceof Element\ElementInterface) {
                    $elements[] = [
                        'id' => $id,
                        'type' => $type,
                        'element' => $element
                    ];
                }
            }
        }

        \Pimcore\Cache\Runtime::set($hash, $elements);

        return $elements;
    }

    /**
     * extracts all dependencies to other elements from wysiwyg text
     *
     * @static
     *
     * @param  string $text
     *
     * @return array
     */
    public static function getDependenciesOfWysiwygText($text)
    {
        $dependencies = [];

        if (!empty($text)) {
            $elements = self::getElementsInWysiwyg($text);
            foreach ($elements as $element) {
                $key = $element['type'] . '_' . $element['id'];
                $dependencies[$key] = [
                    'id' => $element['id'],
                    'type' => $element['type']
                ];
            }
        }

        return $dependencies;
    }

    /**
     * @param $text
     * @param array $tags
     *
     * @return array
     */
    public static function getCacheTagsOfWysiwygText($text, $tags = [])
    {
        $tags = is_array($tags) ? $tags : [];

        if (!empty($text)) {
            $elements = self::getElementsInWysiwyg($text);
            foreach ($elements as $element) {
                $el = $element['element'];
                if (!array_key_exists($el->getCacheTag(), $tags)) {
                    $tags = $el->getCacheTags($tags);
                }
            }
        }

        return $tags;
    }

    /**
     * @param $text
     *
     * @return string
     */
    public static function convertToUTF8($text)
    {
        $encoding = self::detectEncoding($text);
        if ($encoding) {
            $text = iconv($encoding, 'UTF-8', $text);
        }

        return $text;
    }

    /**
     * @param $text
     *
     * @return string
     */
    public static function detectEncoding($text)
    {
        if (function_exists('mb_detect_encoding')) {
            $encoding = mb_detect_encoding($text, [
                'UTF-32',
                'UTF-32BE',
                'UTF-32LE',
                'UTF-16',
                'UTF-16BE',
                'UTF-16LE',
                'UTF-8',
                'UTF-7',
                'UTF7-IMAP',
                'ASCII',
                'Windows-1252',
                'Windows-1254',
                'ISO-8859-1',
                'ISO-8859-2',
                'ISO-8859-3',
                'ISO-8859-4',
                'ISO-8859-5',
                'ISO-8859-6',
                'ISO-8859-7',
                'ISO-8859-8',
                'ISO-8859-9',
                'ISO-8859-10',
                'ISO-8859-13',
                'ISO-8859-14',
                'ISO-8859-15',
                'ISO-8859-16',
                'EUC-CN',
                'CP936',
                'HZ',
                'EUC-TW',
                'BIG-5',
                'EUC-KR',
                'UHC',
                'ISO-2022-KR',
                'Windows-1251',
                'CP866',
                'KOI8-R',
                'KOI8-U',
                'ArmSCII-8',
                'CP850',
                'EUC-JP',
                'SJIS',
                'eucJP-win',
                'SJIS-win',
                'CP51932',
                'JIS',
                'ISO-2022-JP',
                'ISO-2022-JP-MS'
            ]);
        }

        if (!$encoding) {
            $encoding = 'UTF-8';
        }

        return $encoding;
    }
}
