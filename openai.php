<?php

require_once (dirname(__FILE__) . '/config.inc.php');

//----------------------------------------------------------------------------------------
function openai_call($url, $data)
{
	global $config;
	
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
	curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));  
	
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	
	curl_setopt($ch, CURLOPT_HTTPHEADER, 
		array(
			"Content-type: application/json",
			"Authorization: Bearer " . $config['openai_key']
			)
		);
	
	$response = curl_exec($ch);
	if($response == FALSE) 
	{
		$errorText = curl_error($ch);
		curl_close($ch);
		die($errorText);
	}
	
	$info = curl_getinfo($ch);
	$http_code = $info['http_code'];
	
	if (0)
	{
		print_r($info);
		echo $response;
	}
		
	curl_close($ch);
	
	return $response;
}

//----------------------------------------------------------------------------------------
// Get embedding (vector) for text
function get_embedding($text, $model = "text-embedding-ada-002")
{
	global $config;
	
	$embedding = array();
	
	$data = new stdclass;
	$data->model = $model;
	$data->input = $text;
	
	$response = openai_call($config['openai_embeddings'], $data);
	
	if ($response)
	{
		$obj = json_decode($response);
		if ($obj)
		{
			$embedding = $obj->data[0]->embedding;
		}
	} 	
	
	return $embedding;
}


//----------------------------------------------------------------------------------------
// Use ChatGPT to summarise the results to a question
function conversation ($prompt, $text)
{
	global $config;
	
	$summary = '';
			
	$model = "gpt-3.5-turbo";
	$model = "gpt-4o-mini";
	
	$data = new stdclass;
	$data->model = $model;
	$data->messages = array();
	
	$message = new stdclass;
	$message->role = "system";
	$message->content = $prompt;
	
	$data->messages[] = $message;
	
	$message = new stdclass;
	$message->role = "user";
	$message->content = $text;
	
	$data->messages[] = $message;
	
	// print_r($data);
	
	// echo json_encode($data);
	
	$response = openai_call($config['openai_completions'], $data);
	
	//echo $response;
	
	if ($response)
	{
		$obj = json_decode($response);
		if ($obj)
		{
			//print_r($obj);
			
			if (isset($obj->choices))
			{
				$summary = $obj->choices[0]->message->content;
			}
		}
	} 		
	
	return $summary;
}

//----------------------------------------------------------------------------------------


