var MatrixMultiUploadURL;


(function($) {


var $tab = $('#accessoryTabs a.matrix_multi_upload');

$tab.bind('click.matrix_multi_upload', function(){

	// wait for the tab to show
	setTimeout(function(){

		// only initialize once
		$tab.unbind('click.matrix_multi_upload');

		// is Matrix 2.0.8+ being used?
		if (typeof Matrix == 'undefined' || typeof Matrix.instances == 'undefined') return;

		// find File cols on this page
		var targetCols = [];
		for (var i in Matrix.instances) {
			var matrix = Matrix.instances[i];

			for (var c in matrix.cols) {
				var col = matrix.cols[c];

				if (col.type == 'file') {
					targetCols.push({
						label:  matrix.label+' - '+col.label,
						matrix: matrix,
						col:    col,
						index:  c
					});
				}
			}
		}

		// quit if no File cols
		if (! targetCols.length) return;

		var $pane = $('#matrix_multi_upload'),
			$targetDir = $('select[name=matrix_multi_upload_dir]', $pane),
			$uploader = $('#matrix_multi_upload_uploader', $pane);

		// create the Target Col select
		var $targetCol = $('<select id="matrix_multi_upload_target" />');
		$('#matrix_multi_upload_target', $pane).replaceWith($targetCol);

		// add the options
		for (var t in targetCols) {
			$targetCol.append($('<option value="'+t+'">'+targetCols[t].label+'</option>'));
		}

		var getURL = function() {
			return MatrixMultiUploadURL + '&dir=' + $targetDir.val();
		};

		// initialize the uploader
		$uploader.pluploadQueue({
			runtimes: 'gears,html5,flash,silverlight,browserplus',
			url:      getURL()
		});

		// get the plupload object
		var uploader = $uploader.pluploadQueue();

		// keep the URL up-to-date
		$targetDir.change(function() {
			uploader.settings.url = getURL();
		});

		uploader.bind('FileUploaded', function(uploader, file, response) {

			// do we have a filename?
			if (! response.response) {
				$.ee_notice('An unknown error occurred while uploading '+file.name, {type: 'error'});
				return;
			}

			// parse the response JSON
			//  - we'll swap this with $.parseJSON once EE gets jQuery 1.4.1+
			response = JSON.parse(response.response);

			// was there an error?
			if (response.error) {
				// show the error notification and quit
				$.ee_notice(response.error.message, {type: 'error'});
				return;
			}

			var targetCol = targetCols[$targetCol.val()],
				row = targetCol.matrix.addRow(),
				cell = row.cells[targetCol.index];

			// select the new file
			cell.selectFile($targetDir.val(), response.result.name, response.result.thumb);
		});

	}, 1);
});


})(jQuery);
