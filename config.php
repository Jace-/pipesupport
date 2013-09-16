<?php
/*
 The following directive should contain all the emails of your reps
*/
$_STAFF = array(
	"you@example.com",
	"jamie@r.cx"
);
/*
 This parameter should match the support email your clients are given
*/
$_MAIN_EMAIL = "support@yourdomain.com";

$_DATABASE = array(
	"type" => "mysql",
	"host" => "localhost",
	"username" => "root",
	"password" => "",
	"database" => "pipesupport"
);

$_pdo = new PDO($_DATABASE['type'] . ":host={$_DATABASE['host']};dbname={$_DATABASE['database']}", $_DATABASE['username'], $_DATABASE['password']);
// Create the table if we don't already have it
$_pdo->exec("CREATE TABLE IF NOT EXISTS `support_tickets` (
	`ticket_id` INT NOT NULL AUTO_INCREMENT,
	PRIMARY KEY(`ticket_id`),
	`sender` VARCHAR(255),
	`cc` TEXT,
	`update` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) AUTO_INCREMENT=10000000;"); 

// Credit to ricardovermeltfoort[at]gmail[dot]com
// http://www.php.net/manual/en/function.http-parse-headers.php#112986
if(!function_exists('http_parse_headers')) {
	function http_parse_headers($_headers) {
		$headers = array();
		$key = '';
		foreach(explode("\n", $_headers) as $i => $h) {
			$h = explode(':', $h, 2);
			if (isset($h[1])) {
				if (!isset($headers[$h[0]]))
					$headers[$h[0]] = trim($h[1]);
				elseif (is_array($headers[$h[0]]))
					$headers[$h[0]] = array_merge($headers[$h[0]], array(trim($h[1])));
				else
					$headers[$h[0]] = array_merge(array($headers[$h[0]]), array(trim($h[1])));
				$key = $h[0];
			} else {
				if (substr($h[0], 0, 1) == "\t")
					$headers[$key] .= "\r\n\t".trim($h[0]);
				else if (!$key) // [+]
					$headers[0] = trim($h[0]);
			}
		}
		return $headers;
	}
}

