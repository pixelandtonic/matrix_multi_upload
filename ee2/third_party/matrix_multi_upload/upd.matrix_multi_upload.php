<?php if (! defined('BASEPATH')) exit('Invalid file request');


/**
 * Matrix Multi-Upload Update Class
 *
 * @package   Matrix Multi-Upload
 * @author    Pixel & Tonic, Inc <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014 Pixel & Tonic, LLC
 */
class Matrix_multi_upload_upd {

	var $version = '1.1.3';

	/**
	 * Constructor
	 */
	function Matrix_multi_upload_upd()
	{
		$this->EE =& get_instance();
	}

	// --------------------------------------------------------------------

	/**
	 * Install
	 */
	function install()
	{
		$this->EE->db->insert('modules', array(
			'module_name'        => 'Matrix_multi_upload',
			'module_version'     => $this->version,
			'has_cp_backend'     => 'n',
			'has_publish_fields' => 'n'
		));

		// add the upload action
		if ($this->EE->db->field_exists('csrf_exempt', 'actions'))
		{
			$this->EE->db->insert('actions', array(
				'class'  => 'Matrix_multi_upload_mcp',
				'method' => 'upload',
				'csrf_exempt' => 1
			));
		}
		else
		{
			$this->EE->db->insert('actions', array(
				'class'  => 'Matrix_multi_upload_mcp',
				'method' => 'upload'
			));
		}

		return TRUE;
	}

	/**
	 * Update.
	 *
	 * @param $from
	 * @return bool
	 */
	function update($from)
	{
		if ($this->EE->db->field_exists('csrf_exempt', 'actions'))
		{
			$this->EE->db->query("UPDATE exp_actions SET csrf_exempt = 1 WHERE `class` = 'Matrix_multi_upload_mcp'");
		}
		return TRUE;
	}


	/**
	 * Uninstall
	 */
	function uninstall()
	{
		$this->EE->db->query('DELETE FROM exp_modules WHERE module_name = "Matrix_multi_upload"');
		$this->EE->db->query('DELETE FROM exp_actions WHERE class = "Matrix_multi_upload_mcp"');

		return TRUE;
	}

}
