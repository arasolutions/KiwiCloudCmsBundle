<?php

namespace AraSolutions\KiwiCloudCmsBundle\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;

class KiwiCloudCmsApi {
	
	/* ---------------------------------------------------------------------
	* Account API Key
	* Set your account API key. You can find this in the "My Account" 
	* section of Kiwi.
	*/
	var $account_api_key;
	

	/* ---------------------------------------------------------------------
	* Feed Caching
	* You can cache feeds from Kiwi to improve your site's performance and 
	* protect you in the event of and Kiwi outage.
	*/
	
	// Choose whether or not to cache feeds.
	var $cache_feeds;

	// Location of your cache folder. Must be writable. (With trailing slash!)
	var $cache_folder;
	
	// How often should the cache be refreshed? (In seconds)
	var $cache_time;

    /* Error log location */
    var $error_log_file;
	
	
	////////////////////////////////////////////////////////////////////////
	// ! Stop Editing !
	////////////////////////////////////////////////////////////////////////
	
	
	/* API location (With Trailing Slash!) */
	var $api_server			=	'http://www.kiwicloudcms.com/api/call/';
	
	/* Did the API return a header of Kiwi-Status: ok  */
	var $kiwi_status_ok	=	'unknown';
	
	/* Var to hold messages */
	var $msg				=	'';
	
	/* How long to wait for Kiwi response (in seconds). After this, cache will be used. */
	var $timeout_length		=	3;
	
	/* Have we experienced an Kiwi timeout ? */
	var $have_timeout		=	false;
	
	/* Headers from the last call */
	var $last_headers		=	array();
	
	/* Write Errors ? */
	var $error_log			=	true;

	// --------------------------------------------------------------------

	function __construct(ContainerBagInterface $container)
	{
        $kiwiCloudCmsConfig = $container->get('kiwi_cloud_cms');

        $this->account_api_key = $kiwiCloudCmsConfig['account']['api_key'];
        $this->cache_feeds = $kiwiCloudCmsConfig['cache']['feeds'];
        $this->cache_folder = $kiwiCloudCmsConfig['cache']['folder'];
        $this->cache_time = $kiwiCloudCmsConfig['cache']['time'];
        $this->error_log_file = $kiwiCloudCmsConfig['log']['file'];

		if($this->cache_feeds)
		{
			// Create Directory
			if( !file_exists($this->cache_folder)) mkdir($this->cache_folder, 0777, true);

			// Make it writable
			if( !is_writable($this->cache_folder)) chmod($this->cache_folder, 0777);
		}
	}
	
	
	// --------------------------------------------------------------------
		
	/**
	 * Fetch Feed
	 * Caching wrapper for read only feeds
	 */
	function call($data = array(), $force_cache = 'default')
	{
        $this->kiwi_status_ok = 'unknown';

        /*
        *	If method wasn't included in the $data array, assume it's "feed"
        */
		$method = isset($data['method']) ? $data['method'] : 'liste';
		
		/*
		*	Caching
		*	If "default" check the local var
		*/
		$cache = $force_cache == 'default' ? $this->cache_feeds : $force_cache;
		if(is_int($force_cache))
		{
			$this->cache_time = $force_cache;
			$cache = true;
		}
		
		/*
		*	Figure out cache location
		*/
		$cache_file = md5(implode(',', $data)).".txt";
		
		// Prefix cach file with section id so we can clear section specific caches
		if(isset($data['obj_id']))
		{
			// Check for section id value in $this->obj_ids array
			$obj_id = isset($this->obj_ids[$data['obj_id']]) ? $this->obj_ids[$data['obj_id']] : $data['obj_id'];
			$cache_file = $obj_id.'-'.$cache_file;
		}
		
		$cache_path = $this->cache_folder.$cache_file;
		
		/* If Kiwi isn't responding during this session, resort to cache */
		if ($this->have_timeout || $this->kiwi_status_ok === false)
		{
			if(@file_exists($cache_path))
			{
				$response = $this->read_cache($cache_path);
			}
			else
			{
				$response = false;
			}
		}
		else if ($cache && @file_exists($cache_path) && (( @filemtime($cache_path) + $this->cache_time ) > time()))
		{
			// We're caching, and cache file is still good. Read the cache!
			$response = $this->read_cache($cache_path);
			
			// If the cache couldn't be read for some reason, start over and ignore cache
			if($response === false)
			{
				return $this->fetch_feed($data, false);
			}
		}
		else
		{
			// Get feed from Kiwi
			$response = $this->call_method($method, $data);
			
			// Is the response valid? If so, write the cache
			if($this->kiwi_status_ok)
			{
				$this->write_cache($cache_path, $response);
			}
			
			// if an error occured, & we have a cache file for this feed revert to cache
			else if(@file_exists($cache_path))
			{
				$response = $this->read_cache($cache_path);
			}
		}
		
		/*
		*	Pass data through prep_data_out, and return.
		*/
		return $this->prep_data_out($response);
	}



