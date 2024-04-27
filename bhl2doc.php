<?php

// Convert BHL item to a document representing an ordered set of pages

require_once (dirname(__FILE__) . '/bhl.php');
require_once (dirname(__FILE__) . '/shared.php');
require_once (dirname(__FILE__) . '/parse-volume.php');

//----------------------------------------------------------------------------------------
function bhl_item_to_doc($item_data)
{
	global $basedir;

	// generic object to holder ordered list of pages, 
	// to work with both a BHL item and a stand alone PDF

	$doc = new stdclass;
	$doc->pages = array();
	$doc->id_to_page = array();
	$doc->pagenum_to_page = array();
	$doc->page_count = 0;
	$doc->contents_pages = array();
	$doc->title_pages = array();
	$doc->issue_pages = array();
	$doc->article_pages = array();
	
	// BHL specific stuff we might need
	$doc->bhl_title_id = $item_data->Result->PrimaryTitleID;
	$doc->bhl_item_id = $item_data->Result->ItemID;

	$doc->title = '';
	$doc->volume = $item_data->Result->Volume;
	$doc->year = $item_data->Result->Year;	
	
	// tidy up
	$result = parse_volume($doc->volume);
	if ($result->parsed)
	{
		$doc->volume = $result->volume[0];
		
		if (isset($result->{'collection-title'}))
		{
			$doc->series = $result->{'collection-title'}[0];
		}
	
	}
	
	// BHL
	foreach ($item_data->Result->Pages as $Page)
	{
		$doc_page = new stdclass;
	
		// id for page (not page number)
		$doc_page->id = $Page->PageID;
		$doc->id_to_page[$doc_page->id] = $doc->page_count;
	
		// page number
		$page_number = get_page_number($Page->PageNumbers);		
		if ($page_number != '')
		{
			$doc_page->number = $page_number;
		
			// populate reverse lookup of page(s) by number
			if (!isset($doc->pagenum_to_page[$page_number]))
			{
				$doc->pagenum_to_page[$page_number] = array();
			}
			$doc->pagenum_to_page[$page_number][] = $doc->page_count;
		}

		// page type		
		$doc_page->tags = array();
	
		foreach ($Page->PageTypes as $PageType)
		{
			if (preg_match('/Table of Contents/i', $PageType->PageTypeName))
			{
				$doc_page->tags[] = 'contents';
				
				$page_data = get_page($Page->PageID, false, $basedir);
				
				$doc_page->text = $page_data->Result->OcrText;
				
				if (1)
				{
					$doc_page->text = ocr_bhl_page($Page->PageID);
				}
					
				$doc->contents_pages[] = $doc->page_count;
			}

			if (preg_match('/Title Page/i', $PageType->PageTypeName))
			{
				$doc_page->tags[] = 'title';
				
				$page_data = get_page($Page->PageID, false, $basedir);
				$doc_page->text = $page_data->Result->OcrText;
				
				$doc->title_pages[] = $doc->page_count;
			}

			if (preg_match('/Issue Start/i', $PageType->PageTypeName))
			{
				$doc_page->tags[] = 'issue';
				
				$page_data = get_page($Page->PageID, false, $basedir);
				$doc_page->text = $page_data->Result->OcrText;

				if (1)
				{
					$doc_page->text = ocr_bhl_page($Page->PageID);
				}
				
				$doc->issue_pages[] = $doc->page_count;
			}
			
			if (preg_match('/Article Start/i', $PageType->PageTypeName))
			{
				$doc_page->tags[] = 'article';
				
				$page_data = get_page($Page->PageID, false, $basedir);
				$doc_page->text = $page_data->Result->OcrText;

				if (1)
				{
					$doc_page->text = ocr_bhl_page($Page->PageID);
				}
				
				$doc->article_pages[] = $doc->page_count;
			}			
			
			if (preg_match('/Blank/i', $PageType->PageTypeName))
			{
				$doc_page->tags[] = 'blank';
			}
			
			if (preg_match('/Index/i', $PageType->PageTypeName))
			{
				$doc_page->tags[] = 'index';
			}
			
			if (preg_match('/Text/i', $PageType->PageTypeName))
			{
				$doc_page->tags[] = 'text';
			}

			if (preg_match('/Appendix/i', $PageType->PageTypeName))
			{
				$doc_page->tags[] = 'text';
			}
			
			if (preg_match('/^Illustration$/i', $PageType->PageTypeName))
			{
				$doc_page->tags[] = 'figure';
			}
			
			if (preg_match('/Chart/i', $PageType->PageTypeName))
			{
				$doc_page->tags[] = 'figure';
			}

			if (preg_match('/Map/i', $PageType->PageTypeName))
			{
				$doc_page->tags[] = 'figure';
			}

			if (preg_match('/Foldout/i', $PageType->PageTypeName))
			{
				$doc_page->tags[] = 'figure';
			}
			
			if (preg_match('/List of Illustrations/i', $PageType->PageTypeName))
			{
				$doc_page->tags[] = 'list';
			}			

			if (preg_match('/Cover/i', $PageType->PageTypeName))
			{
				$doc_page->tags[] = 'cover';
			}			
			
		}
		
		$doc_page->tags = array_unique($doc_page->tags);
		
		// If we have 'contents' and 'index' tags together then we may try
		// and read the index as if it was a table of contents, so delete this pages
		// from list of contents pages
		$tags = array();
		if (count($doc_page->tags) > 1)
		{
			foreach ($doc_page->tags as $k => $v)
			{
				if ($v == 'contents' || $v == 'index')
				{
					$tags[$v] = $k;
				}
			}
			if (count($tags) == 2)
			{
				// delete this page from list contents and ensure array is reindexed
				$index = array_search($doc->page_count, $doc->contents_pages);
				array_splice($doc->contents_pages, $index, 1);
			}
		}
	
		$doc->pages[] = $doc_page;
		$doc->page_count++;

	}
	
	
	// hack
	if (isset( $doc->id_to_page[37171011]))
	{
		$PageID = 37171011;
		
		$page_index = $doc->id_to_page[$PageID];
		$doc->pages[$page_index]->tags[] = 'contents';
		
		$page_data = get_page($PageID, false, $basedir);
		
		$doc->pages[$page_index]->text = $page_data->Result->OcrText;
		
		if (1)
		{
			$doc->pages[$page_index]->text = ocr_bhl_page($PageID, 'zh-cn');
		}
			
		$doc->contents_pages[] = $page_index;
	}
	
	return $doc;
}

