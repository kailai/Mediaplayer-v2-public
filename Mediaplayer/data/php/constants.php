<?php 
/******************************** Constants *************************************/
$VERSION = "lx-2.0";
$SN = "w123456789";
$COMPANY = "<address><a href=http://www.eye-in.com target=blanc>
		<strong>Eye-In Média Inc.</strong></a><br />
		Tel: <span class=text-info>1-800-890-4213</span> 
		<br />Email: <a href=mailto:info@eyeinmedia.com>info@eyeinmedia.com</a></address>";
$CHROME_FLAGS = "--kiosk --incognito --no-first-run --ignore-gpu-blacklist --enable-threaded-compositing --enable-accelerated-filters";
$LICENCE_KEY = "0123456789";

/******************************** URLs *************************************/
$MEDIA_SERVER = 'http://gestion.eyeinmedia.com/media/playlist_https.php';
$REPORT_URL = "https://gestion.eyeinmedia.com/media/status.php";
$EMAIL_ALERT_URL = "https://gestion.eyeinmedia.com/media/mediaWatcher.php";
$PRICE_URL = "https://gestion.eyeinmedia.com/admin_dealer_data_manage.php";

/******************************** Dirs *************************************/
$MEDIA_PLAYER_DIR = $_SERVER['DOCUMENT_ROOT'].'/';
$MPCS_DIR = $MEDIA_PLAYER_DIR.'mediapc/';
$SCRIPTS_DIR = $MEDIA_PLAYER_DIR.'data/scripts/';
$START_DIR = $MEDIA_PLAYER_DIR.'startup/';
$START_SCRIPT_DIR = $START_DIR.'scripts/';
$LOG_DIR = $START_DIR.'log/';
$SCRSHOT_DIR = $MEDIA_PLAYER_DIR.'data/php/screenshot/';
$SHOT_URL = 'data/php/screenshot/shot.png';

/******************************** Files *************************************/
$HOME_PAGE = "media.html";
$NET_INTERFACES = "/etc/network/interfaces";
$DEALER_NAME = $MEDIA_PLAYER_DIR.'dealer';
$START_ALL = $START_DIR."start_all.sh";
$SET_SCREEN = $SCRIPTS_DIR.'set_screen.php';
$SCREEN_SCRIPT = $START_DIR.'screen.sh';
$PID_WATCHER = $LOG_DIR."pid_watcher";
$PID_PLAYER = $LOG_DIR."pid_player";
$WATCH = $SCRIPTS_DIR."watch";
$PLAY = $SCRIPTS_DIR."play";
$CRON = $SCRIPTS_DIR."cron";

/******************************** Variables **********************************/
$USER =  trim(exec('whoami'));
$PASSWORD = "eyein123";
$mpc_id = $_REQUEST['mpcid'];
$dealer_id = $_REQUEST['dealerid'];
$screen_id = $_REQUEST['screen_id'];
$screen_name = $_REQUEST['screen_name'];
$screen_pos = $_REQUEST['screen_pos'];
$update_cycle = $_REQUEST['upcycle'];
$report_cycle = $_REQUEST['repcycle'];

$mpc_dir = $MPCS_DIR.$dealer_id.'_'.$mpc_id.'/';
$live_dir = $mpc_dir.'live/';
$tmp_dir = $mpc_dir.'temp/';
$update_dir = $mpc_dir.'update/';
$playlist_url = $MEDIA_SERVER.'?dealerid='.$dealer_id.'&mediapcid='.$mpc_id;
$playlist_file = $mpc_dir.'playlist.csv';
$new_title = $_GET['title'];
$live_url = '../../mediapc/'.$dealer_id.'_'.$mpc_id.'/live/';

if(file_exists($START_SCRIPT_DIR.$screen_name.".sh"))
	$play_script = $START_SCRIPT_DIR.$screen_name.".sh";
elseif(file_exists($START_SCRIPT_DIR.$screen_name."_main.sh"))
	$play_script = $START_SCRIPT_DIR.$screen_name."_main.sh";

?>