if (0)
{

	$text = "TWENTIETH-CENTURY ABORIGINAL HARVESTING PRACTICES IN THE RURAL

	LANDSCAPE OF THE LOWER MURRAY, SOUTH AUSTRALIA

	PA CLARKE

	CLARKE, PA. 2003. Twentieth-century Aboriginal harvesting practices in the rural
	landscape of the Lower Murray, South Australia. Records of the South Australian Museum
	36(1): 83-107.

	Since European settlement, Aboriginal people living in rural areas of southern South
	Australia have had a unique relation to the landscape, reflecting both pre-European indigenous
	traditions and post-European historical influences. Aboriginal hunting, fishing and gathering
	practices in the twentieth century were not relics of a pre-European past, but were derived from
	cultural forces that have produced a modern indigenous identity. The Lower Murray
	ethnographic data presented in this cultural geography study were collected mainly during the
	1980s, supplemented with historical information concerning earlier periods.

	PA Clarke, Science Division, South Australian Museum, North Terrace, Adelaide, South
	Australia 5000. Manuscript received 4 November 2002.";
	
	

	$text = "MALACOLOGIA, 1973, 12(1): 1-11

FEEDING AND ASSOCIATED FUNCTIONAL MORPHOLOGY
IN TAGELUS CALIFORNIANUS
AND FLORIMETIS OBESA (BIVALVIA: TELLINACEA)

Ross H. Pohlo

Department of Biology
California State University at Northridge
Northridge, California 91324, U.S.A.

ABSTRACT

";

	/*
	$text = "bo

	В. Н. POHLO

	lem

	FIG. 1. Organs of the mantle cavity of Tagelus californianus viewed from the right side. Right valve and mantle
	lobe removed. Arrows indicate the direction of particle movement. Dotted arrows indicate movement on the
	underside of the surface. AA—anterior adductor; CM—cruciform muscle; ES—exhalant siphon; F—foot;
	ID—inner demibranch; IS—inhalant siphon; L—ligament; L P—labial palp; ML—mantle lobe; OD—outer
	demibranch; PA—posterior adductor; PR—posterior retractor.

	(McLean, 1969).
	";
	*/

	/*
	$text = "J. Conon. 30: 303-304 (1981)

	NOTE ON THE IDENTITY OF FISSURELLA
	IMPEDIMENTUM COOKE, 1885
	(PROSOBRANCHIA: FISSURELLIDAE)

	Hen K K. MIENIS

	Zoological Museum, Mollusc Collection, Hebrew University of Jerusalem, Israel.

	(Read before the Society, 13 December, 1980)
	";
	*/
	
	$text = "REVUE SUISSE DE ZOOLOGIE

Tome 67, n° 27. — Septembre 1960.



:i2lj



Catalogue des Opisthobranches de la Rade

de Villefranche-sur-Mer et ses environs

(Alpes Maritimes)



par



Hans- Rudolf HAEFELFINGER



Station zoologique de Villefranche

et Zoologische Anstalt der Universitàt Basel 1



Avec 1 tableau et 2 cartes.



1. INTRODUCTION
";


$text = "J. Concu. 30: 317-323 (1981)

DIFFERENTIATION OF THE RADULA OF SOUTH
AFRICAN. SP EGIES (Fr oot GENUS, GULELIA
INTO THREE TYPES (GASTROPODA
PULMONATA: STREPTAXIDAE)

D. W. AIKEN

18 Pieter Raath Avenue, Lambton, Germiston, Transvaal, South Africa, 1401
(Read before the Society, 18 October, 1980)
";


	$prompt = "Extract article-level bibliographic metadata from the following text and return in RIS format. If no bibliographic metadata found return message \"No data\".";

	$prompt .= " The article is in the Journal of Conchology.";
	
	//$prompt .= " The text is in French.";

	$response = conversation ($prompt, $text);

	echo $response . "\n";
	
}

if (0)
{

	$text = "JOURNAL OF CONCHOLOGY, VOL. 30, NO. 5

	appearance of coming from the beach deposits of that area. Probably the form is extinct.’ I agree
	with his remark, however, I do not accept Newton’s opinion that the specimens described by
	him belong to the form group of Diodora ruppelli (Sowerby, 1835). Size, height, circular outline
	and sculpture are so different that I consider it a good species, of which the following synonymy
	is now known:

	Diodora impedimenta (Cooke, 1885)
	Fissurella umpedimentum Cooke, 1885: 270.
	Capulina ruppellu var. barront Newton, 1900: 502, pl. 22, figs. 1-4.

	ACKNOWLEDGEMENT

	I should like to thank Dr. C. B. Goodhart (Cambridge) for sending me on loan the type
	specimens of Fissurella impedimentum from the McAndrew collection.

	REFERENCES

	CuRISTIAENS, J., 1974. Le Genre Diodora (Gastropoda): Especes non-européennes. Inf. Soc. Belge Malac., 3 (6-7): 73-97,
	3 pls.

	Cooke, A. H., 1885. Report on the testaceous Mollusca obtained during a dredging-excursion in the Gulf of Suez in the
	months of February and March 1869 by R. MacAndrew. Republished, with additions and corrections by A. H.
	Cooke. Ann. Mag. nat. Hist., 16: 262-276.

	Newton, R. B., 1900. Pleistocene shells from the raised beach deposits of the Red Sea. Geol. Mag. ser. 4, 7: 500-514,

	544-560, pls20 and 22:

	PLATE 12 (opposite)

	Syntypes of Fissurella impedimentum Cooke, Figs. 1-3, UMZC 2354/2; figs. 4-5, UMZC 2354/1; figs. 6-7, UMZC
	2354/4; fig. 8, UMZC 2354/3. All x5.

	304



	";


	$prompt = "Extract article-level bibliographic metadata from the following text and return in RIS format. If no bibliographic metadata found return message \"No data\".";

	$prompt .= " The article is in the Joiurnal of Conchology.";

	$prompt = "Extract a bibliography of bibliographoc references cited in this text. Output list in RIS format: ";


	$response = conversation ($prompt, $text);

	echo $response . "\n";
}


?>
