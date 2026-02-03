<?php

// Given an item that has complete page numbers for multipe articles, each starting 
// with page 1, use that nfrmation to segment the item

require_once (dirname(__FILE__) . '/openai.php');
require_once (dirname(__FILE__) . '/shared.php');
require_once (dirname(__FILE__) . '/bhl.php');


//----------------------------------------------------------------------------------------
// Use ChatGPT to extract a list of structured data
function extract_structured($prompt, $text, $force = false)
{
	global $config;
	
	$chat_filename = $config['chat-cache'] . '/' . md5($prompt . $text) . '.json';

	if (!file_exists($chat_filename) || $force)
	{
		$json = conversation ($prompt, $text);
		file_put_contents($chat_filename, $json);
	}
	
	$json = file_get_contents($chat_filename);
	
	$json = preg_replace('/^\`\`\`json/', '', $json);
	$json = preg_replace('/\`\`\`\s*$/', '', $json);
	
	if (0)
	{
		echo "Response JSON\n";
		echo "-------------\n";
		echo $json;
	}
		
	$obj = json_decode($json);
	
	if (0)
	{
		echo "Response object\n";
		echo "---------------\n";
		print_r($obj);
	}
	
	return $obj;

}

//----------------------------------------------------------------------------------------

function extract_metadata($text, $keys = ["title", "authors", "journal", "volume", "issue", "pages", "year"], $force = false)
{
	$result = array();
	
	$prompt_lines = array();

	$prompt_lines[] = 'Extract metadata for the article that starts on this page.';

	// $prompt_lines[] = 'Include the article abstract if present.';
	
	$prompt_lines[] = 'Output the results in JSON as an array of objects.';
	$prompt_lines[] = 'The object should following keys (where a value is available): "' . join(",", $keys) . '".';	
	$prompt_lines[] = 'The "authors" field should be an array.';	

	$prompt_lines[] = 'The date should be formatted as YYYY-MM-DD.';

	$prompt_lines[] = 'The text to analyse is: ';

	$prompt = " \n" . join(" ", $prompt_lines);
	
	if (0)
	{
		echo $prompt . "\n";
		echo $text . "\n";
		echo "-------\n\n";
	}
	
	$result = extract_structured($prompt, $text, $force);
	
	return $result;
}

//----------------------------------------------------------------------------------------

$debug = false;
//$debug = true;

$force = true;
$force = false;

$filename = '';
if ($argc < 2)
{
	echo "Usage: " . basename(__FILE__) . " <filename>\n";
	exit(1);
}
else
{
	$filename = $argv[1];
}

$json = file_get_contents ($filename);

$doc = json_decode($json);

$doc->pages = (array)$doc->pages;

$doc->parts = array();

$basedir = $config['cache'] . '/' . $doc->bhl_title_id;

// given a list of pages that start issues (e.g., one article per issue) let's get the
// article metadata
if (isset($doc->pagenum_to_page->{"1"}))
{
	foreach ($doc->pagenum_to_page->{"1"} as $page_order)
	{
		$index = $page_order;
		
		$start_page = 1;
		
		$end_page = $start_page;
		
		$ok = true;
		while ($ok)
		{
			if (isset($doc->pages[$page_order]->number))
			{
				if (is_numeric($doc->pages[$page_order]->number))
				{
					$end_page = $doc->pages[$page_order]->number;
					$page_order++;
				}
				else
				{
					$ok = false;
				}
			}
			else
			{
				$ok = false;
			}
		
		}
		
		// get metadata...
		if (!isset($doc->pages[$index]->text))
		{
			$page_data = get_page($doc->pages[$index]->id, false, $basedir);
			$doc->pages[$index]->text = $page_data->Result->OcrText;								
		}	
		
		$text = $doc->pages[$index]->text;
		
		if ($debug)
		{
			echo "\n-----\n";
			echo $text . "\n";
			echo "\n-----\n\n";
		}
		
		switch ($doc->bhl_title_id)
		{
			case 21727: // Results of the Swedish zoological expedition to Egypt and the White 
				$keys = ["title", "authors"];
				break;
				
			default:
				$keys = ["title", "authors", "journal", "volume", "issue", "date"];
				break;
		}
		
		$articles = extract_metadata($text, $keys, $force);
		
		print_r($articles);
		
		// clean 
		foreach ($articles as &$article)
		{
			foreach ($article as $k => $v)
			{					
				// Empty
				if ($v == "")
				{
					unset($article->$k);
				}
				
			}

			// crude copy of BHL info
			if (isset($doc->volume) && $doc->volume != '' && !isset($article->volume))
			{
				$article->volume = $doc->volume;
			}
			if (isset($doc->year)  && $doc->year != '' && !isset($article->year) && !isset($article->date))
			{
				$article->year = $doc->year;
			}
			if (isset($doc->title) && !isset($article->journal))
			{
				$article->journal = $doc->title;
			}
			if (isset($doc->issn) && !isset($article->issn))
			{
				$article->issn = $doc->issn;
			}
			
			$article->spage = $start_page;
			$article->epage = $end_page;
			
			
			$article->url = 'https://biodiversitylibrary.org/page/' . $doc->pages[$index]->id;
			
			$doc->parts[$index][0] = $article;
			
			print_r($article);
		
		
		}
		
		
		
	}
}


// save updated document to disk
file_put_contents($filename, json_encode($doc, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

?>
