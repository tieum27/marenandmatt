<?php
/****************************************************************************
 * DRBPoll
 * http://www.dbscripts.net/poll/
 * 
 * Copyright � 2007-2008 Don B 
 ****************************************************************************/

$PREVENT_DUPLICATE_VOTES = TRUE;
$SHOW_COUNTS = TRUE;

require_once(dirname(__FILE__) . '/config.php');

function show_vote_control($poll_id) {
	global $POLL_URL;
	global $VOTE_STRING;
	global $VOTE_PARAM_NAME;
	global $VOTE_LIST_DEFAULT_LABEL;
	global $VALID_POLLS;
	global $POLL_ID_PARAM_NAME;
	global $SUBMIT_BUTTON_STRING;
	global $VIEW_RESULTS_STRING;
	
	// Validate parameters
	if(!is_valid_poll_id($poll_id)) {
		die('ERROR: An invalid poll ID was submitted.');
	}
	
	$poll = $VALID_POLLS[$poll_id];

	// Output vote control
	echo("<div class=\"voteContainer\">\r\n");
	echo("\t<form class=\"vote\" method=\"post\" action=\"" . $POLL_URL . "vote.php\" target=\"_new\">\r\n");
	echo("\t<fieldset>\r\n");
	if(!empty($poll->legend) && sizeof($poll->legend) > 0) {
		echo("\t<legend>" . htmlspecialchars($poll->legend) . "</legend>\r\n");
	}
	echo("\t<p align=\"left\">\r\n");
	echo("\t\t" . htmlspecialchars($poll->question) . "\r\n");
	echo("\t</p>\r\n\t<p align=\"left\">\r\n");
	
	global $CONTROL_RADIOBUTTONS;
	global $CONTROL_COMBOBOX;
	if($poll->control_type === $CONTROL_RADIOBUTTONS) {
		
		// Iterate through poll values	
		foreach ($poll->values as $value_id => $description) {
			echo("\t\t<input type=\"radio\" name=\"" . htmlspecialchars($VOTE_PARAM_NAME) .
				"\" value=\"" . htmlspecialchars($value_id) . "\" /> " . htmlspecialchars($description) . "<br />\r\n");
		}
		
		echo("\t</p>\r\n\t<p align=\"left\">\r\n");
		
	} else if($poll->control_type === $CONTROL_COMBOBOX) {
		
		echo("\t\t" . htmlspecialchars($VOTE_STRING) . "\r\n");
	
		echo("\t\t<select name=\"" . htmlspecialchars($VOTE_PARAM_NAME) . "\">\r\n");
		echo("\t\t\t<option value=\"\">" . htmlspecialchars($VOTE_LIST_DEFAULT_LABEL) . "</option>\r\n");
	
		// Iterate through poll values	
		foreach ($poll->values as $value_id => $description) {
			echo("\t\t\t<option value=\"" . htmlspecialchars($value_id) . "\">");
			echo(htmlspecialchars($description));
			echo("</option>\r\n");
		}
		echo("\t\t</select>\r\n");
		
	} else {
		
		die('ERROR: Invalid control type.');
		
	}
	echo("\t\t<input type=\"hidden\" name=\"" . htmlspecialchars($POLL_ID_PARAM_NAME) . "\" value=\"" . htmlspecialchars($poll_id) . "\" />\r\n");
	echo("\t\t<input type=\"submit\" value=\"" . htmlspecialchars($SUBMIT_BUTTON_STRING) . "\" class=\"submit\" />\r\n");
	echo("\t</p>\r\n\t<p align=\"left\" class=\"style2\">\r\n");
	
	// Show results link
	echo("\t\t<a href=\"" . $POLL_URL . "results.php?" . htmlspecialchars($POLL_ID_PARAM_NAME) . "=" . htmlspecialchars($poll_id) . "\" target=\"_new\">" . htmlspecialchars($VIEW_RESULTS_STRING) . "</a>\r\n");
	
	echo("\t</p>\r\n");
	echo("\t</fieldset>\r\n");
	echo("\t</form>\r\n");
	echo("</div>\r\n");
	
}

