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
 * @category   Pimcore
 * @package    Document
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

interface Document_Interface extends Element_Interface {

    /**
     * @param string $path
     * @return Document
     */
    public static function getByPath($path);

    /**
     * @param string $id
     * @return Document|Document_Page|Document_Folder|Document_Snippet|Document_Link
     */
    public static function getConcreteById($id);

    /**
     * @param string $path
     * @return Document|Document_Page|Document_Folder|Document_Snippet|Document_Link
     */
    public static function getConcreteByPath($path);


    /**
     * @return void
     */
    public function save();

    /**
     * @return void
     */
    public function delete();
}
