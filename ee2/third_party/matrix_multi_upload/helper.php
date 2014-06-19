<?php if (! defined('BASEPATH')) die('No direct script access allowed');


/**
 * Matrix Multi-Upload Helper
 *
 * @package Matrix Multi-Upload
 * @author    Pixel & Tonic, Inc <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014 Pixel & Tonic, LLC
 */
class Matrix_multi_upload_helper {

	/**
	 * Get Upload Preferences
	 * @param  int $group_id Member group ID specified when returning allowed upload directories only for that member group
	 * @param  int $id       Specific ID of upload destination to return
	 * @return array         Result array of DB object, possibly merged with custom file upload settings (if on EE 2.4+)
	 */
	public static function get_upload_preferences($group_id = NULL, $id = NULL)
	{
		$EE =& get_instance();

		if (version_compare(APP_VER, '2.4', '>='))
		{
			$EE->load->model('file_upload_preferences_model');
			return $EE->file_upload_preferences_model->get_file_upload_preferences($group_id, $id);
		}

		if (version_compare(APP_VER, '2.1.5', '>='))
		{
			$EE->load->model('file_upload_preferences_model');
			$result = $EE->file_upload_preferences_model->get_upload_preferences($group_id, $id);
		}
		else
		{
			$EE->load->model('tools_model');
			$result = $EE->tools_model->get_upload_preferences($group_id, $id);
		}

		// If an $id was passed, just return that directory's preferences
		if ( ! empty($id))
		{
			return $result->row_array();
		}

		// Use upload destination ID as key for row for easy traversing
		$return_array = array();
		foreach ($result->result_array() as $row)
		{
			$return_array[$row['id']] = $row;
		}

		return $return_array;
	}

}
