var req_url = "/data/php/media.php";
var play_url = "/data/php/play.php";
var watch_url = "/data/php/watch.php";
var sync;
var live_url;
var report_url;
var price_url;
var dealer_id;
var mpc_id;
var key;
var version;
var update_interval;
var report_interval;
var dynamic;
var watch_interval;
var watcher;

$(document).ready(function(){
	var playlist = $("#playlist");
	live_url = playlist.attr("liveurl");
	report_url = playlist.attr("repurl");
	price_url = playlist.attr("priceurl");
	dealer_id = playlist.attr("dealerid");
	mpc_id = playlist.attr("mpcid");
	key = playlist.attr("lickey");
	version = playlist.attr("ver");
	update_interval = playlist.attr("up_int");
	report_interval = playlist.attr("rep_int");
	dynamic = playlist.attr("dynamic");
	sync = false;
	watch_interval = 5000;

	if(dynamic != 1)
	{
		var players = initPlayers();
		$.each(players, function(){ play(this); });

		if(update_interval > 1)
			window.setInterval(update, update_interval*60*1000);

		if(report_interval > 1)
			window.setInterval(report, report_interval*60*1000);
	}
	else
	{
		if(update_interval > 1)
		{
			window.setInterval(updateDynamic, update_interval*60*1000);
			window.setInterval(downloadPrice, update_interval*60*1000);
		}

		if(report_interval > 1)
			window.setInterval(reportDynamic, report_interval*60*1000);
	}
});


function play(player)
{
	if(!player.items)
		return false;

	var count = 0;
	var fullscreen = 0;
	var dur = 0;
	var item = "";
	var file = "";
	var div = player.frame;
	var id = player.id;
	var screen = $("#screen");
	var total = player.items.length;

	div.change(function(){
		if(count == total)
		{
			count = 0;
			player.items = jQuery.parseJSON($("#items-"+id).html());
		}

		if(player.isUpdater)
		{
			watch();
			
			if(total == 1)
				watcher = setInterval(function(){ watch(); }, 60000);
		}

		item = player.items[count];
		var pieces = item.split(",");
		dealer_id.match("^mcQC") ? file = '1_'+pieces[4] : file = pieces[4];
		dur = total==1 ? 3600*4 : pieces[11];
		fullscreen = pieces[6];

		var current = $(".playing"+id);
		var next = genHtml(live_url, file, player.id);

		if(fullscreen == 1)
		{
			screen.prepend(next);
			scaleToFill(screen.find("video"));
		}
		else
		{
			div.prepend(next);
			scaleToFill(div.find("video"));
		}

		current.remove();

		window.setTimeout(function(){
			if (watcher)
				window.clearInterval(watcher);
			div.trigger('change');
		}, dur*1000);

		if(count == player.items.length-1 && player.isUpdater && sync)
			moveUpdates();

		count++;
	});

	div.trigger('change');
}

function watch()
{
	var title = $("title").text();

	if(title == "" || title == "Eye-In Media Player")
		return;

	var pieces = title.split("_");
	var mpc_id = pieces[0];
	var scr_id = pieces[1];
	var scr_name = pieces[2];
	var src_pos = pieces[3];

	$.ajax({
		url: watch_url,
		type: "GET",
		async: true, 
		data: {
			mpcid: mpc_id, 
			screen_id: scr_id, 
			screen_name: scr_name, 
			screen_pos: src_pos
		}
	});
}

function downloadPrice()
{
	var request = $.ajax({
		url: price_url,
		type: "GET",
		data: { req: 'get_prices', dealerid: dealer_id }
	});

	request.done(function(msg){
		if(!msg) return false;
		var prices = jQuery.parseJSON(msg);
		if($.isEmptyObject(prices)) return false;

		$.ajax({
			url: req_url,
			type: "POST",
			data: { req: 'save_price', dealerid: dealer_id, mpcid: mpc_id, data: prices }
		});
	});
}

function loadPrice()
{
	var request = $.ajax({
		url: req_url,
		type: "GET",
		data: { req: 'load_prices', dealerid: dealer_id, mpcid: mpc_id }
	});

	request.done(function(msg){
		var prices = jQuery.parseJSON(msg);
		console.info(prices);
	});
}

function reloadPlaylist()
{
	var request = $.ajax({
		url: play_url,
		type: "GET",
		data: {
			req: 'reload_playlist',
			mpcid: mpc_id, 
			dealerid: dealer_id
		}
	});
	request.done(function(msg){
		var playlist = jQuery.parseJSON(msg);
		$.each(playlist, function(index, value){
			$("#items-"+index).html(value);
		});
	});
	request.fail(function(jqXHR, textStatus) {});
}

function moveUpdates()
{
	var request = $.ajax({
		url: req_url,
		type: "GET",
		data: {
			req: 'move_updates',
			mpcid: mpc_id, 
			dealerid: dealer_id
		}
	});
	request.done(function(msg){
		if($.trim(msg) == "done")
			reloadPlaylist();
	});
	request.fail(function(jqXHR, textStatus) {});
}

