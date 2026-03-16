<?php
/* ---------------------------------------------------------------------------
*  gooseRSS the YouTube and EZTV RSS Generator.
*
*  COPYRIGHT NOTICE
*  Copyright 2025-2026 Arnan de Gans. All Rights Reserved.
*
*  COPYRIGHT NOTICES AND ALL THE COMMENTS SHOULD REMAIN INTACT.
*  By using this code you agree to indemnify Arnan de Gans from any
*  liability that might arise from its use.
--------------------------------------------------------------------------- */

/* ------------------------------------------------------------------------ */
/* CACHING																	*/
/* ------------------------------------------------------------------------ */
function cache_set($key, $data, $prefix) {
	if(!is_dir(__DIR__ . CACHE_DIR)) {
		@mkdir(__DIR__ . CACHE_DIR, 0755, true);
	}
	
	$file = __DIR__ . CACHE_DIR . '/' . $prefix . md5($key) . '.cache';
	@file_put_contents($file, serialize($data));
}

function cache_get($key, $prefix) {
	$file = __DIR__ . CACHE_DIR . '/' . $prefix . md5($key) . '.cache';

	if(!is_file($file)) {
		return false;
	}

	return unserialize(file_get_contents($file));
}

function cache_delete($key, $prefix, $ttl) {
	$file = __DIR__ . CACHE_DIR . '/' . $prefix . md5($key) . '.cache';

	// Delete if expired
	if(filemtime($file) < (time() - $ttl)) {
		unlink($file);
	}
}

/* ------------------------------------------------------------------------ */
/* SANITIZE / FORMAT VARIABLES												*/
/* ------------------------------------------------------------------------ */
function sanitize($variable, $keep_newlines = false) {
	switch(gettype($variable)) {
		case 'string':
			if(str_contains($variable, '<')) {
				$variable = preg_replace('/<(\s;)?br \/>/im', ' ', $variable);
				$variable = strip_tags($variable);
				$variable = str_replace('<\n', '&lt;\n', $variable);
			}

			if(!$keep_newlines) {
				$variable = preg_replace('/[\r\n\t ]+/', ' ', $variable);
			}

			$variable = trim(preg_replace('/ {2,}/', ' ', $variable));
		break;
		case 'integer':
			$variable = preg_replace('/[^0-9]/', '', $variable);
			if(strlen($variable) == 0) $variable = 0;
		break;
		case 'boolean':
			$variable = ($variable === FALSE) ? 0 : 1;
		break;
		default:
			$variable = ($variable === NULL) ? 'NULL' : htmlspecialchars(strip_tags(trim($variable)), ENT_QUOTES);
		break;
	}

    return $variable;
}

function human_filesize($bytes, $dec = 2) {
    $size = array('B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
    $factor = floor((strlen($bytes) - 1) / 3);

    return sprintf("%.{$dec}f ", $bytes / pow(1024, $factor)) . @$size[$factor];
}

/* ------------------------------------------------------------------------ */
/* LOG ERRORS AND RESULTS													*/
/* ------------------------------------------------------------------------ */
function logger($error_message, $error = true) {
	// Path of the log file where stuff needs to be logged
	$log_file = ($error) ? "error.log" : "success.log";
	$log_file = __DIR__ . '/' . $log_file;
	
	// Add a newline and date and store the text
	$error_message = "[" . date('r', time()) . "] " . $error_message . "\n";
	error_log($error_message, 3, $log_file);
}

/* ------------------------------------------------------------------------ */
/* DO CURL REQUEST															*/
/* ------------------------------------------------------------------------ */
function make_request($url) {	
    $headers = array(
		'Accept: text/html, application/xhtml+xml, application/xml;q=0.8, application/json;q=0.9, */*;q=0.7',
		'Accept-Language: en-US,en;q=0.5',
		'Accept-Encoding: gzip, deflate',
// 		'Connection: keep-alive',
		'Upgrade-Insecure-Requests: 1',
		'User-Agent: '.trim(USER_AGENT),
		'Sec-Fetch-Dest: document',
		'Sec-Fetch-Mode: navigate',
		'Sec-Fetch-Site: none',
//		'Pragma: no-cache',
//		'Cache-Control: no-cache',
    );

	$ch = curl_init();

	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_HTTPGET, 1); // Redundant? Probably...
	curl_setopt($ch, CURLOPT_PROTOCOLS, CURLPROTO_HTTPS | CURLPROTO_HTTP);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate');
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
	curl_setopt($ch, CURLOPT_REDIR_PROTOCOLS, CURLPROTO_HTTPS | CURLPROTO_HTTP);
	curl_setopt($ch, CURLOPT_TIMEOUT, 3);
	curl_setopt($ch, CURLOPT_VERBOSE, false);

	// execute
	if(!$response = curl_exec($ch)) {
	    // some kind of an error happened
		if(ERROR_LOG) logger('cURL Error: '.curl_error($ch));
	    curl_close($ch);
		
		return false;
	}

	curl_close($ch);
	return $response;
}

/* ------------------------------------------------------------------------ */
/* FIND YOUTUBE CHANNEL ID													*/
/* ------------------------------------------------------------------------ */
function get_youtube_channel_id($handle) {	
	// Fetch the HTML
	$html = make_request('https://www.youtube.com/@'.$handle.'/videos');

	if($html === false) {
		if(ERROR_LOG) logger('YT: Could not access the URL for Channel `@'.$handle.'`.');
		exit;
	}

	/**
	* Extract the Channel ID
	* YouTube keeps the ID in several places:
	* - 'externalId' (most reliable in JS objects)
	* - 'channelId' (standard meta tag)
	* - 'browseId' (often used in internal navigation)
	*/
	$patterns = array(
		'/"externalId":"(UC[a-zA-Z0-9_-]{22})"/', 
		'/"channelId":"(UC[a-zA-Z0-9_-]{22})"/',
		'/itemprop="channelId" content="(UC[a-zA-Z0-9_-]{22})"/'
	);
	
	// Find, cache and return the Channel ID
	foreach($patterns as $pattern) {
		if(preg_match($pattern, $html, $matches)) {
			return $matches[1];
		}
	}
	
	return false;
}

/* ------------------------------------------------------------------------ */
/* PUT RSS FEED TOGETHER												 	*/
/* ------------------------------------------------------------------------ */
function generate_rss_feed($filtered, $now) {
	$rss = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
	$rss .= "<rss version=\"2.0\">\n";
	$rss .= "  <channel>\n";
	$rss .= "    <title>".$filtered['channel_name']."</title>\n";
	$rss .= "    <description>RSS feed for ".$filtered['channel_name']."</description>\n";
	$rss .= "    <link>".$filtered['channel_url']."</link>\n";
	$rss .= "    <lastBuildDate>".date('r', $now)."</lastBuildDate>\n";
	$rss .= "    <generator>gooseRSS</generator>\n";
	
	foreach($filtered['items'] as $item) {
		$rss .= "    <item>\n";
		$rss .= "      <title>".$item['title']."</title>\n";
		$rss .= "      <link><![CDATA[".$item['link']."]]></link>\n";
		$rss .= "      <pubDate>".date("r", $item['date_released'])."</pubDate>\n";
		$rss .= "      <guid isPermaLink=\"false\">".md5($item['link'])."</guid>\n";
		$rss .= "      <description><![CDATA[".$item['description']."]]></description>\n";
		$rss .= "    </item>\n";
		
		unset($item);
	}
	
	$rss .= "  </channel>\n";
	$rss .= "</rss>";

	return $rss;
}
?>