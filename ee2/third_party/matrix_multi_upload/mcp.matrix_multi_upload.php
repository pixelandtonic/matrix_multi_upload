<?php if (! defined('BASEPATH')) exit('Invalid file request');


/**
 * Matrix Multi-Upload Module CP Class
 *
 * @package   Matrix Multi-Upload
 * @author    Brandon Kelly <brandon@pixelandtonic.com>
 * @copyright Copyright (c) 2010 Pixel & Tonic, LLC
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

		$this->EE->load->library('filemanager');
		$this->EE->load->model('tools_model');

		// Get the upload prefs
		$upload_id = $this->EE->input->get('dir');
		$upload_dir_result = $this->EE->tools_model->get_upload_preferences($this->EE->session->userdata('member_group'), $upload_id);
		$upload_dir_prefs = $upload_dir_result->row();


		// validate upload path

		$path = $upload_dir_prefs->server_path;

		if (! $path)
		{
			$this->_error('104');
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
		$file_name = preg_replace('/[^\w\._]+/', '', $file_name);

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


		// upload sucessful, now create thumb

		$thumb_path = $path.'_thumbs';
		$thumb_name = 'thumb_'.$file_name;

		$thumb_url = $upload_dir_prefs->url;
		if (substr($thumb_url, -1) != '/') $thumb_url .= '/';
		$thumb_url .= '_thumbs/'.$thumb_name;

		if ( ! is_dir($thumb_path))
		{
			mkdir($thumb_path);
		}

		$resize['source_image']   = $file_path;
		$resize['new_image']      = $thumb_path.DIRECTORY_SEPARATOR.$thumb_name;
		$resize['maintain_ratio'] = TRUE;
		$resize['image_library']  = $this->EE->config->item('image_resize_protocol');
		$resize['library_path']   = $this->EE->config->item('image_library_path');
		$resize['width']          = 73;
		$resize['height']         = 60;

		$this->EE->load->library('image_lib', $resize);
		$this->EE->image_lib->resize();


		// Return JSON-RPC response
		exit('{"jsonrpc" : "2.0", "result" : {"name" : "'.$file_name.'", "thumb" : "'.$thumb_url.'"}, "id" : "id"}');
	}

}
