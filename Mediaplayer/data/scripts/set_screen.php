#!/usr/bin/php5

<?php
$USER = trim(exec('whoami'));
$HOME = "/home/$USER";
$WATCH = "$HOME/Mediaplayer/data/scripts/watch";
$PLAY = "$HOME/Mediaplayer/data/scripts/play";
$SCREEN_SCRIPT = "$HOME/Mediaplayer/data/scripts/screen.sh";
$PID_WATCHER = "$HOME/Mediaplayer/startup/log/pid_watcher";
$PID_PLAYER = "$HOME/Mediaplayer/startup/log/pid_player";

$cmd="echo $(DISPLAY=:0 xrandr -q | grep ' connected')";
$screens = shell_exec($cmd);
$pieces = explode(' ', $screens);

$SCREEN_WIDTH = 1920;
$SCREEN_HEIGHT = 1080;

for($i = 0; $i < count($pieces); $i++)
	if($pieces[$i] == "connected") $outputs[] = $pieces[$i-1];

$total = count($outputs);

if($argv[1] == "set_all" && count($argv) > 2)
	setAllDisplay($argv);
elseif($argv[1] == "all")
{
	if($argv[2] == "normal" && $argv[3] == "mirror")
		resetRotation('normal', true);
	elseif($argv[2] == "normal" && $argv[3] == "no-mirror")
		resetRotation('normal', false);
	elseif($argv[2] == "right" && $argv[3] == "mirror")
		resetRotation('right', true);
	elseif($argv[2] == "right" && $argv[3] == "no-mirror")
		resetRotation('right', false);
	elseif($argv[2] == "left" && $argv[3] == "mirror")
		resetRotation('left', true);
	elseif($argv[2] == "left" && $argv[3] == "no-mirror")
		resetRotation('left', false);
	elseif($argv[2] == "position" && $argv[3] == "mirror")
		resetPosition(true);
	elseif($argv[2] == "position" && $argv[3] == "no-mirror")
		resetPosition(false);
	elseif($argv[2] == "1920" && $argv[3] == "1080")
		resetResolution(1920, 1080);
	elseif($argv[2] == "1280" && $argv[3] == "720")
		resetResolution(1280, 720);
	elseif($argv[2] == "1680" && $argv[3] == "1050")
		resetResolution(1680, 1050);
	elseif($argv[2] == "1280" && $argv[3] == "800")
		resetResolution(1280, 800);
	elseif($argv[2] == "1024" && $argv[3] == "768")
		resetResolution(1024, 768);
	elseif($argv[2] == "screen" && $argv[3] == "off")
		turnOffScreens();
	elseif($argv[2] == "screen" && $argv[3] == "on")
		turnOnScreens();
	elseif($argv[2] == "screens" && $argv[3] == "on")
		turnOnAllScreens();
	elseif($argv[2] == "screens" && $argv[3] == "off")
		killOff();
	elseif($argv[2] == "watcher" && $argv[3] == "start")
		startWatcher(5);
	elseif($argv[2] == "player" && $argv[3] == "start")
		startplayer(33);
	elseif($argv[2] == "watcher" && $argv[3] == "startnodelay")
		startWatcher(0);
	elseif($argv[2] == "player" && $argv[3] == "startnodelay")
		startplayer(0);
	elseif($argv[2] == "display" && $argv[3] == "dynamic")
		setAllDisplayDynamic();
}
else
{
	if($argv[2] == "normal" && $argv[3] == "mirror")
		setRotation($argv[1], 'normal', true);
	elseif($argv[2] == "normal" && $argv[3] == "no-mirror")
		setRotation($argv[1], 'normal', false);
	elseif($argv[2] == "right" && $argv[3] == "mirror")
		setRotation($argv[1], 'right', true);
	elseif($argv[2] == "right" && $argv[3] == "no-mirror")
		setRotation($argv[1], 'right', false);
	elseif($argv[2] == "left" && $argv[3] == "mirror")
		setRotation($argv[1], 'left', true);
	elseif($argv[2] == "left" && $argv[3] == "no-mirror")
		setRotation($argv[1], 'left', false);
	elseif($argv[2] == "1920" && $argv[3] == "1080")
		setResolution($argv[1], 1920, 1080);
	elseif($argv[2] == "1280" && $argv[3] == "720")
		setResolution($argv[1], 1280, 720);
	elseif($argv[2] == "1680" && $argv[3] == "1050")
		setResolution($argv[1], 1680, 1050);
	elseif($argv[2] == "1280" && $argv[3] == "800")
		setResolution($argv[1], 1280, 800);
	elseif($argv[2] == "1024" && $argv[3] == "768")
		setResolution($argv[1], 1024, 768);
	elseif($argv[2] == "rightof" && $argv[3] == "prev")
		setPosition($argv[1], "right-of");
}

