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

		// Settings
		//$path = ini_get("upload_tmp_dir") . DIRECTORY_SEPARATOR . "plupload";
		if (!( ($dir = $this->EE->input->get('dir'))
			&& ($query = $this->EE->db->query('SELECT server_path, url FROM exp_upload_prefs WHERE id = "'.$dir.'"'))
			&& $query->num_rows()
		))
		{
			//exit('{"jsonrpc" : "2.0", "error" : {"code": 103, "message": "Failed to find upload directory."}, "id" : "id"}');
			exit();
		}

		// get the full path, without a trailing slash
		$path = $query->row('server_path');
		if (substr($path, 0, 1) != '/') $path = BASEPATH . $path;
		if (substr($path, -1) == '/') $path = substr($path, 0, -1);

		// get the URL, *with* a trailing slash
		$url = $query->row('url');
		if (substr($url, -1) != '/') $url .= '/';

		// Temp file age in seconds (60 x 60)
		$maxFileAge = 3600;

		// 5 minutes execution time (5 x 60)
		@set_time_limit(300);

		// Get parameters
		$chunk = isset($_REQUEST["chunk"]) ? $_REQUEST["chunk"] : 0;
		$chunks = isset($_REQUEST["chunks"]) ? $_REQUEST["chunks"] : 0;
		$fileName = isset($_REQUEST["name"]) ? $_REQUEST["name"] : '';

		// Clean the fileName for security reasons
		$fileName = preg_replace('/[^\w\._]+/', '', $fileName);

		// find a unique file name
		if (file_exists($path . DIRECTORY_SEPARATOR . $fileName))
		{
			$ext = strrpos($fileName, '.');
			$fileName_a = substr($fileName, 0, $ext);
			$fileName_b = substr($fileName, $ext);

			$count = 1;
			while (file_exists($path . DIRECTORY_SEPARATOR . $fileName_a . '_' . $count . $fileName_b))
			{
				$count++;
			}

			$fileName = $fileName_a . '_' . $count . $fileName_b;
		}

		// Remove old temp files
		if (is_dir($path) && ($dir = opendir($path)))
		{
			while (($file = readdir($dir)) !== false)
			{
				$filePath = $path . DIRECTORY_SEPARATOR . $file;

				// Remove temp files if they are older than the max age
				if (preg_match('/\\.tmp$/', $file) && (filemtime($filePath) < time() - $maxFileAge))
				{
					@unlink($filePath);
				}
			}

			closedir($dir);
		}
		else
		{
			//exit('{"jsonrpc" : "2.0", "error" : {"code": 100, "message": "Failed to open temp directory."}, "id" : "id"}');
			exit();
		}

		// Look for the content type header
		if (isset($_SERVER["HTTP_CONTENT_TYPE"]))
		{
			$contentType = $_SERVER["HTTP_CONTENT_TYPE"];
		}

		if (isset($_SERVER["CONTENT_TYPE"]))
		{
			$contentType = $_SERVER["CONTENT_TYPE"];
		}

		if (strpos($contentType, "multipart") !== false)
		{
			if (isset($_FILES['file']['tmp_name']) && is_uploaded_file($_FILES['file']['tmp_name']))
			{
				// Open temp file
				$out = fopen($path . DIRECTORY_SEPARATOR . $fileName, $chunk == 0 ? "wb" : "ab");

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
						//exit('{"jsonrpc" : "2.0", "error" : {"code": 101, "message": "Failed to open input stream."}, "id" : "id"}');
						exit();
					}

					fclose($out);
					unlink($_FILES['file']['tmp_name']);
				}
				else
				{
					//exit('{"jsonrpc" : "2.0", "error" : {"code": 102, "message": "Failed to open output stream."}, "id" : "id"}');
					exit();
				}
			}
			else
			{
				//exit('{"jsonrpc" : "2.0", "error" : {"code": 103, "message": "Failed to move uploaded file."}, "id" : "id"}');
				exit();
			}
		}
		else
		{
			// Open temp file
			$out = fopen($path . DIRECTORY_SEPARATOR . $fileName, $chunk == 0 ? "wb" : "ab");

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
					//exit('{"jsonrpc" : "2.0", "error" : {"code": 101, "message": "Failed to open input stream."}, "id" : "id"}');
					exit();
				}

				fclose($out);
			}
			else
			{
				//exit('{"jsonrpc" : "2.0", "error" : {"code": 102, "message": "Failed to open output stream."}, "id" : "id"}');
				exit();
			}
		}

		// Return JSON-RPC response
		//exit('{"jsonrpc" : "2.0", "result" : {"dirUrl" : "'.$url.'", "fileName" : "'.$fileName.'"}, "id" : "id"}');

		// Can't use $.parseJSON until EE2 gets jQuery 1.4.1+,
		// so for now we'll just spit out the actual file name.
		exit($fileName);
	}

}
