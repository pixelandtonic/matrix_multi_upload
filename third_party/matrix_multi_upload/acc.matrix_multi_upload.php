<?php if (! defined('BASEPATH')) exit('No direct script access allowed');


/**
 * Matrix Multi Upload Accessory Class for EE2
 *
 * @package   Matrix Multi-Upload
 * @author    Brandon Kelly <brandon@pixelandtonic.com>
 * @copyright Copyright (c) 2010 Pixel & Tonic, LLC
 */

class Matrix_multi_upload_acc {

	var $name        = 'Matrix Multi-Upload';
	var $id          = 'matrix_multi_upload';
	var $version     = '1.0';
	var $description = 'My accessory has a lovely description.';
	var $sections    = array();

	/**
	 * Constructor
	 */
	function Matrix_multi_upload_acc()
	{
		$this->EE =& get_instance();
	}

	// --------------------------------------------------------------------

	/**
	 * Theme URL
	 */
	private function _theme_url()
	{
		if (! isset($this->cache['theme_url']))
		{
			$theme_folder_url = $this->EE->config->item('theme_folder_url');
			if (substr($theme_folder_url, -1) != '/') $theme_folder_url .= '/';
			$this->cache['theme_url'] = $theme_folder_url.'third_party/matrix_multi_upload/';
		}

		return $this->cache['theme_url'];
	}

	/**
	 * Include Theme CSS
	 */
	private function _include_theme_css($file)
	{
		$this->EE->cp->add_to_head('<link rel="stylesheet" type="text/css" href="'.$this->_theme_url().$file.'" />');
	}

	/**
	 * Include Theme JS
	 */
	private function _include_theme_js($file)
	{
		$this->EE->cp->add_to_foot('<script type="text/javascript" src="'.$this->_theme_url().$file.'"></script>');
	}

	// --------------------------------------------------------------------

	/**
	 * Insert CSS
	 */
	private function _insert_css($css)
	{
		$this->EE->cp->add_to_head('<style type="text/css">'.$css.'</style>');
	}

	/**
	 * Insert JS
	 */
	private function _insert_js($js)
	{
		$this->EE->cp->add_to_foot('<script type="text/javascript">'.$js.'</script>');
	}

	// --------------------------------------------------------------------

	/**
	 * Set Sections
	 */
	function set_sections()
	{
		// are we on the Publish page?
		if ($this->EE->input->get('C') == 'content_publish' && $this->EE->input->get('M') == 'entry_form')
		{
			$query = $this->EE->db->query('SELECT action_id FROM exp_actions WHERE class = "Matrix_multi_upload_mcp" AND method = "upload"');

			// is the module installed?
			if ($query->num_rows())
			{
				$action_id = $query->row('action_id');

				// Prefs

				$this->EE->load->model('tools_model');
				$upload_prefs = $this->EE->tools_model->get_upload_preferences($this->EE->session->userdata('group_id'));

				// are there any upload directories?
				if ($upload_prefs->num_rows())
				{
					foreach($upload_prefs->result() as $row)
					{
						$upload_dirs[$row->id] = $row->name;
					}

					$settings_html = '<div><strong><label for="matrix_multi_upload_dir">Upload Directory</label></strong></div>'
					               . form_dropdown('matrix_multi_upload_dir', $upload_dirs)
					               . '<div><strong><label for="matrix_multi_upload_target">Target Matrix Field/Column</label></strong></div>'
					               . '<div id="matrix_multi_upload_target">No Matrix fields with a File column exist on this page.</div>';

					// uploader

					// get the site index
					if (($site_index = $this->EE->config->item('playa_site_index')) === FALSE) $site_index = $this->EE->functions->fetch_site_index(0, 0);

					// include JS
					$this->_include_theme_js('scripts/matrix_multi_upload.js');
					$this->_include_theme_js('lib/plupload/js/gears_init.js');
					$this->EE->cp->add_to_foot('<script type="text/javascript" src="http://bp.yahooapis.com/2.4.21/browserplus-min.js"></script>');
					$this->_include_theme_js('lib/plupload/js/plupload.full.min.js');
					$this->_include_theme_js('lib/plupload/js/jquery.plupload.queue.min.js');

					// make the upload URL available to JS
					$this->_include_theme_css('styles/matrix_multi_upload.css');
					$this->_insert_js('MatrixMultiUpload.uploadUrl = "'.$site_index.QUERY_MARKER.'ACT='.$action_id.'";');


					$this->sections['Settings'] = $settings_html;
					$this->sections['Upload Files'] = '<div id="matrix_multi_upload_plupload" style="width: 450px; height: 330px;">You browser doesn’t support multi-file uploading.</div>';
				}
				else
				{
					$this->sections['Error'] = 'Either no upload directories exist, or you don’t have permission to upload to any of them.';
				}
			}
			else
			{
				$this->sections['Error'] = 'The Matrix Multi-Upload module is not installed.';
			}
		}
		else
		{
			// just hide the tab
			$this->_insert_js('jQuery("#accessoryTabs a.matrix_multi_upload").parent("li").remove()');
		}
	}

}