function setAllDisplay($args) 
{
	global $outputs, $total;
	
	foreach ($args as $k=>$arg) 
	{
		if($k < 2) continue;
		
		$pieces = explode ( '.', $arg );
		$output = $pieces[0];
		$width = $pieces[1];
		$height = $pieces[2];
		$direction = $pieces[3];
		$position = $pieces[4];
		
		$xrandr = exec("DISPLAY=:0 xrandr -q | grep '$output connected'");
		if(empty($xrandr)) exit();
		
		setRes($output, $width, $height, $xrandr);
		setRot($output, $xrandr, $direction);
		setPos($total, $outputs, $output, $position, $xrandr);
	}
}

// function setAllDisplay($args)
// {
// 	$xrandr = exec("DISPLAY=:0 xrandr -q | grep '$args[1] connected'");
// 	if(empty($xrandr)) exit();
	
// 	global $outputs, $total;
	
// 	$output = $args[1];
// 	$width = $args[2];
// 	$height = $args[3];
// 	$direction = $args[4];
// 	$position = $args[5];
	
// 	setRes($output, $width, $height, $xrandr);
// 	setRot($output, $xrandr, $direction);
// 	setPos($total, $outputs, $output, $position, $xrandr);
// }

function setRes($output, $width, $height, $xrandr)
{
	$pieces = explode('x', $xrandr);
	$w = $pieces[0];
	$pieces = explode('+', $pieces[1]);
	$h = $pieces[0];
	
	if($w == $width && $h == $height) return true;
	$cvt = shell_exec("cvt $width $height 60 | grep Modeline");
	$line = trim(substr($cvt, 9));
	$mode = explode(" ", $line);
	$mode = $mode[0];
	
	$res = shell_exec("DISPLAY=:0 xrandr -q | grep $mode");
	
	if(empty($res)) shell_exec("DISPLAY=:0 xrandr --newmode $line");
	
	shell_exec("DISPLAY=:0 xrandr --addmode $output $mode");
	shell_exec("DISPLAY=:0 xrandr --output $output --mode $mode");
	
	return true;
}

function setRot($output, $xrandr, $direction)
{
	$pieces = explode(' ', $xrandr);
	$dir = $pieces[3];
	
	if($dir == '(normal') $dir = 'normal';
	if($dir == $direction) return true;
	
	shell_exec("DISPLAY=:0 xrandr --output $output --rotate $direction");
	
	return true;
}

function setPos($total, $outputs, $output, $position, $xrandr)
{
	if($total <= 1) return true;
	
	for($i = 0; $i < $total; $i ++) 
	{
		$curr = $outputs [$i];
		
		if($output != $curr) continue;
		
		if($i == 0) 
		{
			shell_exec ( "DISPLAY=:0 xrandr --output $output --primary" );
			break;
		} 
		else 
		{
			$prev = $outputs [$i - 1];
			
			$pieces = explode(' ', $xrandr);
			$pieces = explode ( '+', $pieces[2] );
			$pos_x = $pieces[1];
			$pos_y = $pieces[2];
			
			$pieces = explode ( ' ', exec("DISPLAY=:0 xrandr -q | grep '$prev connected'") );
			$pieces = explode ( '+', $pieces[2] );
			$pos_x_prev = $pieces[1];
			$pieces = explode( 'x', $pieces[0] );
			$width_prev = $pieces[0];
			
			$pos = $pos_x_prev + $width_prev;
			
			if ($pos_x != $pos) 
				shell_exec ( "DISPLAY=:0 xrandr --output $output --$position $prev" );
			
			break;
		}
	}
	
	return true;
}

function setResolution($output, $width, $height)
{
	$cmd = "DISPLAY=:0 xrandr -q | grep '$output connected'";
	$res = exec($cmd);
	if(empty($res)) exit();

	$pieces = explode(' ', $res);
	$res = $pieces[2];
	$pieces = explode('x', $res);
	$w = $pieces[0];
	$res = $pieces[1];
	$pieces = explode('+', $res);
	$h = $pieces[0];

	if($w == $width && $h == $height) exit();

	$cmd = "cvt $width $height 60 | grep Modeline";
	$cvt = shell_exec($cmd);
	$line = trim(substr($cvt, 9));
	$mode = explode(" ", $line);
	$mode = $mode[0];

	$cmd = "DISPLAY=:0 xrandr -q | grep $mode";
	$res = shell_exec($cmd);

	if(empty($res))
		shell_exec("DISPLAY=:0 xrandr --newmode $line");

	$cmd = "DISPLAY=:0 xrandr --addmode $output $mode";
	shell_exec($cmd);

	$cmd = "DISPLAY=:0 xrandr --output $output --mode $mode";
	shell_exec($cmd);
}

