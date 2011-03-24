<?php
/**
 * AutoDetect component
 * 
 * This class accepts a sample of csv and attempts to deduce its format. It then
 * can return a Csv_Dialect tailored to that particular csv file
 * 
 * Please read the LICENSE file
 * 
 * @package 	PHP CSV Utilities
 * @subpackage  AutoDetect
 * @copyright 	(c) 2010 Luke Visinoni <luke.visinoni@gmail.com>
 * @author 		Luke Visinoni <luke.visinoni@gmail.com>
 * @license 	GNU Lesser General Public License
 * @version 	$Id: AutoDetect.php 81 2010-04-22 02:24:16Z luke.visinoni $
 */

/**
 * Attempts to deduce the format of a csv file
 * 
 * @package Csv
 * @deprecated In favor of just use Csv_Reader::getDialect() and Csv_Reader::detectHasHeader()
 */
class Csv_AutoDetect {

    /**
     * Attempts to deduce the format of a sample of a csv file and returns a dialect object
     * eventually it will throw an exception if it can't deduce the format, but for now it just
     * returns the basic csv dialect
     * 
     * @param string A piece of sample data used to deduce the format of the csv file
     * @return Csv_Dialect A {@link Csv_Dialect} object with the appropriate settings
     * @access protected
     */
    public function detect($data) {

    	$linefeed = $this->guessLinefeed($data);
		$data = rtrim($data, $linefeed);
        $count = count(explode($linefeed, $data));
        // threshold is ten, so add one to account for extra linefeed that is supposed to be at the end
        if ($count < 10) {
			throw new Csv_Exception_CannotDetermineDialect('You must provide at least ten lines in your sample data');
		} else {
		}
        list($quote, $delim) = $this->guessQuoteAndDelim($data);
        if (!$quote) {
        	$quote = '"';
        }
        
        if (is_null($delim)) {
            if (!$delim = $this->guessDelim($data, $linefeed, $quote)) {
                throw new Csv_Exception_CannotDetermineDialect('Csv_AutoDetect was unable to determine the file\'s dialect.');
            }
        }
        
        $dialect = new Csv_Dialect();
        $dialect->quotechar = $quote;
        $dialect->quoting = $this->guessQuotingStyle($data, $quote, $delim, $linefeed);
        $dialect->delimiter = $delim;
        $dialect->lineterminator = $linefeed;
        
        return $dialect;
    
    }
	
    /**
     * Determines if a csv sample has a header row - not 100% accurate by any means
     * It basically looks at each row in each column. If all but the first column are similar, 
     * it likely has a header. The way we determine this is first by type, then by length
     * Other possible methods I could use to determine whether the first row is a header is I
     * could look to see if all but the first CONTAIN certain characters or something - think about this
     */
    public function hasHeader($data) {
    
        $reader = new Csv_Reader_String($data, $this->detect($data));
        list($has_headers, $checked, $types, $lengths, $total_lines, $headers) = array(0, 0, array(), array(), $reader->count(), $reader->getRow());
        
        if ($total_lines <= 2) {
        	// please try again with a a larger file :)
        	return false;
        }
        
        $total_columns = count($headers);
        foreach (range(0, $total_columns - 1) as $key => $col) $types[$col] = null;
        // loop through each remaining rows
        while ($row = $reader->current()) {
            // no need to check more than 20 lines
            if ($checked > 20) break; $checked++;
            $line = $reader->key();
            // loop through row and grab type for each column
            foreach ($row as $col => $val) {
                $types[$col][] = $this->getType($val);
                $lengths[$col][] = strlen($val);
            }
            $reader->next();
        }
        // now take a vote and if more than a certain threshold have a likely header, we'll return that we think it has a header
        foreach ($types as $key => $column) {
            $unique = array_unique($column);
            if (count($unique) == 1) { // if all are of the same type
                if ($unique[0] == $this->getType($headers[$key])) {
                    // all rows type matched header type, so try length now
                    $unique = array_unique($lengths[$key]);
                    if (count($unique) == 1) {
                        if ($unique[0] == strlen($headers[$key])) {
                            $has_headers--;
                        } else {
                            $has_headers++;
                        }
                    }
                    //printf ("%s is the same as %s<br>", $unique[0], $this->getType($headers[$key]));
                } else {
                    $has_headers++;
                }
            }
        }
        return ($has_headers > 0);
    
    }
	
