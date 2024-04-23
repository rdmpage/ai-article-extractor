<?php

$config['chat-cache'] = dirname(__FILE__) . '/chat-cache';

//----------------------------------------------------------------------------------------
// Get (cleaned) page number
function get_page_number($PageNumbers)
{
	$value = '';
	
	if (isset($PageNumbers[0]->Number) && ($PageNumbers[0]->Number != ''))
	{
		$value = $PageNumbers[0]->Number;
		$value = preg_replace('/Page%/', '', $value);
		$value = preg_replace('/^p\.%/', '', $value);
		$value = preg_replace('/(Pl\.?(ate)?)%/', '$1 ', $value);
	}
		
	return $value;
}

//----------------------------------------------------------------------------------------
// From http://snipplr.com/view/6314/roman-numerals/
// Expand subtractive notation in Roman numerals.
function roman_expand($roman)
{
	$roman = str_replace("CM", "DCCCC", $roman);
	$roman = str_replace("CD", "CCCC", $roman);
	$roman = str_replace("XC", "LXXXX", $roman);
	$roman = str_replace("XL", "XXXX", $roman);
	$roman = str_replace("IX", "VIIII", $roman);
	$roman = str_replace("IV", "IIII", $roman);
	return $roman;
}
    
//----------------------------------------------------------------------------------------
// From http://snipplr.com/view/6314/roman-numerals/
// Convert Roman number into Arabic
function arabic($roman)
{
	$result = 0;
	
	$roman = strtoupper($roman);

	// Remove subtractive notation.
	$roman = roman_expand($roman);

	// Calculate for each numeral.
	$result += substr_count($roman, 'M') * 1000;
	$result += substr_count($roman, 'D') * 500;
	$result += substr_count($roman, 'C') * 100;
	$result += substr_count($roman, 'L') * 50;
	$result += substr_count($roman, 'X') * 10;
	$result += substr_count($roman, 'V') * 5;
	$result += substr_count($roman, 'I');
	return $result;
} 

?>