function show_poll_results($poll_id) {
	
	// Validate parameters
	if(!is_valid_poll_id($poll_id)) {
		die('ERROR: An invalid poll ID was submitted.');
	}
	
	// Get poll object
	global $VALID_POLLS;
	$poll = $VALID_POLLS[$poll_id];
	
	// Get vote summary
	$summarylist = vote_summary_list($poll_id);
	if($summarylist !== FALSE) {
		$totalVotes = $summarylist[0];
		$largest_count = find_largest_vote_value_count($summarylist);
	} else {
		$totalVotes = 0;
		$largest_count = 0;
	}
	
	// Start table for results
	echo("<table class=\"pollTable\">\r\n");
	
	// Iterate through poll values
	$barNumber = 1;
	global $MAX_POLL_BAR_WIDTH;
	global $SHOW_COUNTS;
	foreach($poll->values as $value_id => $description) {
		
		// Find vote count for this value
		$summary_row = find_vote_value_summary($value_id, $summarylist);
		
		if($summary_row === FALSE) {
			$count = 0;
		} else {
			$count = $summary_row[1];
		}
		
		$percentage = (($totalVotes > 0)?($count / $totalVotes):0);
		$bar_percentage = (($largest_count > 0)?($count / $largest_count):0);
		echo("\t<tr>");
		echo("<td class=\"pollDescriptionCell\" >" . htmlspecialchars($description) . "</td>");
		if($count > 0) {
			echo("<td class=\"pollBarCell\"><div class=\"pollBar\" id=\"pollBar" . ($barNumber++) . "\" style=\"width:" . round($MAX_POLL_BAR_WIDTH * $bar_percentage, 0) . "px;\"></div></td>");
		}
		echo("<td class=\"pollCountCell\">" . (($SHOW_COUNTS === TRUE)?$count . " (":"") . (($count > 0)?round($percentage * 100, 2):0) . "%" . (($SHOW_COUNTS === TRUE)?")":"") . "</td>");
		echo("</tr>\r\n");
		
	}
	
	echo("</table>");
	
	if($SHOW_COUNTS === TRUE) {
		global $NUMBER_OF_VOTES_STRING;
		$numberOfVotesString = sprintf($NUMBER_OF_VOTES_STRING, $totalVotes);
		echo("<p>" . htmlspecialchars($numberOfVotesString) . "</p>");
	}

}

function the_current_poll_results() {
	global $requested_poll_id;
	show_poll_results($requested_poll_id);
}

function the_return_to_url() {
	global $requested_poll_id;
	global $VALID_POLLS;
	$poll = $VALID_POLLS[$requested_poll_id];
	
	if(!empty($poll->returnToURL)) {
		echo $poll->returnToURL;
	} else {
		die("ERROR: Return to URL not defined for this poll.");
	}
	
}

function smarter_is_int($val) {
    return (is_numeric($val)?intval($val)==$val:FALSE);
}

function is_valid_poll_id($poll_id) {
	global $VALID_POLLS;
	return (!empty($poll_id) && preg_match('/^[a-zA-Z0-9]+$/D', $poll_id) === 1 && array_key_exists($poll_id, $VALID_POLLS));
}

function is_valid_vote($poll, $vote_value_id) {
	return (!empty($vote_value_id) && preg_match('/^[a-zA-Z0-9]+$/D', $vote_value_id) === 1 && array_key_exists($vote_value_id, $poll->values));
}

function get_vote_count($summarylist) {

	// Get vote count from summary
 	if($summarylist === FALSE || count($summarylist) < 1) {
 		return 0;
 	} else {
 		return $summarylist[0];
 	}
	
}

function add_new_vote($poll_id, $vote_value_id) {
	global $vote_error_message;
	
	// Make sure vote wasn't left blank
	if(empty($vote_value_id)) {
		global $NO_VOTE_SELECTED_ERROR_MSG;
		$vote_error_message = $NO_VOTE_SELECTED_ERROR_MSG;
		return FALSE;
	}
	
	// Validate poll ID
	if(!is_valid_poll_id($poll_id)) {
		die('ERROR: An invalid poll ID was submitted.');
	}
	
	// Get poll object
	global $VALID_POLLS;
	$poll = $VALID_POLLS[$poll_id];
	
	// Validate vote value ID
	if(!is_valid_vote($poll, $vote_value_id) ) {
		die('ERROR: An invalid vote was submitted.');
	}
	
	// Check for duplicate vote attempt
	$ipaddress = $_SERVER['REMOTE_ADDR'];
	global $PREVENT_DUPLICATE_VOTES;
	if($PREVENT_DUPLICATE_VOTES && has_voted($poll_id, $ipaddress)) {
		global $DUPLICATE_VOTE_ERROR_MSG;
		$vote_error_message = $DUPLICATE_VOTE_ERROR_MSG;
		return FALSE;
	}

	// Add vote	
	vote_history_add($poll_id, $ipaddress, $vote_value_id);
	vote_summary_add($poll_id, $vote_value_id);
	
	return TRUE;
}

function vote_summary_file_path($id) {
 	return dirname(__FILE__) . '../../../polls/poll/data/summary_' . $id . '.dat';
}

function vote_history_file_path($id) {
	return dirname(__FILE__) . '../../../polls/poll/data/history_' . $id . '.dat';
}

function vote_summary_list($poll_id) {

	// Load existing
	$summarylist = @file(vote_summary_file_path($poll_id));
	if($summarylist !== FALSE) {
		$summarylist = array_map("trim", $summarylist);
	}
	return $summarylist;

}

