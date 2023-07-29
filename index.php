<?php

/**
 * @author Robert Strutts
 * @copyright 2023
 * @license MIT
 */

/**
 * Array of shortened links and their corresponding URLs
 * @todo Add new shortURLs to longURLs below here:
 * To be valid the shortURL needs to be a-z, 0-9, 3 to 8 Characters in length!!
 */ 
$a_my_links = [
    'sale'=>'https://amazon.com/AffiliateLINK_HERE',
    'test'=>'https://google.com',
    // Add MORE HERE
];

function do_error(string $error_code = "Unknown") {
?>    
    <!doctype html>
    <html lang="en">
    <head>
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1">
      <title>Link error page</title>
      <meta property="og:title" content="Link error page">
      <meta property="og:type" content="website">
      <meta property="og:description" content="Link error page.">
      <link rel="icon" href="/favicon.ico">
    </head>
    <body>
        <h1>Link error page</h1>
        <h2>Error: <span style="color: red;"><?= htmlentities($error_code ?? "Unknown") ?></span></h2>
    </body>
    </html>
<?php
    exit;    
}

function grab_raw_get_data(int $Kibibytes = 500, int $params = 600): false|array {
	if (!filter_has_var(INPUT_SERVER, "QUERY_STRING")) {
		return false;
	}
	if (strlen($_SERVER["QUERY_STRING"]) > $Kibibytes) {
		return false;
	}
	$get_data_raw = filter_input(INPUT_SERVER, "QUERY_STRING", FILTER_UNSAFE_RAW);
	if (empty($get_data_raw)) {
		return false;
	}
	$count_params = substr_count($get_data_raw, "&");
	if ($count_params > $params) {
		return false;
	}
	parse_str($get_data_raw, $get_data_array);
	unset($get_data_raw);
	return $get_data_array;
}

if (count($_GET) < 1) {
	$_GET = grab_raw_get_data(64, 8);
}

// Get the short link from the query string
$shortLink = isset($_GET['s']) ? $_GET['s'] : false;
if ($shortLink === false || is_array($shortLink)) {
    do_error('No link provided');
}

$s_short = strtolower($shortLink);
unset($shortLink);

// Validate the short link using filter_var
// The short link must only contains alphanumeric characters and has a length between 3 and 8 characters.
if (!filter_var($s_short, FILTER_VALIDATE_REGEXP, array('options' => array('regexp' => '/^[a-zA-Z0-9]{3,8}$/')))) {
    // If the short link is not valid, redirect to an error page or homepage
    do_error('Filter Error');
}

// Function to sanitize input data
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function log_link(string $link, string $redirector) {
    $db = new SQLite3('link_analytics.db');

    // Create a table to store the logs
    $query = "CREATE TABLE IF NOT EXISTS logs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                ip_address TEXT NOT NULL,
                remote_agent TEXT NOT NULL,
                http_referer TEXT NOT NULL,
                link_name TEXT NOT NULL,
                redirect TEXT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )";

    $db->exec($query);
    // Get the user's IP address
    $ip_address = sanitize_input($_SERVER['REMOTE_ADDR']);

    // Get the user's remote agent (User-Agent header)
    $remote_agent = sanitize_input($_SERVER['HTTP_USER_AGENT']);

    $referrer = $_SERVER['HTTP_REFERER'] ?? '';
    $http_ref = sanitize_input($referrer);  

    $link_name = sanitize_input($link);
    $redirect = sanitize_input($redirector);

    // Insert the log into the database
    $query = "INSERT INTO logs (ip_address, remote_agent, http_referer, link_name, redirect) VALUES (:ip_address, :remote_agent, :http_referer, :link_name, :redirect)";
    $stmt = $db->prepare($query);
    $stmt->bindValue(':ip_address', $ip_address, SQLITE3_TEXT);
    $stmt->bindValue(':remote_agent', $remote_agent, SQLITE3_TEXT);
    $stmt->bindValue(':http_referer', $http_ref, SQLITE3_TEXT);
    $stmt->bindValue(':link_name', $link_name, SQLITE3_TEXT);
    $stmt->bindValue(':redirect', $redirect, SQLITE3_TEXT);
    $stmt->execute();

    $db->close();
}

// Check if the short link exists in the array
if (array_key_exists($s_short, $a_my_links)) {
    $link = $a_my_links[$s_short] ?? false;
    if ($link === false) {
        do_error('Lookup failed');
    }
    // If the short link exists, redirect to the corresponding URL
    header('Location: ' . $link);
    log_link($s_short, $link); // Do Analytics
    exit;
} else {
    // If the short link does not exist in the array, redirect to an error page or homepage
    do_error('No short Link found');    
}