    // --------------------------------------------------------------------

    /* ! Core Methods */

    /**
     * Call Method
     * Post data to Kiwi and return response.
     *
     */
    function call_method($method, $data = array())
    {
        /*
        *	Make sure api_key is set
        */
        if( ! isset($data['api_key'])) $data['api_key'] = $this->account_api_key;

        /*
        *	if obj_id is not a number, see if the id is saved in $this->obj_ids
        */
        if(isset($data['obj_id']) && ! is_int($data['obj_id']) && ! ctype_digit($data['obj_id']) && isset($this->obj_ids[$data['obj_id']]))
        {
            $data['obj_id'] = $this->obj_ids[$data['obj_id']];
        }

        /*
        *	Build the POST string
        */
        $post_query = 'methode=' . $method . '&';
        foreach($data as $key => $value) $post_query .= $key.'='.urlencode($value).'&';
        $post_query = trim($post_query, ' &');

        /*
        *	Make the call
        */
        $url = parse_url($this->api_server);
        $host = $url['host'];
        $path = $url['path'];
        $port = $url['scheme'] == 'https' ? '443' : '80';
        $headers = array();
        $flag = false; 	// When headers are done sending
        $response = '';

        /*
        *	This could be done with CURL but we're using fsockopen for compatability in case CURL isn't installed
        */
        $fp = fsockopen($host, $port, $errno, $errstr, $this->timeout_length);
        $fp;
        if($fp)
        {
            $out = "POST $path HTTP/1.0\r\n"
                . "Host: $host\r\n"
                . "Content-Type: application/x-www-form-urlencoded; charset=utf-8\r\n"
                . "Content-length: ". strlen($post_query) ."\r\n"
                . "Connection: Close\r\n\r\n"
                . $post_query . "\r\n";

            if(fwrite($fp, $out) === false) return false;

            stream_set_timeout($fp, $this->timeout_length, 0);
            $info = stream_get_meta_data($fp);
            while ( ! feof($fp) && ! $info['timed_out'])
            {
                $line = fgets($fp, 10240);

                if( ! $flag)
                {
                    if (strlen(trim($line)) == 0)
                    {
                        $flag = true;
                    }
                    else
                    {
                        $header_name = count($headers);
                        $header_value = trim($line);
                        if(strpos($line, ':') !== false)
                        {
                            $header_name = trim(substr($line, 0, strpos($line, ':')));
                            $header_value = trim(substr($line, strpos($line, ':')+1));
                        }
                        $headers[$header_name] = $header_value;
                    }
                }
                else
                {
                    $response .= $line;
                }

                $info = stream_get_meta_data($fp);
            }

            fclose($fp);

            $this->last_headers = $headers;

            if($info['timed_out'])
            {
                $this->log_error('READ TIMEOUT: ['.$this->api_server.'], query: '.$post_query);
                $this->kiwi_status_ok = false;
                $this->have_timeout = true;
                $response = false;
            }
        }
        else
        {
            // Connection time out.
            $this->log_error('CONNECT TIMEOUT: ['.$this->api_server.'], query: '.$post_query);
            $this->kiwi_status_ok = false;
            $this->have_timeout = true;
        }

        /*
        *	Did Kiwi return a status header?
        */
        $this->kiwi_status_ok = (isset($headers['Kiwi-Status']) && $headers['Kiwi-Status'] == 'ok') ? true : false;

        if($this->kiwi_status_ok === false && $this->have_timeout === false)
        {
            $this->log_error('KIWI STATUS "FAIL": ['.$this->api_server.'], query: '.$post_query);
        }

        /*
        *	Return response
        */
        return $response;
    }
	
	
	
