<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

/**
 * @return array<string, mixed>
 */
function xmlToArray(string $file): array
{
    $xml = simplexml_load_file($file, null, LIBXML_NOCDATA);
    $json = json_encode((array) $xml);
    $array = json_decode($json, true);

    return $array;
}

function gzcompressfile(string $source, int $level = null, string $target = null): false|string
{
    // this is a very memory efficient way of gzipping files
    if ($target) {
        $dest = $target;
    } else {
        $dest = $source.'.gz';
    }

    $mode = 'wb' . $level;
    $error = false;
    if ($fp_out = gzopen($dest, $mode)) {
        if ($fp_in = fopen($source, 'rb')) {
            while (!feof($fp_in)) {
                gzwrite($fp_out, fread($fp_in, 1024 * 512));
            }
            fclose($fp_in);
        } else {
            $error = true;
        }
        gzclose($fp_out);
    } else {
        $error = true;
    }
    if ($error) {
        return false;
    } else {
        return $dest;
    }
}

function is_json(mixed $string): bool
{
    if (is_string($string)) {
        json_decode($string);

        return json_last_error() == JSON_ERROR_NONE;
    }

    return false;
}

function foldersize(string $path): int
{
    $total_size = 0;
    $files = scandir($path);
    $cleanPath = rtrim($path, '/'). '/';

    foreach ($files as $t) {
        if ($t != '.' && $t != '..') {
            $currentFile = $cleanPath . $t;
            if (is_dir($currentFile)) {
                $size = foldersize($currentFile);
                $total_size += $size;
            } else {
                $size = filesize($currentFile);
                $total_size += $size;
            }
        }
    }

    return $total_size;
}

/**
 * @param string[] $values
 *
 */
function replace_pcre_backreferences(string $string, array $values): string
{
    array_unshift($values, '');
    $string = str_replace('\$', '###PCRE_PLACEHOLDER###', $string);

    foreach ($values as $key => $value) {
        $string = str_replace('$'.$key, $value, $string);
    }

    $string = str_replace('###URLENCODE_PLACEHOLDER###', '$', $string);

    return $string;
}

/**
 * @param mixed[] $array
 *
 * @return mixed[]
 */
function array_htmlspecialchars(array $array): array
{
    foreach ($array as $key => $value) {
        if (is_string($value) || is_numeric($value)) {
            $array[$key] = htmlspecialchars($value, ENT_COMPAT, 'UTF-8');
        } else {
            if (is_array($value)) {
                $array[$key] = array_htmlspecialchars($value);
            }
        }
    }

    return $array;
}

function in_arrayi(string $needle, array $haystack): bool
{
    return in_array(strtolower($needle), array_map('strtolower', $haystack));
}

/**
 *
 * @return false|int|string the key for needle if it is found in the array, false otherwise.
 */
function array_searchi(string $needle, array $haystack): false|int|string
{
    return array_search(strtolower($needle), array_map('strtolower', $haystack));
}

/**
 * @return array<string, mixed>
 */
function object2array(object $node): array
{
    // dirty hack, should be replaced
    $paj = json_encode($node);

    if (JSON_ERROR_NONE !== json_last_error()) {
        throw new \InvalidArgumentException(json_last_error_msg());
    }

    return @json_decode($paj, true);
}

function array_urlencode(array $args): string
{
    return http_build_query($args);
}

/**
 * same as array_urlencode but no urlencode()
 */
function array_toquerystring(array $args): string
{
    return urldecode(http_build_query($args));
}

/**
 * @param array $array with attribute names as keys, and values as values
 *
 */
function array_to_html_attribute_string(array $array): string
{
    $data = [];

    foreach ($array as $key => $value) {
        if (is_scalar($value)) {
            $data[] = $key . '="' . htmlspecialchars($value) . '"';
        } elseif (is_string($key) && is_null($value)) {
            $data[] = $key;
        }
    }

    return implode(' ', $data);
}

