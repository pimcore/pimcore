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
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Helper;

use Pimcore\Mail as MailClient;
use Pimcore\Tool;
use Pimcore\Model;
use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;

class Mail
{
    /**
     * @param $type
     * @param MailClient $mail
     * @return string
     * @throws \Exception
     */
    public static function getDebugInformation($type, MailClient $mail)
    {
        $type = strtolower($type);

        if ($type != 'html' && $type != 'text') {
            throw new \Exception('$type has to be "html" or "text"');
        }

        //generating html debug info
        if ($type == 'html') {
            $debugInformation = '<br/><br/><table class="pimcore_debug_information">
                                    <tr><th colspan="2">Debug information</th></tr>';

            $debugInformation .= '<tr><td class="pimcore_label_column">From:</td><td>';

            if ($mail->getFrom()) {
                $debugInformation .= self::formatDebugReceivers($mail->getFrom());
            }
            $debugInformation .= '</td></tr>';

            foreach (['To', 'Cc', 'Bcc'] as $key) {

                $getterName = "get" . $key;
                $addresses = $mail->$getterName();

                if ($addresses) {
                    $debugInformation .= '<tr><td class="pimcore_label_column">' . $key . ': </td>';
                    $debugInformation .= '<td>' . self::formatDebugReceivers($addresses) . '</td></tr>';
                }
            }

            $debugInformation .= '</table>';
        } else {
            //generating text debug info
            $debugInformation = "\r\n  \r\nDebug Information:  \r\n  \r\n";
            if ($mail->getFrom()) {
                $debugInformation .= 'From: ' . self::formatDebugReceivers($mail->getFrom()) . "\r\n";;
            }

            //generating text debug info
            foreach (['To', 'Cc', 'Bcc'] as $key) {

                $getterName = "get" . $key;
                $addresses = $mail->$getterName();

                if ($addresses) {
                    $debugInformation .= "$key: " . self::formatDebugReceivers($addresses) . "\r\n";
                }
            }
        }

        return $debugInformation;
    }


    /**
     * Return the basic css styles for the html debug information
     *
     * @static
     * @return string
     */
    public static function getDebugInformationCssStyle()
    {
        $style = <<<'CSS'
<style type="text/css">
.pimcore_debug_information{
    width:100%;
    background-color:#f8f8f8;
    font-size:12px;
    font-family: Arial, sans-serif;
    border-spacing:0px;
    border-collapse:collapse
}
.pimcore_debug_information td, .pimcore_debug_information th{
    padding: 5px 10px;
    vertical-align:top;
    border: 1px solid #ccc;

}
.pimcore_debug_information th{
    text-align:center;
    font-weight:bold;
}
.pimcore_label_column{
    width:80px;
}

</style>
CSS;

        return $style;
    }

    /**
     * Helper to format the receivers for the debug email and logging
     *
     * @param array $receivers
     * @return string
     */
    protected static function formatDebugReceivers(array $receivers)
    {
        $tmpString = '';
        foreach ($receivers as $mail => $name) {
            $tmpString .= $mail;
            if (isset($name)) {
                $tmpString .= " (" . $name . ")";
            }
            $tmpString .= ", ";
        }
        $tmpString = substr($tmpString, 0, strrpos($tmpString, ','));

        return $tmpString;
    }


    /**
     * @param MailClient $mail
     * @return Model\Tool\Email\Log
     */
    public static function logEmail(MailClient $mail)
    {
        $emailLog = new Model\Tool\Email\Log();
        $document = $mail->getDocument();

        if ($document instanceof Model\Document) {
            $emailLog->setDocumentId($document->getId());
        }

        $emailLog->setRequestUri(htmlspecialchars($_SERVER['REQUEST_URI']));
        $emailLog->setParams($mail->getParams());
        $emailLog->setSentDate(time());

        $subject = $mail->getSubjectRendered();
        if (0 === strpos($subject, '=?')) {
            $mbIntEnc = mb_internal_encoding();
            mb_internal_encoding($mail->getCharset());
            $subject = mb_decode_mimeheader($subject);
            mb_internal_encoding($mbIntEnc);
        }
        $emailLog->setSubject($subject);

        $mailFrom = $mail->getFrom();
        if ($mailFrom) {
            $emailLog->setFrom(self::formatDebugReceivers($mailFrom));
        }


        $html = $mail->getBody();
        if ($html) {
            $emailLog->setBodyHtml($html);
        }

        $text = $mail->getBodyTextMimePart();
        if ($text) {
            $emailLog->setBodyText($text->getBody());
        }


        foreach (['To', 'Cc', 'Bcc'] as $key) {
            $getterName = "get" . $key;
            $addresses = $mail->$getterName();

            if ($addresses) {
                if (method_exists($emailLog, 'set' . $key)) {
                    $emailLog->{"set$key"}(self::formatDebugReceivers($addresses));
                }
            }
        }

        $emailLog->save();

        return $emailLog;
    }

