<?php if (! defined('BASEPATH')) exit('Invalid file request');


require_once PATH_THIRD.'matrix_multi_upload/helper.php';


/**
 * Matrix Multi-Upload Module CP Class
 *
 * @package   Matrix Multi-Upload
 * @author    Pixel & Tonic, Inc <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014 Pixel & Tonic, LLC
 */
class Matrix_multi_upload_mcp {

	/**
	 * Constructor
	 */
	function Matrix_multi_upload_mcp()
	{
		$this->EE =& get_instance();
	}

	// --------------------------------------------------------------------

	/**
	 * Error
	 */
	private function _error($code)
	{
		$this->EE->lang->loadfile('matrix_multi_upload');
		exit('{"jsonrpc" : "2.0", "error" : {"code": '.$code.', "message": "'.$this->EE->lang->line('error_'.$code).'"}, "id" : "id"}');
	}

	/**
	 * Upload
	 */
	function upload()
	{
		/**
		 * Adapted from plupload/examples/upload.php
		 *
		 * Copyright 2009, Moxiecode Systems AB
		 * Released under GPL License.
		 *
		 * License: http://www.plupload.com/license
		 * Contributing: http://www.plupload.com/contributing
		 */

		// HTTP headers for no cache etc
		header('Content-type: text/plain; charset=UTF-8');
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
		header("Cache-Control: no-store, no-cache, must-revalidate");
		header("Cache-Control: post-check=0, pre-check=0", false);
		header("Pragma: no-cache");

		// Get the upload prefs
		$group_id = $this->EE->session->userdata('member_group');
		$upload_id = $this->EE->input->get('dir');
		$upload_dir_prefs = Matrix_multi_upload_helper::get_upload_preferences($group_id, $upload_id);


		// validate upload path

		$path = $upload_dir_prefs['server_path'];

		if (! $path)
		{
			$this->_error('104');
		}

		// relative paths are usually relative to the system directory,
		// but this function is loaded via the site URL
		// so attempt to turn relative paths into absolute paths
		if (! preg_match('/^(\/|\\\|[a-zA-Z]+:)/', $path))
		{
			// if the CP is masked, there's no way for us to determine the path to the CP's entry point
			// so people with relative upload directory paths _and_ masked CPs will have to point us in the right direction
			if ($cp_path = $this->EE->assets_lib->normalize_directoryseparator($this->EE->config->item('mmu_cp_path')))
			{
				$path = rtrim($cp_path, '/').'/'.$path;
			}
			else
			{
				$path = SYSDIR.'/'.$path;
			}
		}

		if (function_exists('realpath') AND @realpath($path) !== FALSE)
		{
			$path = str_replace("\\", "/", realpath($path));
		}

		if (! @is_dir($path) || ! is_really_writable($path))
		{
			$this->_error('100');
		}

		if (substr($path, -1) != '/') $path .= '/';



		// Temp file age in seconds (60 x 60)
		$maxFileAge = 3600;

		// 5 minutes execution time (5 x 60)
		@set_time_limit(300);

		// Get parameters
		$chunk = isset($_REQUEST["chunk"]) ? $_REQUEST["chunk"] : 0;
		$chunks = isset($_REQUEST["chunks"]) ? $_REQUEST["chunks"] : 0;
		$file_name = isset($_REQUEST["name"]) ? $_REQUEST["name"] : '';

		// Clean the fileName for security reasons
		$file_name = preg_replace('/[^\w\._-]+/', '', $file_name);

		// Make sure the fileName is unique
		if (file_exists($path.$file_name))
		{
			$ext = strrpos($file_name, '.');
			$file_name_a = substr($file_name, 0, $ext);
			$file_name_b = substr($file_name, $ext);

			$count = 1;
			while (file_exists($path.$file_name_a.'_'.$count.$file_name_b))
			{
				$count++;
			}

			$file_name = $file_name_a.'_'.$count.$file_name_b;
		}

		$file_path = $path.$file_name;

		// Remove old temp files
		if (is_dir($path) && ($dir = opendir($path)))
		{
			while (($file = readdir($dir)) !== false)
			{
				$tmp_file_path = $path.$file;

				// Remove temp files if they are older than the max age
				if (preg_match('/\\.tmp$/', $file) && (filemtime($tmp_file_path) < time() - $maxFileAge))
				{
					@unlink($tmp_file_path);
				}
			}

			closedir($dir);
		}
		else
		{
			$this->_error('100');
		}

		// Look for the content type header
		if (isset($_SERVER["HTTP_CONTENT_TYPE"]))
		{
			$content_type = $_SERVER["HTTP_CONTENT_TYPE"];
		}

		if (isset($_SERVER["CONTENT_TYPE"]))
		{
			$content_type = $_SERVER["CONTENT_TYPE"];
		}

		if (isset($content_type) && strpos($content_type, "multipart") !== false)
		{
			if (isset($_FILES['file']['tmp_name']) && is_uploaded_file($_FILES['file']['tmp_name']))
			{
				// Open temp file
				$out = fopen($file_path, $chunk == 0 ? "wb" : "ab");

				if ($out)
				{
					// Read binary input stream and append it to temp file
					$in = fopen($_FILES['file']['tmp_name'], "rb");

					if ($in)
					{
						while ($buff = fread($in, 4096))
						{
							fwrite($out, $buff);
						}
					}
					else
					{
						$this->_error('101');
					}

					fclose($out);
					unlink($_FILES['file']['tmp_name']);
				}
				else
				{
					$this->_error('102');
				}
			}
			else
			{
				$this->_error('103');
			}
		}
		else
		{
			// Open temp file
			$out = fopen($file_path, $chunk == 0 ? "wb" : "ab");

			if ($out)
			{
				// Read binary input stream and append it to temp file
				$in = fopen("php://input", "rb");

				if ($in)
				{
					while ($buff = fread($in, 4096))
					{
						fwrite($out, $buff);
					}
				}
				else
				{
					$this->_error('101');
				}

				fclose($out);
			}
			else
			{
				$this->_error('102');
			}
		}


		// store the data about upload and generate a thumbnail

		$this->EE->load->library('filemanager');

		$prefs['rel_path'] = $file_path;
		$prefs['file_name'] = $file_name;
		$prefs['file_size'] = filesize($file_path);
		$prefs['uploaded_by_member_id'] = $this->EE->session->userdata('member_id');

		$file_size = getimagesize($file_path);
		if ($file_size !== FALSE)
		{
			$prefs['file_hw_original'] = $file_size[1].' '.$file_size[0];
		}

		$this->EE->filemanager->save_file($file_path, $upload_id, $prefs);

		// Get the thumb URL

		$thumb_url = $upload_dir_prefs['url'];
		if (substr($thumb_url, -1) != '/')
		{
			$thumb_url .= '/';
		}

		$thumb_url .= '_thumbs/'.$file_name;


		// Return JSON-RPC response
		exit('{"jsonrpc" : "2.0", "result" : {"name" : "'.$file_name.'", "thumb" : "'.$thumb_url.'"}, "id" : "id"}');
	}

}

/* End of file mcp.matrix_multi_upload.php */
/* Location: ./system/expressionengine/third_party/matrix/mcp.matrix_multi_upload.php */