function urlencode_ignore_slash(string $var): string
{
    $scheme = parse_url($var, PHP_URL_SCHEME);

    if ($scheme) {
        $var = str_replace($scheme . '://', '', $var);
    }

    $placeholder = 'x-X-x-ignore-' . md5(microtime()) . '-slash-x-X-x';
    $var = str_replace('/', $placeholder, $var);
    $var = rawurlencode($var);
    $var = str_replace($placeholder, '/', $var);

    if ($scheme) {
        $var = $scheme . '://' . $var;
    }

    // allow @2x for retina thumbnails, ...
    $var = preg_replace("/%40([\d]+)x\./", '@$1x.', $var);

    return $var;
}

function return_bytes(string $val): int
{
    $val = trim($val);
    $last = strtolower($val[strlen($val) - 1]);
    $bytes = (int)$val;
    switch ($last) {
        case 'g':
            $bytes *= 1024;
            // no break
        case 'm':
            $bytes *= 1024;
            // no break
        case 'k':
            $bytes *= 1024;
    }

    return $bytes;
}

function formatBytes(int $bytes, int $precision = 2): string
{
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];

    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1000));
    $pow = min($pow, count($units) - 1);

    $bytes /= pow(1000, $pow);

    return round($bytes, $precision) . ' ' . $units[$pow];
}

function filesize2bytes(string $str): int
{
    $bytes_array = [
        'K' => 1024,
        'M' => 1024 * 1024,
        'G' => 1024 * 1024 * 1024,
        'T' => 1024 * 1024 * 1024 * 1024,
        'P' => 1024 * 1024 * 1024 * 1024 * 1024,
    ];

    $bytes = (float)$str;

    if (preg_match('#([KMGTP])?B?$#si', $str, $matches) && (array_key_exists(1, $matches) && !empty($bytes_array[$matches[1]]))) {
        $bytes *= $bytes_array[$matches[1]];
    }

    $bytes = (int)round($bytes, 2);

    return $bytes;
}

/**
 * @param string[] $data
 *
 * @return string[]
 */
function rscandir(string $base = '', array &$data = []): array
{
    if (substr($base, -1, 1) != DIRECTORY_SEPARATOR) { //add trailing slash if it doesn't exists
        $base .= DIRECTORY_SEPARATOR;
    }

    $array = array_diff(scandir($base), ['.', '..', '.svn']);
    foreach ($array as $value) {
        if (is_dir($base . $value)) {
            $data[] = $base . $value . DIRECTORY_SEPARATOR;
            $data = rscandir($base . $value . DIRECTORY_SEPARATOR, $data);
        } elseif (is_file($base . $value)) {
            $data[] = $base . $value;
        }
    }

    return $data;
}

/**
 * Wrapper for explode() to get a trimmed array
 *
 *
 * @return string[]
 *
 * @phpstan-param non-empty-string $delimiter
 */
function explode_and_trim(string $delimiter, string $string, int $limit = PHP_INT_MAX, bool $useArrayFilter = true): array
{
    $exploded = explode($delimiter, $string, $limit);
    foreach ($exploded as $key => $value) {
        $exploded[$key] = trim($value);
    }
    if ($useArrayFilter) {
        $exploded = array_filter($exploded);
    }

    return $exploded;
}

function recursiveDelete(string $directory, bool $empty = true): bool
{
    if (is_dir($directory)) {
        $directory = rtrim($directory, '/');

        if (!file_exists($directory) || !is_dir($directory)) {
            return false;
        } elseif (!is_readable($directory)) {
            return false;
        } else {
            $directoryHandle = opendir($directory);
            $contents = '.';

            while ($contents) {
                $contents = readdir($directoryHandle);
                if (strlen($contents) && $contents != '.' && $contents != '..') {
                    $path = $directory . '/' . $contents;

                    if (is_dir($path)) {
                        recursiveDelete($path);
                    } else {
                        unlink($path);
                    }
                }
            }

            closedir($directoryHandle);

            if ($empty == true) {
                if (!rmdir($directory)) {
                    return false;
                }
            }

            return true;
        }
    } elseif (is_file($directory)) {
        return unlink($directory);
    }

    return false;
}

