function updateFilename (file_id) {
	var delimiter = ' - ';
	var filename = '';
	
	var account     = $('input[name=account' + file_id + ']').val();
	var date        = $('input[name=date' + file_id + ']').val();
	var amount      = $('input[name=amount' + file_id + ']').val();
	var description = $('input[name=description' + file_id + ']').val();
	var extention   = $('input[name=extention' + file_id + ']').val();
	
	amount = amount.replace('\.', ',');
	
	if(account != '') {
		filename += account;
	}
	
	if(date != '' && filename != '') {
		filename += delimiter + date;
	}
	else if(date != '') {
		filename += date;
	}
	
	if(amount != '' && amount.indexOf(',') == -1) {
		amount = amount + ',00';
	}
	if(amount != '' && filename != '') {
		filename += delimiter + amount + ' kr';
	}
	else if(amount != '') {
		filename += amount+' kr';
	}
	
	if(description != '' && filename != '') {
		filename += delimiter + description;
	}
	else if(description != '') {
		filename += description;
	}
	
	if(filename != '') {
		if(extention != '') {
			filename += '.' + extention;
		}
	}
	$('.filename' + file_id).html(filename);
}

$(document).ready(function() {
	
	$('.savefilename').click(function () {
		
		var file_id = $(this).parent().parent().attr('id').substr('file_'.length);
		var folder           = $('.folder' + file_id).html();
		var filenameoriginal = $('.filenameoriginal' + file_id).html();
		var filename         = $('.filename' + file_id).html();
		//console.log(filenameoriginal);
		//console.log(filename);
		
		var post = $.post('/regnskap/regnskap/webroot/index.php/import/transactionfiles/renamejs/', 
			{
				folder: folder,
				filenameoriginal: filenameoriginal,
				filename: filename
			},
			function(data)
		{
			if(jQuery.trim(data) == 'ok') {
				$('#savebutton'+file_id).parent().
					html('<img src="/regnskap/regnskap/webroot//images/tick.png" '+
						'class="canAutoimport">'+
						$('#autoimport_account_id :selected').text()
					);
			} else {
				alert('Something went wrong! Not renamed!\n\n' + data);
			}
		})
		.error(function() { alert("Error"); });		
		return false;
	});
	
	$('.main_file').each(function () {
		var file_id = $(this).attr('id').substr('file_'.length);
		updateFilename(file_id);
	});
	
	$('.main_file input').change(function () {
		var file_id = $(this).parent().parent().attr('id').substr('file_'.length);
		updateFilename(file_id);
	});
	$('.main_file input').keyup(function () {
		var file_id = $(this).parent().parent().attr('id').substr('file_'.length);
		updateFilename(file_id);
	});
	
	$('.file_image').lightBox({
			imageLoading:  '/regnskap/regnskap/webroot/images/lightbox-ico-loading.gif',		// (string) Path and the name of the loading icon
			imageBtnPrev:            '/regnskap/regnskap/webroot/images/lightbox-btn-prev.gif',			// (string) Path and the name of the prev button image
			imageBtnNext:            '/regnskap/regnskap/webroot/images/lightbox-btn-next.gif',			// (string) Path and the name of the next button image
			imageBtnClose:           '/regnskap/regnskap/webroot/images/lightbox-btn-close.gif',		// (string) Path and the name of the close btn
			imageBlank:              '/regnskap/regnskap/webroot/images/lightbox-blank.gif'			// (string) Path and the name of a blank
		});
	
	$(document).ajaxStart($.blockUI).ajaxStop($.unblockUI);
});
