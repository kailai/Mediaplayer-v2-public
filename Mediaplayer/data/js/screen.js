var tv_onoff_url = "http://gestion.eyeinmedia.com/media/https_getvars.php";
var req_url = "/data/php/media.php";

$(document).ready(function(){

	var screen_tab = $("#scr_set_tab_link");
	var screen_table = $("#screen_table");
	var scr_apply_button = $("#scr_apply_button");
	var scr_off_button = $("#scroff_button");

	screen_tab.on('show', function(e){
		$(".alert").remove();
		initSchedule();
		getScreenStatus();
		getScreenInfo();
		getScheduleInfo();
	});

	scr_off_button.click(function(){

		var tr = screen_table.find("#screen_row");
		tr.find('td').remove();
		scr_apply_button.hide();

		scr_off_button.attr('disabled', 'disabled');
		scr_off_button.text("Sending request ...");

		var status = $.trim($(this).attr('status'));

		if(status == 'on')
		{
			var request = $.ajax({
				url: req_url,
				type: "GET",
				data: { req: "screen_off" }
			});
			request.done(function(msg){
				var request1 = $.ajax({
					url: req_url,
					type: "GET",
					data: { req: "screen_status" }
				});
				request1.done(function(msg){
					if($.trim(msg) == "off")
					{
						newMessage('success', 'All screens have been turned off.');

						scr_off_button.text("Turn On Screens");
						scr_off_button.attr('status', 'off');
						scr_off_button.removeAttr('disabled');
					}
					else
					{
						newMessage('error', 'Failed to turn off screens.');
						scr_off_button.text("Reset Screens");
						scr_off_button.attr('status', 'error');
						scr_off_button.removeAttr('disabled');
					}
					
					getScreenInfo();
				});
			});
		}
		else if(status == 'off')
		{
			var request = $.ajax({
				url: req_url,
				type: "GET",
				data: { req: "screen_on" }
			});
			request.done(function(msg){
				if($.trim(msg) == 'done')
				{
					var request1 = $.ajax({
						url: req_url,
						type: "GET",
						data: { req: "screen_status" }
					});
					request1.done(function(msg){
						if($.trim(msg) == "on")
						{
							newMessage('success', 'All screens have been turned on.');
							scr_off_button.text("Turn Off Screens");
							scr_off_button.attr('status', 'on');
							scr_off_button.removeAttr('disabled');
							
							var request = $.ajax({
								url: req_url,
								type: "GET",
								timeout: 3000, 
								data: { req: 'start_watcher' }
							});
							request.done(function(msg){
							});
							request.fail(function(){
							});
						}
						else if($.trim(msg) == "off")
						{
							newMessage('error', 'Failed to turn on screens.');
							scr_off_button.text("Turn On Screens");
							scr_off_button.attr('status', 'off');
							scr_off_button.removeAttr('disabled');
						}
						else
						{
							newMessage('error', 'Failed to turn on screens.');
							scr_off_button.text("Reset Screens");
							scr_off_button.attr('status', 'error');
							scr_off_button.removeAttr('disabled');
						}
						
						getScreenInfo();
					});
				}
			});
		}
		else if(status == 'error')
		{
			var request = $.ajax({
				url: req_url,
				type: "GET",
				data: { req: "reset_screens" }
			});
			request.done(function(msg){
				var request1 = $.ajax({
					url: req_url,
					type: "GET",
					data: { req: "screen_status" }
				});
				request1.done(function(msg){
					if($.trim(msg) == "on")
					{
						newMessage('success', 'All screens have been turned on.');
						scr_off_button.text("Turn Off Screens");
						scr_off_button.attr('status', 'on');
						scr_off_button.removeAttr('disabled');
					}
					else
					{
						newMessage('error', 'Failed to reset screens.');
						scr_off_button.text("Reset Screens");
						scr_off_button.attr('status', 'error');
						scr_off_button.removeAttr('disabled');
					}
					
					getScreenInfo();
				});
			});
		}
	});

	function initSchedule()
	{
		var add_schedule = $("#add_schedule_button");
		var del_schedule = $("#del_schedule_button");

		add_schedule.unbind();
		add_schedule.click(function(){

			var schedule_form = $("#schedule_form_div").clone();
			var modal = $("#modal");
			var modal_body = $("#modal_body");

			modal.modal({ backdrop: 'static', keyboard: true });
			modal.find("#modal_header").text("Edit Screen On/Off Schedule: ");
			modal_body.append(schedule_form);
			modal_body.append($("<label id=modal_label class=text-error></label>"));
			modal_body.css('height', '310px');
			schedule_form.show();
			modal.modal('show');

			var off_button = modal.find('#off_button');
			var on_button = modal.find('#on_button');
			var off_timepicker = modal.find('#off_time');
			var on_timepicker = modal.find('#on_time');
			var submit = modal.find('#send_schedule_button');
			var info = modal.find("#modal_label");

			off_timepicker.timepicker({
				showMeridian: false,
				showInputs: false,
				minuteStep: 5,
				defaultTime: '18:00'
			});

			on_timepicker.timepicker({
				showMeridian: false,
				showInputs: false,
				minuteStep: 5,
				defaultTime: '8:00'
			});

			off_timepicker.attr('readonly', true);
			on_timepicker.attr('readonly', true);


			$('.off_every_day').click(function(){
				var day = $(this).text();

				off_button.text(day);
				off_button.val(day);
				on_button.text(day);
				on_button.val(day);
			});
			$('.on_every_day').click(function(){
				var day = $(this).text();

				on_button.text(day);
				on_button.val(day);
			});
			$('.off_every_week').click(function(){
				var day = $(this).text();
				var next = $(this).parent().next().find('a').text();
				if(day == "Every Friday" || day == "Every Sunday")
					next = "Every Monday";

				off_button.text(day);
				off_button.val(day);

				on_button.text(next);
				on_button.val(next);
			});
			$('.on_every_week').click(function(){
				var day = $(this).text();

				on_button.text(day);
				on_button.val(day);
			});

			submit.click(function(){

				var off_day = off_button.val();
				var off_time = off_timepicker.val();
				var on_day = on_button.val();
				var on_time = on_timepicker.val();

				if(!isValidDay(off_day))
				{
					info.text("Please select a date.");
					return false;
				}
				if(!isValidTime(off_time))
				{
					info.text("Invalid time input.");
					return false;
				}

				var request = $.ajax({
					url: req_url, 
					type: "GET",
					data: {
						req: 'edit_schedule',
						off_day: off_day,
						off_time: off_time,
						on_day: on_day,
						on_time: on_time
					}
				});

				request.done(function(msg){
					if($.trim(msg) == 'failed')
					{
						info.text("Failed to create schedule.");
						return false;
					}
					else
					{
						modal.modal('hide');
						getScheduleInfo();
					}
				});
			});
		});

		del_schedule.click(function(){
			var request = $.ajax({
				url: req_url, 
				type: "GET",
				data: { req: 'remove_schedule' }
			});

			request.done(function(msg){
				if($.trim(msg) == 'failed')
					newMessage('error', "Failed to delete schedule.");
				else
					getScheduleInfo();
			});
		});
	}

	function getScreenStatus()
	{
		var request = $.ajax({
			url: req_url, 
			type: "GET",
			data: {req: 'screen_status'}
		});
		request.done(function(msg){
			if($.trim(msg) == "off")
			{
				scr_off_button.text("Turn On Screens");
				scr_off_button.attr('status', 'off');
			}
			else if($.trim(msg) == "on")
			{
				scr_off_button.text("Turn Off Screens");
				scr_off_button.attr('status', 'on');
			}
			else
			{
				scr_off_button.text("Reset Screens");
				scr_off_button.attr('status', 'error');
			}
		});
	}

	function getScheduleInfo()
	{
		var off_schedule = $("#off_schedule");
		var on_schedule = $("#on_schedule");
		var add_schedule = $("#add_schedule_button");
		var del_schedule = $("#del_schedule_button");

		var request = $.ajax({
			url: req_url, 
			type: "GET",
			data: { req: 'get_shedule' }
		});

		request.done(function(msg){
			if($.trim(msg) == 'none')
			{
				off_schedule.text('none');
				on_schedule.text('none');
				add_schedule.text('Add');
				del_schedule.attr('disabled', 'disabled');
			}
			else
			{
				var crontab = $.parseJSON(msg);

				var off_sch = crontab[0];
				var on_sch = crontab[1];

				var split = off_sch.split(' ');
				var off_min = split[0];
				var off_hour = split[1];
				var off_weekday = split[4];

				split = on_sch.split(' ');
				var on_min = split[0];
				var on_hour = split[1];
				var on_weekday = split[4];

				var off_time = off_hour+":"+off_min;
				var on_time = on_hour+":"+on_min;

				if(off_weekday == '*') off_weekday = "Every day";
				if(off_weekday == '0') off_weekday = "Every Sunday";
				if(off_weekday == '1') off_weekday = "Every Monday";
				if(off_weekday == '2') off_weekday = "Every Tuesday";
				if(off_weekday == '3') off_weekday = "Every Wednesday";
				if(off_weekday == '4') off_weekday = "Every Thursday";
				if(off_weekday == '5') off_weekday = "Every Friday";
				if(off_weekday == '6') off_weekday = "Every Saturday";

				if(on_weekday == '*') on_weekday = "Every day";
				if(on_weekday == '0') on_weekday = "Every Sunday";
				if(on_weekday == '1') on_weekday = "Every Monday";
				if(on_weekday == '2') on_weekday = "Every Tuesday";
				if(on_weekday == '3') on_weekday = "Every Wednesday";
				if(on_weekday == '4') on_weekday = "Every Thursday";
				if(on_weekday == '5') on_weekday = "Every Friday";
				if(on_weekday == '6') on_weekday = "Every Saturday";

				off_schedule.text(off_weekday+" at "+off_time);
				on_schedule.text(on_weekday+" at "+on_time);

				add_schedule.text('Edit');
				del_schedule.removeAttr('disabled');
			}
		});
	}

	function getScreenInfo()
	{
		var tr = screen_table.find("#screen_row");
		tr.find('td').remove();
		scr_apply_button.hide();

		var request = $.ajax({
			url: req_url, 
			type: "GET",
			data: {req: 'screen_info'}
		});

		request.done(function(msg){
			if($.trim(msg) == "")
			{
				newMessage('error', 'Failed to load screen information.');
				return false;
			}

			var screens = $.parseJSON(msg);

			$.each(screens, function(key, value){
				var pieces = value.replace("+", " ").split(" ");
				var name = pieces[0];
				var res = "none";
				var pos = "none";
				var rot = "normal";
				
				if(!pieces[2].startsWith("(")) // screen is off
				{
					res = pieces[2];
					pos = pieces[3];
					
					if(!pieces[4].startsWith("("))
						rot = pieces[4];
				}

				var screen = $("<div></div>");
				screen.attr('class', 'scr_div');
				screen.attr('screen_name', name);
				screen.attr('screen_id', key);
				var status = new Object;

				var id = $("<p class='text-center screen_id'>"+name+" ("+key+")</p>");
				if(res == "none")
				{
					status = $("<p class='text-center screen_status_off'><br>OFF</p>");
					screen.attr('title', 'Off');
					scr_apply_button.attr('disabled', 'disabled');
				}
				else
				{
					status = $("<p class='text-center screen_status_on'><br>ON</p>");
					screen.attr('title', res+' '+pos);
					scr_apply_button.removeAttr('disabled');
				}

				screen.append(id);
				screen.append(status);

				if(rot == "right")
				{
					screen.css('transform', 'rotate(90deg);');
					screen.css('-ms-transform', 'rotate(90deg)');
					screen.css('-webkit-transform', 'rotate(90deg)');
				}
				if(rot == "left")
				{
					screen.css('transform', 'rotate(-90deg);');
					screen.css('-ms-transform', 'rotate(-90deg)');
					screen.css('-webkit-transform', 'rotate(-90deg)');
				}
				if(rot == "normal")
				{
					screen.css('transform', 'rotate(0deg);');
					screen.css('-ms-transform', 'rotate(0deg)');
					screen.css('-webkit-transform', 'rotate(0deg)');
				}

				var rotate_buttons = $("#rotate_buttons").clone();
				rotate_buttons.attr('id', 'rot_btn_'+name);

				var res_select_div = $("#res_select_div").clone();
				var res_select = res_select_div.find('select');

				res_select.attr('id', 'res_sel_'+name);
				res_select.val(res);

				var td = $("<td></td>");
				td.append(screen);

				if(res != "none")
				{
					td.append(rotate_buttons);
					td.append(res_select_div);
					scr_apply_button.show();
				}

				tr.append(td);

				rotate_buttons.find("#rotate_left").click(function(){
					screen.css('transform', 'rotate(-90deg);');
					screen.css('-ms-transform', 'rotate(-90deg)');
					screen.css('-webkit-transform', 'rotate(-90deg)');
				});

				rotate_buttons.find("#rotate_right").click(function(){
					screen.css('transform', 'rotate(90deg);');
					screen.css('-ms-transform', 'rotate(90deg)');
					screen.css('-webkit-transform', 'rotate(90deg)');
				});

				rotate_buttons.find("#rotate_normal").click(function(){
					screen.css('transform', 'rotate(0deg);');
					screen.css('-ms-transform', 'rotate(0deg)');
					screen.css('-webkit-transform', 'rotate(0deg)');
				});
			});

			scr_apply_button.unbind();
			scr_apply_button.click(function(){
				scr_apply_button.attr('disabled', 'disabled');
				scr_apply_button.text("Sending request ...");

				var scr_divs = $(".scr_div");
				var degrees = {};
				degrees['req'] = "config_screens";

				$.each(scr_divs, function(k, v){
					var div = $(v);
					var rotation = div.attr('style');
					var name = div.attr('screen_name');
					var res = $("#res_sel_"+name).val();

					if(rotation)
					{
						var pieces = rotation.split("(");
						var sub = pieces[1]; 
						var pieces = sub.split("deg");
						var deg = pieces[0];
						degrees[name] = deg+'_'+res;
					}
				});

				var request = $.ajax({
					url: req_url, 
					type: "POST", 
					data: degrees
				});
				
				request.done(function(msg){
					if($.trim(msg) == 'done')
						newMessage('success', 'Screens have been configured successfully.');
					else if($.trim(msg) == 'failed')
						newMessage('error', 'Failed to configure screens.');

					scr_apply_button.removeAttr('disabled');
					scr_apply_button.text("Apply");
				});
			});
		});
	}
});

function isValidDay(day)
{
	var days = new Array(
			'Every day', 
			'Every Monday', 
			'Every Tuesday', 
			'Every Wednesday', 
			'Every Thursday', 
			'Every Friday', 
			'Every Saturday', 
	'Every Sunday');

	if($.inArray(day, days) == -1)
		return false;

	return true;
}

function isValidTime(time)
{
	var regexp = /([01][0-9]|[02][0-3]):[0-5][0-9]/;
	var correct = regexp.test(time);

	return correct;
}