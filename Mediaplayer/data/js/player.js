var req_url = "/data/php/media.php";

$(document).ready(function(){

	var player_tab = $("#player_set_tab_link");
	var start_button = $("#start_button");
	var stop_button = $("#stop_button");
	var monitor_button = $("#monitor_button");
	var vnc_button = $("#vnc_button");
	var player_table = $("#player_table");
	var remove_button = $("#remove_button");
	var save_form = $("#playlist_save_form");

	player_tab.on('show', function(e){
		$(".alert").remove();
		initStartButton();
		initStopButton();
		initMonitorButton();
		initVNCButton();
		initRemoveButton();
		init();
	});

	player_tab.tab('show');

	function init()
	{
		$(".clean").find("*").remove();

		var request = $.ajax({
			url: req_url,
			type: "GET",
			data: {req: 'screen_info'}
		});

		request.done(function(msg){
			if($.trim(msg).length == 0)
				return false;

			var screens = $.parseJSON(msg);
			player_table.find(".colspan_all").attr('colspan', screens.length);

			$.each(screens, function(key, value){
				var pieces = value.replace("+", " ").split(" ");
				var name = pieces[0];
				var res = pieces[2];
				var pos = pieces[3];

				var request = $.ajax({
					url: req_url, 
					type: "GET", 
					data: { req: "current_mpc", screen_name: name, screen_id: key }
				});

				request.done(function(msg){
					var ids = $.parseJSON(msg);
					var dealer_id = ids.dealerid;
					var mpc_id = ids.mpcid;

					var screen_drag = $("#screen_drag_tr");
					var draggable_td = genScreenTD(name, key, dealer_id, mpc_id, res, pos);
					screen_drag.append(draggable_td);
				});
			});

			initPlayers(screens.length);
		});
	}

	function initRemoveButton()
	{
		remove_button.unbind();
		remove_button.click(function(){
			var request = $.ajax({
				url: req_url, 
				type: "GET", 
				data: { req: "remove_all" }
			});
			request.done(function(msg){
				if($.trim(msg) == 'done')
					newMessage("success", "All playlists have been removed.");
				else
					newMessage("error", "Failed to remove script file.");
				
				init();
			});
		});
	}

	function initStartButton()
	{
		start_button.unbind();
		start_button.click(function(){
			
			var start = false;
			$.each($('.draggable'), function(k,v){
				if($(this).attr('playlist'))
				{
					start = true;
					return false;
				}
			});
			
			if(!start)
			{
				newMessage('warning', 'No playlist assigned to screens.');
				return false;
			}
			
			start_button.attr('disabled','disabled');
			stop_button.attr('disabled','disabled');

			start_button.text('Sending request ...');

			var request = $.ajax({
				url: req_url,
				type: "GET",
				timeout: 3000, 
				data: { req: 'start_watcher' }
			});
			request.done(function(msg){
				if($.trim(msg) == "running")
					startPlayer();
				else if($.trim(msg) == "noplaylist")
				{
					newMessage('error', 'No playlist assigned to screens.');
					start_button.text('Start Players');
					start_button.removeAttr('disabled');
					stop_button.removeAttr('disabled');
				}
				else
					startPlayer();
			});
			request.fail(function(){
				startPlayer();
			});
		});
	}
	
	function initStopButton()
	{
		stop_button.unbind();
		stop_button.click(function(){
			var request = $.ajax({
				url: req_url,
				type: "GET",
				data: { req: 'stop' }
			});

			request.done(function(msg){
				if($.trim(msg) == "done")
					newMessage('success', 'Players have been stopped.');
				else if($.trim(msg) == "failed")
					newMessage('error', 'Failed to stop players.');
			});
		});
	}

	function initMonitorButton()
	{
		monitor_button.unbind();
		monitor_button.click(function(){
			monitor_button.attr('disabled','disabled');

			var request = $.ajax({
				url: req_url,
				type: "GET",
				data: { req: 'monitor_player' }
			});

			request.done(function(msg){
				if($.trim(msg) == "failed")
					newMessage('error', 'Failed');
				else
				{
					var img = $("<img></img>");
					img.attr('src', msg);
					img.css('width', '100%');
					img.css('height', '100%');

					modal(img);
				}

				monitor_button.removeAttr('disabled');
			});
		});
	}

	function initVNCButton()
	{
		vnc_button.unbind();
		vnc_button.click(function(){

			var app_url = "https://chrome.google.com/webstore/detail/vnc-viewer/iabmpiboiopbgfabjmgeedhcmjenhbla?hl=EN";
			var vnc_url = "chrome-extension://iabmpiboiopbgfabjmgeedhcmjenhbla/index.html";
			var check_url = "chrome-extension://iabmpiboiopbgfabjmgeedhcmjenhbla/help/index.html";

			var request = $.ajax({
				url: check_url,
				type: "GET",
			});
			request.done(function(){ window.open(vnc_url); });
			request.fail(function(){ window.open(app_url); });
		});
	}

	function initPlayers(connected_scr_nb)
	{
		var playlist_select = $("#playlist_select_td");
		var playlist_save = $("#playlist_save_td");
		var screen_drop = $("#screen_drop_tr");
		var playlists = $("#saved_playlists").clone();

		playlists.attr('id', 'select_playlist');
		playlist_select.append(playlists);

		playlists.change(function(){

			$.each($(".draggable"), function(){
				var home = $('#'+$(this).attr('id')+'_home');
				$(this).appendTo(home);
				$(this).attr('playlist', home.attr('playlist'));
				$(this).attr('dealer_id', home.attr('dealerid'));
				$(this).find('.curr_playlist').text(home.attr('playlist'));
			});
			playlist_save.find('form').hide();
			screen_drop.find('*').remove();

			if(this.value == 'Select Playlist')
				return false;

			var selected = $($("#select_playlist option:selected")[0]);
			var dealer_id = selected.attr('dealerid');
			var mpc_id = selected.attr('mpcid');
			var scr_nb = selected.attr('scr_nb');

			if(scr_nb > 0 && scr_nb <= connected_scr_nb)
			{
				var droppables = new Array();

				for(var i=0; i<scr_nb; i++)
				{
					var droppable_td = $("<td></td>");
					var droppable = $("<div class='droppable new_playlist_drop' playlist="+mpc_id+" dealerid="+dealer_id+"></div>");
					droppable_td.append(droppable);
					screen_drop.append(droppable_td);
					droppables[i] = droppable;
				}

				// when drop on new playlist
				$.each(droppables, function(k,droppable){
					droppable.droppable({ accept: ".scr_div", hoverClass: "drop-hover",  
						drop: function(event, ui) {
							var screen = ui.draggable;
							$(this).append(screen);
							screen.css('top', 0);
							screen.css('left', 0);
							screen.addClass('change_playlist');

							// show options when new playlist droppables are completed
							if($('.change_playlist').length == $('.new_playlist_drop').length)
								save_form.show();

							// each droppable can accept only one draggable
							$.each($(".new_playlist_drop"), function(k,v){
								if($(v).find('.draggable').length == 1) $(v).droppable("disable");
								else $(v).droppable("enable");
							});

							// remove playlist for screens (not gragged) if necessary 
							$.each($(".draggable"), function(k,v){
								if(!$(v).hasClass('change_playlist'))
								{
									if($(v).attr('playlist') != '' && $(v).attr('playlist') === screen.attr('playlist'))
									{
										$(v).addClass('remove_playlist');
										$(v).attr('playlist', '');
										$(v).attr('dealer_id', '');
										$(v).find('.curr_playlist').text('');
									}
								}
							});

							screen.attr('playlist', $(this).attr('playlist'));
							screen.attr('dealer_id', $(this).attr('dealerid'));
							screen.find('.curr_playlist').text(screen.attr('playlist'));
						}});
				});

				$.each($(".draggable"), function(k,v){
					if($(this).attr('playlist')) 
						return false;

					$(this).draggable({ containment:"#player_table", opacity:0.6, revert: "invalid", snap: ".droppable", snapMode: "inner" });
					$(this).draggable("enable");
				});

				newMessage('warning', 'Remove current playlists before assign a new one.');

				initSaveButton();
			}
			else if(scr_nb > connected_scr_nb)
			{
				newMessage("warning", "Number of screens needed by the playlist exceeds total number of connected screens.");
				return false;
			}

		});

		save_form.find('input').tooltip({placement: 'top'});
		save_form.hide();
	}

	function genScreenTD(name, key, dealer_id, mpc_id, res, pos)
	{
		var playlist_save = $("#playlist_save_td");
		var td = $("<td></td>");
		var droppable = $("<div class='droppable curr_playlist_drop'></div>");
		droppable.attr('playlist', mpc_id);
		droppable.attr('dealerid', dealer_id);
		droppable.attr('id', name+"_home");

		var draggable = $("<div class='scr_div draggable'></div>");
		draggable.attr('id', name);
		draggable.attr('dealer_id', dealer_id);
		draggable.attr('screen_id', key);
		draggable.attr('screen_name', name);
		draggable.attr('playlist', mpc_id);
		draggable.attr('screen_pos', pos);
		draggable.attr('screen_res', res);

		var id = $("<p class='text-center screen_id'>"+name+" ("+key+")</p>");
		var status = $("<p class='text-center screen_info'><br>"+res+"<br/>"+pos+"</p>");
		var curr_playlist = $("<p class='text-center curr_playlist'>"+mpc_id+"</p>");

		td.append(droppable);
		droppable.append(draggable);
		draggable.append(id);
		draggable.append(status);
		draggable.append(curr_playlist);

		// when drop on "home" droppable
		droppable.droppable({ accept: "#"+name, hoverClass: "drop-hover",
			drop: function(event, ui) {
				var screen = ui.draggable;
				$(this).append(screen);
				screen.css('top', 0);
				screen.css('left', 0);
				screen.attr('playlist', $(this).attr('playlist'));
				screen.attr('dealer_id', $(this).attr('dealerid'));
				screen.find('.curr_playlist').text(screen.attr('playlist'));
				screen.removeClass('change_playlist');
				screen.removeClass('remove_playlist');

				$.each($(".new_playlist_drop"), function(k,v){
					if($(v).find('.draggable').length == 0)
						$(v).droppable("enable"); 
				});

				if($('.change_playlist').length < $('.new_playlist_drop').length)
					playlist_save.find('form').hide();
			}});

		return td;
	}

	function startPlayer()
	{
		var request = $.ajax({
			url: req_url,
			type: "GET",
			timeout: 3000, 
			data: { req: 'start_player' }
		});
		request.done(function(msg){
			if($.trim(msg) == "running")
				newMessage('warning', 'Players are already running on Media Computer.');
			else if($.trim(msg) == "failed")
				newMessage('error', 'No screen watcher script found, please configure screens before launching players.');

			start_button.text('Start Players');
			start_button.removeAttr('disabled');
			stop_button.removeAttr('disabled');
		});
		request.fail(function(){
			newMessage('success', 'Players have been started.');
			start_button.text('Start Players');
			start_button.removeAttr('disabled');
			stop_button.removeAttr('disabled');
		});
	}

	function initSaveButton()
	{
		var draggables = $('.draggable');
		var update_cyc_input = save_form.find('input[name="input_update_cycle"]');
		var report_cyc_input = save_form.find('input[name="input_report_cycle"]');
		var save_button = save_form.find("button");
		
		save_button.unbind();
		save_button.click(function(){
			var up_cycle = update_cyc_input.val();
			var rep_cycle = report_cyc_input.val();

			if(!$.isNumeric(up_cycle) || up_cycle < 1 || !$.isNumeric(rep_cycle) || rep_cycle < 1)
			{
				newMessage('error', 'Invalid update or report cycle.');
				return false;
			}
			
			var req_data = new Object();
			req_data.req = 'save';

			$.each(draggables, function(k,v){
				var screen = $(v);
				var data = new Object();

				data.mpcid = screen.attr('playlist');
				data.dealerid = screen.attr('dealer_id');
				data.screen_id = screen.attr('screen_id');
				data.screen_name = screen.attr('screen_name');
				data.screen_pos = screen.attr('screen_pos');
				data.screen_res = screen.attr('screen_res');
				data.update_cycle = up_cycle;
				data.report_cycle = rep_cycle;

				if(screen.hasClass('change_playlist'))
					data.change = 1;
				if(screen.hasClass('remove_playlsit'))
					data.remove = 1;

				req_data[k] = data;
			});

			var request = $.ajax({
				url: req_url, 
				type: "POST", 
				data: req_data
			});

			request.done(function(msg){
				newMessage("success", msg);
				init();
			});
		});
	}

	function modal(img)
	{
		var modal = newModal("Screenshot", true, '128em', '80em', '90em', '-45em');
		modal.find('.modal-body').append(img);
		modal.modal('show');
	}

	function newModal(header, remove, maxWidth, maxHeight, width, marginLeft)
	{
		var modal = $("<div class='modal hide fade'></div>");
		var modal_header = $("<div class=modal-header>" +
				"<button type=button class=close data-dismiss=modal aria-hidden=true>&times;</button>" +
				"<h3>"+header+"</h3></div>");
		var modal_body = $("<div class='modal-body'></div>");
		var modal_footer = $("<div class='modal-footer'></div>");
		var close_button = $("<button id=close_button class='btn btn-primary'>Close</button>");

		modal_footer.append(close_button);

		modal.css('max-width', maxWidth);
		modal.css('max-height', maxHeight);
		modal.css('width', width);
		modal.css('height', 'auto');
		modal.css('margin-left', marginLeft);

		modal.append(modal_header);
		modal.append(modal_body);
		modal.append(modal_footer);

		if(remove)
			modal.on('hidden', function(){ modal.remove(); });

		close_button.click(function(){ modal.modal('hide'); });

		return modal;
	}
});