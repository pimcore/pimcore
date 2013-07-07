<?php
class GitTestsHelper
{
    public static function rmdir($dir) 
    {
        if (!file_exists($dir)) return true;
        if (!is_dir($dir)) return unlink($dir);
        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') continue;
            if (!self::rmdir($dir.DIRECTORY_SEPARATOR.$item)) return false;
        }
        return rmdir($dir);
    }

    /**
     * Get relative date
     *
     * @param int $timestamp Timestamp to us as pin-point
     * @param string $type Whether 'fulldate' or 'time'
     */
    public static function getRelativeDate($timestamp, $type = 'fulldate')
    {
        // calculate the diffrence
        $timediff = time () - $timestamp ;

        if ($timediff < 3600) {
            if ($timediff < 120) {
                $returndate = "1 minute ago";
            } else {
                $returndate =  ceil($timediff / 60) . " minutes ago";
            }
        } else if ($timediff < 7200) {
            $returndate = "1 hour ago.";
        } else if ($timediff < 86400) {
            $returndate = ceil($timediff / 3600) . " hours ago";
        } else if ($timediff < 172800) {
            $returndate = "1 day ago.";
        } else if ($timediff < 604800) {
            $returndate = ceil($timediff / 86400) . " days ago";
        } else if ($timediff < 1209600) {
            $returndate = "1 week ago.";
            $returndate = ceil($timediff / 86400) . " days ago";
        } else if ($timediff < 2629744) {
            $returndate = ceil($timediff / 86400) . " days ago";
        } else if ($timediff < 3024000) {
            $returndate = ceil($timediff / 604900) . " weeks ago";
        } else if ($timediff > 5259486) {
            $returndate = ceil($timediff / 2629744) . " months ago";
        } else {
            $returndate = ceil($timediff / 604900) . " weeks ago";
        }

        return $returndate;
    }

}