    /**
     * Since the reader returns all strings, this checks the type of the string for comparison
     * against header row in hasHeader()
     *
     * @access protected
     * @param string Value we're trying to detect the type of
     * @return string type of value
     * @todo A better way to do this would be to have Csv_Reader cast values to their correct type
     */
    protected function getType($value) {
    
        switch (true) {
            case ctype_digit($value):
                return "integer";
            case preg_match("/^[array()-9\.]$/i", $value, $matches):
                return "double";
            case ctype_alnum($value):
            default:
                return "string";
        }
    
    }
	
    /**
     * I copied this functionality from python's csv module. Basically, it looks
     * for text enclosed by identical quote characters which are in turn surrounded
     * by identical characters (the probable delimiter). If there is no quotes, the
     * delimiter cannot be determined this way.
     *
     * @param string A piece of sample data used to deduce the format of the csv file
     * @return array An array with the first value being the quote char and the second the delim
     * @access protected
     */
    protected function guessQuoteAndDelim($data) {
    
        $patterns = array();
        $patterns[] = '/([^\w\n"\']) ?(["\']).*?(\2)(\1)/'; 
        $patterns[] = '/(?:^|\n)(["\']).*?(\1)([^\w\n"\']) ?/'; // dont know if any of the regexes starting here work properly
        $patterns[] = '/([^\w\n"\']) ?(["\']).*?(\2)(?:^|\n)/';
        $patterns[] = '/(?:^|\n)(["\']).*?(\2)(?:$|\n)/';
        
        foreach ($patterns as $pattern) {
            if ($nummatches = preg_match_all($pattern, $data, $matches)) {
                if ($matches) break;
            }
        }
        
        if (!$matches) return array("", null); // couldn't guess quote or delim
        
        $quotes = array_count_values($matches[2]);
        arsort($quotes);
        if ($quote = array_shift(array_flip($quotes))) {
            $delims = array_count_values($matches[1]);
            arsort($delims);
            $delim = array_shift(array_flip($delims));
        } else {
            $quote = ""; $delim = null;
        }
        return array($quote, $delim);
    
    }
	
    /**
     * Attempts to guess the delimiter of a set of data
     *
     * @param string The data you would like to get the delimiter of
     * @access protected
     * @return mixed If a delimiter can be found it is returned otherwise false is returned
     * @todo - understand what's going on here (I haven't yet had a chance to really look at it)
     */
    protected function guessDelim($data, $linefeed, $quotechar) {
    
	    $charcount = count_chars($data, 1);
	    
	    $filtered = array();
	    foreach ($charcount as $char => $count) {
	    	if ($char == ord($quotechar)) {
	    		// exclude the quote char
	    		continue;
	    	}
	    	if ($char == ord(" ")) {
	    		// exclude spaces
	    		continue;
	    	}
	    	if ($char >= ord("a") && $char <= ord("z")) {
	    		// exclude a-z
	    		continue;
	    	}
	    	if ($char >= ord("A") && $char <= ord("Z")) {
	    		// exclude A-Z
	    		continue;
	    	}
	    	if ($char >= ord("0") && $char <= ord("9")) {
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
        $tmp = array();
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
	        		$tmp[$char] = array();
	        	}
	        	$tmp[$char][] = $count; // this $char appears $count times on this line
	        }
        }
        
        // a potential delimiter must be present on every non-empty line 
        foreach ($tmp as $char=>$array) {
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
	        $delim = chr(end(array_keys($tmp)));
        } else {
        	// no potential delimiters remain
        	$delim = false;
        }
        
