<?php

// Given issue starts, extract metadata for each part using ChatGPT
// assumes that a volume has >= 1 issue, and each issue is a single article

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

function extract_metadata($text, $keys = ["title", "authors", "journal", "volume", "issue", "pages", "year"])
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
	
	$result = extract_structured($prompt, $text);
	
	return $result;
}

//----------------------------------------------------------------------------------------

$debug = false;
$debug = true;

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

// given a title page (e.g., one article per item) let's get the
// article metadata
if (isset($doc->title_pages))
{
	foreach ($doc->title_pages as $index)
	{
		$text = $doc->pages[$index]->text;
		
		$keys = ["title", "authors", "journal", "volume", "issue", "date"];
		
		$articles = extract_metadata($text, $keys);
			
		if ($articles)
		{
		
			if ($debug)
			{
				echo "Corresponding article(s)\n";
				echo "------------------------\n";
				print_r($articles);
			}

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
					
					// Missing values
					if (isset($article->$k) && !is_array($article->$k))
					{
						if (preg_match('/(Unknown|Not specified)/i', $article->$k))
						{
							unset($article->$k);
						}
					}
					
					// Empty arrays
					if (isset($article->$k) && is_array($article->$k) && count($article->$k) == 0)
					{
						unset($article->$k);
					}						
				
					// Clean up some values
					if (isset($article->$k))
					{
						switch ($k)
						{
							case 'year':
								$article->{$k} = preg_replace('/[A-Z]\w+\s+([0-9]{4})/', '$1', $v);
								break;
								
							case 'authors':
								foreach ($article->{$k} as &$value)
								{
									$value = preg_replace('/\.(\p{Lu})/u', ". $1", $value);
									$value = mb_convert_case($value, MB_CASE_TITLE);
								}
								break;
						
							default:
								break;							
						}
					}
				}
				
				/*
				// pagination?
				$pages = array();
				
				// by default use page number for this page
				if (isset($doc->pages[$c->index]->number))
				{
					$pages[0] = $doc->pages[$c->index]->number;
				}
				
				if (isset($article->pages))
				{
					if (preg_match('/(.*)-(.*)/', $article->pages, $m))
					{
						$pages[0] = $m[1];
						$pages[1] = $m[2];
					}
				}					
									
				if (count($pages) > 0)
				{
					$article->spage = $pages[0];
				}

				if (count($pages) > 1)
				{
					$article->epage = $pages[1];
				}
				
				// if no epage, maybe toc has it?
				if (!isset($article->epage) && isset($c->page))
				{
					if (preg_match('/(.*)--?(.*)/', $c->page, $m))
					{
						$pages[0] = $m[1];
						$pages[1] = $m[2];
						
						$article->epage = $pages[1];
					}						
				}
				*/

				// metadata
				
				// crude copy of BHL info
				if (isset($doc->volume) && !isset($article->volume))
				{
					$article->volume = $doc->volume;
				}
				if (isset($doc->year) && !isset($article->year) && !isset($article->date))
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
				
				$article->url = 'https://biodiversitylibrary.org/page/' . $doc->pages[$index]->id;
					
			}
			
			$go = true;
			
			if ($go)
			{
				$doc->parts[$index][0] = $article;
			}
		}
		else
		{
			echo "No article(s) found.\n";
		}
	}
}

// build page ranges

print_r($doc->parts);

// iterature over issues and try and read page numbers, and store an array of PageIDs
// Note that we store parts as arrays indexed by page number as other methods may find more
// than one part per pags, and downstream tools may assume this
$starts = array_keys($doc->parts);
$n = count($starts);
$num_pages = $doc->page_count;

for ($i = 0; $i < $n; $i++)
{
	//$doc->pages[$starts[$k]]->pageids = array();
	
	if ($i == $n - 1)
	{
		$end = $doc->page_count;
	}
	else
	{
		$end = $starts[$i + 1];
	}
	echo "$i " . $starts[$i] . " $end\n";
	
	$pages = array();
	
	$keep = array('text', 'figure', 'issue', 'title', 'contents', 'index', 'list');
	
	$blank_count = 0;
	
	$spage = 0;
	$epage = 0;
	
	for ($k = $starts[$i]; $k < $end; $k++)
	{	
		if (isset($doc->pages[$k]->number))
		{
			if ($spage == 0)
			{
				$spage = $doc->pages[$k]->number;
				$epage = $spage;
			}
			else
			{
				if (is_numeric($doc->pages[$k]->number))
				{
					$epage = $doc->pages[$k]->number;
				}
			}
		}
	
		if (count(array_intersect($doc->pages[$k]->tags, $keep)) > 0)
		{
			$pages[] = join(",", $doc->pages[$k]->tags);
			
			$doc->parts[$starts[$i]][0]->pageids[] = $doc->pages[$k]->id;
			
			$blank_count = 0;
		}
		
		if (in_array('blank', $doc->pages[$k]->tags))
		{
			if (isset($doc->pages[$k]->number))
			{
				$pages[] = join(",", $doc->pages[$k]->tags);
				$doc->parts[$starts[$i]][0]->pageids[] = $doc->pages[$k]->id;
				$blank_count = 0;
			}
			else
			{		
				$blank_count++;
				if ($blank_count > 1)
				{
					$k = $end;
				}
			}
		}
	
		//echo join(",", $doc->pages[$k]->tags) . "\n";
	}
	
	if ($spage != 0)
	{
		$doc->parts[$starts[$i]][0]->spage = $spage;
		$doc->parts[$starts[$i]][0]->epage = $epage;
	}
	
	print_r($pages);
}

print_r($doc->parts);



// save updated document to disk
file_put_contents($filename, json_encode($doc, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

?>
