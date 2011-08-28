<?php
// $Id: dbtuner.mysqltuner.inc,v 1.1.2.2 2010/05/10 21:20:05 mikeytown2 Exp $

/**
 * @file
 * Port of https://launchpad.net/mysqltuner to Drupal
 */

function dbtuner_mysqltuner_page()
{
    $data = dbtuner_mysqltuner_get_data();
    $myconfig = $data[0];
    $mylist = $data[1];
    $output = '';
    //$output .= str_replace('    ', '&nbsp;&nbsp;&nbsp;&nbsp;', nl2br(htmlentities(print_r($data, TRUE))));
    $output .= dbtuner_mysqltuner_process_data($myconfig, $mylist);
    return $output;
}

function dbtuner_mysqltuner_get_data()
{
    $config_file = 'tuner-default.cnf';

    // Query db for info
    $mylist = array();

    $db = Pimcore_Resource::get();
    $variables = $db->fetchAll("SHOW /*!40003 GLOBAL */ VARIABLES");
    $status = $db->fetchAll("SHOW /*!50002 GLOBAL */ STATUS");

    foreach ($variables as $row) {
        $mylist[$row['Variable_name']] = $row['Value'];
    }

    foreach ($status as $row) {
        $mylist[$row['Variable_name']] = $row['Value'];
    }

    // Get configuration
    $myconfig = file($config_file);
    return array($myconfig, $mylist);
}

function dbtuner_mysqltuner_process_data($myconfig, $mylist)
{
    $output = '';
    foreach ($myconfig as $num => $line) {
        // Skip first 7 lines of config file; comments
        if ($num < 6) {
            continue;
        }

        // Parse comments out
        $line = explode('#', trim($line));
        $comment = '';
        if (!empty($line[1])) {
            $comment = $line[1];
        }
        $line = $line[0];

        // Parse config line
        $config = explode('|||', $line);
        if (count($config) != 4) {
            $output .= '<br /><h4>' . $comment . '</h4>';
            continue;
        }

        // Assign variables
        $label = $config[0];
        $comp = $config[1];
        $expr = $config[2];
        $msg = $config[3];

        // Token Replacement
        $words = preg_split("/\b/", $expr);
        $parsedexpr = '';
        foreach ($words as $key => $value) {
            if (isset($mylist[$value])) {
                $parsedexpr .= $mylist[$value];
            }
            else {
                $parsedexpr .= $value;
            }
        }

        // Function Replacement
        $parsedexpr = dbtuner_perl_to_php_func($parsedexpr);
        // Eval code
        eval('$compval = ' . $parsedexpr . ';');
        $parsedcomp = dbtuner_perl_to_php_comp($compval . $comp);
        eval('$condition = ' . $parsedcomp . ';');

        // Output results
        $output .= $label . ': ' . $compval . '<br \>';
        if ($condition) {
            $output .= '<strong>' . $msg . '</strong><br \><em>' . '&nbsp;&nbsp;&nbsp;&nbsp;(' . $expr . ' ' . $comp . ')' . '<br \>&nbsp;&nbsp;&nbsp;&nbsp;(' . $parsedexpr . ' ' . $parsedcomp . ')' . '</em><br \>';
        }

        // Debug Output
        //~ $output .= $expr . '<br \>';
        //~ $output .= str_replace('    ', '&nbsp;&nbsp;&nbsp;&nbsp;', nl2br(htmlentities(print_r($parsedexpr, TRUE)))) . '<br \>';
        //~ $output .= $compval . '<br \>';
        //~ $output .= $comp . '<br \>';
        //~ $output .= $output . '<br \>';
        //~ $output .= '<br \>';
    }
    return $output;
}

function dbtuner_perl_to_php_func($string)
{
    $string = str_replace('&pretty_uptime', 'dbtuner_pretty_uptime', $string);
    $string = str_replace('&hr_bytime', 'dbtuner_hr_bytime', $string);
    $string = str_replace('&hr_num', 'dbtuner_hr_num', $string);
    $string = str_replace('&hr_bytes', 'dbtuner_hr_bytes', $string);
    return $string;
}