    /**
     * @param $string
     * @param null $document
     * @param null $hostUrl
     * @return mixed
     * @throws \Exception
     */
    public static function setAbsolutePaths($string, $document = null, $hostUrl = null)
    {
        if ($document && $document instanceof Model\Document == false) {
            throw new \Exception('$document has to be an instance of Document');
        }

        $replacePrefix = "";

        if (!$hostUrl && $document) {
            // try to determine if the newsletter is within a site
            $site = \Pimcore\Tool\Frontend::getSiteForDocument($document);
            if ($site) {
                $hostUrl = "http://" . $site->getMainDomain();
                $replacePrefix = $site->getRootPath();
            }

            // fallback
            if (!$hostUrl) {
                $hostUrl = \Pimcore\Tool::getHostUrl();
            }
        }

        //matches all links
        preg_match_all("@(href|src)\s*=[\"']([^(http|mailto|javascript|data:|#)].*?(css|jpe?g|gif|png)?)[\"']@is", $string, $matches);
        if (!empty($matches[0])) {
            foreach ($matches[0] as $key => $value) {
                $path = $matches[2][$key];

                if (strpos($path, '//') === 0) {
                    $absolutePath = "http:" . $path;
                } elseif (strpos($path, '/') === 0) {
                    $absolutePath = preg_replace("@^" . $replacePrefix . "/@", "/", $path);
                    $absolutePath = $hostUrl . $absolutePath;
                } else {
                    $absolutePath = $hostUrl . "/$path";
                    $netUrl = new \Net_URL2($absolutePath);
                    $absolutePath = $netUrl->getNormalizedURL();
                }

                $path = preg_quote($path);
                $string = preg_replace("!([\"'])$path([\"'])!is", "\\1" . $absolutePath . "\\2", $string);
            }
        }

        preg_match_all("@srcset\s*=[\"'](.*?)[\"']@is", $string, $matches);
        foreach ((array)$matches[1] as $i => $value) {
            $parts = explode(',', $value);
            foreach ($parts as $key => $v) {
                $parts[$key] = $hostUrl.trim($v);
            }
            $s = ' srcset="'.implode(', ', $parts).'" ';
            if ($matches[0][$i]) {
                $string = str_replace($matches[0][$i], $s, $string);
            }
        }

        return $string;
    }


    /**
     * @param $string
     * @param null $document
     * @return mixed
     * @throws \Exception
     */
    public static function embedAndModifyCss($string, $document = null)
    {
        if ($document && $document instanceof Model\Document == false) {
            throw new \Exception('$document has to be an instance of Document');
        }

        //matches all <link> Tags
        preg_match_all("@<link.*?href\s*=\s*[\"'](.*?)[\"'].*?(/?>|</\s*link>)@is", $string, $matches);
        if (!empty($matches[0])) {
            $css = "";

            foreach ($matches[0] as $key => $value) {
                $fullMatch = $matches[0][$key];
                $path = $matches[1][$key];

                $fileContent = "";
                $fileInfo = [];
                if (stream_is_local($path)) {
                    $fileInfo = self::getNormalizedFileInfo($path, $document);
                    if ($fileInfo['fileExtension'] == "css" && is_readable($fileInfo['filePathNormalized'])) {
                        if ($fileInfo['fileExtension'] == 'css') {
                            $fileContent = file_get_contents($fileInfo['filePathNormalized']);
                        }
                    }
                } elseif (strpos($path, "http") === 0) {
                    $fileContent = \Pimcore\Tool::getHttpData($path);
                    $fileInfo = [
                        "fileUrlNormalized" => $path
                    ];
                }

                if ($fileContent) {
                    $fileContent = self::normalizeCssContent($fileContent, $fileInfo);

                    $css .= "\n\n\n";
                    $css .= $fileContent;

                    // remove <link> tag
                    $string = str_replace($fullMatch, '', $string);
                }
            }

            $cssToInlineStyles = new CssToInlineStyles();
            $cssToInlineStyles->setHTML($string);
            $cssToInlineStyles->setCSS($css);
            $string = $cssToInlineStyles->convert();
        }

        return $string;
    }


    /**
     * Normalizes the css content (replaces images with the full path including the host)
     *
     * @static
     * @param string $content
     * @param array $fileInfo
     * @return string
     */
    public static function normalizeCssContent($content, array $fileInfo)
    {
        preg_match_all("@url\s*\(\s*[\"']?(.*?)[\"']?\s*\)@is", $content, $matches);
        $hostUrl = Tool::getHostUrl();

        if (is_array($matches[0])) {
            foreach ($matches[0] as $key => $value) {
                $fullMatch = $matches[0][$key];
                $path = $matches[1][$key];

                if ($path[0] == '/') {
                    $imageUrl = $hostUrl . $path;
                } else {
                    $imageUrl = dirname($fileInfo['fileUrlNormalized']) . "/$path";
                    $netUrl = new \Net_URL2($imageUrl);
                    $imageUrl = $netUrl->getNormalizedURL();
                }

                $content = str_replace($fullMatch, " url(" . $imageUrl . ") ", $content);
            }
        }

        return $content;
    }


    /**
     * @param $path
     * @param null $document
     * @return array
     * @throws \Exception
     */
    public static function getNormalizedFileInfo($path, $document = null)
    {
        if ($document && $document instanceof Model\Document == false) {
            throw new \Exception('$document has to be an instance of Document');
        }

        $fileInfo = [];
        $hostUrl = Tool::getHostUrl();
        if ($path[0] != '/') {
            $fileInfo['fileUrl'] = $hostUrl . $document . "/$path"; //relative eg. ../file.css
        } else {
            $fileInfo['fileUrl'] = $hostUrl . $path;
        }


        $fileInfo['fileExtension'] = substr($path, strrpos($path, '.') + 1);
        $netUrl = new \Net_URL2($fileInfo['fileUrl']);
        $fileInfo['fileUrlNormalized'] = $netUrl->getNormalizedURL();
        $fileInfo['filePathNormalized'] = PIMCORE_WEB_ROOT . str_replace($hostUrl, '', $fileInfo['fileUrlNormalized']);

        return $fileInfo;
    }
}
