<?php

// Given TOC matched to pages in doc, extract metadata for each part using ChatGPT

require_once (dirname(__FILE__) . '/openai.php');
require_once (dirname(__FILE__) . '/shared.php');

//----------------------------------------------------------------------------------------
// Use ChatGPT to extract a list of structured data
function extract_structured($prompt, $text, $force = false)
{
	global $config;
	
	$chat_filename = $config['chat-cache'] . '/' . md5($prompt . $text) . '.json';

	if (!file_exists($chat_filename) || $force)
	{
		$json = conversation ($prompt, $text);
		file_put_contents($chat_filename, $json);
	}
	
	$json = file_get_contents($chat_filename);
	
	$json = preg_replace('/^\`\`\`json/', '', $json);
	$json = preg_replace('/\`\`\`\s*$/', '', $json);
	
	if (0)
	{
		echo "Response JSON\n";
		echo "-------------\n";
		echo $json;
	}
		
	$obj = json_decode($json);
	
	if (0)
	{
		echo "Response object\n";
		echo "---------------\n";
		print_r($obj);
	}
	
	return $obj;

}

//----------------------------------------------------------------------------------------

// "title", "authors", "journal", "volume", "issue", "pages", "year"

function extract_metadata($text, $title_hint = '', $keys = ["title", "authors"])
{
	$result = array();
	
	$prompt_lines = array();

	$prompt_lines[] = 'You are an expert in reading the first page of a schoalrly article and extracting the key bibliographic information.';
	
	$prompt_lines[] = 'Extract metadata for the article that starts on this page.';
	
	if ($title_hint != '')
	{
		$prompt_lines[] = 'The title is similar to "' . $title_hint . '"';
	}

	// $prompt_lines[] = 'Include the article abstract if present.';
	
	$prompt_lines[] = 'Output the results in JSON as an array of objects.';
	$prompt_lines[] = 'The object should following keys (where a value is available): "' . join(",", $keys) . '".';	
	$prompt_lines[] = 'The "authors" field should be an array.';	
	
	$prompt_lines[] = 'If title has Roman number as a prefix please retain that Roman number.';	

	$prompt_lines[] = 'Be sure not to conflate the journal, volume, and page numbers with the title of the article.';	
	
	$prompt_lines[] = 'Respect the original capitalisation of the title text.';
	
	$prompt_lines[] = 'Be careful not to confused numbers for plates with page numbers.';
	$prompt_lines[] = 'Plates typically use Roman numbers, do NOT use these for page numbers';

	$prompt_lines[] = 'The text to analyse is: ';

	$prompt = " \n" . join(" ", $prompt_lines);
	
	if (0)
	{
		echo $prompt . "\n";
		echo $text . "\n";
		echo "-------\n\n";
	}
	
	$result = extract_structured($prompt, $text);
	
	return $result;
}

//----------------------------------------------------------------------------------------

$debug = false;

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

if (isset($doc->pagenum_to_page))
{
	$doc->pagenum_to_page = (array)$doc->pagenum_to_page;
}

// settings
$make_title_uppercase = false;

switch ($doc->bhl_title_id)
{
	case 61893:
		$make_title_uppercase = true;
		//$make_title_uppercase = false;
		break;
		
	default:
		$make_title_uppercase = false;
		break;
		
}


$doc->parts = array();

