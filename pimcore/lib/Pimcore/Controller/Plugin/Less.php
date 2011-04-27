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

require_once 'Zend/Controller/Plugin/Abstract.php';
require_once 'simple_html_dom.php';
require_once 'lessc.inc.php';

class Pimcore_Controller_Plugin_Less extends Zend_Controller_Plugin_Abstract {

    protected $enabled = true;
    protected $conf;

    public function routeStartup(Zend_Controller_Request_Abstract $request) {

        $this->conf = Zend_Registry::get("pimcore_config_system");
        if (!$this->conf->outputfilters) {
            return $this->disable();
        }

        if (!$this->conf->outputfilters->less) {
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

            if($this->getRequest()->getParam("pimcore_editmode")) {
                $this->editmode();
            } else {
                $this->frontend();
            }
        }
    }

    protected function frontend () {

        $body = $this->getResponse()->getBody();

        $html = str_get_html($body);
        $styles = $html->find("link[rel=stylesheet/less]");

        $stylesheetContents = array();
        $processedPaths = array();

        foreach ($styles as $style) {

            $media = $style->media;
            if(!$media) {
                $media = "all";
            }

            $source = $style->href;
            $path = "";
            if (is_file(PIMCORE_ASSET_DIRECTORY . $source)) {
                $path = PIMCORE_ASSET_DIRECTORY . $source;
            }
            else if (is_file(PIMCORE_DOCUMENT_ROOT . $source)) {
                $path = PIMCORE_DOCUMENT_ROOT . $source;
            }

            // add the same file only one time
            if(in_array($path, $processedPaths)) {
                continue;
            }

            $compiledContent = "";

            if (is_file("file://".$path)) {

                // use the original less compiler if configured
                if($this->conf->outputfilters->lesscpath) {
                    $output = array();
                    exec($this->conf->outputfilters->lesscpath . " " . $path, $output);
                    $compiledContent = implode($output);

                    // add a comment to the css so that we know it's compiled by lessc
                    $compiledContent = "\n\n/**** compiled with lessc (node.js) ****/\n\n" . $compiledContent;
                }

                // use php implementation of lessc if it doesn't work
                if(empty($compiledContent)) {
                    $less = new lessc();
                    $less->importDir = dirname($path);
                    $compiledContent = $less->parse(file_get_contents($path));

                    // add a comment to the css so that we know it's compiled by lessphp
                    $compiledContent = "\n\n/**** compiled with lessphp ****/\n\n" . $compiledContent;
                }

                // correct references inside the css
                $compiledContent = $this->correctReferences($source, $compiledContent);

                $stylesheetContents[$media] .= $compiledContent . "\n";
                $style->outertext = "";

                $processedPaths[] = $path;
            }
        }

        // put compiled contents into single files, grouped by their media type
        if(count($stylesheetContents) > 0) {
            $head = $html->find("head",0);
            foreach ($stylesheetContents as $media => $content) {
                $stylesheetPath = PIMCORE_TEMPORARY_DIRECTORY."/less_".md5($content).".css";

                if(!is_file($stylesheetPath)) {
                    file_put_contents($stylesheetPath, $content);
                }

                $head->innertext = $head->innertext . "\n" . '<link rel="stylesheet" media="' . $media . '" type="text/css" href="' . str_replace(PIMCORE_DOCUMENT_ROOT,"",$stylesheetPath) . '" />'."\n";
            }
        }

        $body = $html->save();
        $this->getResponse()->setBody($body);
    }

    protected function editmode () {
        $body = $this->getResponse()->getBody();

        $html = str_get_html($body);
        $head = $html->find("head",0);
        $head->innertext = $head->innertext . "\n" . '<script type="text/javascript" src="/pimcore/static/js/lib/less.js" />'."\n";

        $body = $html->save();
        $this->getResponse()->setBody($body);
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

