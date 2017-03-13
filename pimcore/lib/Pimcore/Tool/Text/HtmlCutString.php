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

// original by:
// Author prajwala
// email  m.prajwala@gmail.com
// Date   12/04/2009
// version 1.0

// adapted for Pimcore

namespace Pimcore\Tool\Text;

class HtmlCutString
{

    /**
     * @param $string
     * @param $limit
     */
    public function __construct($string, $limit)
    {
        // create dom element using the html string
        $this->tempDiv = new \DomDocument;
        $this->tempDiv->loadXML('<div>' . $string . '</div>');
        // keep the characters count till now
        $this->charCount = 0;
        $this->encoding = 'UTF-8';
        // character limit need to check
        $this->limit = $limit;
    }

    /**
     * @return string
     */
    public function cut()
    {
        // create empty document to store new html
        $this->newDiv = new \DomDocument;
        // cut the string by parsing through each element
        $this->searchEnd($this->tempDiv->documentElement, $this->newDiv);
        $newhtml = $this->newDiv->saveHTML();

        return $newhtml;
    }

    /**
     * @param $node
     */
    public function deleteChildren($node)
    {
        while (isset($node->firstChild)) {
            $this->deleteChildren($node->firstChild);
            $node->removeChild($node->firstChild);
        }
    }

    /**
     * @param $parseDiv
     * @param $newParent
     * @return bool
     */
    public function searchEnd($parseDiv, $newParent)
    {
        foreach ($parseDiv->childNodes as $ele) {
            // not text node
            if ($ele->nodeType != 3) {
                $newEle = $this->newDiv->importNode($ele, true);
                if (count($ele->childNodes) === 0) {
                    $newParent->appendChild($newEle);
                    continue;
                }
                $this->deleteChildren($newEle);
                $newParent->appendChild($newEle);
                $res = $this->searchEnd($ele, $newEle);
                if ($res) {
                    return $res;
                } else {
                    continue;
                }
            }

            // the limit of the char count reached
            if (mb_strlen($ele->nodeValue, $this->encoding) + $this->charCount >= $this->limit) {
                $newEle = $this->newDiv->importNode($ele);
                $newEle->nodeValue = substr($newEle->nodeValue, 0, $this->limit - $this->charCount);
                $newParent->appendChild($newEle);

                return true;
            }
            $newEle = $this->newDiv->importNode($ele);
            $newParent->appendChild($newEle);
            $this->charCount += mb_strlen($newEle->nodeValue, $this->encoding);
        }

        return false;
    }
}

/**
 * @param $string
 * @param $limit
 * @return string
 */
function cut_html_string($string, $limit)
{
    $output = new HtmlCutString($string, $limit);

    return $output->cut();
}
