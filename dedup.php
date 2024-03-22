<?php

// remove "obvious" duplicates

require_once (dirname(__FILE__) . '/swa.php');


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



if (isset($doc->parts))
{
	foreach ($doc->parts as $index => &$articles)
	{
		$n = count($articles);
		$delete = array();
		
		for ($i = 0; $i < $n; $i++)
		{			
			for ($j = 0; $j < $i; $j++)
			{
				$text1 = $articles[$i]->title;
				$text2 = $articles[$j]->title;
				
				$alignment = swa($text1, $text2);
				print_r($alignment);
				
				if ($alignment->score > 0.9)
				{
					$delete[] = $j;
				}
				
			}
		}
		
		$delete = array_unique($delete);
		
		foreach ($delete as $j)
		{
			unset($articles[$j]);
		}
			

	}
}

// save updated document to disk
file_put_contents($filename, json_encode($doc, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));


?>
