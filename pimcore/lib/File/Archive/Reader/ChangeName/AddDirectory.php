<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Add a directory to the public name of all the files of a reader
 *
 * PHP versions 4 and 5
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330,Boston,MA 02111-1307 USA
 *
 * @category   File Formats
 * @package    File_Archive
 * @author     Vincent Lascaux <vincentlascaux@php.net>
 * @copyright  1997-2005 The PHP Group
 * @license    http://www.gnu.org/copyleft/lesser.html  LGPL
 * @version    CVS: $Id: AddDirectory.php,v 1.1 2005/08/29 15:14:09 vincentlascaux Exp $
 * @link       http://pear.php.net/package/File_Archive
 */

require_once "File/Archive/Reader/ChangeName.php";

/**
 * Add a directory to the public name of all the files of a reader
 *
 * Example:
 *  If archive.tar is a file archive containing files a.txt and foo/b.txt
 *  new File_Archive_Reader_ChangeName_AddDirectory('bar',
 *     new File_Archive_Reader_Tar(
 *         new File_Archive_Reader_File('archive.tar')
 *     )
 *  ) is a reader containing files bar/a.txt and bar/foo/b.txt
 */
class File_Archive_Reader_ChangeName_AddDirectory extends File_Archive_Reader_ChangeName
{
    var $baseName;
    function File_Archive_Reader_ChangeName_AddDirectory($baseName, &$source)
    {
        parent::File_Archive_Reader_ChangeName($source);
        $this->baseName = $this->getStandardURL($baseName);
    }

    /**
     * Modify the name by adding baseName to it
     */
    function modifyName($name)
    {
        return $this->baseName.
               (empty($this->baseName) || empty($name) ? '': '/').
               $name;
    }

    /**
     * Remove baseName from the name
     * Return false if the name doesn't start with baseName
     */
    function unmodifyName($name)
    {
        if (strncmp($name, $this->baseName.'/', strlen($this->baseName)+1) == 0) {
            $res = substr($name, strlen($this->baseName)+1);
            if ($res === false) {
                return '';
            } else {
                return $res;
            }
        } else if (empty($this->baseName)) {
            return $name;
        } else if ($name == $this->baseName) {
            return '';
        } else {
            return false;
        }
    }
}

?>