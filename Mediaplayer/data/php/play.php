<?php
header('Access-Control-Allow-Origin: *');
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");

include 'constants.php';

if(!file_exists($playlist_file))
	exit("Playlist file does not exist.");

if($_GET['req'] == 'reload_playlist')
{
	$new_playlst_file = $mpc_dir."playlist";
	rename($new_playlst_file, $playlist_file);

	$playlist = file($playlist_file);
	foreach($playlist as $key => $line)
	{
		$pieces = explode(",", $line);
		if(startsWith($line, "frame,"))
			$players[] = new Player($pieces[1], $pieces[2], $pieces[3],
					$pieces[4], $pieces[5], $pieces[6]);

		if(startsWith($line, "play,"))
			$players[$pieces[1]]->addPlaylistItem($line);
	}

	foreach($players as $key => $player)
		$items[] = json_encode($player->playlist);
	
	echo json_encode($items);

	exit();
}

echo initWebPlayer();

class Player{

	function __construct($id, $top, $left, $width, $height, $layer)
	{
		$this->id = $id;
		$this->top = $top."%";
		$this->left = $left."%";
		$this->width = $width."%";
		$this->height = $height."%";
		$this->layer = $layer;
	}

	function addPlaylistItem($item)
	{
		$this->playlist[] = htmlspecialchars($item);
	}
}

function initWebPlayer()
{
	global $playlist_file, $new_title, $REPORT_URL, $live_url, $PRICE_URL,
	$dealer_id, $mpc_id, $LICENCE_KEY, $VERSION, $update_cycle, $report_cycle;
	
	$dynamic = false;
	
	$file = file($playlist_file);
	foreach($file as $key => $line)
	{
		$pieces = explode(",", trim($line));
		if($key == 1 && $pieces[7] == 'dynamic')
			$dynamic = true;
		
		if(startsWith($line, "frame,"))
			$players[] = new Player($pieces[1], $pieces[2], $pieces[3], $pieces[4], $pieces[5], $pieces[6]);
	
		if(startsWith($line, "play,"))
			$players[$pieces[1]]->addPlaylistItem($line);
	}

	$doc = new DOMDocument();
	
	if($dynamic)
	{
		$doc->loadHTMLFile($live_url."Index.html");
		
		$links = $doc->getElementsByTagName('link');
		foreach ($links as $link) 
			$link->setAttribute('href', $live_url.$link->getAttribute('href'));
		
		$scripts = $doc->getElementsByTagName('script');
		foreach ($scripts as $script)
			$script->setAttribute('src', $live_url.$script->getAttribute('src'));
		
		$playlist = $doc->createElement('playlist');
		$playlist->setAttribute('id', 'playlist');
		$playlist->setAttribute('dynamic', '1');
		$playlist->setAttribute('liveurl', $live_url);
		$playlist->setAttribute('repurl', $REPORT_URL);
		$playlist->setAttribute('priceurl', $PRICE_URL);
		$playlist->setAttribute('dealerid', $dealer_id);
		$playlist->setAttribute('mpcid', $mpc_id);
		$playlist->setAttribute('lickey', $LICENCE_KEY);
		$playlist->setAttribute('ver', $VERSION);
		$playlist->setAttribute('up_int', $update_cycle);
		$playlist->setAttribute('rep_int', $report_cycle);
		$playlist->setAttribute('display', 'none');
		$doc->getElementsByTagName('head')->item(0)->appendChild($playlist);
		
		$play = $doc->createElement('script');
		$play->setAttribute('src', '../js/play.js');
		$doc->getElementsByTagName('head')->item(0)->appendChild($play);
	}
	else
	{
		$doc->loadHTMLFile("play.html");
		
		$playlist = $doc->getElementByid('playlist');
		$playlist->setAttribute('liveurl', $live_url);
		$playlist->setAttribute('repurl', $REPORT_URL);
		$playlist->setAttribute('dealerid', $dealer_id);
		$playlist->setAttribute('mpcid', $mpc_id);
		$playlist->setAttribute('lickey', $LICENCE_KEY);
		$playlist->setAttribute('ver', $VERSION);
		$playlist->setAttribute('up_int', $update_cycle);
		$playlist->setAttribute('rep_int', $report_cycle);
		$playlist->setAttribute('updater', getMaxPlayerId($doc, $players));
		
		foreach ($players as $p)
			addPlayer($doc, $p, $url);
	}

	$title = $doc->getElementsByTagName('title')->item(0);
	$title->nodeValue = $new_title;
	
	return $doc->saveHTML();
}

function initWebPreview($fileName, $url)
{
	$file = fopen($fileName, "r") or exit("Unable to open playlist file.");
	$players = array();

	while(!feof($file))
	{
		$line = fgets($file);
		$pieces = explode(",", $line);

		if(startsWith($line, "frame"))
			$players[] = new Player(
					$pieces[1], $pieces[2], $pieces[3],
					$pieces[4], $pieces[5], $pieces[6]);

		if(startsWith($line, "play,"))
			$players[$pieces[1]]->addPlaylistItem($line);
	}
	fclose($file);

	$doc = new DOMDocument();
	$doc->loadHTMLFile("media.html");

	foreach ($players as $p)
		addPlayer($doc, $p, $url);

	return $doc->saveHTML();
}

function addPlayer($doc, $newPlayer, $url)
{
	$screen = $doc->getElementByid('screen');

	$pos = "position: absolute; ";
	$top = "top: $newPlayer->top; ";
	$left = "left: $newPlayer->left; ";
	$width = "width: $newPlayer->width; ";
	$height = "height: $newPlayer->height; ";
	$layer = "z-index: -$newPlayer->layer;";
	$style = $pos.$top.$left.$width.$height.$layer;

	$player = $screen->appendChild($doc->createElement('div'));
	$player->setAttribute("id", "p-$newPlayer->id");
	$player->setAttribute("class", "player");
	$player->setAttribute("player-id", $newPlayer->id);
	$player->setAttribute("style", $style);

	$playlist = $doc->getElementByid('playlist');
	$items = $playlist->appendChild($doc->createElement('div'));
	$items->setAttribute('id', 'items-'.$newPlayer->id);
	$items->setAttribute('style', 'display:none;');
	$items->nodeValue = json_encode($newPlayer->playlist);
}

function addToolBar($doc, $id)
{
	$div = $doc->getElementById('p-'.$id);
	$toolbar = $doc->getElementById('toolbar');
	$div->appendChild($toolbar);
	return $doc;
}

function getMaxPlayerId($doc, $players)
{
	$maxSizePlaylist= 0;
	$maxPlayerId = 0;

	foreach ($players as $p)
	{
		$plSize = count($p->playlist);
		if($plSize > $maxSizePlaylist)
		{
			$maxSizePlaylist = $plSize;
			$maxPlayerId = $p->id;
		}
	}
	return $maxPlayerId;
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
