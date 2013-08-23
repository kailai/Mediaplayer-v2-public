<?php 
include 'constants.php';

function printHome()
{
	global $HOME_PAGE;
	
	$doc = new DOMDocument();
	$doc->loadHTMLFile($HOME_PAGE);
	return $doc->saveHTML();
}

function getScreenInfo()
{
	$cmd = "DISPLAY=:0 xrandr -q | grep ' connected'";
	exec($cmd, $info);
	
	if(empty($info))
		return "";
	else
		return json_encode($info);
}

function getCurrMpc()
{
	global $play_script, $MPCS_DIR;
	
	if(empty($play_script)) return json_encode(array("dealerid"=>'', "mpcid"=>''));
	
	$cmd = "cat $play_script | grep dealerid | cut -d '=' -f 4 | cut -d '&' -f 1";
	$dealerid = trim(shell_exec($cmd));
	
	$cmd = "cat $play_script | grep dealerid | cut -d '=' -f 3 | cut -d '&' -f 1";
	$mpcid = trim(shell_exec($cmd));
	
	$mpcdir = $MPCS_DIR.$dealerid.'_'.$mpcid.'/';
	
	// clean script file if mediapc dir does not exist
	if(!is_dir($mpcdir))
	{
		unlink($play_script);
		$dealerid = '';
		$mpcid = '';
	}
	
	return json_encode(array("dealerid"=>$dealerid, "mpcid"=>$mpcid));
}

function removeScript()
{
	global $play_script;
	
	if(unlink($play_script))
	{
		if(checkScripts() == 0 && unlink($START_ALL))
			return "done";
	}
	else 
		return "done";

			
	return "failed";
}

function removeAllScripts()
{
	global $START_SCRIPT_DIR, $START_ALL;
	
	$res = 'done';
	
	$files = glob($START_SCRIPT_DIR."*.sh");
	
	foreach($files as $file){
		if(is_file($file))
			if(!unlink($file))
				$res = 'failed';
	}
	
	if(checkScripts() == 0 && is_file($START_ALL))
		if(!unlink($START_ALL))
			$res = 'failed';
	
	return $res;
}

function stopPlayers()
{
	killWatcher();
	killPlayer();
	killTerminal();
	
	$terminal = shell_exec("pgrep gnome-terminal");
	if(isset($terminal))
		return "failed";
	
	return "done";
}

function screenShot()
{
	global $SCRSHOT_DIR, $SHOT_URL;
	
	$cmd = "DISPLAY=:0 import -window root $SCRSHOT_DIR/shot.png";
	shell_exec($cmd);
	
	return $SHOT_URL;
}

function saveScript()
{
	global $mpc_id, $screen_name, $mpc_dir, $play_script, $START_ALL;
	
	if(!validMpcId($mpc_id, $mpc_dir))
		return "Invalid mediapc id or $mpc_dir not exist.";
	
	$res = genScript();
	
	if(empty($res))
		return "Failed to create script file.";
	
	if(chmod($play_script, 0755) && chmod($START_ALL, 0755))
		return "$mpc_id has been assigned to $screen_name";
	
	return "Failed to create script file.";
}

function saveScriptDynamic($screens)
{
	if(genScriptDynamic($screens))
		return "Playlist has been assigned.";
	
	return "Failed to create script file.";
}

function deletePlaylist()
{
	global $mpc_dir;
	
	if(deleteDirectory("$mpc_dir") && removeAllScripts() == 'done')
		return "done";

	return "failed";
}

function getPlaylists()
{
	global $MPCS_DIR;
	
	$exclude_list = array(".", "..");
	$mpcs = array_diff(scandir($MPCS_DIR), $exclude_list);
	
	$playlsit = array();
	
	foreach ($mpcs as $value)
	{
		$playlist = $MPCS_DIR.$value."/playlist.csv";
		$info = typeOfPlaylist($playlist);
		$type = $info[0];
		$screen_nb = $info[1];
	
		$playlsit[] = array('playlist'=>$value, 'type'=>$type, 'scr_nb'=>$screen_nb);
	}
	
	return json_encode($playlsit);
}

function checkScripts()
{
	global $START_SCRIPT_DIR;
	
	$scripts = array_diff(scandir($START_SCRIPT_DIR), array(".", ".."));

	if(empty($scripts))
		return 0;

	return 1;
}

function checkWatcher()
{
	global $PID_WATCHER;
	
	if(!file_exists($PID_WATCHER))
		shell_exec("echo 0000 > $PID_WATCHER");

	$terms = trim(shell_exec("pgrep gnome-terminal"));
	$pid = trim(shell_exec("cat $PID_WATCHER"));

	if(empty($terms)) // no terminal running
		return 0;
	if($terms == $pid) // one terminal (watcher) running
		return 1;
	if(strstr($terms, $pid)) // more than one terminals (watcher and player) running
		return 2;
}

