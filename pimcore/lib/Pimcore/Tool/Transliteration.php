<?php
/**
 * Pimcore
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.pimcore.org/license
 *
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Pimcore_Tool_Transliteration {

    /**
     * @static
     * @param $value
     * @param null $language
     * @return string
     */
    public static function toASCII ($value, $language = null) {

        if(!$language) {
            if(Zend_Registry::isRegistered("Zend_Locale")) {
                $locale = Zend_Registry::get("Zend_Locale");
                $language = $locale->getLanguage();
            } else {
                // there is no locale use default
                $language = "en";
            }
        }

        $value = self::_transliterationProcess($value,"~",$language);
        
        // then use iconv
        $value = trim(iconv("utf-8","ASCII//IGNORE//TRANSLIT",$value));
        
        return $value;
    }

    /**
     * The following functions are based on:
     * http://drupal.org/project/transliteration
     * http://sourceforge.net/projects/phputf8/
     *
     * The data in Transliteration/Data is a port of Text::Unidecode
     * Data was transformed to PHP by the libs above
     * http://search.cpan.org/~sburke/Text-Unidecode-0.04/lib/Text/Unidecode.pm
     */


    /**
     * @static
     * @param $string
     * @param string $unknown
     * @param null $source_langcode
     * @return string
     */
    private static function _transliterationProcess($string, $unknown = '?', $source_langcode = NULL) {
        // ASCII is always valid NFC! If we're only ever given plain ASCII, we can
        // avoid the overhead of initializing the decomposition tables by skipping
        // out early.
        if (!preg_match('/[\x80-\xff]/', $string)) {
            return $string;
        }

        static $tail_bytes;

        if (!isset($tail_bytes)) {
            // Each UTF-8 head byte is followed by a certain number of tail bytes.
            $tail_bytes = array();
            for ($n = 0; $n < 256; $n++) {
                if ($n < 0xc0) {
                    $remaining = 0;
                }
                elseif ($n < 0xe0) {
                    $remaining = 1;
                }
                elseif ($n < 0xf0) {
                    $remaining = 2;
                }
                elseif ($n < 0xf8) {
                    $remaining = 3;
                }
                elseif ($n < 0xfc) {
                    $remaining = 4;
                }
                elseif ($n < 0xfe) {
                    $remaining = 5;
                }
                else {
                    $remaining = 0;
                }
                $tail_bytes[chr($n)] = $remaining;
            }
        }

        // Chop the text into pure-ASCII and non-ASCII areas; large ASCII parts can
        // be handled much more quickly. Don't chop up Unicode areas for punctuation,
        // though, that wastes energy.
        preg_match_all('/[\x00-\x7f]+|[\x80-\xff][\x00-\x40\x5b-\x5f\x7b-\xff]*/', $string, $matches);

        $result = '';
        foreach ($matches[0] as $str) {
            if ($str[0] < "\x80") {
                // ASCII chunk: guaranteed to be valid UTF-8 and in normal form C, so
                // skip over it.
                $result .= $str;
                continue;
            }

            // We'll have to examine the chunk byte by byte to ensure that it consists
            // of valid UTF-8 sequences, and to see if any of them might not be
            // normalized.
            //
            // Since PHP is not the fastest language on earth, some of this code is a
            // little ugly with inner loop optimizations.

            $head = '';
            $chunk = strlen($str);
            // Counting down is faster. I'm *so* sorry.
            $len = $chunk + 1;

            for ($i = -1; --$len; ) {
                $c = $str[++$i];
                if ($remaining = $tail_bytes[$c]) {
                    // UTF-8 head byte!
                    $sequence = $head = $c;
                    do {
                        // Look for the defined number of tail bytes...
                        if (--$len && ($c = $str[++$i]) >= "\x80" && $c < "\xc0") {
                            // Legal tail bytes are nice.
                            $sequence .= $c;
                        }
                        else {
                            if ($len == 0) {
                                // Premature end of string! Drop a replacement character into
                                // output to represent the invalid UTF-8 sequence.
                                $result .= $unknown;
                                break 2;
                            }
                            else {
                                // Illegal tail byte; abandon the sequence.
                                $result .= $unknown;
                                // Back up and reprocess this byte; it may itself be a legal
                                // ASCII or UTF-8 sequence head.
                                --$i;
                                ++$len;
                                continue 2;
                            }
                        }
                    } while (--$remaining);

                    $n = ord($head);
                    if ($n <= 0xdf) {
                        $ord = ($n - 192) * 64 + (ord($sequence[1]) - 128);
                    }
                    elseif ($n <= 0xef) {
                        $ord = ($n - 224) * 4096 + (ord($sequence[1]) - 128) * 64 + (ord($sequence[2]) - 128);
                    }
                    elseif ($n <= 0xf7) {
                        $ord = ($n - 240) * 262144 + (ord($sequence[1]) - 128) * 4096 + (ord($sequence[2]) - 128) * 64 + (ord($sequence[3]) - 128);
                    }
                    elseif ($n <= 0xfb) {
                        $ord = ($n - 248) * 16777216 + (ord($sequence[1]) - 128) * 262144 + (ord($sequence[2]) - 128) * 4096 + (ord($sequence[3]) - 128) * 64 + (ord($sequence[4]) - 128);
                    }
                    elseif ($n <= 0xfd) {
                        $ord = ($n - 252) * 1073741824 + (ord($sequence[1]) - 128) * 16777216 + (ord($sequence[2]) - 128) * 262144 + (ord($sequence[3]) - 128) * 4096 + (ord($sequence[4]) - 128) * 64 + (ord($sequence[5]) - 128);
                    }
                    $result .= self::_transliterationReplace($ord, $unknown, $source_langcode);
                    $head = '';
                }
                elseif ($c < "\x80") {
                    // ASCII byte.
                    $result .= $c;
                    $head = '';
                }
                elseif ($c < "\xc0") {
                    // Illegal tail bytes.
                    if ($head == '') {
                        $result .= $unknown;
                    }
                }
                else {
                    // Miscellaneous freaks.
                    $result .= $unknown;
                    $head = '';
                }
            }
        }
        return $result;
    }


    /**
     * @static
     * @param $ord
     * @param string $unknown
     * @param null $langcode
     * @return string
     */
    private static function _transliterationReplace($ord, $unknown = '?', $langcode = NULL) {
        $map = array();

        if (!isset($langcode)) {
            $langcode = "en";
        }

        $bank = $ord >> 8;

        if (!isset($map[$bank][$langcode])) {
            $file = __DIR__ . '/Transliteration/Data/' . sprintf('x%02x', $bank) . '.php';
            if (file_exists($file)) {
                $base = array();
                // contains the $base variable
                include ($file);
                if ($langcode != 'en' && isset($variant[$langcode])) {
                    // Merge in language specific mappings.
                    $map[$bank][$langcode] = $variant[$langcode] + $base;
                }
                else {
                    $map[$bank][$langcode] = $base;
                }
            }
            else {
                $map[$bank][$langcode] = array();
            }
        }

        $ord = $ord & 255;

        return isset($map[$bank][$langcode][$ord]) ? $map[$bank][$langcode][$ord] : $unknown;
    }


}
