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

namespace Pimcore\Tool;

use Onnov\DetectEncoding\EncodingDetector;
use Pimcore\Cache\RuntimeCache;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Document;
use Pimcore\Model\Element;
use Pimcore\Model\Site;
use Pimcore\Tool;

class Text
{
    public const PIMCORE_WYSIWYG_SANITIZER_ID = 'html_sanitizer.sanitizer.pimcore.wysiwyg_sanitizer';

    public const PIMCORE_TRANSLATION_SANITIZER_ID = 'html_sanitizer.sanitizer.pimcore.translation_sanitizer';

    public static function removeLineBreaks(string $text = ''): string
    {
        $text = str_replace(["\r\n", "\n", "\r", "\t"], ' ', $text);
        $text = preg_replace('#[ ]+#', ' ', $text);

        return $text;
    }

    public static function wysiwygText(?string $text, array $params = []): ?string
    {
        if (empty($text)) {
            return $text;
        }

        $matches = self::getElementsTagsInWysiwyg($text);

        if (count($matches[2]) > 0) {
            for ($i = 0; $i < count($matches[2]); $i++) {
                preg_match('/[0-9]+/', $matches[2][$i], $idMatches);
                preg_match('/asset|object|document/', $matches[3][$i], $typeMatches);

                $linkAttr = null;
                $path = null;
                $additionalAttributes = [];
                $id = (int) $idMatches[0];
                $type = $typeMatches[0];
                $element = Element\Service::getElementById($type, $id);
                $oldTag = $matches[0][$i];

                if ($element instanceof Element\ElementInterface) {
                    if ($matches[1][$i] == 'a') {
                        $linkAttr = 'href';
                        $path = $element->getFullPath();

                        if (($element instanceof Document || $element instanceof Concrete) && !$element->isPublished()) {
                            $path = null;
                        } elseif ($element instanceof Document) {
                            // get parameters
                            preg_match('/href="([^"]+)*"/', $oldTag, $oldHref);
                            if (isset($oldHref[1]) && (str_contains($oldHref[1], '?') || str_contains($oldHref[1], '#'))) {
                                $urlParts = parse_url($oldHref[1]);
                                if (array_key_exists('query', $urlParts) && !empty($urlParts['query'])) {
                                    $path .= '?' . $urlParts['query'];
                                }
                                if (array_key_exists('fragment', $urlParts) && !empty($urlParts['fragment'])) {
                                    $path .= '#' . $urlParts['fragment'];
                                }
                            }

                            $site = Frontend::getSiteForDocument($element);
                            if ($site instanceof Site) {
                                if (preg_match('~^' . preg_quote($site->getRootPath(), '~') . '~', $path)) {
                                    $path = Tool::getRequestScheme() . '://' . $site->getMainDomain() . preg_replace('~^' . preg_quote($site->getRootPath(), '~') . '~', '', $path);
                                }
                            }

                        } elseif ($element instanceof Concrete) {
                            if ($linkGenerator = $element->getClass()->getLinkGenerator()) {
                                $path = $linkGenerator->generate(
                                    $element,
                                    $params
                                );
                            } else {
                                // no object path without link generator!
                                $path = null;
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
                                'width' => (int)(isset($widthAttr[1]) ? $widthAttr[1] : null),
                                'height' => (int)(isset($heightAttr[1]) ? $heightAttr[1] : null),
                            ];
                        }

                        if (isset($styleAttr[1]) && $styleAttr[1] && preg_match('/(width|height)/', $styleAttr[1])) {
                            $config = []; // reset the config if it was set already before (attributes)

                            $cleanedStyle = preg_replace('#[ ]+#', '', $styleAttr[1]);
                            $styles = explode(';', $cleanedStyle);
                            foreach ($styles as $style) {
                                if (str_starts_with(trim($style), 'width')) {
                                    if (preg_match('/([0-9]+)(px)/i', $style, $match)) {
                                        $config['width'] = $match[1];
                                    }
                                } elseif (str_starts_with(trim($style), 'height')) {
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

                                $imgTagWithCustomMetadata = $path->getImageTag();
                                preg_match('/alt="([^"]*)"/', $imgTagWithCustomMetadata, $altMatches);
                                preg_match('/title="([^"]*)"/', $imgTagWithCustomMetadata, $titleMatches);
                                $alt = $altMatches[1] ?? '';
                                $title = $titleMatches[1] ?? '';

                                $pathHdpi = $element->getThumbnail(array_merge($config, ['highResolution' => 2]));
                                $additionalAttributes = [
                                    'srcset' => $path . ' 1x, ' . $pathHdpi . ' 2x',
                                    'alt' => $alt,
                                    'title' => $title,
                                ];
                            } elseif ($element->getWidth() > 2000 || $element->getHeight() > 2000) {
                                // if the image is too large, size it down to 2000px this is the max. for wysiwyg
                                // for those big images we don't generate a hdpi version
                                $path = $element->getThumbnail([
                                    'width' => 2000,
                                ]);
                            } else {
                                // return the original
                                $path = $element->getFullPath();
                            }
                        }
                    }

                    if ($path) {
                        $pattern = '/' . $linkAttr . '="[^"]*"/';
                        $replacement = $linkAttr . '="' . $path . '"';
                        if (!empty($additionalAttributes)) {
                            $replacement .= ' ' . array_to_html_attribute_string($additionalAttributes);
                        }
                        $newTag = preg_replace($pattern, $replacement, $oldTag);

                        $text = str_replace($oldTag, $newTag, $text);
                    }
                }

                if (!$path) {
                    // in case there's a broken internal reference/link
                    if ($matches[1][$i] == 'img') {
                        // remove the entire tag for images
                        $text = str_replace($oldTag, '', $text);
                    } elseif ($matches[1][$i] == 'a') {
                        // just display the text for links
                        $text = preg_replace('@' . preg_quote($oldTag, '@') . '([^\<]+)\</a\>@i', '$1', $text);
                    }
                }
            }
        }

        return $text;
    }

    private static function getElementsTagsInWysiwyg(string $text): array
    {
        if (strlen($text) < 1) {
            return [];
        }

        $hash = 'elements_raw_wysiwyg_text_' . md5($text);
        if (RuntimeCache::isRegistered($hash)) {
            return RuntimeCache::get($hash);
        }

        //$text = Pimcore_Tool_Text::removeLineBreaks($text);
        preg_match_all("@\<(a|img)[^>]*(pimcore_id=\"[0-9]+\")[^>]*(pimcore_type=\"[asset|document|object]+\")[^>]*\>@msUi", $text, $matches);

        RuntimeCache::set($hash, $matches);

        return $matches;
    }

    private static function getElementsInWysiwyg(string $text): array
    {
        $hash = 'elements_wysiwyg_text_' . md5($text);
        if (RuntimeCache::isRegistered($hash)) {
            return RuntimeCache::get($hash);
        }

        $elements = [];
        $matches = self::getElementsTagsInWysiwyg($text);

        if (count($matches[2]) > 0) {
            for ($i = 0; $i < count($matches[2]); $i++) {
                preg_match('/[0-9]+/', $matches[2][$i], $idMatches);
                preg_match('/asset|object|document/', $matches[3][$i], $typeMatches);

                if (isset($idMatches[0], $typeMatches[0])) {
                    $elements[] = [
                        'id' => (int) $idMatches[0],
                        'type' => $typeMatches[0],
                    ];
                }
            }
        }

        RuntimeCache::set($hash, $elements);

        return $elements;
    }

    /**
     * extracts all dependencies to other elements from wysiwyg text
     *
     *
     */
    public static function getDependenciesOfWysiwygText(?string $text): array
    {
        $dependencies = [];

        if (!empty($text)) {
            $elements = self::getElementsInWysiwyg($text);
            foreach ($elements as $element) {
                $key = $element['type'] . '_' . $element['id'];
                $dependencies[$key] = [
                    'id' => $element['id'],
                    'type' => $element['type'],
                ];
            }
        }

        return $dependencies;
    }

    public static function getCacheTagsOfWysiwygText(?string $text, array $tags = []): array
    {
        if (!empty($text)) {
            $elements = self::getElementsInWysiwyg($text);
            foreach ($elements as $element) {
                $tag = Element\Service::getElementCacheTag($element['type'], $element['id']);
                $tags[$tag] = $tag;
            }
        }

        return $tags;
    }

    public static function convertToUTF8(string $text): string
    {
        $encoding = self::detectEncoding($text);
        if ($encoding) {
            $text = iconv($encoding, 'UTF-8', $text);
        }

        return $text;
    }

    public static function detectEncoding(string $text): string
    {
        // Detect UTF-8, UTF-16 and UTF-32 by BOM
        $utf32_big_endian_bom = chr(0x00) . chr(0x00) . chr(0xFE) . chr(0xFF);
        $utf32_little_endian_bom = chr(0xFF) . chr(0xFE) . chr(0x00) . chr(0x00);
        $utf16_big_endian_bom = chr(0xFE) . chr(0xFF);
        $utf16_little_endian_bom = chr(0xFF) . chr(0xFE);
        $utf8_bom = chr(0xEF) . chr(0xBB) . chr(0xBF);

        $first2bytes = substr($text, 0, 2);
        $first3bytes = substr($text, 0, 3);
        $first4bytes = substr($text, 0, 3);

        if ($first3bytes === $utf8_bom) {
            return 'UTF-8';
        } elseif ($first4bytes === $utf32_big_endian_bom) {
            return 'UTF-32BE';
        } elseif ($first4bytes === $utf32_little_endian_bom) {
            return 'UTF-32LE';
        } elseif ($first2bytes === $utf16_big_endian_bom) {
            return 'UTF-16BE';
        } elseif ($first2bytes === $utf16_little_endian_bom) {
            return 'UTF-16LE';
        }

        $detector = new EncodingDetector();
        $encoding = $detector->getEncoding($text);

        if (empty($encoding)) {
            $encoding = 'UTF-8';
        }

        return $encoding;
    }

    public static function getStringAsOneLine(string $string): string
    {
        $string = str_replace("\r\n", ' ', $string);
        $string = str_replace("\n", ' ', $string);
        $string = str_replace("\r", ' ', $string);
        $string = str_replace("\t", '', $string);
        $string = preg_replace('#[ ]+#', ' ', $string);

        return $string;
    }

    public static function cutStringRespectingWhitespace(string $string, int $length): string
    {
        if ($length < strlen($string)) {
            $text = substr($string, 0, $length);
            if (false !== ($length = strrpos($text, ' '))) {
                $text = substr($text, 0, $length);
            }
            $string = $text . '…';
        }

        return $string;
    }
}