function startWatcher()
{
	global $PID_WATCHER, $WATCH, $USER, $SET_SCREEN;

	if(checkScripts() == 0)
		return "noplaylist";
	
	if(checkWatcher() > 0)
		return "running";
	
	$cmd = "sudo -u $USER $SET_SCREEN all watcher startnodelay";
	shell_exec($cmd);
}

function startPlayer()
{
	global $USER, $PLAY, $PID_PLAYER, $SET_SCREEN;
	
	if(checkWatcher() == 0)
		return "failed";
	
	if(checkPlayer() > 0)
		return "running";
	
	$cmd = "sudo -u $USER $SET_SCREEN all player startnodelay";
	shell_exec($cmd);
}

function checkPlayer()
{
	global $PID_PLAYER;
	
	if(!file_exists($PID_PLAYER))
		shell_exec("echo 0000 > $PID_PLAYER");

	$terms = trim(shell_exec("pgrep gnome-terminal"));
	$pid = trim(shell_exec("cat $PID_PLAYER"));

	if(empty($terms)) // no terminal running
		return 0;
	if($terms == $pid) // one terminal (player) running
		return 1;
	if(strstr($terms, $pid)) // more than one terminals running (player and watcher)
		return 2;
}

function killWatcher()
{
	global $LOG_DIR;

	$pid_watcher = $LOG_DIR."pid_watcher";
	$pid = shell_exec("cat $pid_watcher");

	shell_exec("kill $pid");
}

function killPlayer()
{
	global $LOG_DIR;

	$pid_player = $LOG_DIR."pid_player";

	$pid = shell_exec("cat $pid_player");
	shell_exec("kill $pid");
}

function killTerminal()
{
	shell_exec("pkill watch");
	shell_exec("pkill chrome");
	shell_exec("pkill gnome-terminal");
}

function genScript()
{
	global  $MEDIA_PLAYER_DIR, $START_SCRIPT_DIR, $LOG_DIR, $START_ALL, $VERSION, $CHROME_FLAGS, $EMAIL_ALERT_URL,
	$play_script, $screen_id, $screen_name, $screen_pos,  $dealer_id, $mpc_id, $update_cycle, $report_cycle;
	
	$title = $mpc_id.'_'.$screen_id.'_'.$screen_name.'_'.$screen_pos;
	$searched = $mpc_id.'_'.$screen_id;
	$user_data_dir = $MEDIA_PLAYER_DIR.'user_data/'.$title.'/';
	$pos = explode("+", $screen_pos);
	$search = "'$screen_name connected'";
	$pid_log = $LOG_DIR."pid_$screen_name";

	$event_name = "$screen_name down, failed to start player";
	$params = "'mediawatcherID=$mpc_id&version=$VERSION&logText1=&logText2=&eventName=$event_name&ProcessesList=1&eventLog='";

	$url = "127.0.0.1/data/php/play.php?"
			."mpcid=$mpc_id"
			."&dealerid=$dealer_id"
			."&title=$title"
			."&upcycle=$update_cycle"
			."&repcycle=$report_cycle";
	
	$line = "#!/bin/sh \n";
	$line .= "cd $START_SCRIPT_DIR \n";
	$line .= "if [ ! -f $pid_log ]; then echo 0000 > $pid_log; fi \n";
	$line .= "screens=$(DISPLAY=:0 xrandr -q) \n";
	$line .= 'screen=$(echo $screens | grep -c '.$search.") \n";
	$line .= "pid=$(cat $pid_log) \n";
	$line .= 'pid_count=$(pgrep chrome | grep -c $pid)'." \n";
	$line .= 'if [ $screen -gt 0 ] && [ $pid_count -eq 0 ]'." \n";
	$line .= "then \n";
	$line .= "google-chrome $CHROME_FLAGS --user-data-dir=$user_data_dir '$url' & ";
	$line .= "pid=$! && echo \$pid > $pid_log \n";
	$line .= "sleep 4 && xdotool windowmove $(xdotool search '$searched') $pos[0] $pos[1] \n";
	$line .= "fi \n";
	$line .= 'if [ $screen -eq 0 ]'." \n";
	$line .= "then \n";
	$line .= "curl -d $params -k $EMAIL_ALERT_URL \n";
	$line .= "fi \n";

	$line1 = "#!/bin/sh \n";
	$line1 .= 'for file in $HOME/Mediaplayer/startup/scripts/*.sh; do ($file &); done';

	if(file_put_contents($play_script, $line, LOCK_EX))
	if(file_put_contents($START_ALL, $line1, LOCK_EX))
		return true;

	return false;
}