function report(interval)
{
	$.ajax({
		url: report_url,
		type: "GET",
		data: {
			mediapcid: mpc_id, 
			dealerid: dealer_id, 
			licensekey: key,
			ver: version,
			type: 'Linux',
			status: 'playing',
			frame0: $("#p-0").children().attr("src"),
			frame1: $("#p-1").children().attr("src"),
			frame2: $("#p-2").children().attr("src"),
			frame3: $("#p-3").children().attr("src"),
			frame4: $("#p-4").children().attr("src"),
			frame5: $("#p-5").children().attr("src"),
			frame6: $("#p-6").children().attr("src"),
			frame7: $("#p-7").children().attr("src"),
			frame8: $("#p-8").children().attr("src"),
			frame9: $("#p-9").children().attr("src"),
			pdate: getTime(),
			supportCheck: '',
			rnd: Math.floor((Math.random()*10000)+1)
		}
	});
}

function update()
{
	sync = false;
	var request = $.ajax({
		url: req_url,
		type: "GET",
		data: {
			req: 'update_playlist',
			mpcid: mpc_id, 
			dealerid: dealer_id
		}
	});
	request.done(function(msg){ sync = true; });
	request.fail(function(jqXHR, textStatus) {});
}

function updateDynamic()
{
	var request = $.ajax({
		url: req_url,
		type: "GET",
		data: {
			req: 'update_playlist_dynamic',
			mpcid: mpc_id, 
			dealerid: dealer_id
		}
	});
	request.done(function(msg){
		if($.trim(msg) == 'done')
			location.reload();
	});
}

function reportDynamic()
{
	$.ajax({
		url: report_url,
		type: "GET",
		data: {
			mediapcid: mpc_id, 
			dealerid: dealer_id, 
			licensekey: key,
			ver: version,
			type: 'Linux',
			status: 'playing',
			frame0: 'Dynamic Playlist',
			frame1: 'Dynamic Playlist',
			frame2: 'Dynamic Playlist',
			frame3: 'Dynamic Playlist',
			frame4: 'Dynamic Playlist',
			frame5: 'Dynamic Playlist',
			frame6: 'Dynamic Playlist',
			frame7: 'Dynamic Playlist',
			frame8: 'Dynamic Playlist',
			frame9: 'Dynamic Playlist',
			pdate: getTime(),
			supportCheck: '',
			rnd: Math.floor((Math.random()*10000)+1)
		}
	});
}

function initPlayers()
{
	var updater_id = $("#playlist").attr("updater");
	var players = new Array();

	$.each($(".player"), function(){

		var id = $(this).attr("player-id");
		var player = $("player"+id);
		player.id = $(this).attr("player-id");
		player.items = jQuery.parseJSON($("#items-"+id).html());
		player.frame = $(this);

		if(player.id == updater_id)
			player.isUpdater = true;
		else
			player.isUpdater = false;

		players[id] = player;
	});

	return players;
}

function genHtml(url, file, id)
{
	if(isFlash(file))
		return genFlashHtml(url, file, id);
	if(isImage(file))
		return genImageHtml(url, file, id);
	if(isVideo(file))
		return genVideoHtml(url, file, id);
}

function genFlashHtml(url, file, id)
{
	return $('<embed class=playing'+id+' src='+url+file+
			' type=application/x-shockwave-flash base='+url+
	' scale=exactfit play=true loop=true wmode=transparent></embed>');
}

function genImageHtml(url, file, id)
{
	return $('<img class=playing'+id+' src='+url+file+'></img>');
}

function genVideoHtml(url, file, id)
{
	return $('<video class=playing'+id+
			' src='+url+file.substr(0, file.lastIndexOf('.'))+'.mp4'+
	' type=video/mp4 autoplay loop></video>');
}

function getTime()
{
	var date = new Date();

	var year = date.getFullYear();

	var month = date.getMonth()+1;
	if(month<10) month = '0'+month;

	var day = date.getDate();
	if(day<10) day = '0'+day;

	var hour = date.getHours();
	if(hour<10) hour = '0'+hour;

	var min = date.getMinutes();
	if(min<10) hour = '0'+min;

	var sec = date.getSeconds();
	if(sec<10) hour = '0'+sec;

	return year+"-"+month+"-"+day+" "+hour+":"+min+":"+sec;
}

function isFlash(file)
{
	if(file.toLowerCase().match(/swf$/))
		return true;

	return false;
}

function isImage(file)
{
	if(file.toLowerCase().match(/jpg$/))
		return true;
	if(file.toLowerCase().match(/jpeg$/))
		return true;
	if(file.toLowerCase().match(/png$/))
		return true;
	if(file.toLowerCase().match(/gif$/))
		return true;

	return false;
}

function isVideo(file)
{
	if(file.toLowerCase().match(/mp4$/))
		return true;
	if(file.toLowerCase().match(/webm$/))
		return true;
	if(file.toLowerCase().match(/avi$/))
		return true;
	if(file.toLowerCase().match(/mov$/))
		return true;
	if(file.toLowerCase().match(/wmv$/))
		return true;

	return false;
}

function scaleToFill(videoTags)
{
	$.each(videoTags, function(index, videoTag){
		var $video = $(videoTag);
		$video.bind("loadedmetadata", function(){
			var videoRatio = this.videoWidth / this.videoHeight;
			var tagRatio = $video.width() / $video.height();
			if (videoRatio < tagRatio)
				$video.css('-webkit-transform','scaleX('+tagRatio/videoRatio+')');
			else if (tagRatio < videoRatio)
				$video.css('-webkit-transform','scaleY('+videoRatio/tagRatio+')');
		});
	});
}