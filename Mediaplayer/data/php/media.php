<?php 
header('Access-Control-Allow-Origin: *');
error_reporting(E_ALL);

include 'functions.php';

// if(file_exists($START_SCRIPT_DIR.$screen_name.".sh"))
// 	$play_script = $START_SCRIPT_DIR.$screen_name.".sh";
// elseif(file_exists($START_SCRIPT_DIR.$screen_name."_main.sh"))
// 	$play_script = $START_SCRIPT_DIR.$screen_name."_main.sh";

$req = $_GET['req'];

if($req == 'home')
	echo printHome();
elseif($req == "screen_info")
	echo getScreenInfo();
elseif($req == "current_mpc")
	echo getCurrMpc();
elseif($req == "remove")
	echo removeScript();
elseif($req == "remove_all")
	echo removeAllScripts();
elseif($req == "start_watcher")
	echo startWatcher();
elseif($req == "start_player")
	echo startPlayer();
elseif($req == "stop")
	echo stopPlayers();
elseif($req == "monitor_player")
	echo screenShot();
elseif($req == "save")
	echo saveScript();
elseif($_POST['req'] == "save")
	echo saveScriptDynamic($_POST);
elseif($req == "delete_playlist")
	echo deletePlaylist();
elseif($req == "get_playlists")
	echo getPlaylists();
elseif($req == "get_dealer_name")
	echo getDealer();
elseif($_POST['req'] == "config_screens")
	echo configScreens2($_POST);
elseif($req == "screen_off")
	echo turnOffScreens();
elseif($req == "screen_on")
	echo turnOnScreens();
elseif($req == "reset_screens")
	echo turnOnScreens();
elseif($req == "edit_schedule")
	echo editSchedule();
elseif($req == "get_shedule")
	echo getCrontab();
elseif($req == "remove_schedule")
	echo delCrontab();
elseif($req == "screen_status")
	echo screenStatus();
elseif($req == "change_dealer_name")
	echo editDealer();
elseif($req == "get_system_info")
	echo getSysInfo();
elseif($req == "change_name")
	echo editHost();
elseif($req == "change_ip")
	echo editIP();
elseif($req == "get_sys_info")
	echo getSystemInfo();
elseif($req == "get_about_info")
	echo getAboutInfo();
elseif($req == "download_playlist")
	echo downloadPlaylist();
elseif($req == "download_file")
	echo downloadFile();
elseif($req == "update_playlist")
	echo updatePlaylist();
elseif($req == "update_playlist_dynamic")
	echo updatePlaylistDynamic();
elseif($req == "move_updates")
	echo moveUpdates();
elseif($_POST['req'] == "save_price")
	savePrice($_POST['data']);
elseif($req == "load_prices")
	echo loadPrice();
elseif($req == "check_update")
	exit();
elseif($req == "reboot")
{
	$pass = $_REQUEST['password'];
	if(!checkPassword($pass))
		exit("wrong");
	
	echo "done";
	reboot($pass);
}
else
	exit('Eye-In Media Player\n');

?>