function setRotation($output, $direction, $mirrored)
{
	$cmd = "DISPLAY=:0 xrandr -q | grep '$output connected'";
	$res = exec($cmd);
	if(empty($res)) exit();

	$pieces = explode(' ', $res);
	$dir = $pieces[3];

	if($dir == '(normal') $dir = 'normal';
	if($dir == $direction) exit();
	
	shell_exec("DISPLAY=:0 xrandr --output $output --rotate $direction");
}

function setPosition($name, $position)
{
	global $outputs, $total;

	if($total <= 1) exit();

	for ($i = 0; $i < $total; $i++)
	{
		$curr = $outputs[$i];

		if($i == 0)
		{
			if($name == $curr)
			{
				$cmd = "DISPLAY=:0 xrandr --output $name --primary";
				break;
			}
		}
		else
		{
			$prev = $outputs[$i - 1];

			if($name == $curr)
			{
				$res = exec("DISPLAY=:0 xrandr -q | grep '$curr connected'");
				$pieces = explode(' ', $res);
				$res = $pieces[2];
				$pieces = explode('+', $res);
				$pos_x = $pieces[1];
				$pos_y = $pieces[2];
					
				$res = exec("DISPLAY=:0 xrandr -q | grep '$prev connected'");
				$pieces = explode(' ', $res);
				$res = $pieces[2];
				$pieces = explode('+', $res);
				$pos_x_prev = $pieces[1];
				$res = explode('x', $pieces[0]);
				$width_prev = $res[0];
					
				$pos = $pos_x_prev + $width_prev;

				if($pos_x != $pos)
					shell_exec("DISPLAY=:0 xrandr --output $curr --$position $prev");

				break;
			}
		}
	}
}

function resetRotation($direction, $mirrored)
{
	global $outputs, $total;

	for ($i = 0; $i < $total; $i++)
	{
		$curr = $outputs[$i];
		$cmd = "DISPLAY=:0 xrandr --output $curr --rotate $direction";
		shell_exec($cmd);
	}

	resetPosition($mirrored);
}

function resetPosition($mirrored)
{
	global $outputs, $total;

	if($total <= 1)
		exit();

	$cmd1 = "DISPLAY=:0 ";

	for ($i = 0; $i < $total; $i++)
	{
		if($i == 0) continue;

		$first = $outputs[0];
		$curr = $outputs[$i];
		$prev = $outputs[$i - 1];

		if($mirrored)
		{
			$cmd1 = "DISPLAY=:0 xrandr --output $curr --same-as $first && ";
		}
		else
		{
			$cmd = "DISPLAY=:0 xrandr -q | grep '$curr connected'";
			$res = exec($cmd);
			$pieces = explode(' ', $res);
			$res = $pieces[2];
			$pieces = explode('+', $res);
			$pos_x = $pieces[1];
			$pos_y = $pieces[2];

			$cmd = "DISPLAY=:0 xrandr -q | grep '$prev connected'";
			$res = exec($cmd);
			$pieces = explode(' ', $res);
			$res = $pieces[2];
			$pieces = explode('+', $res);
			$pos_x_prev = $pieces[1];
			$res = explode('x', $pieces[0]);
			$width_prev = $res[0];

			$pos = $pos_x_prev + $width_prev;

			if($pos_x == $pos)
				continue;
			else
				$cmd1 .= "xrandr --output $curr --right-of $prev && sleep 2 && ";
		}

		$cmd1 .= "echo ok";
		shell_exec($cmd1);
	}
}

function resetResolution($width, $height)
{
	$cvt = shell_exec("cvt $width $height 60 | grep Modeline");
	$line = trim(substr($cvt, 9));
	$mode = explode(" ", $line);

	$mode = $mode[0];

	$cmd = "DISPLAY=:0 xrandr -q | grep $mode";
	$res = shell_exec($cmd);

	$cmd = "DISPLAY=:0 xrandr --newmode $line";
	shell_exec($cmd);

	global $outputs, $total;
	for ($i = 0; $i < $total; $i++)
	{
		$curr = $outputs[$i];

		$cmd1 = "DISPLAY=:0 xrandr --addmode $curr $mode";
		$cmd2 = "DISPLAY=:0 xrandr --output $curr --mode $mode";

		shell_exec("$cmd1 && $cmd2");
	}
}

function turnOffScreens()
{
	global $outputs, $total;

	for ($i = 0; $i < $total; $i++)
	{
		$curr = $outputs[$i];
		$cmd = "DISPLAY=:0 xrandr --output $curr --off";
		shell_exec($cmd);
	}

	exit('done');
}

function killWatcher()
{
	global $PID_WATCHER;

	$pid = shell_exec("cat $PID_WATCHER");
	shell_exec("kill $pid");
}

