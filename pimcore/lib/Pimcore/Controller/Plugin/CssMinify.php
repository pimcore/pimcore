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

class Pimcore_Controller_Plugin_CssMinify extends Zend_Controller_Plugin_Abstract {

    protected $enabled = true;

    public function routeStartup(Zend_Controller_Request_Abstract $request) {

        $conf = Pimcore_Config::getSystemConfig();
        if (!$conf->outputfilters) {
            return $this->disable();
        }

        if (!$conf->outputfilters->cssminify) {
            return $this->disable();
        }

    }

    public function disable() {
        $this->enabled = false;
        return true;
    }

    public function dispatchLoopShutdown() {

        if(!Pimcore_Tool::isHtmlResponse($this->getResponse())) {
            return;
        }

        if ($this->enabled) {
            include_once("simple_html_dom.php");

            $body = $this->getResponse()->getBody();
            
            $html = str_get_html($body);
            if($html) {
                $styles = $html->find("link[rel=stylesheet], style[type=text/css]");

                $stylesheetContent = "";

                foreach ($styles as $style) {
                    if($style->tag == "style") {
                        $stylesheetContent .= $style->innertext;
                    }
                    else {

                        $source = $style->href;
                        $path = "";
                        if (is_file(PIMCORE_ASSET_DIRECTORY . $source)) {
                            $path = PIMCORE_ASSET_DIRECTORY . $source;
                        }
                        else if (is_file(PIMCORE_DOCUMENT_ROOT . $source)) {
                            $path = PIMCORE_DOCUMENT_ROOT . $source;
                        }

                        if (!empty($path) && is_file("file://".$path)) {
                            $content = file_get_contents($path);
                            $content = $this->correctReferences($source,$content);

                            if($style->media) {
                                $content = "@media ".$style->media." {" . $content . "}";
                            }

                            $stylesheetContent .= $content;
                            $style->outertext = "";

                        }
                    }
                }


                if(strlen($stylesheetContent) > 1) {
                    $stylesheetPath = PIMCORE_TEMPORARY_DIRECTORY."/minified_css_".md5($stylesheetContent).".css";

                    if(!is_file($stylesheetPath)) {
                        $stylesheetContent = Minify_CSS::minify($stylesheetContent);

                        // put minified contents into one single file
                        file_put_contents($stylesheetPath, $stylesheetContent);
                        chmod($stylesheetPath, 0766);
                    }

                    $head = $html->find("head",0);
                    $head->innertext = $head->innertext . "\n" . '<link rel="stylesheet" type="text/css" href="' . str_replace(PIMCORE_DOCUMENT_ROOT,"",$stylesheetPath) . '" />'."\n";
                }

                $body = $html->save();
                $this->getResponse()->setBody($body);
            }
        }
    }


    protected function correctReferences ($base, $content) {

        // check for url references
        preg_match_all("/url\((.*)\)/iU", $content, $matches);
        foreach ($matches[1] as $ref) {

            // do some corrections
            $ref = str_replace('"',"",$ref);
            $ref = str_replace(' ',"",$ref);
            $ref = str_replace("'","",$ref);

            $path = $this->correctUrl($ref, $base);

            //echo $ref . " - " . $path . " - " . $url . "<br />";

            $content = str_replace($ref,$path,$content);
        }

        // check for @import references
        preg_match_all("/\@import(.*);/iU", $content, $matches);
        foreach ($matches[1] as $ref) {

            // do some corrections
            $ref = str_replace('"',"",$ref);
            $ref = str_replace(' ',"",$ref);
            $ref = str_replace("'","",$ref);

            $path = $this->correctUrl($ref, $base);

            //echo $ref . " - " . $path . " - " . $url . "<br />";

            $content = str_replace($ref,$path,$content);
        }


        return $content;
    }


    protected function correctUrl ($rel, $base) {
        /* return if already absolute URL */
        if (parse_url($rel, PHP_URL_SCHEME) != '') return $rel;

        /* queries and anchors */
        if ($rel[0]=='#' || $rel[0]=='?') return $base.$rel;

        /* parse base URL and convert to local variables:
           $scheme, $host, $path */
        extract(parse_url($base));

        /* remove non-directory element from path */
        $path = preg_replace('#/[^/]*$#', '', $path);

        /* destroy path if relative url points to root */
        if ($rel[0] == '/') $path = '';

        /* dirty absolute URL */
        $abs = "$path/$rel";

        /* replace '//' or '/./' or '/foo/../' with '/' */
        $re = array('#(/\.?/)#', '#/(?!\.\.)[^/]+/\.\./#');
        for($n=1; $n>0; $abs=preg_replace($re, '/', $abs, -1, $n)) {}

        /* absolute URL is ready! */
        return $abs;
    }
}

