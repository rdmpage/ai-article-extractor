<?php

// Create a workflow for a given title

require_once (dirname(__FILE__) . '/config.inc.php');

$format = 'ris';
//$format = 'tsv';

$TitleID = 206514;
$TitleID = 204608; // Alytes

$items = array(
/*
// volume 5
327049,
327056,
327057,
*/

// volume 6
//327023,
//327060,

// 7,8,9,10

// 7
327066,
327059,
327048,
327352,

// 8
327050,
327085,
327066,

// 9
327061,
327062,
327067,
327068,


// 10
327026,
327051,
327366,
327397,


);

if (0)
{
	// Kansas, seems we have issues, need to debug

	$TitleID = 3179 ;// University of Kansas Science Bulletin
	$items = array();
}

$TitleID = 204608; // Alytes (more)

$items = array(
/*
// 11
329862,
329870,
329976,
329997,
*/

329874,
329875,
329879,

329990,
329996,

330049,
331267,
331356,
331786,
331850,
331851,
331860,
331866,
331870,
331898,
331902,
331908,
331911,
331918,
331939,
331962,
331975,
331979,
331980,
331981,
331985,
332651,
332657,
332658,
332659,
332666,
332678,
332697,
332701,
332725,
332729,
332735,
332747,
332757,
332777,

);

$items=array(332701);

if (1)
{
	$TitleID = 3943 ; // Proceedings of the California Academy of Science
	$items = array(334523,334531);
	
	$items = array(334655);
	
	
}

if (1)
{
	$TitleID = 135556 ; // Journal of South African botany
	$items = array(
335237,
335238,
335239,
335240,
335242,
335243,
335244,
	);
	
}

if (0)
{
	$TitleID = 12920 ; // Malacologia
	$items = array(334519);
	$items = array(47264);
	
	$items = array();
}


if (0)
{
	$TitleID = 150137;
	$items = array();
}


if (1)
{
	$TitleID = 123995;
	$items = array();
}

if (1)
{
	$TitleID = 45410;
	$items = array();
}


$default_workflow = '';

if (1)
{
	$TitleID = 103155; // Serket
	$items = array(
183520,
183522,
183538,
183516,
183529,
183542,
199139,
199136,
199140,
199137,
207090,
342001,
341997,
342011,
342195,
342095,
342161,
342228,
342199,
342101,
342690,
342722,
);
	$default_workflow = 'toc'; // important as otherwise we often use the wrong workflow

}

// Memorie della Società entomologica italiana
if (1)
{
	$TitleID = 212322; 
$items = array(
//340868,
//340910,
//341635,
//341984,
342159,
//342009,
);
	$default_workflow = 'toc'; // important as otherwise we often use the wrong workflow
}





$basedir = $config['cache'] . '/' . $TitleID;

$output_dir = dirname(__FILE__);


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

print_r($items);

// figure out which workflow to use

$workflow = array();

foreach ($items as $item)
{
	$docfilename = $output_dir . '/' . $item . '.json';
	
	if (!file_exists($docfilename))
	{
		echo "File $docfilename not found.\n";
		exit();
	}

	$json = file_get_contents($docfilename );
	$obj = json_decode($json);
	
	if ($default_workflow != '')
	{
		$workflow[$item] = $default_workflow;
	}
	else
	{
		// try to figure out the work flow
		
		if (isset($obj->issue_pages) && count($obj->issue_pages) > 0)
		{
			$workflow[$item] = 'issues';
		}
		else
		{
			$workflow[$item] = 'toc';
		}
		
		if (isset($obj->article_pages) && count($obj->article_pages) > 0)
		{
			$workflow[$item] = 'articles';
		}
	}

}

print_r($workflow);

/*
$workflow = array(332410 => 'toc');

$workflow = array(332345 => 'issues');
$workflow = array(332539 => 'issues');
*/



foreach ($workflow as $item => $taskname)
{
	$tools = array();

	switch ($taskname)
	{
		case 'articles':
			$tools = array('article2parts.php');
			break;
	
		case 'issues':
			$tools = array('issue2parts.php');
			break;	

		case 'toc':
			$tools = array('doc2toc.php', 'doc2text.php', 'toc2match.php', 'toc2parts.php', 'dedup.php');
			break;	
	
		default:
			break;
	
	}
	
	foreach ($tools as $tool)
	{
		$command = "php $tool $item.json";
		echo $command . "\n";
		system($command);	
	}
	
	// output
	switch ($format)
	{
		case 'ris':
			$command = "php parts2ris.php $item.json > $item.ris";
			break;
			
		case 'tsv':
		default:
			$command = "php parts2tsv.php $item.json > $item.tsv";
			break;
	}
	
	echo $command . "\n";
	system($command);	
	


}


?>
