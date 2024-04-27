<?php

// Export parts to other formats

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

$basename = basename($filename, '.json');

$json = file_get_contents ($filename);

$doc = json_decode($json);

$keys = array(
'title',
'authors',
'journal',
'issn',
'volume',
'issue',
'spage',
'epage',
'year',
'date',
'doi',
'url',
//'pageids'
);

$format = 'tsv';
//$format = 'csl';

if ($format == 'tsv')
{
	echo join("\t", $keys) . "\n";
}

if (isset($doc->parts))
{
	foreach ($doc->parts as $index => $articles)
	{
		foreach ($articles as $article)
		{
			//print_r($article);
			
			// RIS

			if ($format == 'tsv')
			{

				// TSV
				$row = array();
			
				foreach ($keys as $k)
				{
					if (isset($article->{$k}))
					{
						if (is_array($article->{$k}))
						{
							$row[] = join(";", $article->{$k});
						}
						else
						{
							$row[] = $article->{$k};
						}
					}
					else
					{
						$row[] = "";
					}
				}
			
				echo join("\t", $row) . "\n";
			}
			
			if ($format == 'csl')
			{
				// CSL
			
				$obj = new stdclass;
			
				foreach ($article as $k => $v)
				{
					switch ($k)
					{
						case 'title':
						case 'volume':
						case 'issue':
							$obj->{$k} = $v;
							break;
					
						case 'authors':
							$obj->author = array();
							foreach ($v as $a)
							{
								$author = new stdclass;
								$author->literal = $a;
								$obj->author[] = $author;
							}
							break;
					
						case 'journal':
							$obj->{'container-title'} = $v;
							break;
							
						case 'issn':
							$obj->ISSN = array($v);
							break;

						case 'year':
							$obj->issued = new stdclass;			
							$obj->issued->{'date-parts'} = array();
							$obj->issued->{'date-parts'}[0][] = (Integer)$v;
							break;
						
						case 'doi':
							$obj->DOI = $v;
							break;
						
						case 'url':
							$obj->URL = $v;
							break;
							
						case 'spage':
							if (isset($obj->page))
							{
								$obj->page  = $v . '-' . $obj->page;
							}
							else
							{
								$obj->page 	= $v;
							}
							$obj->{'page-first'} 	= $v;						
							break;
							
						case 'epage':
							if (isset($obj->page))
							{
								if ($obj->page !=  $v)
								{
									$obj->page  .= '-' . $v;
								}
							}
							else
							{
								$obj->page = $v;
							}
							break;							
			
						default:
							break;
					}
				}
			
				echo json_encode($obj) . "\n";
			}
			
		}
	}
}

?>
