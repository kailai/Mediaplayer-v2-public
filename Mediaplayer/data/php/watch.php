<?php 
header('Access-Control-Allow-Origin: *');
include 'functions.php';

$res = trim(shell_exec("DISPLAY=:0 xrandr -q | grep '$screen_name connected'"));

if(empty($res))
{
	echo "$screen_name not connected";
	
	$pid_file = $LOG_DIR."pid_$screen_name";
	$pid = shell_exec("cat $pid_file");
	
	echo $pid_file;
	
	shell_exec("kill $pid");

	$data = genEmailData($mpc_id, $VERSION, "Test", "Test", "$screen_name down, player killed", "PID: $pid", "Test");
	sendEmail($EMAIL_ALERT_URL, $data);
}
else
{
	$pieces = explode(" ", $res);
	$info = $pieces[2];
	$spos = explode("+", $info);
	$sx = $spos[1];
	$sy = $spos[2];

	$searched = $mpc_id.'_'.$screen_id;
	$geo_info = trim(shell_exec("DISPLAY=:0 xdotool getwindowgeometry $(DISPLAY=:0 xdotool search '$searched') | grep Position:"));
	$wpos_info = explode(" ", $geo_info);
	$wpos = explode(",", $wpos_info[1]);
	$wx = $wpos[0];
	$wy = $wpos[1];

	if($wx != $sx || $wy != $sy)
	{
		$wid = trim(shell_exec("DISPLAY=:0 xdotool search '$searched'"));
		shell_exec("DISPLAY=:0 xdotool windowmove $wid $sx $sy");
	}
}

exit();

function sendEmail($url, $data)
{
	$ch = curl_init();

	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

	$result = curl_exec($ch);
	curl_close($ch);
}

function genEmailData($id, $ver, $log1, $log2, $evtName, $procList, $evtLog)
{
	$data = array(
			'mediawatcherID'=>$id,
			'version'=>$ver,
			'logText1'=>$log1,
			'logText2'=>$log2,
			'eventName'=>$evtName,
			'ProcessesList'=>$procList,
			'eventLog'=>$evtLog);
	return $data;
}
?>
