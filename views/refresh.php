<?php
ini_set('output_buffering', 'off');
ini_set('zlib.output_compression', 0);

header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-cache'); // Forces the browser to not cache.
header('Connection: keep-alive'); // Keeps the connection open for continuous output.

require_once("partials/base.php");

if (!isset($_SESSION['password'])) {
    header('Location: ./login');
    exit();
}

// Start output buffering
ob_implicit_flush(true);
ob_start();

// Get URL from query 
$url = $config['public_txt_url'];

$url = $_GET['url'] ?? null;

if (filter_var($url, FILTER_VALIDATE_URL) === FALSE) {
    die('Not a valid URL');
}

// Build Following List
$twtFollowingList = [];

foreach ($fileLines as $currentLine) {
    if (str_starts_with($currentLine, '#')) {
        if (!is_null(getDoubleParameter('follow', $currentLine))) {
            $twtFollowingList[] = getDoubleParameter('follow', $currentLine);
        }
    }
}

// Loop over feeds followed 
$i = 1;
$total = count($twtFollowingList);

foreach ($twtFollowingList as $following) {
    $float = $i / $total;
    $percent = intval($float * 100) . "%";

    $fileURL = $following[1];
    echo "<br>\n<br>\n$percent - $fileURL<br>\n";
    updateCachedFile($fileURL);
    ob_flush(); // Send output to browser immediately
    flush();
    $i++;
}

ob_end_flush(); // End output buffering