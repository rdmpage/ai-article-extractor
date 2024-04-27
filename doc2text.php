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

$json = file_get_contents ($filename);

$doc = json_decode($json);

$basedir = $config['cache'] . '/' . $doc->bhl_title_id;

if (isset($doc->toc))
{
	foreach ($doc->toc as $c)
	{
		// print_r($c);
		
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
						$doc->pages[$index]->text = $page_data->Result->OcrText;					
						
						// OCR?
						if (isset($doc->bhl_title_id))
						{
							switch ($doc->bhl_title_id)
							{
								case 8982:
									$doc->pages[$index]->text = ocr_bhl_page($doc->pages[$index]->id);
									break;
									
								case 53832:
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
