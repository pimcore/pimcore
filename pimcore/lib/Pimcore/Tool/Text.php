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


include_once("simple_html_dom.php");

class Pimcore_Tool_Text
{

    public static function removeLineBreaks ($text = "") {

        $text = str_replace(array("\r\n", "\n", "\r", "\t"), " ", $text);
        $text = preg_replace('#[ ]+#', ' ', $text);

        return $text;
    }
    
    public static function wysiwygText($text)
    {

        $html = str_get_html($text);
        if(!$html) {
            return $text;
        }

        $s = $html->find("[pimcore_id]");

        foreach ($s as $el) {

            // image
            if ($el->src) {
                if ($asset = Asset::getById($el->pimcore_id)) {

                    // only for images
                    if(!$asset instanceof Asset_Image) {
                        continue;
                    }
                    
                    $el->src = $asset->getFullPath();

                    // resize image to the given attributes
                    $config = null;
                    if ($el->width || $el->height) {
                        $config = array(
                            "width" => intval($el->width),
                            "height" => intval($el->height)
                        );
                    }
                    if ($el->style) {
                        $cleanedStyle = preg_replace('#[ ]+#', '', $el->style);
                        $styles = explode(";", $cleanedStyle);
                        foreach ($styles as $style) {
                            if (strpos($style, "width") !== false) {
                                if (preg_match("/([0-9]+)(px)/i", $style, $match)) {
                                    $config["width"] = $match[1];
                                }
                            }
                            else if (strpos($style, "height") !== false) {
                                if (preg_match("/([0-9]+)(px)/i", $style, $match)) {
                                    $config["height"] = $match[1];
                                }
                            }
                        }
                    }

                    if ($config) {
                        $el->src = $asset->getThumbnail($config);
                    } else {
                        $el->src = $asset->getThumbnail(array(
                            "width" => $asset->getWidth(),
                            "height" => $asset->getHeight()
                        ));
                    }
                }
                else {
                    $el->outertext = "";
                }
            }

            // link
            if ($el->href) {
                if ($el->pimcore_type == "asset") {
                    if ($asset = Asset::getById($el->pimcore_id)) {
                        $el->href = $asset->getFullPath();
                    }
                    else {
                        $el->outertext = $el->innertext;
                    }
                }
                else if ($el->pimcore_type == "document") {
                    if ($doc = Document::getById($el->pimcore_id)) {

                        $newHref = $doc->getFullPath();

                        // get parameters
                        $parameters = explode("?",$el->href);
                        if(!empty($parameters[1])) {
                            $newHref .= "?" . $parameters[1];
                        }

                        $el->href = $newHref;
                    }
                    else {
                        $el->outertext = $el->innertext;
                    }
                }
            }
        }

        return $html->save();
    }

    /**
     * cleans wysiwyg text of specified dependencies
     *
     * @static
     * @param  string $text
     * @param  array $validDependencies
     * @return string
     */
    public static function cleanWysiwygTextOfDependencies($text, $validDependencies)
    {
        if (!empty($text)) {

            $html = str_get_html($text);
            if(!$html) {
                return $text;
            }

            $s = $html->find("[pimcore_id]");

            foreach ($s as $el) {

                // image
                if ($el->src) {

                    $key = "asset_" . $el->pimcore_id;
                    if (!in_array($key, array_keys($validDependencies))) {
                        $text = str_replace($el->outertext(), $el->innertext(), $text);
                    }
                }

                // link
                if ($el->href) {
                    if ($el->pimcore_type == "asset") {

                        $key = "asset_" . $el->pimcore_id;
                        if (!in_array($key, array_keys($validDependencies))) {
                            $text = str_replace($el->outertext(), $el->innertext(), $text);
                        }

                    }
                    else if ($el->pimcore_type == "document") {

                        $key = "document_" . $el->pimcore_id;
                        if (!in_array($key, array_keys($validDependencies))) {
                            $text = str_replace($el->outertext(), $el->innertext(), $text);
                        }
                    }
                }
            }
        }
        return $text;

    }

    /**
     * @static
     * @param  array $idMapping e.g. array("asset"=>array(OLD_ID=>NEW_ID),"object"=>array(OLD_ID=>NEW_ID),"document"=>array(OLD_ID=>NEW_ID));
     * @param  string $text html text of wysiwyg field
     * @return mixed
     */
    public static function replaceWysiwygTextRelationIds($idMapping, $text)
    {
        if (!empty($text)) {

            $html = str_get_html($text);
            if(!$html) {
                return $text;
            }

            $s = $html->find("[pimcore_id]");

            foreach ($s as $el) {



                // image
                if ($el->src) {
                    $type = "asset";
                }

                // link
                if ($el->href) {
                    if ($el->pimcore_type == "asset") {
                        $type = "asset";

                    }
                    else if ($el->pimcore_type == "document") {
                        $type = "document";
                    }
                }

                $newId = $idMapping[$type][$el->attr["pimcore_id"]];
                if ($newId) {
                    //update id

                    if($type=="asset"){
                        $pimcoreElement = Asset::getById($newId);
                    } else {
                        $pimcoreElement = Document::getById($newId);
                    }

                    $el->pimcore_id = $newId;
                    $el->src = $pimcoreElement->getFullPath();

                } else {
                    //remove relation, not found in mapping
                    $el->pimcore_id = null;
                    $el->src=null;
                }
            }
            return $html->save();
        }



    }