// given the table of contents that have been matched to pages in the doc, let's get the
// article metadata
if (isset($doc->toc))
{
	foreach ($doc->toc as &$c)
	{
		if ($debug)
		{
			echo "Table of contents entry\n";
			echo "-----------------------\n";
			print_r($c);
		}
		
		// have we matched this content item to a page in the doc?
		if (isset($c->index))
		{
			// text for this page
			$haystack ='';
			if (isset($doc->pages[$c->index]->text))
			{
				$haystack = $doc->pages[$c->index]->text;	
			}
			
			$title = '';
			if ($c->title)
			{
				$title = $c->title;	
			}
			
			$articles = array();
			
			if ($haystack != '' && $title != '')
			{
				// default
				$keys = ["title", "authors", "journal", "volume", "issue", "pages", "year"]; 			
						
				if (isset($doc->bhl_title_id))
				{
					switch ($doc->bhl_title_id)
					{
								
						case 150137: // Biodiversity, biogeography and nature conservation in Wallacea and New Guinea
							$keys = ["title", "authors", "pages"];
							break;
							
						case 8068: // Anales de la Sociedad Española de Historia Natural
						case 10241: // Revista do Museu Paulista
						case 150137: // Contributions of the American Entomological Institute
						case 206514:
						case 154474:
						case 16176:
						case 16143:
						case 58209:
							$keys = ["title", "authors"];
							break;

						default:
							// journal with lots of metadata on page	
							$keys = ["title", "authors", "journal", "volume", "issue", "pages", "year"]; 			
							break;
					}
				}
			
				$articles = extract_metadata($haystack, $title, $keys);
			}
			
			if ($articles)
			{
			
				if ($debug)
				{
					echo "Corresponding article(s)\n";
					echo "------------------------\n";
					print_r($articles);
				}
			
				// clean 
				foreach ($articles as &$article)
				{
					foreach ($article as $k => $v)
					{
						// Empty
						if ($v == "")
						{
							unset($article->$k);
						}
						
						// Missing values
						if (isset($article->$k) && !is_array($article->$k))
						{
							if (preg_match('/(Unknown|Not specified|Not provided|N\/A)/i', $v))
							{
								unset($article->$k);
							}
						}
						
						// Empty arrays
						if (isset($article->$k) && is_array($article->$k) && count($article->$k) == 0)
						{
							unset($article->$k);
						}						
					
						// Clean up some values
						if (isset($article->$k))
						{
							switch ($k)
							{
								case 'year':
									$article->{$k} = preg_replace('/[A-Z]\w+\s+([0-9]{4})/', '$1', $v);
									break;

								case 'issue':
									$article->{$k} = preg_replace('/N°\s+/u', '$1', $v);
									$article->{$k} = preg_replace('/No\.?\s*/u', '$1', $v);
									break;
									
								case 'authors':
									foreach ($article->authors as &$author)
									{
										// suffix
										$author = preg_replace('/,\s+Es[o|q]\..*$/', '', $author);

										$author = preg_replace('/[~:\'\*°]/', '', $author);
										$author = preg_replace('/\s+$/', '', $author);
										$author = preg_replace('/([A-Z])\.([A-Z])/', '$1. $2', $author);
										$author = preg_replace('/([A-Z])\.([A-Z])/', '$1. $2', $author);
										
										// qualifications
										$author = preg_replace('/,\s+Es[q|a]\./', '', $author);
										
									
										$author = mb_convert_case($author, MB_CASE_TITLE);
									}
									break;

								case 'volume':
									if (preg_match('/[ivxlcm]/i', $article->{$k}))
									{
										$article->{$k} = arabic($article->{$k});
									}
								break;
									
								case 'title':
									if ($make_title_uppercase)
									{
										$article->{$k} = mb_convert_case($v, MB_CASE_UPPER);
									}
									else
									{
										//$article->{$k} = mb_convert_case($v, MB_CASE_TITLE);
									}
									
									$article->{$k} = preg_replace('/^Art\.\s+/i', '', $article->{$k});
									$article->{$k} = preg_replace('/^([IVXL]+)\.\s*([A-Z])/i', '$1.—$2', $article->{$k});
									$article->{$k} = preg_replace('/^([IVXL]+)\.\s*-(\s*-)?\s*([A-Z])/i', '$1.—$3', $article->{$k});
									break;
								
																
								default:
									break;							
							}
						}
					}
					
					// pagination?
					$pages = array();
					
					// by default use page number for this page
					if (isset($doc->pages[$c->index]->number))
					{
						$pages[0] = $doc->pages[$c->index]->number;
					}
					
					if (isset($article->pages))
					{
						if (preg_match('/(.*)-(.*)/', $article->pages, $m))
						{
							$pages[0] = trim($m[1]);
							$pages[1] = trim($m[2]);
						}
					}	
										
					if (count($pages) > 0)
					{
						$article->spage = $pages[0];
					}

					if (count($pages) > 1)
					{
						$article->epage = $pages[1];
					}
					
					// if no epage, maybe toc has it?
					if (!isset($article->epage) && isset($c->page))
					{
						if (preg_match('/(.*)--?(.*)/', $c->page, $m))
						{
							$pages[0] = $m[1];
							$pages[1] = $m[2];
							
							$article->epage = $pages[1];
						}						
					}
					
					if (!isset($article->spage) && isset($c->spage))
					{
						$article->spage = $c->spage;
					}					

					if (!isset($article->epage) && isset($c->epage))
					{
						$article->epage = $c->epage;
					}	
					
									
					
					// where does title start on the page?
					$article->title_start = $c->spans[1][0];
					
					// metadata
					
					// crude copy of BHL info
					if (isset($doc->volume) && !isset($article->volume))
					{
						$article->volume = $doc->volume;
					}
					if (isset($doc->issue) && !isset($article->issue))
					{
						$article->issue = $doc->issue;
					}
					if (isset($doc->year) && !isset($article->year))
					{
						$article->year = $doc->year;
					}
					if (isset($doc->title) && !isset($article->journal))
					{
						$article->journal = $doc->title;
					}

					if (isset($doc->issn) && !isset($article->issn))
					{
						$article->issn = $doc->issn;
						
						// sanity check
						if (strlen($article->issn) == 8)
						{
							$article->issn  = substr($article->issn , 0, 4) . '-' . substr($article->issn, 4);
						}
					}
					
					// BHL?
					if (isset($doc->bhl_title_id))
					{
						$article->url = 'https://biodiversitylibrary.org/page/' . $doc->pages[$c->index]->id;
						
						// case
						if (isset($article->title))
						{
							// special handling
							switch ($doc->bhl_title_id)
							{
								case 8068: // Anales de la Sociedad Española de Historia Natural
									$article->issn = '0210-5160';
									break;
									
								case 9612: // Bollettino della Società entomologica italiana
									$article->journal = 'Bollettino della Società entomologica italiana';
									$article->issn = '0037-8747';
									break;																		
							
								case 51678: // The journal of the Asiatic Society of Bengal
									$article->issn = '0368-1068';
									$article->journal = 'Journal of The Asiatic Society of Bengal';
									break;
									
								case 61893:
									$article->journal = 'Records of the South Australian Museum';
									break;
							
								case 79636: // Journal of the Entomological Society of Ontario
									$article->issn = '1713-7845';
									$article->journal =  'Journal of the Entomological Society of Ontario';
									break;

								case 87655: // Horae Societatis Entomologicae Rossicae
									$article->journal 	= 'Horae Societatis Entomologicae Rossicae';
									$article->volume 	= $doc->volume;
									$article->year 		= $doc->year;
									
									unset($article->issue);
									break;	
									
								case 12260: // Deutsche entomologische Zeitschrift Iris
									$article->journal = 'Deutsche entomologische Zeitschrift Iris';
									break;
																
								case 206514: // Contributions of the American Entomological Institute
									$article->title = mb_convert_case($article->title, MB_CASE_UPPER);
									break;
									
								case 212322: // Memorie della Società entomologica italiana
									$article->journal = 'Memorie della Società Entomologica Italiana';
									$article->issn = '2282-1228';
									break;	
																							
								default:
									break;
							}
						}
					}
					else
					{
						// Internet Archive?
						if (isset($doc->ia))
						{
							switch ($doc->ia)
							{
								default:
									$article->title = mb_convert_case($article->title, MB_CASE_UPPER);
									break;
							}
						
							$article->url = 'https://archive.org/details/' . $doc->ia . '/page/n' . $c->index . '/mode/1up';
						}
					
					}
						
				}
				
				// fix journal
				if (isset($doc->bhl_title_id))
				{
					switch ($doc->bhl_title_id)
					{
						case 8648:
							$article->journal = $doc->title;
							break;
							
						default:
							break;
					}
				}
			
				//print_r($articles);
				
				// ignore "empty" articles
				$go = true;
				
				$go = isset($article->title);				
				
				if ($go)
				{			
					if (!isset($doc->parts[$c->index]))
					{
						$doc->parts[$c->index] = array();
					}
					foreach ($articles as $article)
					{
						$doc->parts[$c->index][] = $article;
					}
				}
			}
			else
			{
				//echo "No article(s) found.\n";
			}
		}
	}
}

