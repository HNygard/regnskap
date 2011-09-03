$(document).ready(function() {

	var autoimport_account_id = $('#autoimport_account_id'),
		autoimport_type = $('#autoimport_type'),
		autoimport_text = $('#autoimport_text'),
		autoimport_bankaccount_id = $('#autoimport_bankaccount_id'),
		autoimport_amount_max = $('#autoimport_amount_max'),
		autoimport_amount_min = $('#autoimport_amount_min'),
		autoimport_time_max = $('#autoimport_time_max'),
		autoimport_time_min = $('#autoimport_time_min'),
		autoimport_copy_amount_max = $('#autoimport_copy_amount_max'),
		autoimport_copy_amount_min = $('#autoimport_copy_amount_min'),
		autoimport_copy_time_max = $('#autoimport_copy_time_max'),
		autoimport_copy_time_min = $('#autoimport_copy_time_min'),
		last_transaction_id = $('#last_transaction_id');
	
	$('.transaction').each(function () {
		var transaction_id = $(this).attr('id').substr('transactions_'.length-1);
		var get = $.get('/regnskap/regnskap/webroot/index.php/bankaccount/transaction_canautoimport/'+
						transaction_id,
					function(data)
				{
					if(jQuery.trim(data) != 'false') {
						$('#transaction_'+transaction_id+' td.button'). // TODO: This might not be true!
							html(data); // HTML delivered in response
					} else {
						$('#transaction_'+transaction_id+' td.button'). // TODO: This might not be true!
							html('<button class="canNotAutoimport" '+
								'class="ui-button ui-widget ui-state-default ui-corner-all '+
								'ui-button-text-only ui-state-hover" role="button" '+
								'aria-disabled="false"><span class="ui-button-text">+</span></button>'
								//$('#autoimport_account_id :selected').text()
							);
						$('#transaction_'+transaction_id+' td.button .canNotAutoimport').click(function () {
							// Getting id of the transaction and getting the values
							var tr = $(this). // button
								parent(). // td
								parent(); // tr
		
							// Set the type and text field to the values from the transaction
							autoimport_type.val($('.type', $(tr)).text());
							autoimport_text.val($('.text', $(tr)).text());
		
							// Copying some amount and time to a temp location
							autoimport_copy_amount_max.text($('.amount', $(tr)).text());
							autoimport_copy_amount_min.text($('.amount', $(tr)).text());
							autoimport_copy_time_max.text($('.time', $(tr)).text());
							autoimport_copy_time_min.text($('.time', $(tr)).text());
		
							last_transaction_id.text(tr.attr('id'));
		
							$( "#dialog-form" ).dialog( "open" );
						});
					}
				})
				.error(function() { 
					//alert("Error"); 
				});
	});
	
	$('#autoimport_copy').click(function () {
		
		autoimport_amount_max.val(autoimport_copy_amount_max.text());
		autoimport_amount_min.val(autoimport_copy_amount_min.text());
		autoimport_time_max.val(autoimport_copy_time_max.text());
		autoimport_time_min.val(autoimport_copy_time_min.text());
		
		return false;
	});

	$('#dialog-form').dialog({
		autoOpen: false,
		height: 300,
		width: 650,
		modal: true,
		buttons: {
			"Create an autoimport": function() {
				// index.php/bankaccount_autoimport/createjs/ACCOUNTID/TYPE/TEXT
				$('#dialog-form').dialog( "close" );
				
				if(autoimport_bankaccount_id.attr('checked'))
					var bankaccount_id_checkedvalue = autoimport_bankaccount_id.val();
				else
					var bankaccount_id_checkedvalue = '';
				
				var post = $.post('/regnskap/regnskap/webroot/index.php/bankaccount_autoimport/createjs/'+
						autoimport_account_id.val(),
					{
						type: autoimport_type.val(),
						text: autoimport_text.val(),
						amount_max: autoimport_amount_max.val(),
						amount_min: autoimport_amount_min.val(),
						time_max: autoimport_time_max.val(),
						time_min: autoimport_time_min.val(),
						bankaccount_id: bankaccount_id_checkedvalue,
					},
					function(data)
				{
					if(jQuery.trim(data) == 'ok') {
						$('#'+last_transaction_id.text()+' td.button'). // TODO: This might not be true!
							html('<img src="/regnskap/regnskap/webroot//images/tick.png" '+
								'class="canAutoimport"> - '+
								$('#autoimport_account_id :selected').text()
							);
					} else {
						alert('Something went wrong! Not created!\n\n' + data);
					}
				})
				.error(function() { alert("Error"); });
			},
			Cancel: function() {
				$( this ).dialog( "close" );
			}
		},
		close: function() {
		},
		open: function () {
			autoimport_bankaccount_id.attr('checked', false);
			autoimport_amount_max.val('');
			autoimport_amount_min.val('');
			autoimport_time_max.val('');
			autoimport_time_min.val('');
		},
	});
	
	$(document).ajaxStart($.blockUI).ajaxStop($.unblockUI);
});
