<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @package Custom Tables
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright Copyright (C) 2018-2021. All Rights Reserved
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/

//This will also block the access to internal classes
namespace CustomTables\CustomPHP;

// no direct access
defined('_JEXEC') or die('Restricted access');

use \JoomlaBasicMisc;

class CleanExecute
{
	public static function execute($code,&$error)
	{
		$error = CleanExecute::deleteDangerousFunction($code);
		if($error!=null)
			return null;
			
		$RandomString = JoomlaBasicMisc::generateRandomString(16);
		
		$file=JPATH_SITE.DIRECTORY_SEPARATOR.'tmp'.DIRECTORY_SEPARATOR . 'temp'.$RandomString.'.php';
		
		$function_name = 'customPHP_'.$RandomString;
		
		$prepared_code = '<?php function '.$function_name.'(){echo '.$code.';};'.$function_name.'(); ?>';
		
		file_put_contents($file, $prepared_code);
		
		$output=null;
		$retval=null;
		exec('/usr/bin/php '.$file, $output, $retval);
		
		if($retval !=0 )
		{
			$error = 'Error '.$retval.': '.$code;
			return null;
		}
		
		if(count($output)!=1)
		{
			echo $code;
			print_r($output);	
			die;
		}
		
		unlink($file);
		
		return $output[0];
	}
	
	protected static function deleteDangerousFunction(&$code)
	{
		//https://gist.github.com/mccabe615/b0907514d34b2de088c4996933ea1720
		
		$list_ = 'exec,passthru,system,shell_exec,`,popen,proc_open,pcntl_exec'
		.'assert,eval,preg_replace,create_function,include,include_once,require,require_once,$_GET,$_POST,ReflectionFunction'
		.'invoke,invokeArgs,ob_start,array_diff_uassoc,array_diff_ukey,array_filter,array_intersect_uassoc,array_intersect_ukey,array_map'
		.'array_reduce,array_udiff_assoc,array_udiff_uassoc,array_udiff,array_uintersect_assoc,array_uintersect_uassoc,array_uintersect'
		.'array_walk_recursive,array_walk,assert_options,uasort,uksort,usort,preg_replace_callback,spl_autoload_register,iterator_apply'
		.'call_user_func,call_user_func_array,register_shutdown_function,register_tick_function,set_error_handler,set_exception_handler'
		.'session_set_save_handler,sqlite_create_aggregate,sqlite_create_function,phpinfo,posix_mkfifo,posix_getlogin,posix_ttyname,getenv'
		.'get_current_user,proc_get_status,get_cfg_var,disk_free_space,disk_total_space,diskfreespace,getcwd,getlastmo,getmygid,getmyinode'
		.'getmypid,getmyuid,extract,parse_str,putenv,ini_set,mail,header,proc_nice,proc_terminate,proc_close,pfsockopen,fsockopen,apache_child_terminate'
		.'posix_kill,posix_setpgid,posix_setsid,posix_setuid'
		.'fopen,tmpfile,bzopen,gzopen,SplFileObject,chgrp,chmod,chown,copy,file_put_contents,lchgrp,lchown,link,mkdir,move_uploaded_file'
		.'rename,rmdir,symlink,tempnam,touch,unlink,imagepng,imagewbmp,image2wbmp,imagejpeg,imagexbm,imagegif,imagegd,imagegd2,iptcembed'
		.'ftp_get,ftp_nb_get,file_exists,file_get_contents,file,fileatime,filectime,filegroup,fileinode,filemtime,fileowner,fileperms,filesize'
		.'filetype,glob,is_dir,is_executable,is_file,is_link,is_readable,is_uploaded_file,is_writable,is_writeable,linkinfo,lstat,parse_ini_file'
		.'pathinfo,readfile,readlink,realpath,stat,gzfile,readgzfile,getimagesize,imagecreatefromgif,imagecreatefromjpeg,imagecreatefrompng'
		.'imagecreatefromwbmp,imagecreatefromxbm,imagecreatefromxpm,ftp_put,ftp_nb_put,exif_read_data,read_exif_data,exif_thumbnail,exif_imagetype'
		.'hash_file,hash_hmac_file,hash_update_file,md5_file,sha1_file,highlight_file,show_source,php_strip_whitespace,get_meta_tags';

		$list = explode(',',$list_);

		foreach($list as $l)
		{
			if(stripos($code,$l) !== false)
				return 'The CustomPHP code contains prohibited - potentially dangerous function: "'.$l.'"';
		}

		return null;
	}
}
