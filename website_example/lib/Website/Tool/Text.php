<?php


class Website_Tool_Text
{
	
	public static function getStringAsOneLine ($string) {
		$string = str_replace("\r\n", " ", $string);
		$string = str_replace("\n", " ", $string);
		$string = str_replace("\r", " ", $string);
		$string = str_replace("\t", "", $string);
		$string = preg_replace ('#[ ]+#', ' ', $string);
		return $string;
	}
	
	public static function cutStringRespectingWhitespace ($string,$length) {
		if ( $length < strlen($string) )
		{
			$text = substr($string, 0, $length);
			if ( false !== ($length = strrpos($text,' ')) )
			{
				$text = substr($text, 0, $length);
			}
			$string = $text."...";
		}
		return $string;
	}
	
	public static function toUrl($text) {
		
        $text = Pimcore_Tool_Transliteration::toASCII($text);
        
        $search = array('?', '\'', '"', '/', '-', '+', '.', ',', '(', ')', ' ', '&', 'ä', 'ö', 'ü', 'Ä', 'Ö', 'Ü', 'ß', 'É', 'é', 'È', 'è', 'Ê', 'ê', 'E', 'e', 'Ë', 'ë',
                                  'À', 'à', 'Á', 'á', 'Å', 'å', 'a', 'Â', 'â', 'Ã', 'ã', 'ª', 'Æ', 'æ', 'C', 'c', 'Ç', 'ç', 'C', 'c', 'Í', 'í', 'Ì', 'ì', 'Î', 'î', 'Ï', 'ï',
						 'Ó', 'ó', 'Ò', 'ò', 'Ô', 'ô', 'º', 'Õ', 'õ', 'Œ', 'O', 'o', 'Ø', 'ø', 'Ú', 'ú', 'Ù', 'ù', 'Û', 'û', 'U', 'u', 'U', 'u', 'Š', 'š', 'S', 's', 
						 'Ž', 'ž', 'Z', 'z', 'Z', 'z', 'L', 'l', 'N', 'n', 'Ñ', 'ñ', '¡', '¿',  'Ÿ', 'ÿ', "_",":" );
        $replace = array('', '', '', '', '-', '', '', '-', '', '', '-', '', 'ae', 'oe', 'ue', 'Ae', 'Oe', 'Ue', 'ss', 'E', 'e', 'E', 'e', 'E', 'e', 'E', 'e', 'E', 'e',
                         'A', 'a', 'A', 'a', 'A', 'a', 'a', 'A', 'a', 'A', 'a', 'a', 'AE', 'ae', 'C', 'c', 'C', 'c', 'C', 'c', 'I', 'i', 'I', 'i', 'I', 'i', 'I', 'i',
						   'O', 'o', 'O', 'o', 'O', 'o', 'o', 'O', 'o', 'OE', 'O', 'o', 'O', 'o', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'S', 's', 'S', 's', 
						   'Z', 'z', 'Z', 'z', 'Z', 'z', 'L', 'l', 'N', 'n', 'N', 'n', '', '', 'Y', 'y', "-", "-" );
		
        $value = urlencode(str_replace($search, $replace, $text));
        
		return $value;
	}
}
