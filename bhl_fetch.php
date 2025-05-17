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

$titles = array(
3943, // Proceedings of the California Academy of Sciences, just want to do 334445
135556 // Journal of South African botany
);

$titles = array(
140312, // Bulletin of the Maryland Herpetological Society
);

// 334517

$titles = array(
3943, // Proceedings of the California Academy of Sciences, just want to do 334445
135556, // Journal of South African botany
12920, // Malacologia
);

$titles = array(
3943, // Proceedings of the California Academy of Sciences, just want to do 334445
135556, // Journal of South African botany
11247, // Comunicaciones del Museo nacional de Buenos Aires
);

$titles=array(
45481, // Genera insectorum
);

$titles = array(
3943, // Proceedings of the California Academy of Sciences, just want to do 334445
);

/*
$titles = array(
150137, // Biodiversity, biogeography and nature conservation in Wallacea and New Guinea...
);
*/

$titles = array(
156824, // Schedulae Orchidianae
);

$titles = array(
3943, // Proceedings of the California Academy of Sciences
135556, // Journal of South African botany
);

$titles = array(
135556, // Journal of South African botany
);


$items_to_do = array(); // default
$items_to_do = array(335399,
335398,
335396,);


$titles = array(
//123995, // Advances in the biology of shrews
211183, // Doriana
);
$items_to_do = array();


$titles = array(
135556, // Journal of South African botany
);
/*
$items_to_do = array(
335566,
335567,
335568,
335569,

);
*/

/*
$titles = array(
211407,
);
*/
$titles = array(
43408, // Annali del Museo civico di storia naturale Giacomo Doria
);
$items_to_do = array(336611);

$titles = array(
8648, // The Entomological magazine
);
$items_to_do = array(81634);

$titles = array(
7929, // Annali del Museo civico di storia naturale di Genova
);
$items_to_do = array(33168);

$titles = array(
9985, // Cistula entomologica
);
$items_to_do = array();


$titles = array(
8813, // Mittheilungen aus der Zoologischen Station zu Neapal
);
$items_to_do = array();

$titles = array(
127815, // Boletim do Museu Paraense Emílio Goeldi
129215,
129346,
);
$items_to_do = array();


$titles = array(
50228, // Iheringia. Série zoologia
);
$items_to_do = array();

$titles = array(
211183, // Doriana
);
$items_to_do = array();


$titles = array(
82295, // Bonner zoologische Monographien
);
$items_to_do = array(157028);


$titles = array(
10241, // Revista do Museu Paulista v 1-12 1895-1920
107243, // Revista do Museu Paulista v 13-22, 1922-1938
118995, // Notas preliminares; editadas pela redaccão da Revista do Museu 1-2, 1907-1922
);

$titles = array(
8068, // Anales de la Sociedad Española de Historia Natural
);

$items_to_do = array();

$titles = array(
211183, // Doriana
);
$items_to_do = array(
338969,
339087,
339061,
339066,
339068,
339124
);



$titles = array(
7383, // Records of the Albany Museum
);

$items_to_do = array();

$titles = array(
51678, // The journal of the Asiatic Society of Bengal
);

$titles = array(
7396, // South African journal of natural history
);

$items_to_do = array();

$titles = array(

212322, // Memorie della Società entomologica italiana
);

$titles = array(
103155, // Serket = Sarkat
);

$items_to_do = array(342841,
	342883);


$deep = false;

$force = false;
//$force = true;

$force_title = false; // set true if title has been updated with new items
$force_title = true;

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
	
	$items_to_fetch = array();
	
	// if no items specified get all
	if (count($items_to_do) == 0)
	{
		foreach ($title->Result->Items as $title_item)
		{
			$items_to_fetch[] = $title_item->ItemID;
		}
	}
	else
	{
		$items_to_fetch = $items_to_do;
	}

	foreach ($items_to_fetch as $ItemID)
	{

		$item = get_item($ItemID, $force, $dir);

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
