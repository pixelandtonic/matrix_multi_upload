var MatrixMultiUpload = {};


(function($) {


$(document).ready(function(){
setTimeout(function(){

	var $tab = jQuery('#accessoryTabs a.matrix_multi_upload'), //.parent('li'),
		$pane = $('#matrix_multi_upload'),
		targets = [];

	for (var i in Matrix.instances) {
		var matrix = Matrix.instances[i];

		for (var c in matrix.cols) {
			var col = matrix.cols[c];

			if (col.type == 'file') {
				targets.push({
					label:  matrix.label+' - '+col.label,
					matrix: matrix,
					col:    col
				});
			}
		}
	}

	if (targets.length) {

		var $target = $('<select id="matrix_multi_upload_target" />');

		for (var t in targets) {
			var target = targets[t];

			$target.append($('<option value="'+t+'">'+target.label+'</option>'));
		}

		$('#matrix_multi_upload_target').replaceWith($target);
	}

	$tab.click(function(){
		setTimeout(function(){

			// attach the uploader
			$("#matrix_multi_upload_plupload").pluploadQueue({
				runtimes: 'gears,html5,flash,silverlight,browserplus',
				url:      MatrixMultiUpload.uploadUrl
			});

		}, 1);
	});

}, 1);
});


})(jQuery);