	// --------------------------------------------------------------------
		
	/**
	 * Prep data out
	 * Run data through filters before sending out
	 * 
	 */
	function prep_data_out($data, $type = false)
	{
        return json_decode($data);
	}
	
	
	// --------------------------------------------------------------------
		
	/**
	 * Write error log
	 * 
	 */
	function log_error($err)
	{
		$err = '['.date('r').'] - '.$err."\r\n";
		@file_put_contents($this->error_log_file, $err, FILE_APPEND | LOCK_EX);
	}
	

	
	
	// --------------------------------------------------------------------
		
	/* ! Caching */
	
	/**
	 * Caching
	 * 
	 */
	function read_cache($path)
	{
		if( ! file_exists($path)) return false;
		
		$fp = fopen($path, 'r');
		
		if($fp === false)
		{
			return false;
		}
		else
		{
			$response = '';
			while ( ! feof($fp))
			$response .= fread($fp, 4000);
			fclose($fp);
			return $response;
		}
	}
	
	function write_cache($path, $data)
	{
		$fp = @fopen($path, "w+");
		@fwrite($fp, $data);
		@fclose($fp);
	}
	
	
	
	// --------------------------------------------------------------------
		
	/**
	 * Clear cache, and display notice
	 * 
	 */
	function clear_cache()
	{
		$msg = '<div style="margin:40px;padding:20px;background:#efefef;border:1px solid #ccc;width:500px;margin-left:auto;margin-right:auto;text-align:center;">[msg]</div>';
		
		$cache_path = $this->cache_folder;
		$handle = @opendir($cache_path);
		
		if($handle === false)
		{
			echo str_replace('[msg]', 'Could not open cache folder "'.$cache_path.'"', $msg);
			return false;
		}
		
		$filescleared = 0;
		$fileserror = 0;
		while (false !== ($file = readdir($handle))) {
			if ($file != "." && $file != "..") {
				$cleared = @unlink($cache_path.'/'.$file);
				
				if($cleared){
					$filescleared++;
				}else{
					$fileserror++;
				}
			}
		}
		$cleared = "$filescleared files were cleared<br>";
		if($fileserror > 0) $cleared .= "$fileserror files could not be cleared";
		
		echo str_replace('[msg]', $cleared, $msg);
		closedir($handle);
	}
	
	
	
	// --------------------------------------------------------------------
		
	/**
	 * Used for Kiwi pingback notifications to clear cache for a specific
	 * section
	 * 
	 */
	function clear_section_cache($obj_id = false)
	{
		$cache_path = $this->cache_folder;
		$handle = @opendir($cache_path);
		
		if($handle === false) return false;
		
		$filescleared = 0;
		$fileserror = 0;
		while (false !== ($file = readdir($handle))) {
			if ($file != "." && $file != "..") {
				if(substr($file, 0, strlen($obj_id)+1) == $obj_id.'-')
				{
					$cleared = @unlink($cache_path.'/'.$file);
				}
				
				if($cleared){
					$filescleared++;
				}else{
					$fileserror++;
				}
			}
		}
	}
	
	
	// --------------------------------------------------------------------
		
	/**
	 * Save Images
	 * Try to parse images from response, save them locally, then update the
	 * response, replacing image urls with local urls
	 * 
	 */
	function save_images($data)
	{
		// If it's an array, save for each member, recursively.
		if(is_array($data))
		{
			foreach($data as $key => $value) $data[$key] = $this->save_images($value);			
			return $data;
		}
		
		
		// Parse image urls out of the string, save the image, and replace them with new urls
		else if(is_string($data))
		{
			$data = stripslashes($data);
			$match = preg_match_all('#http://(images|photos).kiwi.com/get/[0-9]{2,11}\.?([a-zA-Z0-9_]*)?\.?(jpg|png|gif)?#', $data, $matches, PREG_SET_ORDER);
			if($match)
			{
				foreach($matches as $m)
				{
					$url = $m[0];
					$data = str_replace($url, $this->save_image($url), $data);
				}
			}
			return $data;
		}		
	}
	
	
	// --------------------------------------------------------------------
		
