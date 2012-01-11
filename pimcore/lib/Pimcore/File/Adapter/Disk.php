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
 * @package    File
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Pimcore_File_Adapter_Disk extends Pimcore_File_Adapter {

	/**
	 * @access public
	 * @param Pimcore_File
	 * @return bool Returns TRUE on success or FALSE on failure.
	 */
	public function canRead($file)
	{
		return is_readable($file->getPath());
	}


	/**
	 * @access public
	 * @param Pimcore_File
	 * @return bool Returns TRUE on success or FALSE on failure.
	 */
	public function canWrite($file)
	{
		return is_writable($file->getPath());
	}


	/**
	 * Makes a copy of the file to destination.
	 *
	 * @access public
	 * @param Pimcore_File
	 * @return bool Returns TRUE on success or FALSE on failure.
	 */
	public function copy($file, $destination)
	{
		$source = $file->getPath();
		if (!file_exists($source)) return false;

		if (is_dir($source)) {
			$result = $this->copyRecursive($file, $destination);
		} else {
			$parentPath = dirname($destination);
			if (!is_dir($parentPath)) {
				mkdir($parentPath, $file->getChmod(), true);
			}

			$result = copy($source, $destination);
		}

		return $result;
	}


	/**
	 * Makes a copy of the directory to destination.
	 *
	 * @access public
	 * @param Pimcore_File
	 * @return bool Returns TRUE on success or FALSE on failure.
	 */
	private function copyRecursive($file, $destination)
	{
		$source = $file->getPath();
		$sourceHandle = opendir($source);
		$result = TRUE;

		if (!is_dir($destination)) {
			$result = mkdir($destination, $file->getChmod(), true);
		}

		while ($next = readdir($sourceHandle)) {
			if ($next == '.' || $next == '..') {
				continue;
			}

			$nextSourcePath = $source . '/' . $next;
			$nextDestPath = $destination . '/' . $next;

			if (is_dir($nextSourcePath)) {
				$newSource = new Pimcore_File_Directory($nextSourcePath);
				$result && $this->copyRecursive($newSource, $nextDestPath);
			} else {
				$result = $result && copy($nextSourcePath, $nextDestPath);
				chmod($nextDestPath, fileperms($nextSourcePath));
			}
		}

		return $result;
	}


	/**
	 * Deletes the file or directory from the filesystem. Directories
	 * are deleted recursively.
	 *
	 * @access public
	 * @param Pimcore_File
	 * @return bool Returns TRUE on success or FALSE on failure.
	 */
	public function delete($file)
	{
		$path = $file->getPath();

		if (!file_exists($path) || !is_writable($path)) return FALSE;

		if (is_file($path)) {
			$result = @unlink($path);
		} elseif (is_dir($path)) {
			$result = recursiveDelete($path, true);
		}

		return $result;
	}


	/**
	 * Tells whether a folder exists at the object's path.
	 *
	 * @access public
	 * @param Pimcore_File
	 * @return bool Returns TRUE on success or FALSE on failure.
	 */
	public function isDir($file)
	{
		return is_dir($file->getPath());
	}


	/**
	 * Tells whether a file exists at the object's path.
	 *
	 * @access public
	 * @param Pimcore_File
	 * @return bool Returns TRUE on success or FALSE on failure.
	 */
	public function isFile($file)
	{
		return is_file($file->getPath());
	}


	/**
	 * Tells whether a file or directory exists at the object's path.
	 *
	 * @access public
	 * @param Pimcore_File
	 * @return bool Returns TRUE on success or FALSE on failure.
	 */
	public function exists($file)
	{
		return file_exists($file->getPath());
	}


	/**
	 * Detects and returns the file's mime type.
	 *
	 * @access public
	 * @param Pimcore_File
	 * @return string The mime type of the file.
	 */
	public function getMimeType($file)
	{
		return MIME_Type::autoDetect($file->getPath());
	}


	/**
	 * Loads the file or directory contents from the filesystem.
	 *
	 * @access public
	 * @param mixed Pimcore_File
	 * @return string|array The contents of the file or an array of files in the directory.
	 */
	public function load($file)
	{
		$path = $file->getPath();

		if (!is_file($path) or !is_readable($path)) {
			return FALSE;
		} elseif (is_dir($path)) {
			return scandir($file->getPath());
		} elseif (is_file($path)) {
			return file_get_contents($file->getPath());
		}
	}


	/**
	 * Creates a directory at $file's path.
	 *
	 * @access public
	 * @param mixed $file
	 * @return void
	 */
	public function mkdir($file)
	{
		$path = $file->getPath();

		if (is_dir($path)) {
			$result = TRUE;
		} elseif (is_file($path)) {
			$result = FALSE;
		} else {
			$result = mkdir($path, $file->getChmod(), TRUE);
			
			if ($result !== FALSE) {
				chmod($path, $file->getChmod());
			}
		}

		return $result;
	}


	/**
	 * Moves the file to $destination. Overwrites $destination if it exists.
	 *
	 * @access public
	 * @return bool Returns TRUE on success or FALSE on failure.
	 */
	public function move($file, $destination)
	{
		$source = $file->getPath();
		if (!file_exists($source)) return FALSE;

		$parentPath = dirname($destination);

		// create the parent folder if it doesn't exist
		if (!is_dir($parentPath)) {
			$dirExists = mkdir($parentPath, $file->getChmod(), TRUE);
		} else {
			$dirExists = TRUE;
		}

		if ($dirExists === TRUE) {
			if($file->isDir() && !is_dir($destination)) {
				$result = FALSE;
			} else {
				$result = rename($source, $destination);
				$file->setOldPath($source);
				$file->setPath($destination);
			}
		} else {
			$result = FALSE;
		}

		return $result;
	}


	/**
	 * Writes the file to the filesystem.
	 *
	 * @access public
	 * @param mixed Pimcore_File $file
	 * @param string|array The data to write. Can be either a string, an array, or a stream resource.
	 * @return bool|int The number of bytes that were written to the file, or FALSE on failure.
	 */
	public function save($file, $data = '')
	{
		// if this is a directory, save means mkdir
		if ($file->isDirectoryType()) {
			return $this->mkdir($file);
		}

		$path = $file->getPath();
		$parentPath = dirname($path);

		// create the parent folder if it doesn't exist
		if (!is_dir($parentPath)) {
			$result = mkdir($parentPath, $file->getChmod(), true);
		}

		// check if file and directory are writeable, if so save the file
		if (!is_writable($parentPath) || (is_file($path) && !is_writable($path))) {
			$result = FALSE;
		} elseif (is_dir($path)) {
			$result = FALSE;
		} else {
			$result = file_put_contents($path, $data);
		}

		if ($result !== FALSE) {
			chmod($path, $file->getChmod());
		}

		return $result;
	}


}