function killOff()
{
	global $PID_WATCHER, $outputs, $total;

	$pid = shell_exec("cat $PID_WATCHER");
	shell_exec("kill $pid");

	$cmd = "";
	for ($i = 0; $i < $total; $i++)
	{
		$curr = $outputs[$i];
		$cmd .= "DISPLAY=:0 xrandr --output $curr --off && ";
	}
	
	for ($i = 0; $i < $total; $i++)
	{
		$curr = $outputs[$i];
		$cmd .= "xrandr --output $curr --off && ";
	}
	
	$cmd .= "echo 'done'";
	
	return shell_exec($cmd);
}

function turnOnScreens()
{
	global $WATCH, $PID_WATCHER, $SCREEN_SCRIPT;

	$terms = trim(shell_exec("pgrep gnome-terminal"));
	$pid = trim(shell_exec("cat $PID_WATCHER"));

	if(strstr($terms, $pid)) // watcher is running
	{
		shell_exec("sleep 2 && $SCREEN_SCRIPT");
	}
	else
	{
		turnOnAllScreens();
		startWatcher(2);
	}

	exit();
}

function turnOnAllScreens()
{
	global $outputs, $total;
	
	for ($i = 0; $i < $total; $i++)
	{
		$curr = $outputs[$i];
		$cmd = "DISPLAY=:0 xrandr --output $curr --auto";
		shell_exec($cmd);
	}
}

function startWatcher($delay)
{
	global $WATCH, $PID_WATCHER;

	if($delay > 0)
		$cmd = "sleep $delay && DISPLAY=:0 gnome-terminal --disable-factory -x $WATCH & pid=$! && echo \$pid > $PID_WATCHER";
	else
		$cmd = "DISPLAY=:0 gnome-terminal --disable-factory -x $WATCH & pid=$! && echo \$pid > $PID_WATCHER";

	shell_exec($cmd);
}

function startplayer($delay)
{
	global $PLAY, $PID_PLAYER, $HOME;

// 	setDynamicScreen();
	
	if($delay > 0)
		$cmd = "sleep $delay && DISPLAY=:0 gnome-terminal --disable-factory -x $PLAY & pid=$! && echo \$pid > $PID_PLAYER";
	else
		$cmd = "sleep 2 && DISPLAY=:0 gnome-terminal --disable-factory -x $PLAY & pid=$! && echo \$pid > $PID_PLAYER";

	shell_exec($cmd);
}

function setDynamicScreen()
{
	global $HOME;
	
	$script_dir = "$HOME/Mediaplayer/startup/scripts/";
	
	foreach (scandir($script_dir) as $file)
		if(substr($file, strrpos($file, '.')+1) == 'sh')
			$scripts[] = $script_dir.$file;
	
	foreach ($scripts as $script)
	{
		$cmd = "cat $script | grep dealerid | cut -d '=' -f 3 | cut -d '&' -f 1";
		$playlist = trim(shell_exec($cmd));
		$playlists[$playlist][] = $script;
	}
	
	$dynamic_outputs = array();
	$output_string = "[";
	
	$index = 0;
	foreach ($playlists as $key=>$playlist)
	{
		$screen_nb = count($playlist);
	
		$width = 1920*$screen_nb;
		$height = 1080;
		if($index==0)
			$pos_x = 0;
		else
			$pos_x = $width + $dynamic_outputs[$index-1]['pos_x'];
		$pos_y = 0;
	
		$dynamic_outputs[$index]['width'] = $width;
		$dynamic_outputs[$index]['height'] = $height;
		$dynamic_outputs[$index]['pos_x'] = $pos_x;
		$dynamic_outputs[$index]['pos_y'] = $pos_y;
	
		$output_string .= "\'".$width.'x'.$height.'+'.$pos_x.'+'.$pos_y."\',";
		$index++;
	}
	
	$output_string = rtrim($output_string, ',');
	$output_string .= "]";
	
	$cmd = "dconf write /org/compiz/profiles/Default/plugins/core/detect-outputs false";
	shell_exec($cmd);
	$cmd = 'dconf write /org/compiz/profiles/Default/plugins/core/outputs \"'.$output_string.'\"';
	shell_exec($cmd);
	$cmd = "DISPLAY=:0 compiz --replace &";
	shell_exec($cmd);
}

function setAllDisplayDynamic()
{
	global $total, $SCREEN_WIDTH, $SCREEN_HEIGHT;
	
	$width = $SCREEN_WIDTH*$total;
	$height = $SCREEN_HEIGHT;
	$pos_x = 0;
	$pos_y = 0;
	
	$output_string = "[\'".$width.'x'.$height.'+'.$pos_x.'+'.$pos_y."\']";
	
	$cmd = "dconf write /org/compiz/profiles/Default/plugins/core/outputs $output_string";
	shell_exec($cmd);
	
	$cmd = "dconf write /org/compiz/profiles/Default/plugins/core/detect-outputs false";
	shell_exec($cmd);
}

?>