	/**
	 * Save image
	 * 
	 */
	function save_image($url)
	{
		$imgurl = $url;
		$filename = substr($imgurl, strrpos($imgurl, "/") + 1);
		$ds = $this->cache_images_folder.$filename;
		
		if( ! file_exists($ds))
		{
			$ch = curl_init($imgurl);
			$fp = fopen($ds, 'w');
			curl_setopt($ch, CURLOPT_FILE, $fp);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_exec($ch);
			$curl_info =  curl_getinfo($ch);
			curl_close($ch);
			fclose($fp);
		}
		
		return $this->cache_images_uri.$filename;
	}
	
	
	// --------------------------------------------------------------------
		
	/**
	 * Clear image cache
	 * 
	 */
	function clear_image_cache()
	{
		// Images
		if($this->cache_images)
		{
			$msg = '<div style="margin:40px;padding:20px;background:#efefef;border:1px solid #ccc;width:500px;margin-left:auto;margin-right:auto;text-align:center;">[msg]</div>';
		
			$cache_path = $this->cache_images_folder;
			$handle = @opendir($cache_path);
			
			if($handle === false)
			{
				echo str_replace('[msg]', 'Could not open cache folder "'.$cache_path.'"', $msg);
				return;
			}
			
			$filescleared = 0;
			$fileserror = 0;
			while (false !== ($file = readdir($handle))) {
				if ($file != "." && $file != "..") {
					$cleared = @unlink($cache_path.'/'.$file);
					
					if($cleared){
						$filescleared++;
					}else{
						$fileserror++;
					}
				}
			}
			$cleared = "$filescleared images were deleted<br>";
			if($fileserror > 0) $cleared .= "$fileserror images could not be deleted";
			
			echo str_replace('[msg]', $cleared, $msg);
			closedir($handle);
		}
	}
	
	
	
	
	
	
	
	/* ! Write Methods */
	
	// --------------------------------------------------------------------
		
	/**
	 * Create an item
	 * 
	 */
	function create($sect_id, $data)
	{
		$data['obj_id'] = $sect_id;		
		return $this->call_method('create', $data);
	}

	
	
	// --------------------------------------------------------------------
		
	/**
	 * Update an item
	 * 
	 */
	function update($sect_id, $item_id, $data)
	{
		$data['obj_id'] = $sect_id;
		$data['item_id'] = $item_id;
		
		return $this->call_method('update', $data);
	}
	
	
	
	
	// --------------------------------------------------------------------
		
	/**
	 * New Contact
	 * Add a contact to a "contacts" section
	 * 
	 */
	function new_contact($sect_id, $data)
	{
		// If it's not an array, it's probably just an email address
		if( ! is_array($data))
		{
			$data = array('email' => $data);
		}
		$data['obj_id'] = $sect_id;		
		return $this->call_method('new_contact', $data);
	}
	
	
	
	// --------------------------------------------------------------------
		
	/**
	 * Submit a log entry to Kiwi
	 * 
	 */
	function log($data)
	{		
		return $this->call_method('log', $data);
	}
	
	
	// --------------------------------------------------------------------
		
	/**
	 * Upload a photo
	 * 
	 */
	function upload_photo($file_name, $tmp_name, $data = array())
	{
		$post = array_merge(array(
			'api_key' => $this->account_api_key,
			'file_name' => $file_name,
			'Filedata' => "@".$tmp_name
		), $data);
		
		$ch = curl_init();
	    curl_setopt($ch, CURLOPT_HEADER, 0);
	    curl_setopt($ch, CURLOPT_VERBOSE, 0);
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible;)");
	    curl_setopt($ch, CURLOPT_URL, 'http://api.kiwi.com/upload_photo');
	    curl_setopt($ch, CURLOPT_POST, true);
	    curl_setopt($ch, CURLOPT_POSTFIELDS, $post); 
	    $response = curl_exec($ch);
	    
	    return $response;
	}
	

}




/* End of file Kiwi.php */
/* Location: ./application/libraries/kiwi.php */