// if we don't have page ranges, try and get those

// ensure list of articles is sorted in order
$contents_list = array();
foreach ($doc->parts as $index => $articles)
{
	$contents_list[] = $index;
}
ksort($contents_list, SORT_NUMERIC);

$n = count($contents_list);

for ($i = 0; $i < ($n - 1); $i++)
{
	foreach($doc->parts[$contents_list[$i]] as &$article)
	{
		if (!isset($article->epage))
		{			
			// page fixes
			if (isset($doc->bhl_title_id))
			{
				switch ($doc->bhl_title_id)
				{
					case 61893: // RecordsSouthAus
						// get the current page number 
						
						$index = $contents_list[$i];
						if (isset($doc->pages[$index]->number))
						{
							$article->spage = $doc->pages[$index]->number;
							
							// try to extend it, assume pages are numbered consecuitively,
							// and articles are separated by un-numbered pages
							$page_index = $index;
							while (isset($doc->pages[$page_index]->number))
							{
								$article->epage = $doc->pages[$page_index]->number;
								$page_index++;
							}
						}
						break;
						
					default:
					
						$next_index = $i + 1;
						if (isset($doc->parts[$contents_list[$next_index]][0]->spage))
						{
							$next_spage = $doc->parts[$contents_list[$next_index]][0]->spage;
						
							if (isset($doc->pagenum_to_page[$next_spage]))
							{
								// to do: handle having multiple pages with the same number
								
								$next_spage_index = $doc->pagenum_to_page[$next_spage][0];
								
								// need to figure out if article ends on same page as next article, 
								// or before
								
								if (isset($doc->parts[$contents_list[$next_index]][0]->title_start))
								{
									if ($doc->parts[$contents_list[$next_index]][0]->title_start > 100)
									{
										// next title likely starts at the top of the page
										switch ($doc->bhl_title_id)
										{
											case 150137:
												$next_spage_index--;
												break;
																			
											default:
												break;
										}
									}
									else
									{
										// next title is down the page, so current article ends on
										// same page as next article starts
										$next_spage_index--;
									}
								}
								else
								{					
									$next_spage_index--;
								}
								
								// handle blank pages
								$is_blank = false;
								
								if (in_array('blank', $doc->pages[$next_spage_index]->tags))
								{
									$is_blank = true;
								}
								
								if (!isset($doc->pages[$next_spage_index]->number))
								{
									$is_blank = true;
								}					
								
								if ($is_blank)
								{
									$next_spage_index--;
								}
								
								if (isset($doc->pages[$next_spage_index]->number))
								{
									$article->epage = $doc->pages[$next_spage_index]->number;
									
									// sanity check
									if (isset($article->spage))
									{
										if ($article->epage < $article->spage)
										{
											unset($article->epage);
										}
									}
								}
							}
						}
						break;
				}
			
			}
		
		}
	}

}


// save updated document to disk
file_put_contents($filename, json_encode($doc, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

?>
