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

require_once(__DIR__ . '/config.php');
require_once(__DIR__ . '/functions.php');

$access_key = isset($_GET['access']) ? sanitize($_GET['access']) : '';
$ythandle = isset($_GET['ytid']) ? strtolower(sanitize($_GET['ytid'])) : '';
$ezhandle = isset($_GET['ezid']) ? strtolower(sanitize($_GET['ezid'])) : '';

// Basic "security"
if(empty($access_key) OR $access_key !== trim(ACCESS)) {
	echo 'Access key incorrect!';
	if(ERROR_LOG) logger('YT: Access key incorrect on subscribe.php.');
	exit;
}

if(!empty($ythandle)) {
	// Remove encoded @
	if(substr($ythandle, 0, 3) == "%40") {
		$ythandle = substr($ythandle, 3);
	}
	
	// Remove @
	if(substr($ythandle, 0, 1) == "@") {
		$ythandle = substr($ythandle, 1);
	}

	$feed_file = "ytrss";
	$handle = $ythandle;
}

// Check IMDb id */
if(!empty($ezhandle)) {
	// Add prefix if it's not there
	if(substr($ezhandle, 0, 2) != "tt") {
		$ezhandle = "tt".$ezhandle;
	}

	$feed_file = "eztv";
	$handle = $ezhandle;
}

// Figure out the URL (for sharing this page)
$current_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
$current_url .= '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>GooseRSS: RSS Link Generator</title>
	<link rel="stylesheet" href="./assets/embed-simple.css">

	<meta name="description" content="Generate Youtube and EZTV Rss feeds that you can subscribe to in a RSS reader." />
	<meta name="generator" content="GooseRSS - RSS Link Generator" />

	<meta property="og:type" content="website" />
	<meta property="og:locale" content="en_US" />
	<meta property="og:url" content="<?php echo $current_url; ?>" />
	<meta property="og:site_name" content="GooseRSS - RSS Link Generator" />
	<meta property="og:title" content="RSS Link Generator" />
	<meta property="og:description" content="Generate Youtube and EZTV Rss feeds that you can subscribe to in a RSS reader." />
</head>

<body id="top">

	<header>
		<h1>RSS Link Generator</h1>
	</header>
	
	<main>
		<section id="text">

			<div class="card">
		    <?php if(!empty($handle)) { ?>

	        <div class="result">
	            <strong>Your RSS Link:</strong><br>
	            <p>[ <a href="<?php echo MAIN_URL.$feed_file.".php?access=".ACCESS."&id=".$handle; ?>">Subscribe to <?php echo $handle; ?></a> ]</p>
	            
	            <p class="copy-hint">If clicking the above link doesn't prompt your RSS reader to subscribe, right-click the link and select "Copy Link Address" to paste it into your RSS reader.</p>
	            <p><a href="<?php echo MAIN_URL."subscribe.php?access=".ACCESS; ?>">Make another link</a></p>
	        </div>

		    <?php } else { ?>

	        <p>Enter a YouTube Channel Handle or IMDb ID in the correct field and click 'Generate Link' to get a RSS link for either item.</p>
	        <p>The generated links won't work if you enter the wrong values. Check the error.log file for details if a feed doesn't work.</p>

		    <form method="GET">
		        <label for="handle">Enter a YouTube Handle:</label>
		        <input type="hidden" name="access" id="access" value="<?php echo $access_key; ?>">
		        <input type="text" name="ytid" id="ytid" placeholder="e.g. @arnandegans or arnandegans">

				<p>- OR -</p>
				
		        <label for="handle">Enter an IMDb ID:</label>
		        <input type="text" name="ezid" id="ezid" placeholder="e.g. tt0758774 or 0758774">
		        <button type="submit">GENERATE LINK</button>
		    </form>

		    <?php } ?>
			</div>

		</section>
	</main>

	<footer>
		<p>This page does not store or distribute videos.</p>
	</footer>

</body>
</html>