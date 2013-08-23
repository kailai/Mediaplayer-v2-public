var req_url = "/data/php/media.php";

$(document).ready(function(){
	var screen = $("#preview_screen");
	var modal = $("#modal");
	var modal_body = $("#modal_body");
	var mpc_info = $("#mpc_name");

	screen.height(screen.width()*9/16);
	$(window).resize(function(){ screen.height(screen.width()*9/16); });
	modal.on('hide', function(){
		modal_body.find("*").remove();
		modal.find("#close_modal").show();
	});

	var request = $.ajax({
		url: req_url,
		type: "GET",
		data: { req: 'get_system_info' }
	});

	request.done(function(msg){
		var info = $.parseJSON(msg);
		mpc_info.find("#name").text(info.name);
		mpc_info.find("#ip").text(info.ip);
	});
	
	$(document).ajaxStart(function(){ disableTabs(); });
	$(document).ajaxStop(function(){ enableTabs(); });
	
	reloadPlaylists();
	
});

function reloadPlaylists()
{
	var saved_playlists = $("#saved_playlists");
	saved_playlists.find(".playlist-option").remove();

	var request = $.ajax({
		url: req_url, 
		type: "GET", 
		data: { req: "get_playlists" }
	});

	request.done(function(msg){
		var playlists = $.parseJSON(msg);

		$.each(playlists, function(k, v){
			var ids = v.playlist.split("_");
			var dealer_id = ids[0];
			var mpc_id = ids[1];
			var type = v.type=='' ? 'classic' : v.type;
			var screen_nb = v.scr_nb=='' ? 1 : v.scr_nb;
			
			var opt = $("<option></option>");
			opt.attr('class', 'playlist-option');
			opt.attr('dealerid', dealer_id);
			opt.attr('mpcid', mpc_id);
			opt.attr('type', type);
			opt.attr('scr_nb', screen_nb);
			opt.text(mpc_id+' - '+type+' - '+screen_nb+' screen(s)');
			
			saved_playlists.append(opt);
		});
	});
}

function disableTabs()
{
	$.each($('.nav-tabs li'), function(k, v){ $(v).addClass('disabled'); });
	$.each($('button'), function(k, v){ $(v).attr('disabled', 'disabled'); });
}

function enableTabs()
{
	$.each($('.nav-tabs li'), function(k, v){ $(v).removeClass('disabled'); });
	$.each($('button'), function(k, v){ $(v).removeAttr('disabled'); });
}

function newMessage(type, message)
{
	var alert = $("<div></div>");

	if(type == "warning")
		alert.attr('class', 'alert');
	if(type == "success")
		alert.attr('class', 'alert alert-success');
	if(type == "error")
		alert.attr('class', 'alert alert-error');
	if(type == "info")
		alert.attr('class', 'alert alert-info');

	alert.text(message);
	alert.append($("<button type='button' class='close' data-dismiss='alert'>&times;</button>"));
	$("#message_div").prepend(alert);

	setTimeout(function(){ alert.remove(); },10000);
}

if (typeof String.prototype.startsWith != 'function') {
	String.prototype.startsWith = function (str){
		return this.indexOf(str) == 0;
	};
}