function p_r(): void
{
    $cloner = new \Symfony\Component\VarDumper\Cloner\VarCloner();
    $dumper = 'cli' === PHP_SAPI ? new \Symfony\Component\VarDumper\Dumper\CliDumper() : new \Symfony\Component\VarDumper\Dumper\HtmlDumper();

    foreach (func_get_args() as $var) {
        $dumper->dump($cloner->cloneVar($var));
    }
}

/**
 * @param string[] $array
 *
 * @return string[]
 */
function wrapArrayElements(array $array, string $prefix = "'", string $suffix = "'"): array
{
    foreach ($array as $key => $value) {
        $array[$key] = $prefix . trim($value). $suffix;
    }

    return $array;
}

/**
 * Checks if an array is associative
 *
 *
 */
function isAssocArray(array $arr): bool
{
    return array_keys($arr) !== range(0, count($arr) - 1);
}

/**
 * this is an alternative for realpath() which isn't able to handle symlinks correctly
 *
 *
 */
function resolvePath(string $filename): string
{
    $protocol = '';
    if (!stream_is_local($filename)) {
        $protocol = parse_url($filename, PHP_URL_SCHEME) . '://';
        $filename = str_replace($protocol, '', $filename);
    }

    $filename = str_replace('//', '/', $filename);
    $parts = explode('/', $filename);
    $out = [];
    foreach ($parts as $part) {
        if ($part == '.') {
            continue;
        }
        if ($part == '..') {
            array_pop($out);

            continue;
        }
        $out[] = $part;
    }

    $finalPath = $protocol . implode('/', $out);

    return $finalPath;
}

function closureHash(Closure $closure): string
{
    $ref = new ReflectionFunction($closure);
    $file = new SplFileObject($ref->getFileName());
    $file->seek($ref->getStartLine() - 1);
    $content = '';
    while ($file->key() < $ref->getEndLine()) {
        $content .= $file->current();
        $file->next();
    }

    $hash = md5(json_encode([
        $content,
        $ref->getStaticVariables(),
    ]));

    return $hash;
}

/**
 * Checks if the given directory is empty
 *
 *
 */
function is_dir_empty(string $dir): ?bool
{
    if (!is_readable($dir)) {
        return null;
    }
    $handle = opendir($dir);
    while (false !== ($entry = readdir($handle))) {
        if ($entry != '.' && $entry != '..') {
            return false;
        }
    }

    return true;
}

function var_export_pretty(mixed $var, string $indent = ''): string
{
    switch (gettype($var)) {
        case 'string':
            return '"' . addcslashes($var, "\\\$\"\r\n\t\v\f") . '"';
        case 'array':
            $indexed = array_keys($var) === range(0, count($var) - 1);
            $r = [];
            foreach ($var as $key => $value) {
                $r[] = "$indent    "
                    . ($indexed ? '' : var_export_pretty($key) . ' => ')
                    . var_export_pretty($value, "$indent    ");
            }

            return "[\n" . implode(",\n", $r) . "\n" . $indent . ']';
        case 'boolean':
            return $var ? 'TRUE' : 'FALSE';
        default:
            return var_export($var, true);
    }
}

function to_php_data_file_format(mixed $contents, ?string $comments = null): string
{
    $contents = var_export_pretty($contents);

    $export = '<?php';

    if (!empty($comments)) {
        $export .= "\n\n";
        $export .= $comments;
        $export .= "\n";
    }

    $export .= "\n\nreturn ".$contents.";\n";

    return $export;
}

function generateRandomSymfonySecret(): string
{
    return base64_encode(random_bytes(24));
}

function implode_recursive(array $array, string $glue): string
{
    $ret = '';

    foreach ($array as $item) {
        if (is_array($item)) {
            $ret .= implode_recursive($item, $glue) . $glue;
        } else {
            $ret .= $item . $glue;
        }
    }

    $ret = substr($ret, 0, 0 - strlen($glue));

    return $ret;
}
