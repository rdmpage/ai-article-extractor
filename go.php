<?php

// Create a workflow for a given title

require_once (dirname(__FILE__) . '/config.inc.php');


$TitleID = 206514;
$items = array();

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
	$json = file_get_contents($output_dir . '/' . $item . '.json');
	$obj = json_decode($json);
	
	if (count($obj->issue_pages) > 0)
	{
		$workflow[$item] = 'issues';
	}
	else
	{
		$workflow[$item] = 'toc';
	}

}

print_r($workflow);

exit();

$workflow = array(332410 => 'toc');

$workflow = array(332345 => 'issues');
$workflow = array(332539 => 'issues');




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
	$command = "php parts2ris.php $item.json > $item.ris";
	echo $command . "\n";
	system($command);	
	


}


?>
