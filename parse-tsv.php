<?php

/*
(.*[A-Z]\w+)[\.|,]\s+(.*)\s+in\s+(?<journal>Voyage\s+de\s+Ch.\s+Alluaud\s+et\s+R.\s+Jeannel\s+en\s+Afrique\s+orientale,?\s+ \(?\s*1911\s*-\s*1912\s*\)?.)\s+(?<title>.*),\s+pp.\s+(?<spage>\d+)-(?<epage>\d+),?\s+.*(\d+\s+\p{L}+\.?\s+[0-9]{4})

E. Meyrick. Microlepidoptera, in Voyage de Ch. Alluaud et 
R. Jeannel en Afrique orientale, 1911-1912. Résultats scientifiques. Lepidoplera, II, pp. 33-120 (Paris, A. Lhomme, i5 août 1920). 
M. Bezzi, Bombijliidae et Syrpliidae, in Voyage de Ch. Alluaud et R. Jeannel en Afrique orientale ( 1911 - 1912 ). Résultats scientifiques. Diplera, VI, pp. 315-351 (Paris, L. Lhomme, i5 avril 1923 ). 
H. Desbordes, Histeridae, in Voyage de Ch. Alluaud et R. Jeannel en Afrique orientale, 1911-1912. Résultats scientifiques. Coleoptera, XI, pp. 347-384, avec 12 figures dans le texte [Paris, A. Schulz, 30 nov. 1914).
M. Bezzi, Bombijliidae et Syrpliidae, in Voyage de Ch. Alluaud et R. Jeannel en Afrique orientale ( 1911 - 1912 ). Résultats scientifiques. Diplera, VI, pp. 315-351 (Paris, L. Lhomme, i5 avril 1923 ). 
*/

require_once (dirname(__FILE__) . '/shared.php');
require_once (dirname(__FILE__) . '/openai.php');

//----------------------------------------------------------------------------------------
// Use ChatGPT to extract a list of structured data
function extract_structured($prompt, $text, $force = false)
{
	global $config;
		
	$chat_filename = $config['chat-cache'] . '/' . md5($prompt . $text) . '.txt';

	if (!file_exists($chat_filename) || $force)
	{
		$output = conversation ($prompt, $text);
		file_put_contents($chat_filename, $output);
	}
	
	$output = file_get_contents($chat_filename);
	return $output;

}

//----------------------------------------------------------------------------------------

$headings = array();

$row_count = 0;

$filename = "voyage.tsv";
$filename = "vextra.tsv";

$file_handle = fopen($filename, "r");
while (!feof($file_handle)) 
{
	$line = trim(fgets($file_handle));
		
	$row = explode("\t",$line);
	
	$go = is_array($row) && count($row) > 1;
	
	if ($go)
	{
		if ($row_count == 0)
		{
			$headings = $row;		
		}
		else
		{
			$obj = new stdclass;
		
			foreach ($row as $k => $v)
			{
				if ($v != '')
				{
					$obj->{$headings[$k]} = $v;
				}
			}
		
			print_r($obj);	
			
			$prompts = array(
				'Parse this citation string and extract the details in RIS format.',
				'The volume number is typically preceeded by "no."',
				'The date is in French, please parse and extract in YYYY-MM-DD form.',
				'The journal name starts with "Voyage".',
				'The phrase starting "Résultats" and ending before the pagination is the prefix for the title.',
				'Please include the Roman numeral in the title prefix.',
				'Please add the text after the author name and the journal name to the main title.',
				
			);
			
			$prompt = join("\n", $prompts);
			
			$input = $obj->text;
				
			$output = extract_structured($prompt, $input, true);
			
			echo "Extracted result\n";
			echo $output . "\n";
			
			//exit();
			
			
		}
	}	
	$row_count++;	
}	

?>
