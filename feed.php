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
$handle = isset($_GET['id']) ? strtolower(sanitize($_GET['id'])) : '';

// Remove encoded @
if(substr($handle, 0, 3) == "%40") {
	$handle = substr($handle, 3);
}

// Remove @
if(substr($handle, 0, 1) == "@") {
	$handle = substr($handle, 1);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>YouTube RSS Generator</title>
    <style>
        body { font-family: sans-serif; display: flex; justify-content: center; padding: 50px; background: #f4f4f4; }
        .card { background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); width: 100%; max-width: 400px; }
        input[type="text"] { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        button { width: 100%; padding: 10px; background: #ff0000; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; }
        .result { margin-top: 20px; padding: 15px; background: #e7f3ff; border-radius: 4px; word-break: break-all; }
        .copy-hint { font-size: 0.8rem; color: #666; margin-top: 5px; }
    </style>
</head>
<body>

<?php
// Basic "security"
if(empty($access_key) OR $access_key !== trim(ACCESS)) {
	echo 'Access key incorrect!';
	if(ERROR_LOG) logger('YT: Access key incorrect on feed.php.');
	exit;
}
?>
<div class="card">
    <h2>RSS Link Generator</h2>

    <form method="GET">
        <label for="handle">Enter YouTube Handle:</label>
        <input type="hidden" name="access" id="access" value="<?php echo $access_key; ?>">
        <input type="text" name="id" id="handle" placeholder="e.g. @arnandegans or arnandegans" value="<?php echo htmlspecialchars($handle); ?>" required>
        <button type="submit">Generate Link</button>
    </form>

    <?php if(!empty($handle)): ?>
        <div class="result">
            <strong>Your RSS Link:</strong><br>
            <a href="<?php echo MAIN_URL."ytrss.php?access=".ACCESS."&id=".$handle; ?>" target="_blank">Subscribe to <?php echo $handle; ?></a>
            
            <p class="copy-hint">If clicking the link doesn't prompt your RSS reader to subscribe, right-click the link and select "Copy Link Address" to paste it into your RSS reader.</p>
        </div>
    <?php endif; ?>
</div>

</body>
</html>