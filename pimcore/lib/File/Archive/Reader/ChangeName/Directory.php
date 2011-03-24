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
 * @version    CVS: $Id: Directory.php,v 1.1 2005/08/29 15:14:09 vincentlascaux Exp $
 * @link       http://pear.php.net/package/File_Archive
 */

require_once "File/Archive/Reader/ChangeName.php";

/**
 * Change a directory name to another
 *
 * Example:
 *  If archive.tar is a file archive containing files a.txt and foo/b.txt
 *  new File_Archive_Reader_ChangeName_Directory('foo', 'bar'
 *     new File_Archive_Reader_Tar(
 *         new File_Archive_Reader_File('archive.tar')
 *     )
 *  ) is a reader containing files a.txt and bar/b.txt
 */
class File_Archive_Reader_ChangeName_Directory extends File_Archive_Reader_ChangeName
{
    var $oldBaseName;
    var $newBaseName;

    function File_Archive_Reader_ChangeName_Directory
                        ($oldBaseName, $newBaseName, &$source)
    {
        parent::File_Archive_Reader_ChangeName($source);
        $this->oldBaseName = $this->getStandardURL($oldBaseName);
        if (substr($this->oldBaseName, -1) == '/') {
            $this->oldBaseName = substr($this->oldBaseName, 0, -1);
        }

        $this->newBaseName = $this->getStandardURL($newBaseName);
        if (substr($this->newBaseName, -1) == '/') {
            $this->newBaseName = substr($this->newBaseName, 0, -1);
        }
    }

    function modifyName($name)
    {
        if (empty($this->oldBaseName) ||
          !strncmp($name, $this->oldBaseName.'/', strlen($this->oldBaseName)+1) ||
           strcmp($name, $this->oldBaseName) == 0) {
            return $this->newBaseName.
                   (
                    empty($this->newBaseName) ||
                    strlen($name)<=strlen($this->oldBaseName)+1 ?
                    '' : '/'
                   ).
                   substr($name, strlen($this->oldBaseName)+1);
        } else {
            return $name;
        }
    }
    function unmodifyName($name)
    {
        if (empty($this->newBaseName) ||
          !strncmp($name, $this->newBaseName.'/', strlen($this->newBaseName)+1) ||
           strcmp($name, $this->newBaseName) == 0) {
            return $this->oldBaseName.
                   (
                    empty($this->oldBaseName) ||
                    strlen($name)<=strlen($this->newBaseName)+1 ?
                    '' : '/'
                   ).
                   substr($name, strlen($this->newBaseName)+1);
        } else {
            return $name;
        }
    }
}

?>