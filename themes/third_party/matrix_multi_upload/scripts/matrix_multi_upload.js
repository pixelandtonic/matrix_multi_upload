(function($) {

MMU = {

	assetsInstalled: false,

	/**
	 * Initialize
	 */
	init: function() {

		this.$pane = $('#matrix_multi_upload');
		this.$sections = $('.accessorySection', this.$pane);

		// initially hide everything but the first section
		this.$sections.not(':first').hide();

		// make sure Matrix 2.0.8 or later is installed
		if (typeof Matrix == 'undefined' || typeof Matrix.instances == 'undefined') {
			return;
		}

		// -------------------------------------------
		//  Get the File columns
		// -------------------------------------------

		this.cols = [];

		// should we be looking for Assets cols as well?
		this.assetsInstalled = (typeof Assets != 'undefined');

		for (var i in Matrix.instances) {
			var matrix = Matrix.instances[i];

			for (var c in matrix.cols) {
				var col = matrix.cols[c];

				if (col.type == 'file' || (this.assetsInstalled && col.type == 'assets')) {
					this.cols.push({
						label:  matrix.label+' - '+col.label,
						matrix: matrix,
						col:    col,
						index:  c
					});
				}
			}
		}

		// stop here if there aren't any File or Assets cols
		if (! this.cols.length) {
			return;
		}

		// initialize the upload handlers
		MMU.FileHandler.init();
		if (this.assetsInstalled) MMU.AssetsHandler.init();

		// create the Matrix Col select
		this.$colSelect = $('<select id="mmu_matrix_col" />');

		for (var t in this.cols) {
			this.$colSelect.append($('<option value="'+t+'">'+this.cols[t].label+'</option>'));
		}

		// replace the "No Columns" notice with the select
		$('#mmu_matrix_col', this.$sections[0]).replaceWith(this.$colSelect);

		// handle select changes
		this.$colSelect.change($.proxy(this, 'onColSelectChange'));
		this.onColSelectChange();
	},

	/**
	 * On Col Select Change
	 */
	onColSelectChange: function() {
		this.selectedCol = this.cols[this.$colSelect.val()];

		if (this.selectedCol.col.type == 'file') {
			this.FileHandler.show();
			if (this.assetsInstalled) this.AssetsHandler.hide();
			if (typeof this.selectedCol.col.settings.directory != "undefined" && this.selectedCol.col.settings.directory != 'all') {
                MMU.FileHandler.setPluploadUrl(this.selectedCol.col.settings.directory);
            } else {
                MMU.FileHandler.updatePluploadUrl();
            }
		} else {
			if (this.assetsInstalled) this.AssetsHandler.show();
			this.FileHandler.hide();
		}
	}
};

/**
 * File Col Handler
 */
MMU.FileHandler = {

	/**
	 * Initialize
	 */
	init: function() {
		this.$sections = $([MMU.$sections[1], MMU.$sections[2]]);
		this.$filedirSection = $(this.$sections[0]);
		this.$uploadHeading = $('h5', this.$sections[1]);
		this.uploadHeadingText = this.$uploadHeading.html();
		this.$filedirSelect = $('#mmu_filedir', this.$sections[0]);
	},

	/**
	 * Initialize Plupload
	 */
	initPlupload: function() {
		this.$plupload = $('#mmu_plupload', this.$sections[1]);

		// initialize Plupload
		this.$plupload.pluploadQueue({
			runtimes: 'html5,flash',
			url: this.getUploadUrl(),
			multiple_queues: true
		});

		// get the Plupload object
		this.plupload = this.$plupload.pluploadQueue();

		// keep the URL up-to-date
		this.$filedirSelect.change($.proxy(this, 'updatePluploadUrl'));

		this.plupload.bind('FileUploaded', $.proxy(this, 'addFiles'));
	},

	/**
	 * Get Upload URL
	 */
	getUploadUrl: function() {
		return this.uploadUrl + '&dir=' + this.$filedirSelect.val();
	},

	/**
	 * Update Plupload URL
	 */
	updatePluploadUrl: function() {
		this.plupload.settings.url = this.getUploadUrl();
	},

    /**
     * Set Plupload URL according to an upload folder setting
     * @param upload_dir
     */
    setPluploadUrl: function (upload_dir) {
        this.plupload.settings.url = this.uploadUrl + '&dir=' + upload_dir;
    },

	/**
	 * Add Files
	 */
	addFiles: function(plupload, file, response) {

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

		// add a new Matrix row and get the File cell
		var row = MMU.selectedCol.matrix.addRow(),
			cell = row.cells[MMU.selectedCol.index];

		// select the new file
		cell.selectFile(this.$filedirSelect.val(), response.result.name, response.result.thumb);
	},

	/**
	 * Show
	 */
	show: function() {
		this.$sections.show();

		// if the File col is already tied to a specific upload directory,
		// preselect that option here, and disable the Upload Directory select
		if (typeof MMU.selectedCol.col.settings.directory != 'undefined') {
			if (MMU.selectedCol.col.settings.directory == 'all') {
				this.$filedirSection.show();
				this.$uploadHeading.html('3. '+this.uploadHeadingText);
			} else {
				this.$filedirSelect.val(MMU.selectedCol.col.settings.directory);
				this.$filedirSection.hide();
				this.$uploadHeading.html('2. '+this.uploadHeadingText);
			}
		}

		if (typeof this.plupload == 'undefined') {
			this.initPlupload();
		}
	},

	/**
	 * Hide
	 */
	hide: function() {
		this.$sections.hide();
	}
};

/**
 * Assets Col Handler
 */
MMU.AssetsHandler = {

	/**
	 * Initialize
	 */
	init: function() {
		this.$section = $(MMU.$sections[3]);
		this.$chooseFilesSelect = $('#mmu_choose_files', this.$section);

		this.$chooseFilesSelect.click($.proxy(this, 'showSheet'));
	},

	/**
	 * Show Sheet
	 */
	showSheet: function() {
		if (typeof this.sheet == 'undefined') {
			this.initSheet();
		}

		this.sheet.show();
	},

	/**
	 * Initialize Sheet
	 */
	initSheet: function() {
		this.sheet = new Assets.Sheet({
			multiSelect: 'y',
			filedirs:    MMU.selectedCol.col.settings.filedirs,
			onSelect:    $.proxy(this, 'addFiles')
		});
	},

	/**
	 * Add Files
	 */
	addFiles: function(files) {
		for (var i in files) {

			var row = MMU.selectedCol.matrix.addRow(),
				cell = row.cells[MMU.selectedCol.index];

			cell.assetsField._selectFiles([files[i]]);
		}
	},

	/**
	 * Show
	 */
	show: function() {
		this.$section.show();
	},

	/**
	 * Hide
	 */
	hide: function() {
		this.$section.hide();
	}
};


var $tab = $('#accessoryTabs a.matrix_multi_upload');

$tab.bind('click.matrix_multi_upload', function() {
	// only initialize once
	$tab.unbind('click.matrix_multi_upload');

	// wait for the tab to show
	setTimeout(function(){
		MMU.init();
	}, 1);
});


})(jQuery);
