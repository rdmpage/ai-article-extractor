<?php

// BHL document might not have text for every page (as this requires API calls)
// so add text only where we need it, for example pages that may have articles

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

// Page flagged a having contents, make sure it has text

$have_contents_page = isset($doc->contents_pages) && (count($doc->contents_pages) > 0);

// Do we have a contents page?
if ($have_contents_page )
{	
	foreach ($doc->contents_pages as $index)
	{	
		if (!isset($doc->pages[$index]->text))
		{
			$page_data = get_page($doc->pages[$index]->id, $force, $basedir);
			$doc->pages[$index]->text = $page_data->Result->OcrText;					
		
			// OCR?
			if (isset($doc->bhl_title_id))
			{
				switch ($doc->bhl_title_id)
				{
					case 150137:
					case 135556:
						if (0)
						{
							$doc->pages[$index]->text = ocr_ia_page($doc, $index);						
						}
						else
						{
							$doc->pages[$index]->text = ocr_bhl_page($doc->pages[$index]->id);
						}
						break;

					default:
						break;
				}
			}								
		}
	}
}


// We have a table of contents, make sure pages listed there have text
if (isset($doc->toc))
{
	foreach ($doc->toc as $c)
	{
		//print_r($c);
		
		if (isset($c->page))
		{
			$page_number = $c->page; // for now assume is single number
			$page_number = preg_replace('/^(\d+|[ivx]+)\s*(-.*)$/', '$1', $page_number);
		
			if (isset($doc->pagenum_to_page->{$page_number}))
			{
				foreach ($doc->pagenum_to_page->{$page_number} as $index)
				{				
					if (!isset($doc->pages[$index]->text))
					{
						$page_data = get_page($doc->pages[$index]->id, false, $basedir);
						
						// by default use BHL OCR text
						$doc->pages[$index]->text = $page_data->Result->OcrText;
						
						// OCR?
						if (isset($doc->bhl_title_id))
						{
							switch ($doc->bhl_title_id)
							{
								case   7929:
								case   8648:
								case   8982:
								case   9985;
								case  43408:
								case 135556:
								//case 150137:
									$doc->pages[$index]->text = ocr_bhl_page($doc->pages[$index]->id);
									break;
									
								case  53832:
									$doc->pages[$index]->text = ocr_bhl_page($doc->pages[$index]->id, 'zh-cn');
									break;
		
								default:
									break;
							}
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
