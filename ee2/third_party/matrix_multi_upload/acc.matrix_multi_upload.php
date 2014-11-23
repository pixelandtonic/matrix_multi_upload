<?php if (! defined('BASEPATH')) exit('No direct script access allowed');


require_once PATH_THIRD.'matrix_multi_upload/helper.php';


/**
 * Matrix Multi Upload Accessory Class for EE2
 *
 * @package   Matrix Multi-Upload
 * @author    Brandon Kelly <brandon@pixelandtonic.com>
 * @copyright Copyright (c) 2014 Pixel & Tonic, LLC
 */

class Matrix_multi_upload_acc {

	var $name        = 'Matrix Multi-Upload';
	var $id          = 'matrix_multi_upload';
	var $version     = '1.1.3';
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
			$theme_folder_url = defined('URL_THIRD_THEMES') ? URL_THIRD_THEMES : $this->EE->config->slash_item('theme_folder_url').'third_party/';
			$this->cache['theme_url'] = $theme_folder_url.'matrix_multi_upload/';
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

				$group_id = $this->EE->session->userdata('group_id');
				$upload_prefs = Matrix_multi_upload_helper::get_upload_preferences($group_id);

				// are there any upload directories?
				if ($upload_prefs)
				{
					foreach($upload_prefs as $row)
					{
						$upload_dirs[$row['id']] = $row['name'];
					}

					// add the Matrix Column section
					$this->sections['1. '.lang('choose_col')] = '<p>'.lang('choose_col_info').'</p>'
					                                    . '<div id="mmu_matrix_col"><p class="notice">'.lang('choose_col_notice').'</p></div>';

					// get the site url
					if (($site_url = $this->EE->config->item('mmu_site_url')) === FALSE) $site_url = $this->EE->functions->fetch_site_index(0, 0);

					// include CSS and JS
					$this->_include_theme_js('lib/plupload/js/plupload.js');
					$this->_include_theme_js('lib/plupload/js/plupload.html5.js');
					$this->_include_theme_js('lib/plupload/js/plupload.flash.js');
					$this->_include_theme_js('lib/plupload/js/jquery.plupload.queue/jquery.plupload.queue.js');
					$this->_include_theme_js('scripts/matrix_multi_upload.js');

					// make the upload URL available to JS
					$this->_include_theme_css('styles/matrix_multi_upload.css');
					$this->_insert_js('MMU.FileHandler.uploadUrl = "'.$site_url.QUERY_MARKER.'ACT='.$action_id.'";');

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
