<?php 

//Deny direct initialization for extra security
if(!defined("IN_MYBB")) {
    die("You Cannot Access This File Directly. Please Make Sure IN_MYBB Is Defined.");
}

function steam_activation_customcheck_blacklist($forumUserID, $steamID) {
	steam_activation_f_debug("Function: customcheck_blacklist()");
	global $mybb, $db;

	if(isset($steamID)) {
		steam_activation_f_debug("Steam ID found: ".$steamID);

		$apilink = "https://clwo.eu/jailbreak/api/v2/blacklist.php?cSteamID64=".$steamID;

		if($mybb->settings["steam_activation_curl"]) {
			steam_activation_f_debug("Using CURL to get blacklist info");
			$curler = curl_init();
			curl_setopt($curler, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($curler, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curler, CURLOPT_URL, $apilink);
			$content = curl_exec($curler);
			curl_close($curler);
		} else {
			steam_activation_f_debug("NOT using CURL to get blacklist info, but doing it the non-curl way");
			$content = file_get_contents($apilink);
		}

		$blacklistinfo = json_decode($content, true);

		if($blacklistinfo["status"] == 200) {
			//API Online
			steam_activation_f_debug("Blacklist API online");

			steam_activation_f_debug("Results: ".$blacklistinfo["results"]);

			if($blacklistinfo["results"] > 0) {
				steam_activation_f_debug("Blacklisted");
				//blacklisted

				$baninfo = reset($blacklistinfo["data"]);

				$accountID = $baninfo["cAccountID"];
				$blacklistreason = $baninfo["Reason"];
				$banlength = $baninfo["ExpiresInMinutes"] * 60;
				$link = "https://clwo.eu/jailbreak/admin/view-blacklist.php?AccountID=".$accountID;

				if($baninfo["Perm"] == 1) {
					steam_activation_f_debug("Permanent ban");

					$banlength = -1;
				}

				steam_activation_f_debug("Lenght: ".$banlength);

				//NB, WHEN CHANGING THE REASON FORMAT, CHECK THE UNBAN PROCEDURE THAT IS BELOW
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

				steam_activation_f_debug("Going to check if this forum user was banned for blacklist.");
				$forumbaninfo = $db->simple_select("banned", "reason", "uid=".$forumUserID);
				if($db->num_rows($forumbaninfo)){
				    steam_activation_f_debug("The user is banned for something. Let's see if it is a blacklist.");
				    $banreason = $db->fetch_array($forumbaninfo)["reason"];
				    if(explode(".",$banreason)[0] === "You are blacklisted"){
				        steam_activation_f_debug("User was previously banned for being blacklisted. Unbanning.");
				        return array("error" => "unban");
                    } else {
				        steam_activation_f_debug("User was banned for a reason other than a blakclist. Cya!");
                    }
                } else {
				    steam_activation_f_debug("The user is not banned off the forums. Cya!");
                }

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