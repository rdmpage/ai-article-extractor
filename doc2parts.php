<?php

// If table of contents have all we need, output parts directly

require_once (dirname(__FILE__) . '/bhl.php');
require_once (dirname(__FILE__) . '/parse-volume.php');
require_once (dirname(__FILE__) . '/swa.php');

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

$force = false;
$force = true;

$json = file_get_contents ($filename);

$doc = json_decode($json);

if (isset($doc->pagenum_to_page))
{
	$doc->pagenum_to_page = (array)$doc->pagenum_to_page;
}

$basedir = $config['cache'] . '/' . $doc->bhl_title_id;


// We have a table of contents
if (isset($doc->toc))
{
	$doc->parts = array();


	foreach ($doc->toc as $c)
	{
		print_r($c);

		$article = new stdclass;
		
		foreach ($c as $k => $v)
		{
			switch ($k)
			{
				case 'title':
				case 'authors':
				case 'date':
				case 'spage':
				case 'epage':
					$article->{$k} = $v;
					break;
					
				default:
					break;
			}
		}
		
		// clean authors
		if (isset($article->authors))
		{
			foreach ($article->authors as &$value)
			{
				$value = preg_replace('/\.(\p{Lu})/u', ". $1", $value);
				$value = preg_replace('/\s+\([^\)]+\)/u', "", $value);
				$value = preg_replace('/\s+$/u', "", $value);
				$value = mb_convert_case($value, MB_CASE_TITLE);
			}
		}			
		
				
		if (isset($doc->volume) && !isset($article->volume))
		{
			$article->volume = $doc->volume;
		}
		
		if (isset($doc->issue) && !isset($article->issue))
		{
			$article->issue = $doc->issue;
		}
		
		if (isset($doc->title) && !isset($article->journal))
		{
			$article->journal = $doc->title;
		}
		
		if (isset($doc->year) && (!isset($article->year) && !isset($article->date)))
		{
			$article->year = $doc->year;
		}

		if (isset($doc->issn))
		{
			$article->issn = $doc->issn;
		}
		
		$page_number = $article->spage;
		$theshold = 0.8;
		
		// sanity check and BHL URL
		if (isset($doc->pagenum_to_page[$page_number]))
		{
			foreach ($doc->pagenum_to_page[$page_number] as $index)
			{
				if (isset($doc->pages[$index]->text))
				{						
					$haystack = $doc->pages[$index]->text;				
					$needle = $c->title;
				
					$haystack = preg_replace('/\R/u', ' ', $haystack);
					$haystack = preg_replace('/\s\s+/u', ' ', $haystack);
					
					$alignment = swa($needle, $haystack);
					
					print_r($alignment);
					
					$c->alignment = join("\n", $alignment->text);
					$c->spans = $alignment->spans;
					$c->score = $alignment->score;
					
					if ($c->score > $theshold)
					{
						if (isset($doc->bhl_title_id))
						{
							$article->url = 'https://biodiversitylibrary.org/page/' . $doc->pages[$index]->id;
						}
						
						if (!isset($doc->parts[$index]))
						{
							$doc->parts[$index] = array();
						}
						
						$doc->parts[$index][] = $article;
						
					}
				}
			}
		}
		
		
		
		
		
		
	}
}

if (isset($doc->parts))
{
	print_r($doc->parts);
}

// save updated document to disk
file_put_contents($filename, json_encode($doc, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

?>
