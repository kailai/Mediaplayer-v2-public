var mpc_url = "http://eyein.serveftp.net/mpc.php";
var req_url = "/data/php/media.php";

var dealerids = new Array();
var mpcids = new Array();

$(document).ready(function(){

	var about_tab = $("#about_info_tab_link");
	var about_table = $("#about_table");
	var edit_dealer_button = $("#edit_dealer_name");
	var update_button = $("#update_software");
	var modal = $("#modal");
	var modal_body = $("#modal_body");
	
	about_tab.on('show', function(e){
		$(".alert").remove();
		init();
	});

	
	edit_dealer_button.click(function(){
		modal.modal({ backdrop: 'static', keyboard: true });
		modal.find("#modal_header").text("Edit Dealer Name: ");
		var modal_form = $("<form id=modal_form class=form-horizontal>");
		modal_body.append(modal_form);

		modal_form.append($("<label id=modal_label class=text-error></label>"));
		modal_form.append($("<div class=control-group><input type=text id=dealer_input></div>"));
		modal_form.append($("<div class=control-group><input type=text id=name_input placeholder='Computer Name'></div>"));
		modal_form.append($("<div class=control-group><input type=password id=password_input placeholder=Password></div>"));
		modal_form.append($("<div class=control-group><button type=button id=modal_button class='btn btn-danger'>Submit</button></div>"));

		modal.modal('show');
		
		var dealer_input = modal.find("#dealer_input");
		var name_input = modal.find("#name_input");
		var pass_input = modal.find("#password_input");
		var dealerids = [''];
		
		dealer_input.attr('placeholder', 'Loading ...');
		dealer_input.prop('disabled', true);

		var request = $.ajax({
			url: mpc_url, 
			type: "GET",
			data: {
				req: "get_dealers", 
				dealerid: 'Dealer'
			}
		});

		request.done(function(msg){
			dealerids = $.parseJSON(msg);
			
			dealer_input.typeahead({source: dealerids});
			dealer_input.prop('disabled', false);
			dealer_input.attr('placeholder', 'Dealer Name');
			
			name_input.focusin(function(){
				if(dealer_input.val())
				{
					var request1 = $.ajax({
						url: mpc_url, 
						type: "GET",
						data: {
							req: "get_computers", 
							dealerid: dealer_input.val()
						}
					});
					
					request1.done(function(msg){
						computers = $.parseJSON(msg);
						var autocomplete = name_input.typeahead();
						autocomplete.data('typeahead').source = computers;
					});
				}
			});
		});
		
		modal.find("#modal_button").click(function(){
			var dealer = dealer_input.val();
			var computer = name_input.val();
			var name = name_input.val();
			var pass = pass_input.val();

			if($.inArray(dealer, dealerids) == -1)
			{
				dealer_input.val('');
				modal.find("#modal_label").text('Invalid Dealer Name');
				return false;
			}
			if($.inArray(computer, computers) == -1)
			{
				name_input.val('');
				modal.find("#modal_label").text('Invalid Computer Name');
				return false;
			}

			var request = $.ajax({
				url: req_url,
				type: "GET",
				data: {
					req: 'change_dealer_name',
					dealer: dealer,
					name: name, 
					password: pass
				}
			});
			
			request.done(function(msg){
				if($.trim(msg) == "wrong")
				{
					pass_input.val('');
					modal.find("#modal_label").text('Invalid Password');
				}
				else if($.trim(msg) == "done")
				{
					modal.modal('hide');
					init();
					var mpc_info = $("#mpc_name");
					mpc_info.find("#name").text(name);
				}
			});
		});
	});
	
	update_button.click(function(){
		modal.modal({ backdrop: 'static', keyboard: true });
		modal.find("#modal_header").text("Checking for updates ...");
		modal_body.append($("<p id=modal_info class=text-info></p>"));
		var modal_info = modal.find("#modal_info");
		var prog_bar_div = $("<div class=progress id=prog_bar_div><div class=bar style=width:0%; id=prog_bar></div></div>");
		modal_body.append(prog_bar_div);
		var prog_bar = prog_bar_div.find("#prog_bar");

		modal_info.show();
		prog_bar.show();
		modal.modal('show');
		
		var request = $.ajax({
			url: req_url,
			type: "GET",
			data: { req: 'check_update' }
		});
		request.done(function(msg){
			modal.modal('hide');
		});
	});
	
	function init()
	{
		var request = $.ajax({
			url: req_url,
			type: "GET",
			data: { req: 'get_about_info' }
		});

		request.done(function(msg){
			var about_info = $.parseJSON(msg);

			about_table.find('#dealer_name').text(about_info.dealer);
			about_table.find('#version').text(about_info.version);
			about_table.find('#sn').text(about_info.sn);
			about_table.find('#cpu').text(about_info.cpu);
			about_table.find('#memory').text(about_info.memory);
			about_table.find('#disk').text(about_info.disk);
			about_table.find('#suppurt').html(about_info.suppurt);
		});
	}
});

