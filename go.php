<?php

// Create a workflow for a given title

require_once (dirname(__FILE__) . '/config.inc.php');

$format = 'ris';
$format = 'tsv';

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

if (1)
{
	// Kansas, seems we have issues, need to debug

	$TitleID = 3179 ;// University of Kansas Science Bulletin
	$items = array();
}

/*
<option value="0|327026|">Vol. 10 : no. 2 (1992)</option>
<option value="0|327048|">Vol. 7 : no. 1 (1988)</option>
<option value="0|327050|">Vol. 8 : no. 2 (1989)</option>
<option value="0|327051|">Vol. 10 : no. 1 (1992)</option>
<option value="0|327059|">Vol. 7 : no. 3 (1988)</option>
<option value="0|327061|">Vol. 9 : no. 1 (1991)</option>
<option value="0|327062|">Vol. 9 : no. 2 (1991)</option>
<option value="0|327066|">Vol. 8 : no. 1 (1989)</option>
<option value="0|327067|">Vol. 9 : no. 3 (1991)</option>
<option value="0|327068|">Vol. 9 : no. 4 (1991)</option>
<option value="0|327078|">Vol. 7 : no. 4 (1988)</option>
<option value="0|327085|">Vol. 8 : no. 3-no. 4 (1989)</option>
<option value="0|327352|">Vol. 7 : no. 2 (1988)</option>
<option value="0|327366|">Vol. 10 : no. 4 (1992)</option>
<option value="0|327397|">Vol. 10 : no. 3 (1992)</option>
<option value="0|329862|">Vol. 11 : no. 3 (1993)</option>
<option value="0|329870|">Vol. 11 : no. 1 (1993)</option>
<option value="0|329874|">Vol. 12 : no. 1 (1994)</option>
<option value="0|329875|">Vol. 12 : no. 3 (1994)</option>
<option value="0|329879|">Vol. 13 : no. 1 (1995)</option>
<option value="0|329976|">Vol. 11 : no. 4 (1993)</option>
<option value="0|329990|">Vol. 12 : no. 4 (1995)</option>
<option value="0|329996|">Vol. 12 : no. 2 (1994)</option>
<option value="0|329997|">Vol. 11 : no. 2 (1993)</option>
<option value="0|330049|">Vol. 13 : no. 2(1995)</option>
<option value="0|331267|">Vol. 13 : no. 4 (1996)</option>
<option value="0|331356|">Vol. 13 : no. 3 (1995)</option>
<option value="0|331786|">Vol. 19 : no. 1 (2001)</option>
<option value="0|331850|">Vol. 20 : no. 1 - no. 2 (2002)</option>
<option value="0|331851|">Vol. 16 : no. 3 - no. 4 (1999)</option>
<option value="0|331860|">Vol. 17 : no. 1 - no. 2 (1999)</option>
<option value="0|331866|">Vol. 20 : no. 3 - no. 4 (2003)</option>
<option value="0|331870|">Vol. 19 : no. 2 - no. 4 (2001)</option>
<option value="0|331898|">Vol. 14 : no. 2 (1996)</option>
<option value="0|331902|">Vol. 15 : no. 1 (1997)</option>
<option value="0|331908|">Vol. 16 : no. 1 - no. 2 (1998)</option>
<option value="0|331911|">Vol. 18 : no. 1 - no. 2 (2000)</option>
<option value="0|331918|">Vol. 18 : no. 3 - no. 4 (2001)</option>
<option value="0|331939|">Vol. 15 : no. 3 (1997)</option>
<option value="0|331962|">Vol. 17 : no. 3 - no. 4 (2000)</option>
<option value="0|331975|">Vol. 15 : no. 2 (1997)</option>
<option value="0|331979|">Vol. 14 : no. 1 (1996)</option>
<option value="0|331980|">Vol. 15 : no. 4 (1998)</option>
<option value="0|331981|">Vol. 14 : no. 3 (1996)</option>
<option value="0|331985|">Vol. 14 : no. 4 (1997)</option>
<option value="0|332651|">Vol. 23 : no. 3 - no. 4 (2006)</option>
<option value="0|332657|">Vol. 21 : no. 3 - no. 4 (2004)</option>
<option value="0|332658|">Vol. 25 : no. 3 - no. 4 (2008)</option>
<option value="0|332659|">Vol. 21 : no. 1 - no. 2 (2003)</option>
<option value="0|332666|">Vol. 22 : no. 1 - no. 2 (2004)</option>
<option value="0|332678|">Vol. 27 : no. 3 (2011)</option>
<option value="0|332697|">Vol. 23 : no. 1 - no. 2 (2005)</option>
<option value="0|332701|">Vol. 27 : no. 4 (2011)</option>
<option value="0|332725|">Vol. 26 : no. 1 - no. 4 (2009)</option>
<option value="0|332729|">Vol. 27 : no. 2 (2009)</option>
<option value="0|332735|">Vol. 27 : no. 1 (2009)</option>
<option value="0|332747|">Vol. 25 : no. 1 - no. 2 (2007)</option>
<option value="0|332757|">Vol. 22 : no. 3 - no. 4 (2005)</option>
<option value="0|332777|">Vol. 24 : no. 1 - no. 4 (2006)</option>
*/


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
		
	if (isset($obj->issue_pages) && count($obj->issue_pages) > 0)
	{
		$workflow[$item] = 'issues';
	}
	else
	{
		$workflow[$item] = 'toc';
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
