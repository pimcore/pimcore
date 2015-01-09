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
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Helper;

use Pimcore\Mail as MailClient;
use Pimcore\Tool;
use Pimcore\Model;

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

        $temporaryStorage = $mail->getTemporaryStorage();

        //generating html debug info
        if ($type == 'html') {
            $debugInformation = '<br/><br/><table class="pimcore_debug_information">
                                    <tr><th colspan="2">Debug information</th></tr>';

            $debugInformation .= '<tr><td class="pimcore_label_column">From:</td><td>';

            if ($mail->getFrom()) {
                $debugInformation .= $mail->getFrom();
            } else {
                $defaultFrom = $mail->getDefaultFrom();
                $debugInformation .= $defaultFrom["email"] . '<br/>Info: No "from" email address given so the default "from" email address is used from "Settings" -> "System" -> "Email Settings" )';
            }
            $debugInformation .= '</td></tr>';

            foreach (array('To', 'Cc', 'Bcc') as $key) {
                if (isset($temporaryStorage[$key]) && is_array($temporaryStorage[$key])) {
                    $debugInformation .= '<tr><td class="pimcore_label_column">' . $key . ': </td>';
                    $debugInformation .= '<td>' . self::formatDebugReceivers($temporaryStorage[$key]) . '</td></tr>';
                }
            }

            $debugInformation .= '</table>';
        } else {
            //generating text debug info
            $debugInformation = "\r\n  \r\nDebug Information:  \r\n  \r\n";
            foreach (array('To', 'Cc', 'Bcc') as $key) {
                if (isset($temporaryStorage[$key]) && is_array($temporaryStorage[$key])) {
                    $debugInformation .= "$key: " . self::formatDebugReceivers($temporaryStorage[$key]) . "\r\n";
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
    protected static function formatDebugReceivers(Array $receivers)
    {
        $tmpString = '';
        foreach ($receivers as $entry) {
            if(isset($entry['email'])) {
                $tmpString .= $entry['email'];
                if (isset($entry['name'])) {
                    $tmpString .= " (" . $entry["name"] . ")";
                }
                $tmpString .= ", ";
            }
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
        $emailLog->setSubject($mail->getSubject());
        $emailLog->setSentDate(time());

        $mailFrom = $mail->getFrom();
        if ($mailFrom) {
            $emailLog->setFrom($mailFrom);
        } else {
            $defaultFrom = $mail->getDefaultFrom();
            $tmpString = $defaultFrom['email'];
            if ($defaultFrom['name']) {
                $tmpString .= " (" . $defaultFrom["name"] . ")";
            }
            $emailLog->setFrom($tmpString);
        }


        $html = $mail->getBodyHtml();
        if ($html instanceof \Zend_Mime_Part) {
            $emailLog->setBodyHtml($html->getRawContent());
        }

        $text = $mail->getBodyText();
        if ($text instanceof \Zend_Mime_Part) {
            $emailLog->setBodyText($text->getRawContent());
        }

        $temporaryStorage = $mail->getTemporaryStorage();
        foreach (array('To', 'Cc', 'Bcc') as $key) {
            if (isset($temporaryStorage[$key]) && is_array($temporaryStorage[$key])) {
                if (method_exists($emailLog, 'set' . $key)) {
                    $emailLog->{"set$key"}(self::formatDebugReceivers($temporaryStorage[$key]));
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

        if(is_null($hostUrl)){
            $hostUrl = \Pimcore\Tool::getHostUrl();
        }

        //matches all links
        preg_match_all("@(href|src)\s*=[\"']([^(http|mailto|javascript)].*?(css|jpe?g|gif|png)?)[\"']@is", $string, $matches);
        if (!empty($matches[0])) {
            foreach ($matches[0] as $key => $value) {
                $fullMatch = $matches[0][$key];
                $linkType = $matches[1][$key];
                $path = $matches[2][$key];
                $fileType = $matches[3][$key];

                if (strpos($path, '/') === 0) {
                    $absolutePath = $hostUrl . $path;
                } else {
                    $absolutePath = $hostUrl . "/$path";
                    $netUrl = new \Net_URL2($absolutePath);
                    $absolutePath = $netUrl->getNormalizedURL();
                }

                $path = preg_quote($path);
                $string = preg_replace("!([\"'])$path([\"'])!is", "\\1" . $absolutePath . "\\2", $string);
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
        preg_match_all("@<link.*?href\s*=\s*[\"']([^http].*?)[\"'].*?(/?>|</\s*link>)@is", $string, $matches);
        if (!empty($matches[0])) {
            foreach ($matches[0] as $key => $value) {
                $fullMatch = $matches[0][$key];
                $path = $matches[1][$key];
                $fileInfo = self::getNormalizedFileInfo($path, $document);
                if (in_array($fileInfo['fileExtension'], array('css', 'less'))) {
                    if (is_readable($fileInfo['filePathNormalized'])) {

                        if ($fileInfo['fileExtension'] == 'css') {
                            $fileContent = file_get_contents($fileInfo['filePathNormalized']);
                        } else {
                            $fileContent = \Pimcore\Tool\Less::compile($fileInfo['filePathNormalized']);
                            $fileContent = str_replace('/**** compiled with lessphp ****/','',$fileContent);
                        }
                        if ($fileContent) {
                            $fileContent = self::normalizeCssContent($fileContent, $fileInfo);
                            $string = str_replace($fullMatch, '<style type="text/css">' . $fileContent . '</style>', $string);
                        }
                    }
                }
            }
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
    public static function normalizeCssContent($content, Array $fileInfo)
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

        $fileInfo = array();
        $hostUrl = Tool::getHostUrl();
        if ($path[0] != '/') {
            $fileInfo['fileUrl'] = $hostUrl . $document . "/$path"; //relative eg. ../file.css
        } else {
            $fileInfo['fileUrl'] = $hostUrl . $path;
        }


        $fileInfo['fileExtension'] = substr($path, strrpos($path, '.') + 1);
        $netUrl = new \Net_URL2($fileInfo['fileUrl']);
        $fileInfo['fileUrlNormalized'] = $netUrl->getNormalizedURL();
        $fileInfo['filePathNormalized'] = PIMCORE_DOCUMENT_ROOT . str_replace($hostUrl, '', $fileInfo['fileUrlNormalized']);

        return $fileInfo;
    }
}