function genScriptDynamic($screens)
{
	$to_assign = array();
	$to_remove = array ();
	$res = true;
	
	foreach ($screens as $key=>$screen) 
	{
		if($key === 'req') continue;
		
// 		$change = $screen['change'];
// 		$remove = $screen['remove'];
		
// 		if($change)
// 			$to_assign[$screen['screen_name']] = $screen;
// 		elseif($remove)
// 			$to_remove[$screen['screen_name']] = $screen;

		if($key == 0)
			genAssignScript($screen, true);
		else
			genAssignScript($screen, false);
	}
		
// 	foreach ( $to_assign as $name => $screen )
// 		if(!genAssignScript($screen))
// 			$res = false;
	
// 	foreach ( $to_remove as $name => $screen )
// 		if(!removeScript($screen))
// 			$res = false;
	
	return $res;
}

function genAssignScript($screen, $main)
{
	global $MEDIA_PLAYER_DIR, $START_SCRIPT_DIR, $LOG_DIR, $START_ALL, $VERSION, $CHROME_FLAGS, $EMAIL_ALERT_URL;
	
	$mpc_id = $screen['mpcid'];
	$dealer_id = $screen['dealerid'];
	$screen_id = $screen['screen_id'];
	$screen_name = $screen['screen_name'];
	$screen_pos = $screen['screen_pos'];
	$update_cycle = $screen['update_cycle'];
	$report_cycle = $screen['report_cycle'];
	
	if($main)
		$play_script = $START_SCRIPT_DIR.$screen_name."_main.sh";
	else 
		$play_script = $START_SCRIPT_DIR.$screen_name.".sh";
	
	$title = $mpc_id . '_' . $screen_id . '_' . $screen_name . '_' . $screen_pos;
	$searched = $mpc_id . '_' . $screen_id;
	$user_data_dir = $MEDIA_PLAYER_DIR . 'user_data/' . $title . '/';
	$pos = explode ( "+", $screen_pos );
	$search = "'$screen_name connected'";
	$pid_log = $LOG_DIR . "pid_$screen_name";
	$event_name = "$screen_name down, failed to start player";
	$params = "'mediawatcherID=$mpc_id&version=$VERSION&logText1=&logText2=&eventName=$event_name&ProcessesList=1&eventLog='";
	$url = "127.0.0.1/data/php/play.php?" . "mpcid=$mpc_id" . "&dealerid=$dealer_id" . "&title=$title" . "&upcycle=$update_cycle" . "&repcycle=$report_cycle";
	$line = "#!/bin/sh \n";
	$line .= "cd $START_SCRIPT_DIR \n";
	$line .= "if [ ! -f $pid_log ]; then echo 0000 > $pid_log; fi \n";
	$line .= "screens=$(DISPLAY=:0 xrandr -q) \n";
	$line .= 'screen=$(echo $screens | grep -c ' . $search . ") \n";
	$line .= "pid=$(cat $pid_log) \n";
	$line .= 'pid_count=$(pgrep chrome | grep -c $pid)' . " \n";
	$line .= 'if [ $screen -gt 0 ] && [ $pid_count -eq 0 ]' . " \n";
	$line .= "then \n";
	$line .= "google-chrome $CHROME_FLAGS --user-data-dir=$user_data_dir '$url' & ";
	$line .= "pid=$! && echo \$pid > $pid_log \n";
	$line .= "sleep 4 && DISPLAY=:0 xdotool windowmove $(xdotool search '$searched') $pos[0] $pos[1] \n";
	$line .= "fi \n";
	$line .= 'if [ $screen -eq 0 ]' . " \n";
	$line .= "then \n";
	$line .= "curl -d $params -k $EMAIL_ALERT_URL \n";
	$line .= "fi \n";
	
	$line1 = "#!/bin/sh \n";
	$line1 .= "for file in \$HOME/Mediaplayer/startup/scripts/*_main.sh \n";
	$line1 .= "do \n";
	$line1 .= "\$file & \n";
	$line1 .= "done";
	
	if (file_put_contents ( $play_script, $line, LOCK_EX ))
		if (file_put_contents ( $START_ALL, $line1, LOCK_EX ))
			if (chmod ( $play_script, 0755 ) && chmod ( $START_ALL, 0755 ))
				return true;
			
	return false;
}

// function removeScript($screen)
// {
// 	global $START_SCRIPT_DIR;
	
// 	$screen_name = $screen['screen_name'];
// 	$play_script = $START_SCRIPT_DIR.$screen_name.".sh";
	
// 	if(unlink($play_script))
// 		return true;

// 	return false;
// }

function validMpcId($mpcid, $mpcdir)
{
	if(empty($mpcid))
		return false;
	if(!is_dir($mpcdir))
		return false;

	return true;
}