function dbtuner_perl_to_php_comp($string)
{
    $string = str_replace('eq ', '=== ', $string);
    $string = str_replace('ne ', '!== ', $string);

    if (stristr($string, '=~')) {
        $values = explode('=~', $string);
        if ($values[1] == ' /second|minute/') {
            return "dbtuner_stristr('" . $values[0] . "', array('second', 'minute'))";
        }
        if ($values[1] == ' /source/i') {
            return "dbtuner_stristr('" . $values[0] . "', array('source'))";
        }
        if ($values[1] == ' /percona/i') {
            return "dbtuner_stristr('" . $values[0] . "', array('percona'))";
        }
    }

    if (stristr($string, '!~')) {
        $values = explode('!~', $string);
        if ($values[1] == ' /64/') {
            return "dbtuner_strnistr('" . $values[0] . "', array('64'))";
        }
    }

    if (stristr($string, "=== ''")) {
        $values = explode('=== ', $string);
        return "'" . $values[0] . "' === " . $values[1];
    }
    if (stristr($string, '=== "0 bytes"')) {
        $values = explode('=== ', $string);
        return "'" . $values[0] . "' === " . $values[1];
    }
    if (stristr($string, '=== "1.0 Mb"')) {
        $values = explode('=== ', $string);
        return "'" . $values[0] . "' === " . $values[1];
    }

    if (stristr($string, '!== "5"')) {
        $values = explode('!== ', $string);
        return "'" . $values[0] . "' !== " . $values[1];
    }
    if (stristr($string, '!== "5.1"')) {
        $values = explode('!== ', $string);
        return "'" . $values[0] . "' !== " . $values[1];
    }

    return $string;
}

function dbtuner_stristr($haystack, $arr)
{
    foreach ($arr as $key => $search_needle) {
        if (stristr($haystack, $search_needle) != FALSE) {
            return TRUE;
        }
    }
    return FALSE;
}

function dbtuner_strnistr($haystack, $arr)
{
    foreach ($arr as $key => $search_needle) {
        if (stristr($haystack, $search_needle) != FALSE) {
            return FALSE;
        }
    }
    return TRUE;
}

function dbtuner_pretty_uptime($uptime)
{
    $seconds = $uptime % 60;
    $minutes = floor(($uptime % 3600) / 60);
    $hours = floor(($uptime % 86400) / (3600));
    $days = floor($uptime / (86400));
    $uptimestring;
    if ($days > 0) {
        $uptimestring = "${days}d ${hours}h ${minutes}m ${seconds}s";
    }
    elseif ($hours > 0) {
        $uptimestring = "${hours}h ${minutes}m ${seconds}s";
    }
    elseif ($minutes > 0) {
        $uptimestring = "${minutes}m ${seconds}s";
    }
    else {
        $uptimestring = "${seconds}s";
    }
    return $uptimestring;
}

function dbtuner_hr_bytime($num)
{
    $per = '';
    if ($num >= 1) { # per second
        $per = "per second";
    }
    elseif ($num * 60 >= 1) { # per minute
        $num = $num * 60;
        $per = "per minute";
    }
    elseif ($num * 60 * 60 >= 1) { # per hour
        $num = $num * 60 * 60;
        $per = "per hour";
    }
    else {
        $num = $num * 60 * 60 * 24;
        $per = "per day";
    }
    $num = dbtuner_round2($num);
    return "$num $per";
}

function dbtuner_round2($num)
{
    # if the result is a number with a decimal point, round to the nearest 0.01
    //~ if ($num=~/^-?\d+\.?\d*(e.\d+)?$/ && $expr !~ /version/i) {
    //~ $num=sprintf("%.2f",$num);
    //~ }
    //~ return round($num, 2);
    return $num;
}

function dbtuner_hr_num($num)
{
    if ($num >= (pow(1000, 3))) { # Billions
        return floor(($num / pow(1000, 3))) . " Billion";
    }
    elseif ($num >= pow(1000, 2)) { # Millions
        return floor(($num / pow(1000, 2))) . " Million";
    }
    elseif ($num >= 1000) { # Thousands
        return floor(($num / 1000)) . " Thousand";
    }
    else {
        return $num;
    }
}

function dbtuner_hr_bytes($num)
{
    if ($num >= pow(1024, 3)) { #GB
        return sprintf("%.1f", ($num / pow(1024, 3))) . " Gb";
    }
    elseif ($num >= pow(1024, 2)) { #MB
        return sprintf("%.1f", ($num / pow(1024, 2))) . " Mb";
    }
    elseif ($num >= 1024) { #KB
        return sprintf("%.1f", ($num / 1024)) . " Kb";
    }
    else {
        return $num . " bytes";
    }
}
