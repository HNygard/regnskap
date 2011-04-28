$(document).ready(function() {

	var autoimport_account_id = $('#autoimport_account_id'),
		autoimport_type = $('#autoimport_type'),
		autoimport_text = $('#autoimport_text'),
		last_transaction_id = $('#last_transaction_id');
	
	$('.canNotAutoimport').click(function () {
		// Getting id of the transaction and getting the values
		var tr = $(this). // button
			parent(). // td
			parent(); // tr
		
		autoimport_type.val($('.type', $(tr)).text());
		autoimport_text.val($('.text', $(tr)).text());
		last_transaction_id.text(tr.attr('id'));
		
		$( "#dialog-form" ).dialog( "open" );
	});

	$('#dialog-form').dialog({
		autoOpen: false,
		height: 300,
		width: 350,
		modal: true,
		buttons: {
			"Create an autoimport": function() {
				// index.php/bankaccount_autoimport/createjs/ACCOUNTID/TYPE/TEXT
				$('#dialog-form').dialog( "close" );
				$.get('/regnskap/regnskap/webroot/index.php/bankaccount_autoimport/createjs/'+
						autoimport_account_id.val()+'/'+
						autoimport_type.val()+'/'+
						autoimport_text.val(), 
					function(data)
				{
					if(jQuery.trim(data) == 'ok') {
						$('#'+last_transaction_id.text()+' td.button').
							html('<img src="/regnskap/regnskap/webroot//images/tick.png" '+
								'class="canAutoimport"> - '+
								$('#autoimport_account_id :selected').text()
							);
					} else {
						alert('Something went wrong! Not created!\n\n' + data);
					}
				});
			},
			Cancel: function() {
				$( this ).dialog( "close" );
			}
		},
		close: function() {
		}
	});
	
	$(document).ajaxStart($.blockUI).ajaxStop($.unblockUI);
});