function deleteDirectory($dir)
{
	if (!file_exists($dir)) return true;
	if (!is_dir($dir) || is_link($dir)) return unlink($dir);

	foreach (scandir($dir) as $item) {
		if ($item == '.' || $item == '..') continue;
		if (!deleteDirectory($dir . "/" . $item)) {
			chmod($dir . "/" . $item, 0777);
			if (!deleteDirectory($dir . "/" . $item)) return false;
		};
	}

	return rmdir($dir);
}

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

function screenOff()
{
	global $SET_SCREEN;

	$cmd = "$SET_SCREEN all screen off";

	shell_exec("$cmd && sleep 1 && $cmd");
}

function getCrontab()
{
	global $USER, $CRON;
	exec("crontab -u $USER -l", $CRON);

	if(empty($CRON))
		return 'none';

	return json_encode($CRON);
}

function delCrontab()
{
	global $USER;
	shell_exec("crontab -u $USER -r");

	exec("crontab -u $USER -l", $crontab);
	if(empty($crontab))
		return 'done';

	return 'failed';
}

function getDealer()
{
	global $DEALER_NAME;
	$dealer_name = shell_exec("cat $DEALER_NAME");
	return $dealer_name;
}

function getVersion()
{
	global $VERSION;
	return $VERSION;
}

function getSN()
{
	global $SN;
	return $SN;
}

function getCPUInfo()
{
	$cpu_info = exec("cat /proc/cpuinfo | grep 'model name'");
	$model = explode(':', $cpu_info);

	return trim($model[1]);
}

function getMemInfo()
{
	$mem_info = exec("cat /proc/meminfo | grep MemTotal");
	$pieces = explode(':', $mem_info);
	$total_kb = trim($pieces[1]);
	$pieces = explode(' ', $total_kb);
	$total_kb = trim($pieces[0]);
	$total_kb = $total_kb/(1024*1024);

	$total = shell_exec("free -m | grep Mem | awk '{print $2}'");
	$used = shell_exec("free -m | grep Mem | awk '{print $3}'");
	$free = shell_exec("free -m | grep Mem | awk '{print $4}'");
	$usage = round($used / $total * 100)."%";

	return trim(round($total/1024))." GB ($usage used)";
}

function getDiskInfo()
{
	global $PASSWORD;

	$disk_info = shell_exec("echo $PASSWORD | sudo -S fdisk -l |grep Disk");
	$total_gb = explode(' ', $disk_info);
	$disk_usage = shell_exec("df -a | grep '/sda1' | awk '{print $5}'");

	return trim($total_gb[2])." GB ($disk_usage used)";
}

function getSupportInfo()
{
	global $COMPANY;
	return $COMPANY;
}

function getSysUseInfo()
{
	global $PASSWORD;
	
	exec("top -b -n 1 | head -10", $info);
	exec("echo $PASSWORD | sudo -S ifconfig | grep 'inet addr'", $info);
	
	return $info;
}

function checkPassword($pass)
{
	global $PASSWORD;

	if($pass == $PASSWORD)
		return true;

	return false;
}

function checkInterface()
{
	global $PASSWORD;
	
	$res = shell_exec("echo $PASSWORD | sudo -S ifconfig | grep eth");
	
	if(empty($res))
		return null;
	
	$if = substr(trim($res), 0, 4);
	
	return($if);
}

function reGenInterface()
{
	global $PASSWORD;
	
	$RULES = "/etc/udev/rules.d/70-persistent-net.rules";
	$cmd1 = "echo $PASSWORD | sudo -S rm $RULES";
	$cmd2 = "echo $PASSWORD | sudo -S udevadm trigger --action=add";
	
	shell_exec("$cmd1 && $cmd2");
	sleep(2);
	
	$contents = shell_exec("echo $(cat $RULES)");
	$if = strstr($contents, 'NAME="eth');

	if(empty($if))
		return null;

	if(preg_match('/"([^"]+)"/', $if, $m))
		return $m[1];
	
	return null;
}

function setIP($interface, $ip, $mask, $gw, $dns, $pass)
{
	global $USER, $NET_INTERFACES;

	$HOSTNAME = gethostname();

	$line = "auto lo \n";
	$line .= "iface lo inet loopback \n";
	$line .= "auto $interface \n";
	$line .= "iface $interface inet static \n";
	$line .= "address $ip \n";
	$line .= "netmask $mask \n";
	$line .= "gateway $gw \n";
	$line .= "dns-nameservers $dns \n";

	shell_exec("echo $pass | sudo -S chown $USER $NET_INTERFACES");
	file_put_contents($NET_INTERFACES, $line, LOCK_EX);
	shell_exec("echo $pass | sudo -S chown $USER /etc/hosts");
	shell_exec("echo '127.0.0.1 localhost \n$ip $HOSTNAME' | tee /etc/hosts");
	shell_exec("echo $pass | sudo -S ifconfig $interface $ip netmask $mask");
	shell_exec("echo $pass | sudo -S sudo route add default gw $gw $interface");

	$res = shell_exec("ifconfig | grep $ip");
	return isset($res);
}

