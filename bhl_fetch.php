<?php

// fetch data from BHL and store on disk

require_once (dirname(__FILE__) . '/bhl.php');


$titles = array(
	78705,	// Ruwenzori expedition, 1934-5 ...
	68672,
	130490,
	68619, // Insects of Samoa and other Samoan terrestrial arthropoda
	42247, // Fieldiana Botany
);

$titles = array(
//206514
//45410, // Journal of conchology
//7414, // The journal of the Bombay Natural History Society
//142893,
//47024,
8981, // Revue suisse de zoologie
130490, // The Pan-Pacific entomologist
142707, // Special publications - The Museum, Texas Tech University
61893, // Records of the South Australian Museum

);

$titles = array(
6170, // Nautilus
3882, // Novitates zoologicae : a journal of zoology in connection with the Tring Museum
122512, // Memórias do Instituto Butantan
);

$titles = array(
6170, // Nautilus
8982, // Tijdschrift der Nederlandsche Dierkundige Vereeniging
);

$titles = array(
8128, // Smithsonian miscellaneous collections
);

$titles = array(
206514, // Contributions of the American Entomological Institute
);

$titles = array(
42256, // Fieldiana Zoology
);

$titles = array(
44963, // Proceedings of the Zoological Society of London
);

$titles = array(
204608, // Alytes
);

// Acta Her Sin, etc.
$titles = array(
53833, //
53832, // Acta herpetologica Sinica
46858, // Chinese herpetological research
40011, // Chinese herpetological research
2804, // Asiatic herpetological research
);

$titles = array(
3179, // University of Kansas Science Bulletin
15415, // The Kansas University science bulletin
);


$deep = false;

$force = false;

$force_title = false; // set true if title has been updated with new items

$fetch_counter = 1;

foreach ($titles as $TitleID)
{
	$dir = $config['cache'] . '/' . $TitleID;

	if (!file_exists($dir))
	{
		$oldumask = umask(0); 
		mkdir($dir, 0777);
		umask($oldumask);
	}

	$title = get_title($TitleID, $force_title, $dir);

	foreach ($title->Result->Items as $title_item)
	{
		$item = get_item($title_item->ItemID, $force, $dir);

		/*
		foreach ($item->Result->Parts as $part)
		{
			get_part($part->PartID, $force, $dir);
		}
		*/
	
		// don't get pages if we have lots 
		if ($deep)
		{
			foreach ($item->Result->Pages as $page)
			{
				get_page($page->PageID, $force, $dir);
				
				// Give server a break every 10 items
				if (($fetch_counter % 10) == 0)
				{
					$rand = rand(1000000, 3000000);
					echo "\n-- ...sleeping for " . round(($rand / 1000000),2) . ' seconds' . "\n\n";
					usleep($rand);
				}

			}
		}

	}
}

?>