    /**
     * extracts all dependencies to other elements from wysiwyg text
     *
     * @static
     * @param  string $text
     * @return array
     */
    public static function getDependenciesOfWysiwygText($text)
    {

        $dependencies = array();

        if (!empty($text)) {

            $html = str_get_html($text);
            if(!$html) {
                return $text;
            }

            $s = $html->find("[pimcore_id]");

            foreach ($s as $el) {

                // image
                if ($el->src) {
                    if ($asset = Asset::getById($el->pimcore_id)) {
                        $key = "asset_" . $asset->getId();
                        $dependencies[$key] = array(
                            "id" => $asset->getId(),
                            "type" => "asset"
                        );
                    }
                }

                // link
                if ($el->href) {
                    if ($el->pimcore_type == "asset") {
                        if ($asset = Asset::getById($el->pimcore_id)) {

                            $key = "asset_" . $asset->getId();
                            $dependencies[$key] = array(
                                "id" => $asset->getId(),
                                "type" => "asset"
                            );

                        }
                    }
                    else if ($el->pimcore_type == "document") {
                        if ($doc = Document::getById($el->pimcore_id)) {

                            $key = "document_" . $doc->getId();
                            $dependencies[$key] = array(
                                "id" => $doc->getId(),
                                "type" => "document"
                            );

                        }
                    }
                }
            }
        }

        return $dependencies;
    }


    public static function getCacheTagsOfWysiwygText($text, $tags = array())
    {
        $tags = is_array($tags) ? $tags : array();
        
        if (!empty($text)) {

            $html = str_get_html($text);
            if(!$html) {
                return $text;
            }

            $s = $html->find("[pimcore_id]");

            foreach ($s as $el) {

                // image
                if ($el->src) {
                    if ($asset = Asset::getById($el->pimcore_id)) {
                        if (!array_key_exists($asset->getCacheTag(), $tags)) {
                            $tags = $asset->getCacheTags($tags);
                        }
                    }
                }

                // link
                if ($el->href) {
                    if ($el->pimcore_type == "asset") {
                        if ($asset = Asset::getById($el->pimcore_id)) {
                            if (!array_key_exists($asset->getCacheTag(), $tags)) {
                                $tags = $asset->getCacheTags($tags);
                            }
                        }
                    }
                    else if ($el->pimcore_type == "document") {
                        if ($doc = Document::getById($el->pimcore_id)) {
                            if (!array_key_exists($doc->getCacheTag(), $tags)) {
                                $tags = $doc->getCacheTags($tags);
                            }
                        }
                    }
                }
            }
        }

        return $tags;
    }

    public static function detectEncoding($text)
    {

        if (function_exists("mb_detect_encoding")) {
            $encoding = mb_detect_encoding($text, array(
                                                       "UTF-32",
                                                       "UTF-32BE",
                                                       "UTF-32LE",
                                                       "UTF-16",
                                                       "UTF-16BE",
                                                       "UTF-16LE",
                                                       "UTF-8",
                                                       "UTF-7",
                                                       "UTF7-IMAP",
                                                       "ASCII",
                                                       "Windows-1252",
                                                       "Windows-1254",
                                                       "ISO-8859-1",
                                                       "ISO-8859-2",
                                                       "ISO-8859-3",
                                                       "ISO-8859-4",
                                                       "ISO-8859-5",
                                                       "ISO-8859-6",
                                                       "ISO-8859-7",
                                                       "ISO-8859-8",
                                                       "ISO-8859-9",
                                                       "ISO-8859-10",
                                                       "ISO-8859-13",
                                                       "ISO-8859-14",
                                                       "ISO-8859-15",
                                                       "ISO-8859-16",
                                                       "EUC-CN",
                                                       "CP936",
                                                       "HZ",
                                                       "EUC-TW",
                                                       "BIG-5",
                                                       "EUC-KR",
                                                       "UHC",
                                                       "ISO-2022-KR",
                                                       "Windows-1251",
                                                       "CP866",
                                                       "KOI8-R",
                                                       "KOI8-U",
                                                       "ArmSCII-8",
                                                       "CP850",
                                                       "EUC-JP",
                                                       "SJIS",
                                                       "eucJP-win",
                                                       "SJIS-win",
                                                       "CP51932",
                                                       "JIS",
                                                       "ISO-2022-JP",
                                                       "ISO-2022-JP-MS"
                                                  ));
        }

        if (!$encoding) {
            $encoding = "UTF-8";
        }
        return $encoding;
    }
}