function configScreens($screens)
{
	global $SET_SCREEN, $SCREEN_SCRIPT;
	
	$cmd = "#!/bin/sh \n";
	foreach($screens as $name => $conf) {
		if($name == 'req')
			continue;
	
		$pieces = explode('_', $conf);
		$degree = $pieces[0];
		$res = $pieces[1];
	
		$ress = explode('x', $res);
		$width = $ress[0];
		$height = $ress[1];
	
		if($degree == "90") $degree = 'right';
		elseif($degree == "-90") $degree = 'left';
		elseif($degree == "0") $degree = 'normal';
	
		$cmd .= "$SET_SCREEN $name $width $height && ";
	}
	
	foreach($screens as $name => $conf) {
		if($name == 'req')
			continue;
	
		$pieces = explode('_', $conf);
	
		$degree = $pieces[0];
		$res = $pieces[1];
	
		$ress = explode('x', $res);
		$width = $ress[0];
		$height = $ress[1];
	
		if($degree == "90") $degree = 'right';
		elseif($degree == "-90") $degree = 'left';
		elseif($degree == "0") $degree = 'normal';
	
		$cmd .= "$SET_SCREEN $name $degree no-mirror && ";
	}
	
	foreach($screens as $name => $conf) {
		if($name == 'req')
			continue;
	
		$pieces = explode('_', $conf);
	
		$degree = $pieces[0];
		$res = $pieces[1];
	
		$ress = explode('x', $res);
		$width = $ress[0];
		$height = $ress[1];
	
		if($degree == "90") $degree = 'right';
		elseif($degree == "-90") $degree = 'left';
		elseif($degree == "0") $degree = 'normal';
	
		$cmd .= "$SET_SCREEN $name rightof prev && ";
	}
	
	$cmd .= "echo 'done'";
	
	if(file_put_contents($SCREEN_SCRIPT, $cmd, LOCK_EX))
		if(chmod($SCREEN_SCRIPT, 0755))
		{
			$res = shell_exec("DISPLAY=:0 $SCREEN_SCRIPT");
			if(trim($res) == 'done') 
				return "done";
		}
	
	return "failed";
}

function configScreens1($screens)
{
	global $SET_SCREEN, $SCREEN_SCRIPT;

	$cmd = "#!/bin/sh \n";
	foreach($screens as $name => $conf)
	{
		if($name == 'req') continue;

		$pieces = explode('_', $conf);
		$degree = $pieces[0];
		$res = $pieces[1];

		$ress = explode('x', $res);
		$width = $ress[0];
		$height = $ress[1];

		if($degree == "90") $rot = 'right';
		elseif($degree == "-90") $rot = 'left';
		elseif($degree == "0") $rot = 'normal';
		
		$cmd .= "$SET_SCREEN $name $width $height $rot right-of && ";
	}

	$cmd .= "echo 'done'";

	if(file_put_contents($SCREEN_SCRIPT, $cmd, LOCK_EX))
		if(chmod($SCREEN_SCRIPT, 0755))
			if(trim(shell_exec("DISPLAY=:0 $SCREEN_SCRIPT")) == 'done')
				return "done";

	return "failed";
}

function configScreens2($screens)
{
	global $SET_SCREEN, $SCREEN_SCRIPT;

	$cmd = "#!/bin/sh \n";
	foreach($screens as $name => $conf)
	{
		if($name == 'req') continue;

		$pieces = explode('_', $conf);
		$degree = $pieces[0];
		$res = $pieces[1];

		$ress = explode('x', $res);
		$width = $ress[0];
		$height = $ress[1];

		if($degree == "90") $rot = 'right';
		elseif($degree == "-90") $rot = 'left';
		elseif($degree == "0") $rot = 'normal';

		$scrs .= "$name.$width.$height.$rot.right-of ";
	}
	
	$cmd .= "$SET_SCREEN set_all $scrs && echo 'done'";

	if(file_put_contents($SCREEN_SCRIPT, $cmd, LOCK_EX))
	if(chmod($SCREEN_SCRIPT, 0755))
	if(trim(shell_exec("DISPLAY=:0 $SCREEN_SCRIPT")) == 'done')
		return "done";

	return "failed";
}

function turnOffScreens()
{
	global $SET_SCREEN;
	return shell_exec("$SET_SCREEN all screens off");
}

function turnOnScreens()
{
	global $SET_SCREEN, $SCREEN_SCRIPT;
	
	shell_exec("$SET_SCREEN all screens on");
	
	return shell_exec($SCREEN_SCRIPT);
}

