#!/bin/env php
<?php
error_reporting(E_ALL ^ E_NOTICE);
require "config.php";

define("DEBUG", 0); // Debug mode is used as a way to test the script without setting it up
define("STDIN_PATH", DEBUG ? "mail.dump" : "php://stdin");

if(php_sapi_name() != "cli" && PHP_SAPI != "cli" && !DEBUG)
	exit; // No web access...

$_pipe = trim(file_get_contents(STDIN_PATH), "\r\n");
file_put_contents(tempnam("/tmp", "MAIL"), $_pipe);

// Time to parse the email.
list($_headers, $_body) = explode("\n\n", str_replace("\r\n", "\n", $_pipe), 2); // remove \r\n, stupid winblows

$_headers = http_parse_headers($_headers); // RFC-2822 uses the same struct as HTTP for headers

$_to = $_headers["To"];
$_cc = (array) $_headers["Cc"];
if(is_array($_to)){
	foreach($_to as $_i => $_to_orig){
		preg_match("/([^<]*)\w?\"?\'?<?([^>]*)\"?\'?[>]?/", $_to_orig, $_matches);
		$_to[$_i] = $_matches[2];
	}
	$_cc = array_diff(array_merge($_cc, $_to), array($_MAIN_EMAIL)); // remove the main email from here
	$_to = $_to[0]; // and finally set the first "to" to the original :)
}
$_subject = $_headers["Subject"];
$_from = $_headers["From"];
/* Rip the address from any GT/LT symbols */
preg_match("/([^<]*)\w?\"?\'?<?([^>]*)\"?\'?[>]?/", $_from, $_matches);
$_raw_from = $_matches[2];
$_raw_cc = array(); // TODO
foreach($_cc as $_cc_orig){
	preg_match("/([^<]*)\w?\"?\'?<?([^>]*)\"?\'?[>]?/", $_cc_orig, $_matches);
	$_raw_cc[] = $_matches[2];
}

$_content_type = $_headers["Content-Type"] != null ? "Content-Type: " . $_headers["Content-Type"]: ""; // Preserve this header
$_ticket_id = -1;
if(preg_match("/^(.*)?\[#([0-9]{1,12})\](.*)/", $_subject, $_matches)) { // Check the header for [#1234]
	$_result = $_pdo->query("SELECT * FROM `support_tickets` WHERE `ticket_id`='" . ((int) $_matches[2]) . "';")->fetchAll();
	if(count($_result) != 0)
		$_ticket_id = $_matches[2];
}
if($_ticket_id == -1){
	// Check if they have another open ticket in the past 24 hours, if so, add it to that - useful if they haven't received a response
	$_stmt = $_pdo->prepare("SELECT * FROM `support_tickets` WHERE `update` >= DATE_SUB(NOW(), INTERVAL 1 DAY) AND (`sender`=? OR FIND_IN_SET(?, `cc`));");
	$_stmt->execute(array($_raw_from, $_raw_from));
	if($_stmt->rowCount() > 0){
		$_ticket_id = $_stmt->fetchAll();
		$_ticket_id = $_ticket_id[0]["ticket_id"];
		$_new_ticket = true; // Tricks the below code into thinking a new ticket has already been created
	}
}
/***********************************

       Ticket Create/Update
       
***********************************/
if($_ticket_id == -1){
	// Let's create a ticket for this, and then send it to staff :)
	$_stmt = $_pdo->prepare("INSERT INTO `support_tickets` (`sender`, `cc`) VALUES (?, ?);");
	$_stmt->execute(array($_raw_from, implode(",", (array) $_raw_cc)));
	$_ticket_id = $_pdo->lastInsertId('ticket_id');
	$_new_ticket = true;
}
$_orig = "";
// now we update the ticket.
if(in_array($_raw_from, $_STAFF)){ // work out the original
	$_result = $_pdo->query("SELECT `sender` FROM `support_tickets` WHERE `ticket_id` = '" . ((int) $_ticket_id) . "';");
	$_orig = $_result->fetchAll();
	$_orig = $_orig[0]["sender"];
	$_is_staff_reply = true;
}
foreach(($_all_recps = array_unique(array_merge($_STAFF, (array) $_cc, (array) $_orig, (array) $_raw_from))) as $_recipient){ // Merge staff, Cc, Original Sender and From address, remove duplicates
	if($_recipient == $_raw_from || $_recipient == null) continue; // Don't send the email to myself, derp.
	mail($_recipient,
	      ($_new_ticket ? "Ticket [#" . $_ticket_id . "] - " . $_subject : $_subject),
	      $_body,
	      "From: " . ($_is_staff_reply && !in_array($_recipient, $_STAFF) ? $_MAIN_EMAIL : $_from) . "\r\nReply-To: " . $_MAIN_EMAIL . "\r\n" . $_content_type // Mask the support agent's email to create "thread" for client, but set reply-to for staff
	);
}

