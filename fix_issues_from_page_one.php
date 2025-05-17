<?php

// BHL document might have multiple articles/issues starting with page 1 but these
// might not be flagged as such, e.g. https://www.biodiversitylibrary.org/item/338628
// Simple fix is to make every page 1 the start of an issue

require_once (dirname(__FILE__) . '/bhl.php');

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

$basedir = $config['cache'] . '/' . $doc->bhl_title_id;

// Make sure we don't have issues pages already so we don't overwrite things
$have_issue_page = isset($doc->issue_pages) && (count($doc->issue_pages) > 0);

// Create issues page if missing
if (!$have_issue_page)
{	
	$doc->issue_pages = array();
	
	foreach ($doc->pages as $index => $page)
	{	
		if (isset($page->number))
		{
			if ($page->number == "1")
			{
				$doc->pages[$index]->tags[] = 'issue';
				$doc->issue_pages[] = $index;
				
				if (!isset($doc->pages[$index]->text))
				{
					$page_data = get_page($doc->pages[$index]->id, false, $basedir);
					
					// by default use BHL OCR text
					$doc->pages[$index]->text = $page_data->Result->OcrText;
				}				
			}						
		}
	}
}


// save updated document to disk
file_put_contents($filename, json_encode($doc, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

?>
