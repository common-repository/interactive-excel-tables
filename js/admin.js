jQuery(function($) {

	$('body').on('click', '#upload_ext_button', ext_open_media_window);

	function ext_open_media_window() {
		this.window = wp.media({
			title: 'Add file',
			multiple: false,
			library: {
				type: 'text/csv, application/vnd.ms-excel, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel.sheet.macroEnabled.12, application/vnd.ms-excel.sheet.binary.macroEnabled.12, application/vnd.openxmlformats-officedocument.spreadsheetml.template, application/vnd.ms-excel.template.macroEnabled.12, application/vnd.ms-excel.addin.macroEnabled.12'
			},
			button: {text: 'Insert'}
		});

		var self = this;
		this.window.on('select', function() {
			var file = self.window.state().get('selection').first().toJSON();

			$('.ext-upload-txt').css('display', 'none');
			$('#extable_prev_metabox').css('display', 'block');
			$('#extable_prev_metabox .ext-preview').html('');
			$('#extable_prev_metabox .ext-preview').append('<p>To see the preview, please update/save the table.</p>');
			$('#upload_ext_button').remove();

			var fileFormat = ext_get_file_format(file.filename);
			$('#upload_extable').val(file.id);
			$('.ext_fileinfo_cont').addClass('brd');			
			$('.ext_fileinfo_cont').append('<p><b>File ID:</b> '+ file.id +'</p>');
			$('.ext_fileinfo_cont').append('<p><b>File Name:</b> '+ file.filename +'</p>');
			$('.ext_fileinfo_cont').append('<p><b>File URL:</b> '+ file.url +'</p>');
			$('.ext_fileinfo_cont').append('<div class="ext_fileclose">Delete file</div>');

			if(fileFormat == 'csv'){
				$('.ext_fileinfo').append('<div class="ext-csv-separator"><p>Columns are separated from: </p><input type="text" name="ext_csv_separator" /></div>');
			}
		});

		this.window.open();
		return false;
	}

	function ext_get_file_format(filename){
		var extension = filename.substr( (filename.lastIndexOf('.') +1) );
		return extension;
	}

	function ext_delete_file(){
		$('.ext-upload-txt').css('display', 'block');
		$('#extable_prev_metabox').css('display', 'none');
		$('.ext_fileinfo').prepend('<input class="button-secondary" id="upload_ext_button" type="button" value="Upload File" />');
		$('.ext_fileinfo_cont').removeClass('brd');			
		$('#upload_extable').val('');
		$('.ext_fileinfo_cont').html('');			
		$('.ext_fileinfo .ext-csv-separator').remove();
	}

	$('.ext_fileinfo_cont').on('click', '.ext_fileclose', ext_delete_file);

	if($('#upload_extable').val() == ''){
		$('#extable_prev_metabox').css('display', 'none');
	}
});