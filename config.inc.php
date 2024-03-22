<?php

error_reporting(E_ALL);

global $config;

// Date timezone
date_default_timezone_set('UTC');

if (file_exists(dirname(__FILE__) . '/env.php'))
{
	include 'env.php';
}

$config['cache']   = dirname(__FILE__) . '/cache';

$config['bhl_key'] =  getenv('BHL_APIKEY');

$config['openai_key'] = getenv('OPENAI_APIKEY');
$config['openai_embeddings'] = 'https://api.openai.com/v1/embeddings';
$config['openai_completions'] = 'https://api.openai.com/v1/chat/completions';

?>
