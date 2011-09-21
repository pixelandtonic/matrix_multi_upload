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
	var $description = 'Upload multiple files to Matrix at once';
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
			$this->EE->lang->loadfile('matrix_multi_upload');

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

					// add the Matrix Column section
					$this->sections['1. '.lang('choose_col')] = '<p>'.lang('choose_col_info').'</p>'
					                                    . '<div id="mmu_matrix_col"><p class="notice">'.lang('choose_col_notice').'</p></div>';

					// get the site index
					if (($site_index = $this->EE->config->item('playa_site_index')) === FALSE) $site_index = $this->EE->functions->fetch_site_index(0, 0);

					// include JS
					$this->_include_theme_js('lib/plupload/js/gears_init.js');
					$this->EE->cp->add_to_foot('<script type="text/javascript" src="http://bp.yahooapis.com/2.4.21/browserplus-min.js"></script>');
					$this->_include_theme_js('lib/plupload/js/plupload.full.min.js');
					$this->_include_theme_js('lib/plupload/js/jquery.plupload.queue.min.js');
					$this->_include_theme_js('lib/json2.js');

					$this->_include_theme_js('scripts/matrix_multi_upload.js');

					// make the upload URL available to JS
					$this->_include_theme_css('styles/matrix_multi_upload.css');
					$this->_insert_js('MMU.FileHandler.uploadUrl = "'.$site_index.QUERY_MARKER.'ACT='.$action_id.'";');

					// add the Plupload sections
					$this->sections['2. '.lang('choose_filedir')] = '<p>'.lang('choose_filedir_info').'</p>'
					                                        . form_dropdown('mmu_filedir', $upload_dirs, '', 'id="mmu_filedir"');

					$this->sections[lang('upload_files')]   = '<div id="mmu_plupload" style="width: 450px; height: 330px;">'
					                                        .   'You browser doesn’t support multi-file uploading.'
					                                        . '</div>';

					// -------------------------------------------
					//  Assets integration
					// -------------------------------------------

					if (array_key_exists('assets', $this->EE->addons->get_installed()))
					{
						$this->sections['2. '.lang('choose_files')] = '<p>'.lang('choose_files_info').'</p>'
						                                            . '<input id="mmu_choose_files" type="button" value="'.lang('choose_files').'">';

						// include the sheet resources
						require_once PATH_THIRD.'assets/helper.php';
						$assets_helper = new Assets_helper;
						$assets_helper->include_sheet_resources();
					}

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
