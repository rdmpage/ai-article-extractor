<?php

// Given OCR text for VOLUME, try and extract articles titles

require_once (dirname(__FILE__) . '/openai.php');

$config['chat-cache'] = dirname(__FILE__) . '/chat-cache';

//----------------------------------------------------------------------------------------
// Use ChatGPT to extract a list of structured data
function extract_things($prompt, $text, $force = false)
{
	global $config;
	
	$chat_filename = $config['chat-cache'] . '/' . md5($prompt . $text) . '.json';

	if (!file_exists($chat_filename) || $force)
	{
		$json = conversation ($prompt, $text);
		file_put_contents($chat_filename, $json);
	}
	
	$result = file_get_contents($chat_filename);
	
	return $result;

}

//----------------------------------------------------------------------------------------


$filename = '343274.txt';
$filename = '340906.txt';
$filename = '188347.txt';
$filename = '95824.txt';

$text = file_get_contents($filename);
$text = preg_replace('/\x0D$/u', '', $text);
$pages = preg_split('/\x0D/u', $text);

$chunk_size = 10;

$chunks = array_chunk($pages, $chunk_size);

//print_r($chunks);

foreach ($chunks as $chunk)
{
	$text = join("<PAGEBREAK>\n", $chunk);
	
	//echo $text;
	
	$prompt_lines = array();
	
	$prompt_lines[] = "You are an expert at reading a journal volume and recognising the individual articles.";
	$prompt_lines[] = "Each page is separated by the text '<PAGEBREAK>'.";
	$prompt_lines[] = "The starting page for an article typically has information on the journal name, volume, year, and pages at the very top of the page, followed by title and authors (or authors and title).";
	$prompt_lines[] = "The title is usually separated from the rest of the text by on or more blank lines.";

	$prompt_lines[] = "The bibliographic details for the article are usually in the first ten lines of text on a page.";
	
	$prompt_lines[] = "Given the text, extract bibliographic details for the article that starts on that page.";
	$prompt_lines[] = "Do not include any articles listed in literature cited, references, tables of contents, or the index.";	
	
	$prompt_lines[] = "Format the output in RIS format.";	


	// older stuff
	$prompt_lines = array();
	
	$prompt_lines[] = "You are an expert at reading a journal volume and recognising the individual articles.";
	$prompt_lines[] = "Each page is separated by the text '<PAGEBREAK>'.";
	$prompt_lines[] = "The starting page for an article typically has information on the title, author, and starting page at the top.";

	$prompt_lines[] = "The title is usually in ALL CAPs.";
	$prompt_lines[] = "The author name will appear after the title";
	$prompt_lines[] = "The author's name is usually preceeded by the word 'par' or 'by'";
	
	$prompt_lines[] = "If the title page mentions that the article has plates, please include that as a note.";	
	$prompt_lines[] = "Be careful not to be confused by running heads which repeat the title at the top of alternating pages. Do not use these to start a new article.";
	$prompt_lines[] = "If the title of an article is the same as the previous article it is likely the same article continued, so merge those articles together.";	
	

	//$prompt_lines[] = "The bibliographic details for the article are usually in the first ten lines of text on a page.";
	
		
	$prompt_lines[] = "Given the text, extract bibliographic details for the article that starts on that page.";
	$prompt_lines[] = "Do not include any articles listed in literature cited, references, tables of contents, or the index.";	
	
	$prompt_lines[] = "The last page of an article often lists plates, and the page may be partly blank and so have less text than usual.";	
	
	$prompt_lines[] = "Format the output as JSON listing title, author, start page and end page (if known), and note.";	
	
	
	
	$prompt = join("\n", $prompt_lines);
	
	$result = extract_things($prompt, $text);
	
	echo $result;
}


?>
