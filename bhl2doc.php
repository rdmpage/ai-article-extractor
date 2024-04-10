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
			
			if (preg_match('/Blank/i', $PageType->PageTypeName))
			{
				$doc_page->tags[] = 'blank';
			}
		}
	
		$doc->pages[] = $doc_page;
		$doc->page_count++;

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

https://www.biodiversitylibrary.org/item/37615#page/7/mode/1up



/*
$TitleID = 45410; // Journal of conchology

$items = array(333458); 
$items = array(327359); // v38:no.4-6
$items = array(327842); // v.38:no.1-3
*/

$basedir = $config['cache'] . '/' . $TitleID;

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
	// Get item
	$filename = $basedir . '/item-' . $ItemID . '.json';
	$json = file_get_contents($filename);
	$item_data = json_decode($json);

	$doc = bhl_item_to_doc($item_data);
	
	$doc->title = $title;
	
	if ($issn != '')
	{
		$doc->issn = $issn;
	}
	
	print_r($doc);
	
	$output_filename = $ItemID . '.json';
	file_put_contents($output_filename, json_encode($doc, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
}

?>
