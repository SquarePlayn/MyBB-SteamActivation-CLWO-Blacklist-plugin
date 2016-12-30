<?php 

//Deny direct initialization for extra security
if(!defined("IN_MYBB")) {
    die("You Cannot Access This File Directly. Please Make Sure IN_MYBB Is Defined.");
}

function steam_activation_customcheck_blacklist($forumUserID, $steamID) {
	steam_activation_f_debug("Function: customcheck_blacklist()");

	if(isset($steamID)) {
		steam_activation_f_debug("Steam ID found: ".$steamID);

		$apilink = "https://clwo.eu/jailbreak/api/v2/blacklist.php?cSteamID64=".$steamID;

		/* OLD PHP FILE_GET_CONTENTS WAY THAT DOES NOT USE CURL
		** FEEL FREE TO UNCOMMENT THIS OLD WAY AND COMMENT OUT CURL IF YOU PREFER SO
		*/ 
		//$content = file_get_contents($apilink);

		/* NEW CURL WAY */
		$curler = curl_init();
		curl_setopt($curler, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curler, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curler, CURLOPT_URL, $apilink);
		$content = curl_exec($curler);
		curl_close($curler);
		/* END OF CURL*/

		$blacklistinfo = json_decode($content, true);

		if($blacklistinfo["status"] == 200) {
			//API Online
			steam_activation_f_debug("API online");

			steam_activation_f_debug("Results: ".$blacklistinfo["results"]);

			if($blacklistinfo["results"] > 0) {
				steam_activation_f_debug("Blacklisted");
				//blacklisted

				$baninfo = reset($blacklistinfo["data"]);

				$accountID = $baninfo["cAccountID"];
				$blacklistreason = $baninfo["Reason"];
				$banlength = $baninfo["ExpiresInMinutes"];
				$link = "https://clwo.eu/jailbreak/admin/view-blacklist.php?AccountID=".$accountID;

				if($baninfo["Perm"] == 1) {
					steam_activation_f_debug("Permanent ban");

					$banlength = -1;
				}

				$returnarray = array(
					"error" => "blacklisted",
					"banreason" => "You are blacklisted. Check ".$link,
					"banlength" => $banlength,
					"blacklistreason" => $blacklistreason,
					"blacklistlink" => $link
				);

				return $returnarray;
			} else {
				//Not blacklisted
				steam_activation_f_debug("Not blacklisted");

				//Returning empty array since no problem found
				return array();
			}
		} else {
			steam_activation_f_debug("<b>BLACKLIST-API OFFLINE!!!</b>");

			return array();
		}
	} else {
		steam_activation_f_debug("No SteamID set, all good");

		return array();
	}
}

?>