function find_vote_value_summary($vote_value_id, $summarylist) {
	
	if($summarylist === FALSE) return FALSE;
	for($i = 1; $i < sizeof($summarylist); $i++) {
		
		$summary_row = explode_history($summarylist[$i]);
		if($summary_row[0] === ("" . $vote_value_id)) {
			return $summary_row;
		}
		
	}
	return FALSE;
	
}

function find_largest_vote_value_count($summarylist) {
	
	$largest_count = 0;
	for($i = 1; $i < sizeof($summarylist); $i++) {
		
		$summary_row = explode_history($summarylist[$i]);
		if($summary_row[1] > $largest_count) {
			$largest_count = $summary_row[1]; 
		}
		
	}
	return $largest_count;
	
}

function vote_summary_add($poll_id, $vote_value_id) {

	// Get existing poll summary
	$summarylist = vote_summary_list($poll_id);
	$vote_summary_file_path = vote_summary_file_path($poll_id);
	
	// Create summary file if it doesn't exist
	if(!file_exists($vote_summary_file_path)) {
		if(@touch($vote_summary_file_path) === FALSE) {
			die("Unable to create summary file");
		}
	}
	
	// Open summary file
 	$summary_fp = @fopen($vote_summary_file_path, "r+");
 	if($summary_fp === FALSE) {
 		die("Unable to open summary file for writing");
 	}
 	if(@flock($summary_fp, LOCK_EX) === FALSE) {
 		@fclose($summary_fp);
 		die("Unable to lock summary file");
 	}
 	if(@ftruncate($summary_fp, 0) === FALSE) {
 		@fclose($summary_fp);
 		die("Unable to truncate summary file");
 	}
 	
 	// Update total vote count
 	if($summarylist === FALSE || count($summarylist) < 1) {
 		$count = 1;
 	} else {
 		$count = $summarylist[0] + 1;
 	}
 	fputs($summary_fp, $count . "\n");

	// Add vote to value total
	if($summarylist === FALSE) {
		
		// First vote
		$out = $vote_value_id . "|1";
		fputs($summary_fp, $out . "\n");

	} else {
 	
 		// Iterate through existing vote values
 		$vote_counted = FALSE;
 		for($i = 1; $i < sizeof($summarylist); $i++) {
 			
 			$summary_row = explode_history($summarylist[$i]);
 			if($summary_row[0] === $vote_value_id) {
 				
 				// Increase vote count for this value
 				$summary_row[1] += 1;
 				$vote_counted = TRUE;
 				
 			}
 			
 			// Write out new vote count for this id
 			$out = implode("|", $summary_row);
 			fputs($summary_fp, $out . "\n");
 			
 		}

 		if($vote_counted === FALSE) {
 			
 			// This is the first vote for this value
 			$out = $vote_value_id . "|1";
 			fputs($summary_fp, $out . "\n");
 			
 		}
 		
 		
 	}
 	fclose($summary_fp);
	
}

function vote_history_add($poll_id, $ipaddress, $vote_value_id) {
	
	// Open/create history file
 	$history_fp = @fopen(vote_history_file_path($poll_id), "a");
 	if($history_fp === FALSE) {
 		die("Unable to open history file for writing");
 	}
 	
 	// Add IP address and vote to history
 	@flock($history_fp, LOCK_EX); 
 	fputs($history_fp, $ipaddress . "|" . $vote_value_id . "\n");
 	fclose($history_fp);
 	
}

function explode_history($line) {
	return array_map("trim", explode("|", $line));
}

function vote_history_list($poll_id) {

	// Load existing vote history
	$summarylist = @file(vote_history_file_path($poll_id));
	if($summarylist !== FALSE) {
		$summarylist = array_map("explode_history", $summarylist);
	}
	
	return $summarylist;
	
}

function has_voted($poll_id, $ipaddress) {

	// Find vote history
	$vote_history_list = vote_history_list($poll_id);
	if($vote_history_list !== FALSE) {
		return( find_vote_history(trim($ipaddress), $vote_history_list) !== FALSE );
	} else {
		return FALSE;
	}

}

function find_vote_history($ipaddress, $list) {
	
	// Search vote history for this IP address
	if(!empty($list)) {
		for($i = 0; $i < count($list); $i++) {
			if($list[$i][0] == $ipaddress) return $list[$i];
		}
	}
	return FALSE;
	
}

/* DO NOT REMOVE OR HIDE THE CREDIT BELOW, PER LICENSE! */
function the_credits() {
	echo("<div class=\"credit\">Powered by DRBPoll<br /><a href=\"http://www.dbscripts.net/\">PHP Scripts</a> &middot; <a href=\"http://www.dbscripts.net/hosting/\">PHP Hosting</a></div>\n");
}
/* END CREDIT */

?>
