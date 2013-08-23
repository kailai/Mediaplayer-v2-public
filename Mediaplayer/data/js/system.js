var req_url = "/data/php/media.php";

var dealerids = new Array();
var mpcids = new Array();

$(document).ready(function(){

	var system_tab = $("#sys_set_tab_link");
	var reboot_button = $("#reboot_button");
	var edit_name_button = $("#edit_mpc_name");
	var edit_ip_button = $("#edit_ip");
	var modal = $("#modal");
	var modal_body = $("#modal_body");
	var system_table = $("#system_table");
	var monitor_button = $("#sys_monitor");
	var screen = $("#preview_screen");
	var clean_button = $(".clean_button");

	system_tab.on('show', function(e){
		$(".alert").remove();
		init();
	});

	reboot_button.click(function(){

		modal.modal({ backdrop: 'static', keyboard: true });
		modal.find("#modal_header").text("Reboot Media Computer");

		var modal_form = $("<form id=modal_form class=form-horizontal>");
		modal_body.append(modal_form);

		modal_form.append($("<label id=modal_label class=text-error></label>"));
		modal_form.append($("<div class=control-group><input type=password id=password_input placeholder=Password></div>"));
		modal_form.append($("<div class=control-group><button type=button id=modal_button class='btn btn-danger'>Reboot</button></div>"));

		modal.modal('show');

		modal_form.find($("#modal_button")).click(function(){

			var modal_label = modal_form.find($("#modal_label"));
			var password_input = modal_form.find($("#password_input"));
			var pass = password_input.val();

			var request = $.ajax({
				url: req_url,
				type: "GET",
				data: {
					req: 'reboot',
					password: pass
				}
			});

			request.done(function(msg){
				password_input.val('');

				if($.trim(msg) == "wrong")
					modal_label.text('Invalid password');
				else if($.trim(msg) == "done")
					modal_label.text('Rebooted');
			});
		});
	});

	edit_name_button.click(function(){
		modal.modal({ backdrop: 'static', keyboard: true });
		modal.find("#modal_header").text("Edit Media Computer Name: ");
		var modal_form = $("<form id=modal_form class=form-horizontal>");
		modal_body.append(modal_form);

		modal_form.append($("<label id=modal_label class=text-error></label>"));
		modal_form.append($("<div class=control-group><input type=text id=name_input placeholder='Media Computer Name'></div>"));
		modal_form.append($("<div class=control-group><input type=password id=password_input placeholder=Password></div>"));
		modal_form.append($("<div class=control-group><button type=button id=modal_button class='btn btn-danger'>Submit</button></div>"));
		modal.modal('show');
		
		var name_input = modal.find("#name_input");
		var pass_input = modal.find("#password_input");
		
		var request = $.ajax({
			url: req_url,
			type: "GET",
			data: { req: 'get_dealer_name' }
		});
		
		request.done(function(msg){
			var dealer = $.trim(msg);
			
			var request1 = $.ajax({
				url: mpc_url, 
				type: "GET",
				data: {
					req: "get_computers", 
					dealerid: dealer
				}
			});
			
			request1.done(function(msg){
				computers = $.parseJSON(msg);
				var autocomplete = name_input.typeahead();
				autocomplete.data('typeahead').source = computers;
			});

			modal.find("#modal_button").click(function(){
				var computer = name_input.val();
				var name = name_input.val();
				var pass = pass_input.val();

				if($.inArray(computer, computers) == -1)
				{
					name_input.val('');
					modal.find("#modal_label").text('Invalid Computer Name');
					return false;
				}
				
				var request2 = $.ajax({
					url: req_url,
					type: "GET",
					data: {
						req: 'change_name',
						name: name, 
						password: pass
					}
				});
				
				request2.done(function(msg){
					if($.trim(msg) == "wrong")
					{
						pass_input.val('');
						modal.find("#modal_label").text('Invalid Password');
					}
					else if($.trim(msg) == "done")
					{
						modal.modal('hide');
						init();
					}
				});
			});
		});
	});

	edit_ip_button.click(function(){

		modal.modal({ backdrop: 'static', keyboard: true });
		modal.find("#modal_header").text("Edit IP Address: ");

		var modal_form = $("<form id=modal_form class=form-horizontal>");
		modal_body.append(modal_form);

		modal_form.append($("<label id=modal_label class=text-error></label>"));
		modal_form.append($("<div class=control-group><input type=text id=ip_input placeholder='IP Address'></div>"));
		modal_form.append($("<div class=control-group><input type=text id=mask_input placeholder='Network Mask'></div>"));
		modal_form.append($("<div class=control-group><input type=text id=gateway_input placeholder='Gateway'></div>"));
		modal_form.append($("<div class=control-group><input type=text id=dns_input placeholder='DNS'></div>"));
		modal_form.append($("<div class=control-group><input type=password id=password_input placeholder=Password></div>"));
		modal_form.append($("<div class=control-group><button type=button id=modal_button class='btn btn-danger'>Submit</button></div>"));

		var pass_input = modal.find("#password_input");
		var ip_input = modal.find("#ip_input");
		var mask_input = modal.find("#mask_input");
		var gateway_input = modal.find("#gateway_input");
		var dns_input = modal.find("#dns_input");

		modal.modal('show');

		modal.find("#modal_button").click(function(){
			var pass = pass_input.val();
			var ip = ip_input.val();
			var mask = mask_input.val();
			var gateway = gateway_input.val();
			var dns = dns_input.val();

			if(!isValidIP(ip))
			{
				modal.find("#modal_label").text('Invalid IP Address');
				return false;
			}
			if(!isValidMask(mask))
			{
				modal.find("#modal_label").text('Invalid Mask Address');
				return false;
			}
			if(!isValidIP(gateway))
			{
				modal.find("#modal_label").text('Invalid Gateway Address');
				return false;
			}
			if(!isValidIP(dns))
			{
				modal.find("#modal_label").text('Invalid DNS Address');
				return false;
			}
			
			var request = $.ajax({
				url: req_url,
				type: "GET",
				timeout: 2000,
				data: {
					req: 'change_ip',
					ip: ip,
					mask: mask,
					gateway: gateway,
					dns: dns,
					password: pass
				}
			});
			request.done(function(msg){
				if($.trim(msg) == "wrong")
					modal.find("#modal_label").text('Invalid Password');
				else ($.trim(msg) == "failed");
					modal.find("#modal_label").text('Failed to set IP Address.');
			});
			request.fail(function(msg){
				modal_form.remove();
				var url = "http://"+ip;
				var link = $("<a id=modal_info class=text-info>Go to new address</a>");
				link.attr('href', url);
				modal_body.append(link);
			});
		});
	});

	monitor_button.click(function(){
		var request = $.ajax({
			url: req_url,
			type: "GET",
			data: { req: 'get_sys_info' }
		});

		request.done(function(msg){
			var sys_info = $.parseJSON(msg);
			var div = $("<div id=sys_info_div></div>");

			$.each(sys_info, function(k, v){
				var line = $("<p style='color:white'>&nbsp;&nbsp;"+v+"</p>");
				div.append(line);
			});
			screen.find('*').remove();
			screen.append(div);
		});
	});

	clean_button.click(function(){
		screen.find('*').remove();
	});

	function init()
	{
		var mpc_info = $("#mpc_name");

		var request = $.ajax({
			url: req_url,
			type: "GET",
			data: { req: 'get_system_info' }
		});

		request.done(function(msg){
			var info = $.parseJSON(msg);
			system_table.find("#ip_address").text(info.ip);
			system_table.find("#host_name").text(info.name);
			mpc_info.find("#name").text(info.name);
			mpc_info.find("#ip").text(info.ip);
		});
	}
});

function isValidIP(ip)
{
	var split = ip.split('.');
	if (split.length != 4) 
		return false;

	for (var i=0; i<split.length; i++) {
		var s = split[i];
		if (s.length==0 || isNaN(s) || s<0 || s>255)
			return false;
	}
	return true;
}

function isValidMask(mask)
{
	var mask_format = /^[1-2]{1}[2,4,5,9]{1}[0,2,4,5,8]{1}\.[0-2]{1}[0,2,4,5,9]{1}[0,2,4,5,8]{1}\.[0-2]{1}[0,2,4,5,9]{1}[0,2,4,5,8]{1}\.[0-9]{1,3}$/;    
	return mask.match(mask_format);
}

if (typeof String.prototype.startsWith != 'function') {
	String.prototype.startsWith = function (str){
		return this.indexOf(str) == 0;
	};
}