        return $delim;
    
    }
	
    /**
     * @todo - understand what's going on here (I haven't yet had a chance to really look at it)
     */
	protected function deviation ($array){
    
	    $avg = array_sum($array) / count($array);
	    foreach ($array as $value) {
	        $variance[] = pow($value - $avg, 2);
	    }
	    $deviation = sqrt(array_sum($variance) / count($variance));
	    return $deviation;
	
    }
	
    /**
    * Guess what the line feed character is, default to CR/LF
	 * @access protected
	 * @return string The line feed character(s)
	 * @param $data string The raw CSV data
     * @todo - maybe rewrite this? it seems to be not working every time
     */
    protected function guessLinefeed($data) {
    
    	$charcount = count_chars($data);
    	$cr = "\r";
    	$lf = "\n";
    	
    	$count_cr = $charcount[ord($cr)];
    	$count_lf = $charcount[ord($lf)];
        
    	if ($count_cr == $count_lf) {
    		return "$cr$lf";
    	}
    	if ($count_cr == 0 && $count_lf > 0) {
    		return "$lf";
    	}
    	if ($count_lf == 0 && $count_cr > 0) {
    		return "$cr";
    	}
        
    	// sane default: cr+lf
    	return "$cr$lf";
    
    }
	
    /**
     * Guess what the quoting style is, default to none
	 * @access protected
	 * @return integer (quoting style constant qCal_Dialect::QUOTE_NONE)
	 * @param $data string The raw CSV data
	 * @param $quote string The quote character
	 * @param $delim string The delimiter character
	 * @param $linefeed string The line feed character
     */
    protected function guessQuotingStyle($data, $quote, $delim, $linefeed) {
    
    	$dialect = new Csv_Dialect();
    	$dialect->delimiter = $delim;
    	$dialect->quotechar = $quote;
    	$dialect->lineterminator = $linefeed;
        
    	$lines = explode($linefeed, $data);
        
    	$lines_processed = 0;
    	
        $reader = new Csv_Reader_String($data, $dialect);
        $quotingstyle_count = array();
        foreach ($reader as $parsedline) {
        
        	do {
        		// fetch next line until a non-empty line is found
        		$line = array_shift($lines);
        	} while (strlen($line)==0);
            
        	// how many quotes are present in this line?
        	$quote_count = substr_count($line, $quote);
        	
        	// how many quotes are in the data itself?
        	$quotecount_in_data = substr_count(implode("", $parsedline), $quote);
        	
        	// how many columns are in this line?
        	$column_count = count($parsedline);
            
			// default quoting style for this line: QUOTE_NONE
        	$quotingstyle = Csv_Dialect::QUOTE_NONE;
        	
        	// determine this line's quoting style
        	if ($quote_count==0 || $quote_count<=$quotecount_in_data) {
        		// there are no quotes, or there are less quotes than the number of quotes in the data
        		$quotingstyle = Csv_Dialect::QUOTE_NONE;
        	} elseif ($quote_count>=($column_count*2)) {
        		// the number of quotes is larger than, or equal to, the number of quotes 
        		// necessary to quote each column 
        		$quotingstyle = Csv_Dialect::QUOTE_ALL;
        	} elseif ($quote_count>=$quotecount_in_data) {
        		// there are more quotes than the number of quotes in the data
        		$quotingstyle = Csv_Dialect::QUOTE_MINIMAL;
        	}
        	
        	if (!array_key_exists($quotingstyle, $quotingstyle_count)) {
        		$quotingstyle_count[$quotingstyle] = 0;
        	}
        	
        	$quotingstyle_count[$quotingstyle]++;
        	
        	$lines_processed++;
        	
        	if ($lines_processed > 15) {
        		// don't process the whole file - stop processing after fifteen lines
        		break;
        	}
        }
        
        // return the quoting style that was used most often
        asort($quotingstyle_count);
        $guess = end(array_keys($quotingstyle_count));
        
    	return $guess;
    
    }

}