//----------------------------------------------------------------------------------------


$TitleID = 130490; // Pan Pacific Entomologist
$items = array(254683);


if (1)
{
	$TitleID = 142707; // Special publications - The Museum, Texas Tech University
	$items = array(275161); // volume with contents

	$items = array(274998); // volume as monograph
}

if (0)
{
	$TitleID = 7414; // The journal of the Bombay Natural History Society
	$items = array(188009); // v.73 (1976)
}

if (1)
{
	$TitleID = 61893; // Records of the South Australian Museum
	$items = array(126052); // v.31 (1998-1999)
	$items = array(126146); // v.32 (1999)
}


if (1)
{
	$TitleID = 6170; // Nautilus
	$items = array(279209); // v.130:no.4
	
	$items = array(318715);
}


if (1)
{
	$TitleID = 3882; // Novitates
	$items = array(22362); // v.28
}

if (0)
{
	$TitleID = 122512; // Memórias do Instituto Butantan
	$items = array(243620); // v.55: suppl (1993)
}

if (0)
{
	$TitleID = 8982;
	$items = array(37615); // ser.2:d.7 (1901-1902)
}

if (1)
{
	$TitleID = 8128; // Smithsonian miscellaneous collections
	$items = array(110960); // v.151
	
	$items = array(111083); // v.153:no.4
	$items = array(36101);
}


if (1)
{
	$TitleID = 206514;
	$items = array(332199);  // didn't work very well
	$items = array(332655);
	$items = array(332370);
	$items = array(332539);
	$items = array(332345);
	
	// force us to do every item
	$items = array();
}

if (0)
{
	$TitleID = 142707;
	
	// force us to do every item
	$items = array();
}

/*
if (0)
{
	$TitleID = 142707; // Special publications - The Museum, Texas Tech University
	$items = array(275161); // volume with contents

	//$items = array(274998); // volume as monograph
}
*/

if (0) // Records Australian Museum
{
	$TitleID = 61893;
	
	$items = array(125955); // not all pages have numbers
	$items = array(125986);
	$items = array(126201);
	
	$items = array();
}

if (0)
{
	$TitleID = 44963; // Proceedings of the Zoological Society of London
	$items = array(98562); // 1901:v.1 (Jan.-Apr.)
}

if (1)
{
	$TitleID = 204608; // Alytes
	$items = array(332701); // Vol. 27 : no. 4 (2011)
	
	$items = array(332777);
}

if (1)
{
	$TitleID = 53832; // Liangqi baxing dongwu yanjiu = Acta herpetologica Sinica
	$items = array(114352); 
}

if (1)
{
	$TitleID = 204608; // Alytes
	$items = array(327060);
}

if (0)
{
	$TitleID = 44963;
	$items = array(96442);
}

if (1)
{
	$TitleID = 3179 ;// University of Kansas Science Bulletin
	$items = array();
}






$force = true;
//$force = false;

$basedir = $config['cache'] . '/' . $TitleID;

// make sure we have items
if (count($items) == 0)
{
	$files = scandir($basedir);
	foreach ($files as $filename)
	{
		if (preg_match('/item-(\d+).json/', $filename, $m))
		{
			$items[] = $m[1];
		}
	}

}

// get title info
$title = '';
$filename = $basedir . '/title-' . $TitleID . '.json';
$json = file_get_contents($filename);
$obj = json_decode($json);

$title = $obj->Result->FullTitle;

// do we have an ISSN?
$issn = '';
foreach ($obj->Result->Identifiers as $identifier)
{
	switch ($identifier->IdentifierName)
	{
		case 'ISSN':
			if ($issn == '')
			{
				$issn = $identifier->IdentifierValue;
			}
			break;
			
		default:
			break;
	}
}

foreach ($items as $ItemID)
{
	echo $ItemID . "\n";

	// Get item
	$filename = $basedir . '/item-' . $ItemID . '.json';
	$json = file_get_contents($filename);
	$item_data = json_decode($json);
	
	$output_filename = $ItemID . '.json';
	
	if (!file_exists($output_filename) || $force)
	{

		$doc = bhl_item_to_doc($item_data);
	
		$doc->title = $title;
	
		if ($issn != '')
		{
			$doc->issn = $issn;
		}
	
		print_r($doc);
		
		file_put_contents($output_filename, json_encode($doc, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	}
}

?>
