<!doctype html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Analytics admin page</title>
        <link rel="icon" href="/favicon.ico">
    </head>
    <body>
        <h1>This months - Analytics - admin page</h1>
<?php

/**
 * @author Bob S. (Tips@TechnoWizardBob.com)
 * @copyright 2023
 * @license MIT
 */

const MY_PASSWORD = false;
/** 
 * @todo CHANGE MY_PASSWORD above:
 * It must be a string with a decent length!!!
 */

function grab_raw_post_data(int $Kibibytes = 500, int $params = 600): false|array {
	$stream = fopen('php://input', 'r');
	$i = 0;
	$post_data = '';
	while (!feof($stream)) {
		$i++; // 500 Kibibytes = 512 Kilobytes or .512Megabytes or Half 1MB
		$post_data .= fread($stream, 1024); // 1Kibibytes = 1024 Bytes = 8192 Bits = 1 Loop
		// Limit Data to 65KB, 64 Loops = 65536 characters
		// 8192 Bits × 500 Loops = 4096000 Bits = 512KB = 524288 characters
		if ($i > $Kibibytes) { 
			fclose($stream);
			return false;
		}   
	}
	fclose($stream);
	
	$count_params = substr_count($post_data, "&");
	if ($count_params > $params) {
		return false;
	}
	parse_str($post_data, $data_array);
	unset($post_data);
	return $data_array;
}

if (count($_POST) < 1) {
	$_POST = grab_raw_post_data(64, 8);
}

function data_output() {
    $db = new SQLite3('link_analytics.db');
    $currentMonth = date('Y-m');

    $query = "SELECT 
    id, ip_address, remote_agent, http_referer, link_name, redirect, created_at 
    FROM logs 
    WHERE strftime('%Y-%m', created_at) = :currentMonth";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':currentMonth', $currentMonth);

    $result = $stmt->execute();
    if (!$result) {
        return;
    }

    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        echo "ID: " . $row['id'] . "<br>";
        echo "IP Address: " . $row['ip_address'] . "<br>";
        echo "Remote Agent: " . $row['remote_agent'] . "<br>";
        echo "HTTP Referer: " . $row['http_referer'] . "<br>";
        echo "Short link name: " . $row['link_name'] . "<br>";
        echo "Redirect URL: " . $row['redirect'] . "<br>";
        echo "Clicked on: " . $row['created_at'] . "<br>";
        echo "--------------------------------------<br>";
    }
    $db->close();
}

function count_output() {
    $db = new SQLite3('link_analytics.db');
    $currentMonth = date('Y-m');

    $query = "SELECT 
    link_name, COUNT(*) as count 
    FROM logs 
    WHERE strftime('%Y-%m', created_at) = :currentMonth
    GROUP BY link_name";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':currentMonth', $currentMonth);

    $result = $stmt->execute();
    if (!$result) {
        return;
    }

    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $linkName = $row['link_name'];
        $count = $row['count'];
        echo "Short link name: $linkName, Count: $count<br>";
    }
    $db->close();    
}

function form_output() {
    ?>    
            <form method="POST">
                <label for="password">Enter Admin Password:</label>
                <input type="password" id="password" name="password" />
                <input type="submit" value="Submit" />
            </form>
    <?php
}

$pwd = $_POST['password'] ?? false;
if ($pwd === false || $pwd !== MY_PASSWORD || MY_PASSWORD === false) {
    form_output();
} else {
    count_output();
    echo "<hr/>";
    data_output();
}
?>
    </body>
</html>