function editSchedule()
{
	global $SET_SCREEN, $CRON, $USER;
	
	$off_day = $_REQUEST['off_day'];
	$off_time = $_REQUEST['off_time'];
	$on_day = $_REQUEST['on_day'];
	$on_time = $_REQUEST['on_time'];
	
	$split = explode(':', $off_time);
	$off_hour = $split[0];
	$off_min = $split[1];
	
	$split = explode(':', $on_time);
	$on_hour = $split[0];
	$on_min = $split[1];
	
	$cron_off_day = getCronDay($off_day);
	$cron_on_day = getCronDay($on_day);
	
	$line = "$off_min $off_hour * * $cron_off_day DISPLAY=:0 $SET_SCREEN all screens off \n";
	$line .= "$on_min $on_hour * * $cron_on_day DISPLAY=:0 $SET_SCREEN all screen on \n";
	
	if(file_put_contents($CRON, $line, LOCK_EX))
	{
		shell_exec("crontab -u $USER -r");
		shell_exec("crontab -u $USER $CRON");
		return 'done';
	}
	
	return 'failed';
}

function getCronDay($off_day)
{
	if($off_day == "Every day")
		return '*';
	if($off_day == "Every Sunday")
		return 0;
	if($off_day == "Every Monday")
		return 1;
	if($off_day == "Every Tuesday")
		return 2;
	if($off_day == "Every Wednesday")
		return 3;
	if($off_day == "Every Thursday")
		return 4;
	if($off_day == "Every Friday")
		return 5;
	if($off_day == "Every Saturday")
		return 6;
	
	return "";
}

function screenStatus()
{
	$cmd = "DISPLAY=:0 xrandr -q";
	$info = shell_exec($cmd);
	$total = substr_count($info, ' connected');
	$total_on = substr_count($info, '*');
	
	if($total_on == 0)
		return "off";
	elseif($total_on == $total)
		return "on";
	elseif($total_on > 0 && $total_on < $total)
		return "partly_on";
	
	return "";
}

function editDealer()
{
	$pass = $_REQUEST['password'];
	
	if(!checkPassword($pass))
		return "wrong";
	
	global $DEALER_NAME, $USER;
	
	$dealer_name = $_REQUEST['dealer'];
	$mpc_name = $_REQUEST['name'];
	shell_exec("echo $dealer_name | tee $DEALER_NAME");
	
	$IP = $_SERVER['SERVER_ADDR'];
	shell_exec("echo $pass | sudo -S chown $USER /etc/hosts");
	shell_exec("echo $pass | sudo -S chown $USER /etc/hostname");
	shell_exec("echo $mpc_name | tee /etc/hostname");
	shell_exec("echo '127.0.0.1 localhost \n$IP $mpc_name' | tee /etc/hosts");
	
	return "done";
}

function editHost()
{
	$pass = $_REQUEST['password'];
	
	if(!checkPassword($pass))
		return "wrong";
	
	global $USER;
	
	$name = $_REQUEST['name'];
	$pass = $_REQUEST['password'];
	$ip = $_SERVER['SERVER_ADDR'];
	
	shell_exec("echo $pass | sudo -S chown $USER /etc/hosts");
	shell_exec("echo $pass | sudo -S chown $USER /etc/hostname");
	shell_exec("echo $name | tee /etc/hostname");
	shell_exec("echo '127.0.0.1 localhost \n$ip $name' | tee /etc/hosts");
	
	return "done";
}

function editIP()
{
	$pass = $_REQUEST['password'];
	
	if(!checkPassword($pass))
		return "wrong";
	
	$ip = $_REQUEST['ip'];
	$mask = $_REQUEST['mask'];
	$gw = $_REQUEST['gateway'];
	$pass = $_REQUEST['password'];
	$dns = $_REQUEST['dns'];
	
	$if = checkInterface();
	
	if(empty($if))
		$if = reGenInterface();
	
	if(empty($if))
		return 'failed';
	
	if(setIP($if, $ip, $mask, $gw, $dns, $pass))
		return 'failed';
	
	return 'failed';
}

function getSysInfo()
{
	return json_encode(array("ip"=>$_SERVER['SERVER_ADDR'], "name"=>gethostname()));
}

function getSystemInfo()
{
	$info = getSysUseInfo();
	return json_encode($info);
}

function getAboutInfo()
{
	return json_encode(array(
			"dealer"=>getDealer(),
			"version"=>getVersion(),
			"sn"=>getSN(),
			"cpu"=>getCPUInfo(),
			"memory"=>getMemInfo(),
			"disk"=>getDiskInfo(),
			"suppurt"=>getSupportInfo()
	));
}

function reboot($pass)
{	
	shell_exec("echo $pass | sudo -S reboot");
}

