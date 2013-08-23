<?php
require_once('getid3/getid3.php');

$fname = $_GET['file'];
if(is_file($fname)&&file_exists($fname))
{
 	$finfo = finfo_open(FILEINFO_MIME_TYPE);
	$getID3 = new getID3;
	$file = $getID3->analyze($fname);
	echo intval($file['playtime_seconds']);
 	finfo_close($finfo);
}
else
{
	echo 0;
}
?>
