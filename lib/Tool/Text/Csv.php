<?php
declare(strict_types=1);

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

// this is a port / excerpt of: CSV Reader By Luke Visinoni which isn't maintained anymore

namespace Pimcore\Tool\Text;

use Exception;
use stdClass;

/**
 * @internal
 */
class Csv
{
    /**
     * @throws Exception
     */
    public function detect(string $data): stdClass
    {
        $linefeed = $this->guessLinefeed($data);
        $data = rtrim($data, $linefeed);
        $count = count(explode($linefeed, $data));
        // threshold is ten, so add one to account for extra linefeed that is supposed to be at the end
        if ($count < 10) {
            throw new Exception('You must provide at least ten lines in your sample data');
        }
        [$quote, $delim] = $this->guessQuoteAndDelim($data);
        if (!$quote) {
            $quote = '"';
        }

        if (is_null($delim)) {
            if (!$delim = $this->guessDelim($data, $linefeed, $quote)) {
                throw new Exception('Unable to determine the file\'s dialect.');
            }
        }

        $dialect = new stdClass();
        $dialect->quotechar = $quote;
        $dialect->delimiter = $delim;
        $dialect->lineterminator = $linefeed;
        $dialect->escapechar = '\\';

        return $dialect;
    }

    /**
     * @phpstan-return non-empty-string
     */
    protected function guessLinefeed(string $data): string
    {
        $charcount = count_chars($data);
        $cr = "\r";
        $lf = "\n";

        $count_cr = $charcount[ord($cr)];
        $count_lf = $charcount[ord($lf)];

        if ($count_cr == $count_lf) {
            return "$cr$lf";
        }
        if ($count_cr == 0 && $count_lf > 0) {
            return $lf;
        }
        if ($count_lf == 0 && $count_cr > 0) {
            return $cr;
        }

        // sane default: cr+lf
        return "$cr$lf";
    }

    protected function guessQuoteAndDelim(string $data): array
    {
        $patterns = [];
        $patterns[] = '/([^\w\n"\']) ?(["\']).*?(\2)(\1)/';
        $patterns[] = '/(?:^|\n)(["\']).*?(\1)([^\w\n"\']) ?/'; // dont know if any of the regexes starting here work properly
        $patterns[] = '/([^\w\n"\']) ?(["\']).*?(\2)(?:^|\n)/';
        $patterns[] = '/(?:^|\n)(["\']).*?(\2)(?:$|\n)/';

        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $data, $matches)) {
                break;
            }
        }

        if (!$matches) {
            return ['', null];
        } // couldn't guess quote or delim

        $quotes = array_count_values($matches[2]);
        arsort($quotes);
        $quotes = array_flip($quotes);
        if ($quote = array_shift($quotes)) {
            $delims = array_count_values($matches[1]);
            arsort($delims);
            $delims = array_flip($delims);
            $delim = array_shift($delims);
        } else {
            $quote = '';
            $delim = null;
        }

        return [$quote, $delim];
    }

    /**
     * @phpstan-param non-empty-string $linefeed
     */
    protected function guessDelim(string $data, string $linefeed, string $quotechar): false|string
    {
        $charcount = count_chars($data, 1);

        $filtered = [];
        foreach ($charcount as $char => $count) {
            if ($char == ord($quotechar)) {
                // exclude the quote char
                continue;
            }
            if ($char == ord(' ')) {
                // exclude spaces
                continue;
            }
            if ($char >= ord('a') && $char <= ord('z')) {
                // exclude a-z
                continue;
            }
            if ($char >= ord('A') && $char <= ord('Z')) {
                // exclude A-Z
                continue;
            }
            if ($char >= ord('0') && $char <= ord('9')) {
                // exclude 0-9
                continue;
            }
            if ($char == ord("\n") || $char == ord("\r")) {
                // exclude linefeeds
                continue;
            }
            $filtered[$char] = $count;
        }

        // count every character on every line
        $data = explode($linefeed, $data);
        $tmp = [];
        $linecount = 0;
        foreach ($data as $row) {
            if (empty($row)) {
                continue;
            }

            // count non-empty lines
            $linecount++;

            // do a charcount on this line, but only remember the chars that
            // survived the filtering above
            $frequency = array_intersect_key(count_chars($row, 1), $filtered);

            // store the charcount along with the previous counts
            foreach ($frequency as $char => $count) {
                if (!array_key_exists($char, $tmp)) {
                    $tmp[$char] = [];
                }
                $tmp[$char][] = $count; // this $char appears $count times on this line
            }
        }

        // a potential delimiter must be present on every non-empty line
        foreach ($tmp as $char => $array) {
            if (count($array) < 0.98 * $linecount) {
                // ... so drop any delimiters that aren't
                unset($tmp[$char]);
            }
        }

        foreach ($tmp as $char => $array) {
            // a delimiter is very likely to occur the same amount of times on every line,
            // so drop delimiters that have too much variation in their frequency
            $dev = $this->deviation($array);
            if ($dev > 0.5) { // threshold not scientifically determined or something
                unset($tmp[$char]);

                continue;
            }

            // calculate average number of appearances
            $tmp[$char] = array_sum($tmp[$char]) / count($tmp[$char]);
        }

        // now, prefer the delimiter with the highest average number of appearances
        if (count($tmp) > 0) {
            asort($tmp);
            $keys = array_keys($tmp);
            $lastEl = end($keys);
            $delim = chr($lastEl);
        } else {
            // no potential delimiters remain
            $delim = false;
        }

        return $delim;
    }

    protected function deviation(array $array): float
    {
        $avg = array_sum($array) / count($array);
        $variance = [];
        foreach ($array as $value) {
            $variance[] = pow($value - $avg, 2);
        }
        $deviation = sqrt(array_sum($variance) / count($variance));

        return $deviation;
    }
}