function typeOfPlaylist($playlist_file)
{
	$lines = file($playlist_file);
	
	$pieces = explode(",", trim($lines[1]));
	$type = $pieces[7];
	$screenNB = $pieces[8];

	return array($type, $screenNB);
}

function downloadPlaylist()
{
	global $dealer_id, $mpc_id, $mpc_dir, $live_dir, $tmp_dir, $update_dir, $playlist_url, $playlist_file;
	
	if(empty($dealer_id) || empty($mpc_id))
		return '#ERROR: invalid dealer id or mpc id.';
	
	if(!makeDir($mpc_dir))
		return"#ERROR: caonnot create ".$mpc_dir;
	
	if(!makeDir($live_dir))
		return "#ERROR: caonnot create ".$live_dir;
	
	if(!makeDir($tmp_dir))
		return "#ERROR: caonnot create ".$tmp_dir;
	
	if(!makeDir($update_dir))
		return "#ERROR: caonnot create ".$update_dir;
	
	if(!downloadTxtFile($playlist_url, $playlist_file))
		return "#ERROR: caonnot download playlist from ".$playlist_url;
	
	emptyDir($update_dir);
	emptyDir($live_dir);
	emptyDir($tmp_dir);
	
	$file = fopen($playlist_file, "r");
	while(!feof($file))
		$line .= fgets($file).':::';
	fclose($file);
	
	return $line;
}

function downloadFile()
{
	global $tmp_dir, $live_dir;
	
	$url = $_GET['url'];
	$filename = $_GET['filename'];
	
	if(downloadBinFile($url, $tmp_dir.$filename))
		if(copyFile($tmp_dir.$filename, $live_dir.$filename, $live_dir))
			return "done";
	
	return "failed";
}

function updatePlaylist()
{
	global $update_dir, $mpc_dir, $tmp_dir, $playlist_file, $playlist_url;
	
	if(count(scandir($update_dir)) > 2) // cancel update if update dir is not empty
		return "done";
	
	$old_playlst = file($playlist_file);
	$new_playlst_file = $mpc_dir."playlist";
	
	if(!downloadTxtFile($playlist_url, $new_playlst_file))
		return "#ERROR: caonnot download playlist from $playlist_url";
	
	$new_playlst = file($new_playlst_file);
	
	$updates = collectUpdates($old_playlst, $new_playlst);
	foreach($updates as $k =>$v)
	{
		$pieces = explode(",", $v);
		$url = $pieces[0];
		$filename = $pieces[1];
		if(downloadBinFile($url.$filename, $tmp_dir.$filename))
			copyFile($tmp_dir.$filename, $update_dir.$filename, $update_dir);
	}
	
	return "done";
}

function updatePlaylistDynamic()
{
	global $update_dir, $mpc_dir, $tmp_dir, $live_dir, $playlist_file, $playlist_url;
	
	if(count(scandir($update_dir)) > 2) // cancel update if update dir is not empty
		return "done";
	
	$old_playlst = file($playlist_file);
	$new_playlst_file = $mpc_dir."playlist";
	
	if(!downloadTxtFile($playlist_url, $new_playlst_file))
		return "#ERROR: caonnot download playlist from $playlist_url";
	
	$new_playlst = file($new_playlst_file);
	$updates = collectUpdates($old_playlst, $new_playlst);
	
	if(empty($updates))
		return 'empty';
	
	foreach($updates as $k =>$v)
	{
		$pieces = explode(",", $v);
		$url = $pieces[0];
		$filename = $pieces[1];
	
		if(!downloadBinFile($url.$filename, $tmp_dir.$filename))
			return "failed";
	
		if(!copyFile($tmp_dir.$filename, $update_dir.$filename, $live_dir))
			return "failed";
	}
	
	$exclude_list = array(".", "..");
	$updates = array_diff(scandir($update_dir), $exclude_list);
	
	foreach($updates as $k =>$v)
	{
		if(!copy($update_dir.$v, $live_dir.$v))
			return "failed";
		if(!unlink($update_dir.$v))
			return "failed";
	}
	
	$new_playlst_file = $mpc_dir."playlist";
	if(!rename($new_playlst_file, $playlist_file))
		return "failed";
	
	return "done";
}

function moveUpdates()
{
	global $update_dir, $live_dir;
	
	$exclude_list = array(".", "..");
	$updates = array_diff(scandir($update_dir), $exclude_list);
	foreach($updates as $k =>$v)
	{
		if(!copy($update_dir.$v, $live_dir.$v))
			return "failed";
		if(!unlink($update_dir.$v))
			return "failed";
	}
	
	return "done";
}

function savePrice($data)
{
	global $MEDIA_PLAYER_DIR, $_POST;
	
	$price_json = $MEDIA_PLAYER_DIR.'mediapc/'.$_POST['dealerid'].'_'.$_POST['mpcid'].'/live/price.json';
	
	file_put_contents($price_json, json_encode($data));
}

