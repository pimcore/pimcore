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

namespace Pimcore\Helper;

use Exception;
use Net_URL2;
use Pimcore\Mail as MailClient;
use Pimcore\Model;
use Pimcore\Tool;
use Symfony\Component\Mime\Address;
use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;

/**
 * @internal
 */
class Mail
{
    /**
     * @throws Exception
     */
    public static function getDebugInformation(string $type, MailClient $mail): string
    {
        $type = strtolower($type);

        if ($type != 'html' && $type != 'text') {
            throw new Exception('$type has to be "html" or "text"');
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

            foreach (['To', 'Cc', 'Bcc', 'ReplyTo'] as $key) {
                $getterName = 'get' . $key;
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
                $debugInformation .= 'From: ' . self::formatDebugReceivers($mail->getFrom()) . "\r\n";
            }

            //generating text debug info
            foreach (['To', 'Cc', 'Bcc', 'ReplyTo'] as $key) {
                $getterName = 'get' . $key;
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
     */
    public static function getDebugInformationCssStyle(): string
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
     * @internal
     *
     * Helper to format the receivers for the debug email and logging
     */
    public static function formatDebugReceivers(array $receivers): string
    {
        $formatedReceiversArray = [];

        foreach ($receivers as $mail => $name) {
            if ($name instanceof Address) {
                $formatedReceiversArray[] = $name->toString();
            } else {
                if (strlen(trim($name)) > 0) {
                    $formatedReceiversArray[] = $name . ' <' . $mail . '>';
                } else {
                    $formatedReceiversArray[] = $mail;
                }
            }
        }

        return implode(', ', $formatedReceiversArray);
    }

    public static function logEmail(MailClient $mail, array $recipients, string $error = null): Model\Tool\Email\Log
    {
        $emailLog = new Model\Tool\Email\Log();

        if ($documentId = $mail->getDocumentId()) {
            $emailLog->setDocumentId($documentId);
        }

        if (isset($_SERVER['REQUEST_URI'])) {
            $emailLog->setRequestUri(htmlspecialchars($_SERVER['REQUEST_URI']));
        }

        $emailLog->setParams($mail->getParams());
        $emailLog->setSentDate(time());

        $subject = $mail->getSubjectRendered();
        if (str_starts_with($subject, '=?')) {
            $mbIntEnc = mb_internal_encoding();
            mb_internal_encoding($mail->getTextCharset());
            $subject = mb_decode_mimeheader($subject);
            mb_internal_encoding($mbIntEnc);
        }
        $emailLog->setSubject($subject);

        $mailFrom = $mail->getFrom();
        if ($mailFrom) {
            $emailLog->setFrom(self::formatDebugReceivers($mailFrom));
        }

        $html = $mail->getHtmlBody();
        if ($html) {
            $emailLog->setBodyHtml($html);
        }

        $text = $mail->getTextBody();
        if ($text) {
            $emailLog->setBodyText($text);
        }

        foreach (['To', 'Cc', 'Bcc', 'ReplyTo'] as $key) {
            $addresses = isset($recipients[$key]) ? $recipients[$key] : null;

            if ($addresses) {
                if (method_exists($emailLog, 'set' . $key)) {
                    $emailLog->{"set$key"}(self::formatDebugReceivers($addresses));
                }
            }
        }

        $emailLog->setError($error);

        $emailLog->save();

        return $emailLog;
    }

    /**
     *
     *
     * @throws Exception
     */
    public static function setAbsolutePaths(string $string, ?Model\Document $document = null, string $hostUrl = null): string
    {
        $replacePrefix = '';

        if (!$hostUrl && $document) {
            // try to determine if the document is within a site
            $site = \Pimcore\Tool\Frontend::getSiteForDocument($document);
            if ($site) {
                $hostUrl = \Pimcore\Tool::getRequestScheme() . '://' . $site->getMainDomain();
                $replacePrefix = $site->getRootPath();
            }
        }

        // fallback
        if (!$hostUrl) {
            $hostUrl = \Pimcore\Tool::getHostUrl();
        }

        //matches all links
        preg_match_all("@(href|src)\s*=[\"']([^(http|mailto|javascript|data:|#)].*?(css|jpe?g|gif|png)?)[\"']@is", $string, $matches);

        foreach ($matches[0] as $key => $value) {
            $path = $matches[2][$key];

            if (str_starts_with($path, '//')) {
                $absolutePath = 'http:' . $path;
            } elseif (str_starts_with($path, '/')) {
                $absolutePath = preg_replace('@^' . $replacePrefix . '(/(.*))?$@', '/$2', $path);
                $absolutePath = $hostUrl . $absolutePath;
            } elseif (str_starts_with($path, 'file://')) {
                continue;
            } else {
                $absolutePath = $hostUrl . "/$path";
                if ($path[0] == '?') {
                    $absolutePath = $hostUrl . $document . $path;
                }
                $netUrl = new Net_URL2($absolutePath);
                $absolutePath = $netUrl->getNormalizedURL();
            }

            $path = preg_quote($path, '!');
            $string = preg_replace("!([\"'])$path([\"'])!is", '\\1' . $absolutePath . '\\2', $string);
        }

        preg_match_all("@srcset\s*=[\"'](.*?)[\"']@is", $string, $matches);
        foreach ($matches[1] as $i => $value) {
            $parts = explode(',', $value);
            foreach ($parts as $key => $v) {
                $v = trim($v);
                // ignore absolute urls
                if (str_starts_with($v, 'http://') ||
                    str_starts_with($v, 'https://') ||
                    str_starts_with($v, '//') ||
                    str_starts_with($v, 'file://')
                ) {
                    continue;
                }
                $parts[$key] = $hostUrl.$v;
            }
            $s = ' srcset="'.implode(', ', $parts).'" ';
            if ($matches[0][$i]) {
                $string = str_replace($matches[0][$i], $s, $string);
            }
        }

        return $string;
    }

    /**
     * @throws Exception
     */
    public static function embedAndModifyCss(string $string, ?Model\Document $document = null): string
    {
        $css = null;

        //matches all <link> Tags
        preg_match_all("@<link.*?href\s*=\s*[\"'](.*?)[\"'].*?(/?>|</\s*link>)@is", $string, $matches);
        if ($matches[0]) {
            $css = '';

            foreach ($matches[0] as $key => $value) {
                $fullMatch = $matches[0][$key];
                $path = $matches[1][$key];

                $fileContent = '';
                $fileInfo = [];
                if (stream_is_local($path)) {
                    $fileInfo = self::getNormalizedFileInfo($path, $document);
                    if ($fileInfo['fileExtension'] === 'css' && is_readable($fileInfo['filePathNormalized'])) {
                        $fileContent = file_get_contents($fileInfo['filePathNormalized']);
                    }
                } elseif (str_starts_with($path, 'http')) {
                    $fileContent = \Pimcore\Tool::getHttpData($path);
                    $fileInfo = [
                        'fileUrlNormalized' => $path,
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
        }

        $cssToInlineStyles = new CssToInlineStyles();
        $string = $cssToInlineStyles->convert($string, $css);

        return $string;
    }

    /**
     * Normalizes the css content (replaces images with the full path including the host)
     */
    public static function normalizeCssContent(string $content, array $fileInfo): string
    {
        preg_match_all("@url\s*\(\s*[\"']?(.*?)[\"']?\s*\)@is", $content, $matches);
        $hostUrl = Tool::getHostUrl();

        foreach ($matches[0] as $key => $value) {
            $fullMatch = $matches[0][$key];
            $path = $matches[1][$key];

            if ($path[0] == '/') {
                $imageUrl = $hostUrl . $path;
            } else {
                $imageUrl = dirname($fileInfo['fileUrlNormalized']) . "/$path";
                $netUrl = new Net_URL2($imageUrl);
                $imageUrl = $netUrl->getNormalizedURL();
            }

            $content = str_replace($fullMatch, ' url(' . $imageUrl . ') ', $content);
        }

        return $content;
    }

    /**
     * @throws Exception
     */
    public static function getNormalizedFileInfo(string $path, ?Model\Document $document = null): array
    {
        $fileInfo = [];
        $hostUrl = Tool::getHostUrl();
        if ($path[0] != '/') {
            $fileInfo['fileUrl'] = $hostUrl . $document . "/$path"; //relative eg. ../file.css
        } else {
            $fileInfo['fileUrl'] = $hostUrl . $path;
        }

        $fileInfo['fileExtension'] = substr($path, strrpos($path, '.') + 1);
        $netUrl = new Net_URL2($fileInfo['fileUrl']);
        $fileInfo['fileUrlNormalized'] = $netUrl->getNormalizedURL();

        $fileInfo['filePathNormalized'] = PIMCORE_WEB_ROOT . preg_replace('@^/cache-buster\-\d+\/@', '/', str_replace($hostUrl, '', $fileInfo['fileUrlNormalized']));

        return $fileInfo;
    }

    /**
     * parses an email string in the following name/mail list annotation: 'Name 1 <address1@mail.com>, Name 2 <address2@mail.com>, ...'
     *
     * @return list<array{email: string, name: string}>
     */
    public static function parseEmailAddressField(?string $emailString): array
    {
        $cleanedEmails = [];
        $emailArray = preg_split('/,|;/', ($emailString ?? ''));
        if ($emailArray) {
            foreach ($emailArray as $emailStringEntry) {
                $entryAddress = trim($emailStringEntry);
                $entryName = ''; // Symfony mailer want a string
                $matches = [];
                if (preg_match('/(.*)<(.*)>/', $entryAddress, $matches)) {
                    $entryAddress = trim($matches[2]);
                    $entryName = trim($matches[1]);
                } elseif (preg_match('/(.*)\((.*)\)/', $entryAddress, $matches)) {
                    $entryAddress = trim($matches[1]);
                    $entryName = trim($matches[2]);
                }

                if ($entryAddress) {
                    $cleanedEmails[] = ['email' => $entryAddress, 'name' => $entryName];
                }
            }
        }

        return $cleanedEmails;
    }
}
