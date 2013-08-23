$(document).ready(function(){
	var downoaded = "Playlist file downloaded.\n";
	var mpc_url = "http://eyein.serveftp.net/mpc.php";
//	var init_url = "/data/php/init.php";
	var play_url = "/data/php/play.php";
	var req_url = "/data/php/media.php";
	
	var playlist_tab = $("#playlist_set_tab_link");
	var saved_playlists = $("#saved_playlists");
	var dealerid_input = $("#input_dealerid");
	var mpcid_input = $("#input_mpcid");
	var send = $("#send_button");
	var del_playlist = $("#delete_button");
	var mpc_table = $("#current_mpc_tbody");
	var screen = $("#preview_screen");
	var open = $("#open_button");

	playlist_tab.on('show', function(){
		$(".alert").remove();
		
		var dealerids = new Array();
		var mpcids = new Array();

		var request = $.ajax({
			url: req_url,
			type: "GET",
			data: { req: 'get_dealer_name' }
		});

		request.done(function(msg){
			var dealer = $.trim(msg);
			
			dealerid_input.val('');
			mpcid_input.val('');

			dealerid_input.attr('placeholder', 'Loading ...');
			mpcid_input.attr('placeholder', 'Loading ...');
			dealerid_input.prop('disabled', true);
			mpcid_input.prop('disabled', true);
			send.prop('disabled', true);

			var request = $.ajax({
				url: mpc_url, 
				type: "GET",
				data: {
					req: "get_dealers", 
					dealerid: dealer
				}
			});

			request.done(function(msg){
				dealerids = $.parseJSON(msg);
				
				var autocomplete = dealerid_input.typeahead();
				autocomplete.data('typeahead').source = dealerids;

				dealerid_input.prop('disabled', false);
				dealerid_input.attr('placeholder', 'Dealer ID');

				var count = 0;
				var mpcArrays = new Array();
				if(dealer == "Dealer")
				{
					var request1 = $.ajax({
						url: mpc_url, 
						type: "GET", 
						data: {
							req: "get_mpcs", 
							dealerid: dealer
						}
					});

					request1.done(function(msg){
						if(!msg) return false;

						var mpcids = $.parseJSON(msg);
						mpcid_input.typeahead({source: mpcids});
						mpcid_input.prop('disabled', false);
						mpcid_input.attr('placeholder', 'Playlist ID');
						send.prop('disabled', false);
						initSendButton(dealerids, mpcids, dealer);
					});
				}
				else
				{
					$.each(dealerids, function(key, value){
						var request1 = $.ajax({
							url: mpc_url, 
							type: "GET", 
							data: {
								req: "get_mpcs", 
								dealerid: value
							}
						});
						request1.done(function(msg){
							count++;
							var marray = $.parseJSON(msg);
							mpcArrays[key] = marray;
							mpcids = $.merge(mpcids, marray);

							if(count == dealerids.length)
							{
								var autocomplete = mpcid_input.typeahead();
								autocomplete.data('typeahead').source = mpcids;

								mpcid_input.prop('disabled', false);
								mpcid_input.attr('placeholder', 'Playlist ID');
								send.prop('disabled', false);

								initSendButton(dealerids, mpcArrays, dealer);
							}
						});
						request1.fail(function(jqXHR, textStatus){
							newMessage("error", "Request failed.");
						});
					});
				}
			});
		});
		
		reloadPlaylists();
	});

	open.click(function(){
		var opt = $(saved_playlists.find("option:selected").get(0));
		var dealer_id = opt.attr('dealerid');
		var mpc_id = opt.attr('mpcid');

		if(!mpc_id || !dealer_id)
			return false;

		screen.find("*").remove();
		window.open(play_url+'?dealerid='+dealer_id+'&mpcid='+mpc_id+'&upcycle=-1&repcycle=-1');
	});

	del_playlist.click(function(){
		var opt = $(saved_playlists.find("option:selected").get(0));
		var dealer_id = opt.attr('dealerid');
		var mpc_id = opt.attr('mpcid');
		var scr_name = "";
		var scr_id = "";

		if(!mpc_id || !dealer_id)
			return false;

		mpc_table.find("tr").each(function(){
			if($(this).attr('mpc_id') == mpc_id)
			{
				scr_name = $(this).attr('screen_name');
				scr_id = $(this).attr('screen_id');
			}
		});

		var request = $.ajax({
			url: req_url,
			type: "GET",
			data: {
				req: 'delete_playlist', 
				mpcid: mpc_id, 
				dealerid: dealer_id,
				screen_name: scr_name,
				screen_id: scr_id
			}
		});

		request.done(function(msg){
			if($.trim(msg) == 'done')
			{
				newMessage("success", mpc_id+" has been deleted.");
				reloadPlaylists();
			}
		});
	});

	function initSendButton(dealerids, mpcArrays, dealerName)
	{
		var send = $("#send_button");
		var dealerid_input = $("#input_dealerid");
		var mpcid_input = $("#input_mpcid");
		var modal = $("#modal");
		var modal_body = $("#modal_body");
		
		send.unbind();
		send.click(function(){
			var dlid = $.trim(dealerid_input.val());
			var mpcid = $.trim(mpcid_input.val());
			var ind = -1;

			if(!dlid || $.inArray(dlid, dealerids) == -1)
			{
				newMessage("error", "Invalid Dealer ID");
				return false;
			}

			if(dealerName == "Dealer")
			{
				if(!mpcid || $.inArray(mpcid, mpcArrays) == -1)
				{
					newMessage("error", "Invalid Playlsit ID");
					return false;
				}
			}
			else
			{
				$.each(dealerids, function(k, v){ if(dlid == v) ind = k; });
				if(!mpcid || $.inArray(mpcid, mpcArrays[ind]) == -1)
				{
					newMessage("error", "Invalid Playlsit ID");
					return false;
				}
			}

			modal.modal({ backdrop: 'static', keyboard: true });
			modal.find("#close_modal").hide();
			modal.find("#modal_header").text("Downloading files ...");

			modal_body.append($("<p id=modal_info class=text-info></p>"));
			var modal_info = modal.find("#modal_info");

			var prog_bar_div = $("<div class=progress id=prog_bar_div><div class=bar style=width:0%; id=prog_bar></div></div>");
			modal_body.append(prog_bar_div);
			var prog_bar = prog_bar_div.find("#prog_bar");

			modal_info.show();
			prog_bar.show();
			modal.modal('show');

			modal_info.text(downoaded);
			prog_bar.css("width", "0%");

			var request = $.ajax({
				url: req_url, 
				type: "GET", 
				data: {
					req: 'download_playlist', 
					dealerid: dlid, 
					mpcid: mpcid
				}
			});

			request.done(function(msg){
				if(msg.match("#ERROR$"))
					modal_info.text(msg);
				else
				{
					modal_info.text(downoaded);

					var playlist = msg.split(":::");
					var urls = [];

					$.each(playlist, function(key, value){
						if(value.indexOf("play,") == 0 || value.indexOf("support,") == 0)
						{
							var pieces = value.split(",");
							var url = pieces[3]+","+pieces[4];
							if($.inArray(url, urls) == -1)
								urls.push(url);
						}
					});

					var total = urls.length;
					var count = 0;

					$.each(urls, function(key, value){
						var pieces = value.split(",");
						
						var request = $.ajax({
							url: req_url, 
							type: "GET", 
							data: {
								req: 'download_file', 
								url: pieces[0]+pieces[1], 
								filename: pieces[1], 
								mpcid: mpcid, 
								dealerid: dlid
							}
						});
						request.done(function(msg){
							var response = $.trim(msg);
							if(response == "exist" || response == "done")
							{
								count++;
								modal_info.text(count+"/"+total);
								prog_bar.css("width", (count/total)*100+"%");
							}
							else if(response == "failed")
							{
								count++;
								modal_info.text(count+"/"+total);
								prog_bar.css("width", (count/total)*100+"%");
								modal_info.append(" Failed to download "+pieces[1]);
							}
						});
					});

					var timer = new Object;
					timer = window.setInterval(function(){
						if(count == total)
						{
							newMessage("success", "Done, "+total+" files downloaded.");
							modal_info.text("Done.");
							modal.modal('hide');
							modal_info.text("");
							reloadPlaylists();

							window.clearInterval(timer);
						}
					}, 5000);
				}
			});

			request.fail(function(jqXHR, textStatus) {
				newMessage("error", "Request failed.");
			});
		});
	}

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
});

function encode(string)
{
	return $('<div/>').text(string).html();
}

function decode(string)
{
	return $('<div/>').html(string).text();
}