function loadPrice()
{
	global $live_dir;
	$price_json = $live_dir.'price.json';
	
	if (!file_exists($price_json))
		return "";
	
	$json = file_get_contents($price_json);
	return utf8_encode($json);
}

function makeDir($dir)
{
	if(!is_dir($dir))
	if(!mkdir($dir))
		return 0;
	return 1;
}

function emptyDir($dir)
{
	$res = true;
	$exclude_list = array(".", "..");
	$files = array_diff(scandir($dir), $exclude_list);
	foreach($files as $k =>$v)
	{
		if(!unlink($dir.$v))
			$res = false;
	}
	return $res;
}

function downloadTxtFile($url, $destFile)
{
	$srcFile = file_get_contents($url);
	$utf8 = mb_convert_encoding($srcFile, 'UTF-8');

	return file_put_contents($destFile, $utf8);
}

function downloadBinFile($url, $destFile)
{
	$url = utf8_decode($url);

	if(file_put_contents($destFile, fopen($url, 'r')))
		return true;

	return false;
}

function copyFile($srcFile, $destFile, $destDir)
{
	global $dealer_id;

	if(isFlash($srcFile) && startsWith($dealer_id, 'mcQC'))
		return copy($srcFile, renameFlash($destFile));
	elseif(isVideo($srcFile))
		return convertFile($srcFile, $destFile);
	elseif(isZip($srcFile))
		return extractFile($srcFile, $destDir);
	else
		return copy($srcFile, $destFile);
}

function extractFile($srcFile, $destDir)
{
	$zip = new ZipArchive;

	if ($zip->open($srcFile) === true)
	{
		$zip->extractTo($destDir);
		$zip->close();
		return true;
	}

	return false;
}

function convertFile($srcFile, $destFile)
{
	$dest = substr_replace($destFile , 'mp4', strrpos($destFile , '.') +1);
	$convert = "avconv -i $srcFile -c:v libx264 -qscale 3 -strict experimental -y $dest";

	$exec = shell_exec($convert);

	return true;
}

function renameFlash($swf)
{
	$fn = basename($swf);
	$new_fn = "1_$fn";

	return str_replace($fn, $new_fn, $swf);
}

function isZip($file)
{
	$lower = strtolower($file);

	if(endsWith($lower, '.zip'))
		return true;

	return false;
}

function isVideo($file)
{
	$lower = strtolower($file);

	if(endsWith($lower, '.mp4'))
		return true;
	if(endsWith($lower, '.wmv'))
		return true;
	if(endsWith($lower, '.mov'))
		return true;
	if(endsWith($lower, '.avi'))
		return true;
	if(endsWith($lower, '.mpg'))
		return true;
	if(endsWith($lower, '.mpeg'))
		return true;
	if(endsWith($lower, '.flv'))
		return true;
	if(endsWith($lower, '.m1v'))
		return true;
	if(endsWith($lower, '.m2v'))
		return true;
	if(endsWith($lower, '.asf'))
		return true;
	if(endsWith($lower, '.mpe'))
		return true;

	return false;
}

function isFlash($file)
{
	$lower = strtolower($file);

	if(endsWith($lower, '.swf'))
		return true;

	return false;
}

function collectUpdates($old, $new)
{
	$updates = array();

	foreach ($new as $new_line_num => $new_line)
	{
		if(startsWith($new_line, "play,") || startsWith($new_line, "support,"))
		{
			$not_found = true;
			$new_pieces = explode(",", $new_line);
			$new_url = $new_pieces[3];
			$new_filename = $new_pieces[4];
			$new_timestamp = $new_pieces[14];
			$new_item = "$new_url,$new_filename";

			foreach ($old as $old_line_num => $old_line)
			{
				if(startsWith($old_line, "play,") || startsWith($old_line, "support,"))
				{
					$old_pieces = explode(",", $old_line);
					$old_filename = $old_pieces[4];
					$old_timestamp = $old_pieces[14];

					if(strcmp($new_filename, $old_filename) == 0)
					{
						$not_found = false;

						if(strcmp($new_timestamp, $old_timestamp) != 0)
						{
							if(!in_array($new_item, $updates))
							{
								$updates[] = $new_item;
								break;
							}
						}
					}
				}
			}
			if($not_found && !in_array($new_item, $updates))
				$updates[] = $new_item;
		}
	}
	return $updates;
}

function startsWith($haystack, $needle)
{
	$length = strlen($needle);
	return (substr($haystack, 0, $length) === $needle);
}

function endsWith($haystack, $needle)
{
	$length = strlen($needle);
	
	if ($length == 0)
		return true;

	return (substr($haystack, -$length) === $needle);
}
?>