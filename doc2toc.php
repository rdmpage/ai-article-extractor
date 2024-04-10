<?php

// Given a document with one or more pages tagged as "contents", attempt to extract
// the contents as structured data

require_once (dirname(__FILE__) . '/openai.php');
require_once (dirname(__FILE__) . '/shared.php');


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
	
	$obj = json_decode($json);
	
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

$have_contents_page = isset($doc->contents_pages) && (count($doc->contents_pages) > 0);

// Do we have a contents page?
if ($have_contents_page )
{
	$prompts = array();
	$prompts[] = 'Extract a table of contents from the following text.';
	$prompts[] = 'Output the results in JSON as an array of objects with values for the keys "title", "authors", and "page".';
	
	// specific tweaks
	
	if (isset($doc->bhl_title_id))
	{
		switch ($doc->bhl_title_id)
		{
			case 8982:
				$prompts[] = 'The author names appear at the start of the lines of text.';
				break;
		
			default:
				break;
		}
	}
	
	$prompts[] = 'The text to analyse is:';

	$prompt = join(" ", $prompts);

	foreach ($doc->contents_pages as $index)
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
				
					$doc->toc[] = $c;
				}
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
		$prompts[] = 'If a filed has no information do not include that field in the JSON output.';
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
