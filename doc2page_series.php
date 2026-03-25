<?php

// Given a document, look for repeated series of page numbers, for example,
// multiple cases of pages numbered 1, ..., n. These can be used to define parts.

require_once (dirname(__FILE__) . '/openai.php');
require_once (dirname(__FILE__) . '/shared.php');

require_once (dirname(__FILE__) . '/bhl.php');

if (!function_exists('array_key_first')) {
    function array_key_first(array $arr) {
        foreach($arr as $key => $unused) {
            return $key;
        }
        return NULL;
    }
}


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
// Display sequences as a text-based matrix for debugging
function display_sequences($page_series)
{
	$num_sequences = count($page_series->sequence);
	
	foreach ($page_series->labels as $label)
	{
		echo str_pad($label, 10, ' ', STR_PAD_LEFT);
		echo " | ";
		
		// if only one series then we always have a page with this label
		if ($num_sequences == 1)
		{
			echo 'x';
		}
		else
		{
			// mutliple series, need to check whether this label is in series
			for ($i = 0; $i < $num_sequences; $i++)
			{
				if (isset($page_series->sequence[$i][$label]))
				{
					echo 'x';		
				}
				else
				{
					echo ' ';
				}
			}	
		}
		echo "\n";
	}
}

//----------------------------------------------------------------------------------------

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

$basedir = $config['cache'] . '/' . $doc->bhl_title_id;


// find repeated series of pages

$doc->page_series = new stdclass;

$doc->page_series->sequence = array();		// 2D array to hold sequences of page numbers
$doc->page_series->labels   = array(); 		// list of unique page labels
$doc->page_series->sequence_labels = array();

$page_counter = 0;
$sequence_counter = 0; // counter for number of series
		
foreach ($doc->pages as $scan_order => $scan_page)
{
	if (isset($scan_page->number))
	{
		// create current sequence if it doesn't exist	
		if (!isset($doc->page_series->sequence[$sequence_counter]))
		{
			$doc->page_series->sequence[$sequence_counter] = array();
		}
	
		// if we've seen this label before in the current sequence assume 
		// it belongs to the next sequence, which we now create
		if (isset($doc->page_series->sequence[$sequence_counter][$scan_page->number]))
		{
			// new series
			$sequence_counter++;
			$doc->page_series->sequence[$sequence_counter] = array();
		}
		
		// add label to sequence and store page data		
		$page = new stdclass;
		$page->id = $scan_page->id;
		$page->label = $scan_page->number;
		$page->order = $scan_order;
		
		$doc->page_series->sequence[$sequence_counter][$scan_page->number] = $page;
	
		// keep track of unique labels for pages
		if (!in_array($scan_page->number, $doc->page_series->labels))
		{
			$doc->page_series->labels[] = $scan_page->number;
		}
	}
}

display_sequences($doc->page_series);


// check that each series starts at page 1

if (isset($doc->parts))
{
	unset($doc->parts);
}

foreach ($doc->page_series->sequence as $index => $sequence)
{
	$first_page = $sequence[array_key_first($sequence)];
	echo $index . ' ' . $first_page->label . ' ' . $first_page->order . "\n";
	
	if (is_numeric($first_page->label) && (Integer)$first_page->label > 1)
	{
		$offset = (Integer)$first_page->label - 1;		
		$pos = $first_page->order - $offset;
		
		// actual page 1
		$PageID = $doc->pages[$pos]->id;
		
		// get text
		if (!isset($doc->pages[$pos]->text))
		{
			if (0)
			{
				$page_data = get_page($doc->pages[$pos]->id, false, $basedir);
				
				// by default use BHL OCR text
				$doc->pages[$pos]->text = $page_data->Result->OcrText;
			}
			else
			{
				$doc->pages[$pos]->text = ocr_bhl_page($doc->pages[$pos]->id);
			}
		}
		
		// get metadata
		$articles = extract_metadata($doc->pages[$pos]->text, ['title', 'author', 'volume', 'issue', 'year'], false);
		
		print_r($articles);
		
		foreach ($articles as $article)
		{
			$article->url = 'https://biodiversitylibrary.org/page/' . $PageID;
		
			if (isset($article->title))
			{
				switch ($doc->bhl_title_id)
				{
					case 13353:
						$article->title = mb_convert_case($article->title, MB_CASE_UPPER);
						break;
								
					default:
						break;
				}
			}
			
			if (isset($doc->issn))
			{
				$article->issn = $doc->issn;
			}
			
			if (!isset($article->journal))
			{
				$article->journal = $doc->title;
			}
			
			// pagination
			$article->spage = 1;			
			$article->epage = key(array_slice($sequence, -1, 1, true));
			
			// print_r($article);
			
			// store as a part
			if (isset($article->title))
			{
				if (!isset($doc->parts[$first_page->order]))
				{
					$doc->parts[$first_page->order] = array();
				}
				foreach ($articles as $article)
				{
					$doc->parts[$first_page->order][] = $article;
				}
			
			}
		}
	}

}
	
// save updated document to disk
file_put_contents($filename, json_encode($doc, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

?>
