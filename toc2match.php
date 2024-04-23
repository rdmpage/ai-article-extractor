<?php

// Check whether titles in TOC match those on text pages inside doc, these matches are
// candidates for parts

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

$json = file_get_contents ($filename);

$doc = json_decode($json);

$theshold = 0.8;

if (isset($doc->toc))
{
	foreach ($doc->toc as &$c)
	{
		//print_r($c);
		
		if (isset($c->page) && isset($c->title))
		{
			$page_number = $c->page; // for now assume is single number
			$page_number = preg_replace('/^(\d+|[ivx]+)\s*(-.*)$/', '$1', $page_number);
			
			echo "page_number $page_number\n";
			
			if (isset($doc->pagenum_to_page->{$page_number}))
			{
				foreach ($doc->pagenum_to_page->{$page_number} as $index)
				{
					if (isset($doc->pages[$index]->text))
					{						
						$haystack = $doc->pages[$index]->text;				
						$needle = $c->title;
					
						$haystack = preg_replace('/\R/u', ' ', $haystack);
						$haystack = preg_replace('/\s\s+/u', ' ', $haystack);
						
						// title-specific fixes
						if (isset($doc->bhl_title_id))
						{
							switch ($doc->bhl_title_id)
							{
								case 8982:
									$needle = preg_replace('/\s+With\s*Plates.*$/iu', '', $needle);
									break;
		
								default:
									break;
							}
						}						
						

						$alignment = swa($needle, $haystack);
						
						print_r($alignment);
						
						$c->alignment = join("\n", $alignment->text);
						$c->spans = $alignment->spans;
						$c->score = $alignment->score;
						
						if ($c->score > $theshold)
						{
							$c->index = $index;
						}
					}
				}
			}
		}
	}
}


// save updated document to disk
file_put_contents($filename, json_encode($doc, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

?>
