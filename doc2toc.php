<?php

// Given a document with one or more pages tagged as "contents", attempt to extract
// the contents as structured data

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
	
	echo $json;
	
	$obj = json_decode($json);
	
	if (json_last_error() != JSON_ERROR_NONE)
	{
		echo $json . "\n";
		echo "Error parsing JSON: " . json_last_error_msg();
		exit();
	}
	
	return $obj;

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

// Use ChatGPT to convert to structured data
$doc->toc = array();

$basedir = "";
if (isset($doc->bhl_title_id))
{
	// We may need to fecth conettns page if we've added one manually
	$basedir = $config['cache'] . '/' . $doc->bhl_title_id;
}

$have_contents_page = isset($doc->contents_pages) && (count($doc->contents_pages) > 0);

// Do we have a contents page?
if ($have_contents_page )
{	
	$prompts = array();
	$prompts[] = 'Extract a table of contents from the following text.';
	
	// specific tweaks
	
	if (isset($doc->bhl_title_id))
	{
		switch ($doc->bhl_title_id)
		{
			case 8648:
				$prompts[] = 'Each article title starts with the prefix "Art." and then a Roman number, please include these in the title.';
				$prompts[] = 'Output the results in JSON as an array of objects with values for the keys "title", "authors", and "page".';
				break;
		
			case 8982:
				$prompts[] = 'The author names appear at the start of the lines of text.';
				$prompts[] = 'Output the results in JSON as an array of objects with values for the keys "title", "authors", and "page".';
				break;

			case 150137:
				$prompts[] = 'The author names appear at the start of the lines of text, separated from the title by a semicolon (":").';
				$prompts[] = 'The page number appears at the end of a line of text, usually (but not always) after a series of dots(".").';
				$prompts[] = 'Output the results in JSON as an array of objects with values for the keys "title", "authors", and "page".';
				break;
				
			case 156824:
				$prompts[] = 'A line begining "Number" followed by a Roman number contains the volume number and publication date. Please write date in YYYY-MM-DD format.';
				$prompts[] = 'Each article starts on page 1.';
				$prompts[] = 'Please add values for "volume" and "date" to the output.';
				$prompts[] = 'Output the results in JSON as an array of objects with values for the keys "title", "authors", and "page".';
				break;	
				
			case 135556:
				$prompts[] = "The title appears before the list of author names.";		
				$prompts[] = "The page number appears on the next line after the title and author names.";		
				$prompts[] = "Remove any job titles or academic degrees from the author names.";		
				$prompts[] = 'Output the results in JSON as an array of objects with values for the keys "title", "authors", and "page".';
				break;

			// Annali del Museo civico di storia naturale Giacomo Doria
			case 43408:
				$prompts[] = 'The author names appear at the start of the lines of text.';
				$prompts[] = "The date of publication follows the title and is of the form (\d+.[IVX]+.\d+). Convert this to YYYY-MM-DD format.";
				$prompts[] = "The page numbers include the start and end page.";		
				$prompts[] = 'Output the results in JSON as an array of objects with values for the keys "title", "authors", "date", "spage", and "epage".';
				$prompts[] = "Separate the author names as elements of an array, with each name in the form initials + surname.";
				break;

			// Boletim do Museu Paraense Emílio Goeldi
			case 129346:
			//case 127815: ?
			//case 129215: ?
				$prompts[] = "Separate the author names as elements of an array.";			
				$prompts[] = 'Output the results in JSON as an array of objects with values for the keys "title", "authors", and "page".';
				break;

			default:
				$prompts[] = 'Output the results in JSON as an array of objects with values for the keys "title", "authors", and "page".';
				break;
		}
	}
	
	$prompts[] = 'The text to analyse is:';

	$prompt = join(" ", $prompts);
	
	// echo $prompt;
	
	print_r($doc->contents_pages);

	foreach ($doc->contents_pages as $index)
	{	
		
		if (!isset($doc->pages[$index]->text))
		{
			$page_data = get_page($doc->pages[$index]->id, false, $basedir);
			$doc->pages[$index]->text = $page_data->Result->OcrText;
			
			$doc->pages[$index]->text = ocr_bhl_page($doc->pages[$index]->id);
				
		}				

		if (1)
		{
			echo $doc->pages[$index]->text;
		}
		
		//$toc_from_text = extract_as_array_of_objects($prompt, $doc->pages[$index]->text);
		$toc_from_text = extract_structured($prompt, $doc->pages[$index]->text);

		echo "\ntoc_from_text\n";
		print_r($toc_from_text);
			
		if (is_array($toc_from_text))
		{
			foreach ($toc_from_text as &$c)
			{
				// clean
				
				print_r($c);
			
				foreach ($c as $k => $v)
				{
					if ($v == "")
					{
						unset($c->$k);
					}
				}

				foreach ($c as $k => $v)
				{
					switch ($k)
					{
						case 'volume':
							$c->{$k} = preg_replace('/(No\.|Number)\s+/i', '', $v);
							break;

						case 'spage':
							$c->page = $v;
							break;

						case 'page':
							if (preg_match('/(.*)-(.*)/', $v, $m))
							{
								$c->spage = trim($m[1]);
								$c->epage = trim($m[2]);
								
								$c->page = $c->spage ;
							}							
							break;
						
						default:
							break;							
					}
				}
				
				$doc->toc[] = $c;
			}
		}
	}
}
else
{
	// Try to get info from title page(s)
	if (isset($doc->title_pages) && (count($doc->title_pages) > 0))
	{
	
		$prompts = array();
		$prompts[] = 'Extract bibliographic data from the following text which is the title page for the work.';
		$prompts[] = 'Output the results in JSON as an array of objects with values for the keys "title", "authors", "journal", "volume", "issue", "year", and "pages".';
		$prompts[] = 'If a field has no information do not include that field in the JSON output.';
		$prompts[] = 'The text to analyse is:';

		$prompt = join(" ", $prompts);
	
		foreach ($doc->title_pages as $index)
		{
			if (isset($doc->pages[$index]->text))
			{
				//$toc_from_text = extract_as_array_of_objects($prompt, $doc->pages[$index]->text);
				$toc_from_text = extract_structured($prompt, $doc->pages[$index]->text);
				
				print_r($toc_from_text);
		
				if (is_array($toc_from_text))
				{
					foreach ($toc_from_text as $c)
					{
						// clean
				
						foreach ($c as $k => $v)
						{
							if ($v == "")
							{
								unset($c->$k);
							}
							
							if (isset($c->$k))
							{
								switch ($k)
								{
									case 'volume':
										$c->{$k} = preg_replace('/(No\.|Number)\s+/i', '', $v);
										break;
									
									default:
										break;							
								}
							}
						}
				
						$doc->toc[] = $c;
					}
				}
			}
		}
	}	
}


// save updated document to disk
file_put_contents($filename, json_encode($doc, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

?>
