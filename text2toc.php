<?php

// Given OCR text for BHL item, try and extract articles titles

$filename = '335798.txt';
$filename = '335792.txt';

$text = file_get_contents($filename);
$pages = preg_split('/\x0D/u', $text);

//print_r($pages);

$toc = array();

$page_number_to_page = array();

foreach ($pages as $index => $page)
{
	$lines = explode("\n", trim($page));
	
	//print_r($lines);
	
	$first_line = '';
	
	if (isset($lines[0]))
	{
		$first_line = $lines[0];
		
		echo $first_line . "\n";
		
		if (preg_match('/[J|I][a-zA-Z]*\.(\s*S\.)?\s+A[a-zA-Z]*\.\s+B[a-zA-Z]*\.\s+\d+.*:\s*(?<spage>\d+)/', $first_line, $m))
		{
			$c = new stdclass;
			$c->title = '';
			$c->page = $m['spage'];
			
			$i = 2;
			while (trim($lines[$i]) != '' && $i < 4)
			{
				$c->title .= $lines[$i] . ' ';
				$i++;
			}
			$c->title = trim($c->title);
			
			$toc[] = $c;
			
			$page_number_to_page[$c->page] = array();
			$page_number_to_page[$c->page][] = $index;
		}
	}

}

//print_r($toc);

// inferred table of contents
echo json_encode($toc);
echo "\n";

//print_r($page_number_to_page);

// inferred map between page numbers and index of page in item (in case pagination not complete)
echo json_encode($page_number_to_page);
echo "\n";


?>
