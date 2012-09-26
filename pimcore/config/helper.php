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



function replace_pcre_backreferences($string, $values) {

    array_unshift($values,"");
    $string = str_replace("\\$","###PCRE_PLACEHOLDER###", $string);

    foreach ($values as $key => $value) {
        $string = str_replace("$".$key, $value, $string);
    }

    $string = str_replace("###URLENCODE_PLACEHOLDER###", "$", $string);

    return $string;
}

/**
 * @param  $array
 * @return array
 */
function array_htmlspecialchars ($array) {
    foreach ($array as $key => $value) {
        if(is_string($value) || is_numeric($value)) {
            $array[$key] = htmlspecialchars($value,ENT_COMPAT,"UTF-8");
        } else {
            if (is_array($value)) {
                $array[$key] = array_htmlspecialchars($value);
            }
        }
    }

    return $array;
}

/**
 * @param  $node
 * @return array
 */
function object2array($node) {
    // dirty hack, should be replaced
    $paj = @Zend_Json::encode($node);
    return @Zend_Json::decode($paj);
}

/**
 * @param  $args
 * @return bool|string
 */
function array_urlencode ($args) {
    if (!is_array($args)) {
        return false;
    }
  $out = '';
  foreach($args as $name => $value)
  {
    if(is_array($value))
    {
        foreach($value as $key => $val) {
            $out .= urlencode($name).'['.urlencode($key).']'.'=';
            $out .= urlencode($val).'&';

        }
    }else{
        $out .= urlencode($name).'=';
        $out .= urlencode($value).'&';
    }
  }
  return substr($out,0,-1); //trim the last & }
}

/**
 * same as  array_urlencode but no urlencode()
 * @param  $args
 * @return bool|string
 */
function array_toquerystring ($args) {
    if (!is_array($args)) {
        return false;
    }
    $out = '';
    foreach($args as $name => $value)
    {
        if(is_array($value))
        {
            foreach($value as $key => $val) {
                $out .= $name.'['.$key.']'.'=';
                $out .= $val.'&';

            }
        }else{
            $out .= $name.'=';
            $out .= $value.'&';
        }
    }
    return substr($out,0,-1); //trim the last & }
}

/**
 * @param string $var
 */
function urlencode_ignore_slash($var) {
    $placeholder = "x-X-x-ignore-" . md5(microtime()) . "-slash-x-X-x";
    $var = str_replace("/", $placeholder, $var);
    $var = urlencode($var);
    $var = str_replace($placeholder, "/", $var);

    return $var;
}

/**
 * @depricated
 * @param  $filename
 * @return bool
 */
function is_includeable($filename) {
    return Pimcore_File::isIncludeable($filename);
}

/**
 * @param  $val
 * @return int|string
 */
function return_bytes($val) {
    $val = trim($val);
    $last = strtolower($val[strlen($val) - 1]);
    switch ($last) {
        case 'g':
            $val *= 1024;
        case 'm':
            $val *= 1024;
        case 'k':
            $val *= 1024;
    }

    return $val;
}


/**
 * @param  $bytes
 * @param int $precision
 * @return string
 */
function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');

    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);

    $bytes /= pow(1024, $pow);

    return round($bytes, $precision) . ' ' . $units[$pow];
}


/**
 * @param  $str
 * @return float|int
 */
function filesize2bytes($str) {
    $bytes = 0;

    $bytes_array = array(
        'B' => 1,
        'KB' => 1024,
        'MB' => 1024 * 1024,
        'GB' => 1024 * 1024 * 1024,
        'TB' => 1024 * 1024 * 1024 * 1024,
        'PB' => 1024 * 1024 * 1024 * 1024 * 1024,
    );

    $bytes = floatval($str);

    if (preg_match('#([KMGTP]?B)$#si', $str, $matches) && !empty($bytes_array[$matches[1]])) {
        $bytes *= $bytes_array[$matches[1]];
    }

    $bytes = intval(round($bytes, 2));

    return $bytes;
}

/**
 * @param string $base
 * @param array $data
 * @return array
 */
function rscandir($base = '', &$data = array()) {
	
	$array = array_diff(scandir($base), array('.', '..', '.svn'));

    foreach ($array as $value) {
        if (is_dir($base . $value)) {
            $data[] = $base . $value . DIRECTORY_SEPARATOR;
            $data = rscandir($base . $value . DIRECTORY_SEPARATOR, $data);
		}
        elseif (is_file($base . $value)) { 
            $data[] = $base . $value;
		}
    }
    return $data;
}


/**
 * Recursively delete a directory
 *
 * @param string $dir Directory name
 * @param boolean $deleteRootToo Delete specified top-level directory as well
 */
function recursiveDelete ($directory, $empty = true) { 

    if(substr($directory,-1) == "/") { 
        $directory = substr($directory,0,-1); 
    } 

    if(!file_exists($directory) || !is_dir($directory)) { 
        return false; 
    } elseif(!is_readable($directory)) { 
        return false; 
    } else { 
        $directoryHandle = opendir($directory); 
        
        while ($contents = readdir($directoryHandle)) { 
            if($contents != '.' && $contents != '..') { 
                $path = $directory . "/" . $contents; 
                
                if(is_dir($path)) { 
                    recursiveDelete($path); 
                } else { 
                    unlink($path); 
                } 
            } 
        } 
        
        closedir($directoryHandle); 

        if($empty == true) { 
            if(!rmdir($directory)) {
                return false; 
            } 
        } 
        
        return true; 
    } 
}

/**
 * @param  $var
 * @return void
 */
function p_r($var) {
    echo "<pre>";
    print_r($var);
    echo "</pre>";
}

/**
 * @param  $errno
 * @param  $errstr
 * @param  $errfile
 * @param  $errline
 * @param  $errcontext
 * @return bool
 */
function pimcore_error_handler($errno, $errstr, $errfile, $errline, $errcontext) {

    //Log::info($errno . " | " . $errstr . " in " . $errfile . " on line: " .$errline );

    // enable php internal error handling
    return false;
}
