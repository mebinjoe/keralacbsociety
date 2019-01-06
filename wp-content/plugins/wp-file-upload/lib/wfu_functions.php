<?php

//********************* String Functions ***************************************************************************************************

function wfu_upload_plugin_clean($label) {
	$clean = sanitize_file_name($label);
	if ( WFU_VAR("WFU_SANITIZE_FILENAME_MODE") != "loose" ) {
		$search = array ( '@[^a-zA-Z0-9._]@' );	 
		$replace = array ( '-' );
		$clean =  preg_replace($search, $replace, remove_accents($clean));
	}

	return $clean;
}

function _wildcard_to_preg_preg_replace_callback($matches) {
    global $wfu_preg_replace_callback_var;
    array_push($wfu_preg_replace_callback_var, $matches[0]);
    $key = count($wfu_preg_replace_callback_var) - 1;
    return "[".$key."]";
}

function wfu_upload_plugin_wildcard_to_preg($pattern, $strict = false) {
	global $wfu_preg_replace_callback_var;
	$wfu_preg_replace_callback_var = array();
	$pattern = preg_replace_callback("/\[(.*?)\]/", "_wildcard_to_preg_preg_replace_callback", $pattern);
	if ( !$strict ) $pattern = '/^' . str_replace(array('\*', '\?', '\[', '\]'), array('.*', '.', '[', ']'), preg_quote($pattern)) . '$/is';
	else $pattern = '/^' . str_replace(array('\*', '\?', '\[', '\]'), array('[^.]*', '.', '[', ']'), preg_quote($pattern)) . '$/is';
	foreach ($wfu_preg_replace_callback_var as $key => $match)
		$pattern = str_replace("[".$key."]", $match, $pattern);
	return $pattern;
}

function wfu_upload_plugin_wildcard_to_mysqlregexp($pattern) {
	if ( substr($pattern, 0, 6) == "regex:" ) return str_replace("\\", "\\\\", substr($pattern, 6));
	else return str_replace("\\", "\\\\", '^'.str_replace(array('\*', '\?', '\[', '\]'), array('.*', '.', '[', ']'), preg_quote($pattern)).'$');
}

function wfu_upload_plugin_wildcard_match($pattern, $str, $strict = false) {
	$pattern = wfu_upload_plugin_wildcard_to_preg($pattern, $strict);
	return preg_match($pattern, $str);
}

function wfu_plugin_encode_string($string) {
	$array = unpack('H*', $string);
	return $array[1];

	$array = unpack('C*', $string);
	$new_string = "";	
	for ($i = 1; $i <= count($array); $i ++) {
		$new_string .= sprintf("%02X", $array[$i]);
	}
	return $new_string;
}

function wfu_plugin_decode_string($string) {
	return pack('H*', $string);

	$new_string = "";	
	for ($i = 0; $i < strlen($string); $i += 2 ) {
		$new_string .= sprintf("%c", hexdec(substr($string, $i ,2)));
	}
	return $new_string;
}

function wfu_create_random_string($len) {
	$base = 'ABCDEFGHKLMNOPQRSTWXYZabcdefghjkmnpqrstwxyz123456789';
	$max = strlen($base) - 1;
	$activatecode = '';
	if ( WFU_VAR("WFU_ALTERNATIVE_RANDOMIZER") != "true" )
		mt_srand((double)microtime()*1000000);
	else mt_srand((double)substr(uniqid("", true), 15));
	while (strlen($activatecode) < $len)
		$activatecode .= $base{mt_rand(0, $max)};
	return $activatecode;
}

function wfu_join_strings($delimeter) {
	$arr = func_get_args();
	unset($arr[0]);
	foreach ($arr as $key => $item)
		if ( $item == "" ) unset($arr[$key]);
	return join($delimeter, $arr);
}

function wfu_create_string($size) {
	$piece = str_repeat("0", 1024);
	$str = "";
	$reps = $size / 1024;
	$rem = $size - 1024 * $reps;
	for ( $i = 0; $i < $reps; $i++ ) $str .= $piece;
	$str .= substr($piece, 0, $rem);
	return $str;
}

function wfu_html_output($output) {
	$output = str_replace(array("\r\n", "\r", "\n"), "<br/>", $output);
	return str_replace(array("\t", " "), "&nbsp;", $output);
}

function wfu_sanitize_code($code) {
	return preg_replace("/[^A-Za-z0-9]/", "", $code);
}

function wfu_sanitize_int($code) {
	return preg_replace("/[^0-9+\-]/", "", $code);
}

function wfu_sanitize_tag($code) {
	return preg_replace("/[^A-Za-z0-9_]/", "", $code);
}

function wfu_slash( $value ) {
	if ( is_array( $value ) ) {
		foreach ( $value as $k => $v ) {
			if ( is_array( $v ) ) {
				$value[$k] = wfu_slash( $v );
			}
			else {
				$value[$k] = addslashes( $v );
			}
		}
	}
	else {
		$value = addslashes( $value );
	}

	return $value;
}

function wfu_generate_global_short_token($timeout) {
	$token = wfu_create_random_string(16);
	$expire = time() + (int)$timeout;
	update_option('wfu_gst_'.$token, $expire);
	return $token;
}

function wfu_verify_global_short_token($token) {
	$timeout = get_option('wfu_gst_'.$token);
	if ( $timeout === false ) return false;
	delete_option('wfu_gst_'.$token);
	return ( $timeout > time() );
}

//********************* Array Functions ****************************************************************************************************

function wfu_encode_array_to_string($arr) {
	$arr_str = json_encode($arr);
	$arr_str = wfu_plugin_encode_string($arr_str);
	return $arr_str;
}

function wfu_decode_array_from_string($arr_str) {
	$arr_str = wfu_plugin_decode_string($arr_str);
	$arr = json_decode($arr_str, true);
	return $arr;
}

function wfu_plugin_parse_array($source) {
	$keys = array_keys($source);
	$new_arr = array();
	for ($i = 0; $i < count($keys); $i ++) 
		$new_arr[$keys[$i]] = wp_specialchars_decode($source[$keys[$i]]);
	return $new_arr;
}

function wfu_array_remove_nulls(&$arr) {
	foreach ( $arr as $key => $arri )
		if ( $arri == null )
			array_splice($arr, $key, 1);
}

function wfu_safe_array($arr) {
	return array_map("htmlspecialchars", $arr);
}

function wfu_sanitize($var) {
	$typ = gettype($var);
	if ( $typ == "boolean" || $typ == "integer" || $typ == "double" || $typ == "resource" || $typ == "NULL" )
		return $var;
	elseif ( $typ == "string" )
		return htmlspecialchars($var);
	elseif ( $typ == "array" || $typ == "object" ) {
		foreach ( $var as &$item ) $item = wfu_sanitize($item);
		return $var;
	}
	else
		return $typ;
}

function _wfu_preg_replace_callback_alt($contents, $token) {
	$in_block = false;
	$prev_pos = 0;
	$new_contents = '';
	$ret['items'] = array();
	$ret['tokens'] = array();
	$ii = 0;
	while ( ($pos = strpos($contents, '"', $prev_pos)) !== false ) {
		if ( !$in_block ) {
			$new_contents .= substr($contents, $prev_pos, $pos - $prev_pos + 1);
			$in_block = true;
		}
		else {
			$ret['items'][$ii] = substr($contents, $prev_pos, $pos - $prev_pos);
			$ret['tokens'][$ii] = $token.sprintf('%03d', $ii);
			$new_contents .= $token.sprintf('%03d', $ii).'"';
			$ii ++;
			$in_block = false;
		}
		$prev_pos = $pos + 1;
	}
	if ( $in_block ) {
		$ret['items'][$ii] = substr($contents, $prev_pos);
		$ret['tokens'][$ii] = $token.sprintf('%03d', $ii);
		$new_contents .= $token.sprintf('%03d', $ii).'"';
	}
	else
		$new_contents .= substr($contents, $prev_pos);
	$ret['contents'] = $new_contents;
	return $ret;
}

function wfu_shortcode_string_to_array($shortcode) {
	$i = 0;
	$m1 = array();
	$m2 = array();
	//for some reason preg_replace_callback does not work in all cases, so it has been replaced by a similar custom inline routine
//	$mm = preg_replace_callback('/"([^"]*)"/', function ($matches) use(&$i, &$m1, &$m2) {array_push($m1, $matches[1]); array_push($m2, "attr".$i); return "attr".$i++;}, $shortcode);
	$ret = _wfu_preg_replace_callback_alt($shortcode, "attr");
	$mm = $ret['contents'];
	$m1 = $ret['items'];
	$m2 = $ret['tokens'];
	$arr = explode(" ", $mm);
	$attrs = array();
	foreach ( $arr as $attr ) {
		if ( trim($attr) != "" ) {
			$attr_arr = explode("=", $attr, 2);
			$key = "";
			if ( count($attr_arr) > 0 ) $key = $attr_arr[0];
			$val = "";
			if ( count($attr_arr) > 1 ) $val = $attr_arr[1];
			if ( trim($key) != "" ) $attrs[trim($key)] = str_replace('"', '', $val);
		}
	}
	$attrs2 = str_replace($m2, $m1, $attrs);
	return $attrs2;
}

function wfu_array_sort_function_string_asc($a, $b) {
	return strcmp(strtolower($a), strtolower($b));
}

function wfu_array_sort_function_string_asc_with_id0($a, $b) {
	$cmp = strcmp(strtolower($a["value"]), strtolower($b["value"]));
	if ( $cmp == 0 ) $cmp = ( (int)$a["id0"] < (int)$b["id0"] ? -1 : 1 );
	return $cmp;
}

function wfu_array_sort_function_string_desc($a, $b) {
	return -strcmp(strtolower($a), strtolower($b));
}

function wfu_array_sort_function_string_desc_with_id0($a, $b) {
	$cmp = strcmp(strtolower($a["value"]), strtolower($b["value"]));
	if ( $cmp == 0 ) $cmp = ( (int)$a["id0"] < (int)$b["id0"] ? -1 : 1 );
	return -$cmp;
}

function wfu_array_sort_function_numeric_asc($a, $b) {
	$aa = (double)$a;
	$bb = (double)$b;
	if ( $aa < $bb ) return -1;
	elseif ( $aa > $bb ) return 1;
	else return 0;
}

function wfu_array_sort_function_numeric_asc_with_id0($a, $b) {
	$aa = (double)$a["value"];
	$bb = (double)$b["value"];
	if ( $aa < $bb ) return -1;
	elseif ( $aa > $bb ) return 1;
	elseif ( (int)$a["id0"] < (int)$b["id0"] ) return -1;
	else return 1;
}

function wfu_array_sort_function_numeric_desc($a, $b) {
	$aa = (double)$a;
	$bb = (double)$b;
	if ( $aa > $bb ) return -1;
	elseif ( $aa < $bb ) return 1;
	else return 0;
}

function wfu_array_sort_function_numeric_desc_with_id0($a, $b) {
	$aa = (double)$a["value"];
	$bb = (double)$b["value"];
	if ( $aa > $bb ) return -1;
	elseif ( $aa < $bb ) return 1;
	elseif ( (int)$a["id0"] > (int)$b["id0"] ) return -1;
	else return 1;
}

function wfu_array_sort($array, $on, $order = SORT_ASC, $with_id0 = false) {
    $new_array = array();
    $sortable_array = array();
	
	$pos = strpos($on, ":");
	if ( $pos !== false ) {
		$sorttype = substr($on, $pos + 1);
		if ( $sorttype == "" ) $sorttype = "s";
		$on = substr($on, 0, $pos);
	}
	else $sorttype = "s";

    if (count($array) > 0) {
        foreach ($array as $k => $v) {
            if (is_array($v)) {
                foreach ($v as $k2 => $v2) {
                    if ($k2 == $on) {
                        $sortable_array[$k] = ( $with_id0 ? array( "id0" => $v["id0"], "value" => $v2 ) : $v2 );
                    }
                }
            } else {
                $sortable_array[$k] = $v;
				$with_id0 = false;
            }
        }

		uasort($sortable_array, "wfu_array_sort_function_".( $sorttype == "n" ? "numeric" : "string" )."_".( $order == SORT_ASC ? "asc" : "desc" ).( $with_id0 ? "_with_id0" : "" ));

        foreach ($sortable_array as $k => $v) {
            $new_array[$k] = $array[$k];
        }
    }

    return $new_array;
}

function wfu_echo_array($arr) {
	if ( !is_array($arr) ) return;
	echo '<pre>'.print_r($arr, true).'</pre>';
}

//********************* Plugin Options Functions *******************************************************************************************

function wfu_get_server_environment() {
	$php_env = '';
	if ( PHP_INT_SIZE == 4 ) $php_env = '32bit';
	elseif ( PHP_INT_SIZE == 8 ) $php_env = '64bit';
	else {
		$int = "9223372036854775807";
		$int = intval($int);
		if ($int == 9223372036854775807) $php_env = '64bit';
		elseif ($int == 2147483647) $php_env = '32bit';
	}

	return $php_env;
}

function wfu_ajaxurl() {
	$plugin_options = wfu_decode_plugin_options(get_option( "wordpress_file_upload_options" ));
	return ( $plugin_options['admindomain'] == 'siteurl' || $plugin_options['admindomain'] == '' ? site_url("wp-admin/admin-ajax.php") : ( $plugin_options['admindomain'] == 'adminurl' ? admin_url("admin-ajax.php") : home_url("wp-admin/admin-ajax.php") ) );
}

function wfu_encode_plugin_options($plugin_options) {
	$encoded_options = 'version='.( isset($plugin_options['version']) ? $plugin_options['version'] : "1.0" ).';';
	$encoded_options .= 'shortcode='.( isset($plugin_options['shortcode']) ? wfu_plugin_encode_string($plugin_options['shortcode']) : "" ).';';
	$encoded_options .= 'hashfiles='.( isset($plugin_options['hashfiles']) ? $plugin_options['hashfiles'] : "" ).';';
	$encoded_options .= 'basedir='.( isset($plugin_options['basedir']) ? wfu_plugin_encode_string($plugin_options['basedir']) : "" ).';';
	$encoded_options .= 'postmethod='.( isset($plugin_options['postmethod']) ? $plugin_options['postmethod'] : "" ).';';
	$encoded_options .= 'modsecurity='.( isset($plugin_options['modsecurity']) ? $plugin_options['modsecurity'] : "" ).';';
	$encoded_options .= 'relaxcss='.( isset($plugin_options['relaxcss']) ? $plugin_options['relaxcss'] : "" ).';';
	$encoded_options .= 'admindomain='.( isset($plugin_options['admindomain']) ? $plugin_options['admindomain'] : "" ).';';
	$encoded_options .= 'mediacustom='.( isset($plugin_options['mediacustom']) ? $plugin_options['mediacustom'] : "" ).';';
	$encoded_options .= 'includeotherfiles='.( isset($plugin_options['includeotherfiles']) ? $plugin_options['includeotherfiles'] : "" ).';';
	$encoded_options .= 'captcha_sitekey='.( isset($plugin_options['captcha_sitekey']) ? wfu_plugin_encode_string($plugin_options['captcha_sitekey']) : "" ).';';
	$encoded_options .= 'captcha_secretkey='.( isset($plugin_options['captcha_secretkey']) ? wfu_plugin_encode_string($plugin_options['captcha_secretkey']) : "" ).';';
	$encoded_options .= 'dropbox_accesstoken='.( isset($plugin_options['dropbox_accesstoken']) ? wfu_plugin_encode_string($plugin_options['dropbox_accesstoken']) : "" ).';';
	$encoded_options .= 'dropbox_defaultpath='.( isset($plugin_options['dropbox_defaultpath']) ? wfu_plugin_encode_string($plugin_options['dropbox_defaultpath']) : "" ).';';
	$encoded_options .= 'browser_permissions='.( isset($plugin_options['browser_permissions']) ? wfu_encode_array_to_string($plugin_options['browser_permissions']) : "" );
	return $encoded_options;
}

function wfu_decode_plugin_options($encoded_options) {
	$plugin_options['version'] = "1.0";
	$plugin_options['shortcode'] = "";
	$plugin_options['hashfiles'] = "";
	$plugin_options['basedir'] = "";
	$plugin_options['postmethod'] = "";
	$plugin_options['modsecurity'] = "";
	$plugin_options['relaxcss'] = "";
	$plugin_options['admindomain'] = "";
	$plugin_options['mediacustom'] = "";
	$plugin_options['includeotherfiles'] = "";
	$plugin_options['captcha_sitekey'] = "";
	$plugin_options['captcha_secretkey'] = "";
	$plugin_options['dropbox_accesstoken'] = "";
	$plugin_options['dropbox_defaultpath'] = "";
	$plugin_options['browser_permissions'] = "";

	$decoded_array = explode(';', $encoded_options);
	foreach ($decoded_array as $decoded_item) {
		if ( trim($decoded_item) != "" ) {
			list($item_key, $item_value) = explode("=", $decoded_item, 2);
			if ( $item_key == 'shortcode' || $item_key == 'basedir' || $item_key == 'captcha_sitekey' || $item_key == 'captcha_secretkey' || $item_key == 'dropbox_accesstoken' || $item_key == 'dropbox_defaultpath' )
				$plugin_options[$item_key] = wfu_plugin_decode_string($item_value);
			elseif ( $item_key == 'browser_permissions' )
				$plugin_options[$item_key] = wfu_decode_array_from_string($item_value);
			else
				$plugin_options[$item_key] = $item_value;
		}
	}
	return $plugin_options;
}

function WFU_VAR($varname) {
	if ( !isset($GLOBALS["WFU_GLOBALS"][$varname]) ) return false;
	return $GLOBALS["WFU_GLOBALS"][$varname][3];
}

function wfu_get_plugin_version() {
	$plugin_data = get_plugin_data(WPFILEUPLOAD_PLUGINFILE);
	return $plugin_data['Version'];
}

function wfu_get_latest_version() {
	$postfields = array();
	$postfields['action'] = 'wfuca_check_latest_version_free';
	$postfields['version_hash'] = WFU_VERSION_HASH;
	$url = WFU_VERSION_SERVER_URL;
	$result = wfu_post_request($url, $postfields);
	return $result;
}

function wfu_compare_versions($current, $latest) {
	$ret['status'] = true;
	$ret['custom'] = false;
	$ret['result'] = 'equal';
	$res = preg_match('/^([0-9]*)\.([0-9]*)\.([0-9]*)(.*)/', $current, $cur_data);
	if ( !$res || count($cur_data) < 5 )
		return array( 'status' => false, 'custom' => false, 'result' => 'current version invalid' );
	if ( $cur_data[1] == '' || $cur_data[2] == '' || $cur_data[3] == '' )
		return array( 'status' => false, 'custom' => false, 'result' => 'current version invalid' );
	$custom = ( $cur_data[4] != '' );
	$res = preg_match('/^([0-9]*)\.([0-9]*)\.([0-9]*)/', $latest, $lat_data);
	if ( !$res || count($lat_data) < 4 )
		return array( 'status' => false, 'custom' => $custom, 'result' => 'latest version invalid' );
	if ( $lat_data[1] == '' || $lat_data[2] == '' || $lat_data[3] == '' )
		return array( 'status' => false, 'custom' => $custom, 'result' => 'latest version invalid' );
	if ( intval($cur_data[1]) < intval($lat_data[1]) )
		return array( 'status' => true, 'custom' => $custom, 'result' => 'lower' );
	elseif ( intval($cur_data[1]) > intval($lat_data[1]) )
		return array( 'status' => false, 'custom' => $custom, 'result' => 'current version invalid' );
	if ( intval($cur_data[2]) < intval($lat_data[2]) )
		return array( 'status' => true, 'custom' => $custom, 'result' => 'lower' );
	elseif ( intval($cur_data[2]) > intval($lat_data[2]) )
		return array( 'status' => false, 'custom' => $custom, 'result' => 'current version invalid' );
	if ( intval($cur_data[3]) < intval($lat_data[3]) )
		return array( 'status' => true, 'custom' => $custom, 'result' => 'lower' );
	elseif ( intval($cur_data[3]) > intval($lat_data[3]) )
		return array( 'status' => false, 'custom' => $custom, 'result' => 'current version invalid' );
	return array( 'status' => true, 'custom' => $custom, 'result' => 'equal' );	
}

//********************* File / Directory Functions ************************************************************************************************

function wfu_fileext($basename, $with_dot = false) {
	if ( $with_dot ) return preg_replace("/^.*?(\.[^.]*)?$/", "$1", $basename);
	else return preg_replace("/^.*?(\.([^.]*))?$/", "$2", $basename);
}

function wfu_filename($basename) {
	return preg_replace("/^(.*?)(\.[^.]*)?$/", "$1", $basename);
}

function wfu_basename($path) {
	if ( !$path || $path == "" ) return "";
	return preg_replace('/.*(\\\\|\\/)/', '', $path);
}

function wfu_basedir($path) {
	if ( !$path || $path == "" ) return "";
	return substr($path, 0, strlen($path) - strlen(wfu_basename($path)));
}

function wfu_path_abs2rel($path) {
	$abspath_notrailing_slash = substr(ABSPATH, 0, -1);
//	return ( substr($path, 0, 6) == 'ftp://' || substr($path, 0, 7) == 'ftps://' || substr($path, 0, 7) == 'sftp://' ? $path : str_replace($abspath_notrailing_slash, "", $path) );
	return ( substr($path, 0, 6) == 'ftp://' || substr($path, 0, 7) == 'ftps://' || substr($path, 0, 7) == 'sftp://' ? $path : substr($path, strlen($abspath_notrailing_slash)) );
}

function wfu_path_rel2abs($path) {
	if ( substr($path, 0, 1) == "/" ) $path = substr($path, 1);
	return ( substr($path, 0, 6) == 'ftp://' || substr($path, 0, 7) == 'ftps://' || substr($path, 0, 7) == 'sftp://' ? $path : ABSPATH.$path );
}

function wfu_upload_plugin_full_path( $params ) {
	$path = $params["uploadpath"];
	if ( $params["accessmethod"]=='ftp' && $params["ftpinfo"] != '' && $params["useftpdomain"] == "true" ) {
		$ftpdata_flat =  str_replace(array('\:', '\@'), array('\_', '\_'), $params["ftpinfo"]);
		//remove parent folder symbol (..) in path so that the path does not go outside host
		$ftpdata_flat =  str_replace('..', '', $ftpdata_flat);
		$pos1 = strpos($ftpdata_flat, ":");
		$pos2 = strpos($ftpdata_flat, "@");
		if ( $pos1 && $pos2 && $pos2 > $pos1 ) {
			$ftp_username = substr($params["ftpinfo"], 0, $pos1);
			$ftp_password = substr($params["ftpinfo"], $pos1 + 1, $pos2 - $pos1 - 1);
			$ftp_host = substr($params["ftpinfo"], $pos2 + 1);
			$ftp_username = str_replace('@', '%40', $ftp_username);   //if username contains @ character then convert it to %40
			$ftp_password = str_replace('@', '%40', $ftp_password);   //if password contains @ character then convert it to %40
			$start_folder = 'ftp://'.$ftp_username.':'.$ftp_password."@".$ftp_host.'/';
		}
		else $start_folder = 'ftp://'.$params["ftpinfo"].'/';
	}
	else $start_folder = WP_CONTENT_DIR.'/';
	if ($path) {
		if ( $path == ".." || substr($path, 0, 3) == "../" ) {
			$start_folder = ABSPATH;
			$path = substr($path, 2, strlen($path) - 2);
		}
		//remove additional parent folder symbols (..) in path so that the path does not go outside the $start_folder
		$path =  str_replace('..', '', $path);
		if ( substr($path, 0, 1) == "/" ) $path = substr($path, 1, strlen($path) - 1);
		if ( substr($path, -1, 1) == "/" ) $path = substr($path, 0, strlen($path) - 1);
		$full_upload_path = $start_folder;
		if ( $path != "" ) $full_upload_path .= $path.'/';
	}
	else {
		$full_upload_path = $start_folder;
	}
	return $full_upload_path;
}

function wfu_upload_plugin_directory( $path ) {
	$dirparts = explode("/", $path);
	return $dirparts[count($dirparts) - 1];
}

//function to extract sort information from path, which is stored as [[-sort]] inside the path
function wfu_extract_sortdata_from_path($path) {
	$pos1 = strpos($path, '[[');
	$pos2 = strpos($path, ']]');
	$ret['path'] = $path;
	$ret['sort'] = "";
	if ( $pos1 !== false && $pos2 !== false )
		if ( $pos2 > $pos1 ) {
			$ret['sort'] = substr($path, $pos1 + 2, $pos2 - $pos1 - 2);
			$ret['path'] = str_replace('[['.$ret['sort'].']]', '', $path);
		}
	return $ret;
}

//extract sort information from path and return the flatten path
function wfu_flatten_path($path) {
	$ret = wfu_extract_sortdata_from_path($path);
	return $ret['path'];
}

function wfu_delTree($dir) {
	$files = array_diff(scandir($dir), array('.','..'));
	foreach ($files as $file) {
		is_dir("$dir/$file") ? wfu_delTree("$dir/$file") : unlink("$dir/$file");
	}
	return rmdir($dir);
}

function wfu_getTree($dir) {
	$tree = array();
	$files = array_diff(scandir($dir), array('.','..'));
	foreach ($files as $file) {
		if ( is_dir("$dir/$file") ) array_push($tree, $file);
	}
	return $tree;
}
function wfu_parse_folderlist($subfoldertree) {
	$ret['path'] = array();
	$ret['label'] = array();
	$ret['level'] = array();
	$ret['default'] = array();

	if ( substr($subfoldertree, 0, 4) == "auto" ) return $ret;
	$subfolders = explode(",", $subfoldertree);
	if ( count($subfolders) == 0 ) return $ret;
	if ( count($subfolders) == 1 && trim($subfolders[0]) == "" ) return $ret;
	$dir_levels = array ( "root" );
	$prev_level = 0;
	$level0_count = 0;
	$default = -1;
	foreach ($subfolders as $subfolder) {
		$subfolder = trim($subfolder);			
		$star_count = 0;
		$start_spaces = "";
		$is_default = false;
		//check for folder level
		while ( $star_count < strlen($subfolder) ) {
			if ( substr($subfolder, $star_count, 1) == "*" ) {
				$star_count ++;
				$start_spaces .= "&nbsp;&nbsp;&nbsp;";
			}
			else break;
		}
		if ( $star_count - $prev_level <= 1 && ( $star_count > 0 || $level0_count == 0 ) ) {
			$subfolder = substr($subfolder, $star_count, strlen($subfolder) - $star_count);
			// check for default value
			if ( substr($subfolder, 0, 1) == '&' ) {
				$subfolder = substr($subfolder, 1);
				$is_default = true;
			}
			//split item in folder path and folder name
			$subfolder_items = explode('/', $subfolder);
			if ( count($subfolder_items) > 1 && $subfolder_items[1] != "" ) {
				$subfolder_dir = $subfolder_items[0];
				$subfolder_label = $subfolder_items[1];
			}
			else {
				$subfolder_dir = $subfolder;
				$subfolder_label = $subfolder;
			}
			if ( $subfolder_dir != "" ) {
				// set is_default flag to true only for the first default item
				if ( $is_default && $default == -1 ) $default = count($ret['path']);
				else $is_default = false;
				// set flag that root folder has been included (so that it is not included it again)
				if ( $star_count == 0 ) $level0_count = 1;
				if ( count($dir_levels) > $star_count ) $dir_levels[$star_count] = $subfolder_dir;
				else array_push($dir_levels, $subfolder_dir);
				$subfolder_path = "";
				for ( $i_count = 1; $i_count <= $star_count; $i_count++) {
					$subfolder_path .= $dir_levels[$i_count].'/';
				}
				array_push($ret['path'], $subfolder_path);
				array_push($ret['label'], $subfolder_label);
				array_push($ret['level'], $star_count);
				array_push($ret['default'], $is_default);
				$prev_level = $star_count;
			}
		}
	}

	return $ret;
}

function wfu_filesize($filepath) {
	$fp = fopen($filepath, 'r');
	$pos = 0;
	if ($fp) {
		$size = 1073741824;
		fseek($fp, 0, SEEK_SET);
		while ($size > 1) {
			fseek($fp, $size, SEEK_CUR);
			if (fgetc($fp) === false) {
				fseek($fp, -$size, SEEK_CUR);
				$size = (int)($size / 2);
			}
			else {
				fseek($fp, -1, SEEK_CUR);
				$pos += $size;
			}
		}
		while (fgetc($fp) !== false)  $pos++;
		fclose($fp);
	}

    return $pos;
}

function wfu_filesize2($filepath) {
    $fp = fopen($filepath, 'r');
    $return = false;
    if (is_resource($fp)) {
      if (PHP_INT_SIZE < 8) {
        // 32bit
        if (0 === fseek($fp, 0, SEEK_END)) {
          $return = 0.0;
          $step = 0x7FFFFFFF;
          while ($step > 0) {
            if (0 === fseek($fp, - $step, SEEK_CUR)) {
              $return += floatval($step);
            } else {
              $step >>= 1;
            }
          }
        }
      } elseif (0 === fseek($fp, 0, SEEK_END)) {
        // 64bit
        $return = ftell($fp);
      }
      fclose($fp);
    }
    return $return;
}

function wfu_fseek($fp, $pos, $first = 1) {
	// set to 0 pos initially, one-time
	if ( $first ) fseek($fp, 0, SEEK_SET);

	// get pos float value
	$pos = floatval($pos);

	// within limits, use normal fseek
	if ( $pos <= PHP_INT_MAX )
		fseek($fp, $pos, SEEK_CUR);
	// out of limits, use recursive fseek
	else {
		fseek($fp, PHP_INT_MAX, SEEK_CUR);
		$pos -= PHP_INT_MAX;
		wfu_fseek($fp, $pos, 0);
	}
}

function wfu_fseek2($fp, $pos) {
	$pos = floatval($pos);
	if ( $pos <= PHP_INT_MAX ) {
		return fseek($fp, $pos, SEEK_SET);
	}
	else {
		$fsize = wfu_filesize2($filepath);
		$opp = $fsize - $pos;
		if ( 0 === ($ans = fseek($fp, 0, SEEK_END)) ) {
			$maxstep = 0x7FFFFFFF;
			$step = $opp;
			if ( $step > $maxstep ) $step = $maxstep;
			while ($step > 0) {
				if ( 0 === ($ans = fseek($fp, - $step, SEEK_CUR)) ) {
					$opp -= floatval($step);
				}
				else {
					$maxstep >>= 1;
				}
				$step = $opp;
				if ( $step > $maxstep ) $step = $maxstep;
			}
		}
	}
	return $ans;
}

function wfu_debug_log($message) {
	$logpath = WP_CONTENT_DIR.'/debug_log.txt';
	file_put_contents($logpath, $message, FILE_APPEND);
}

function wfu_safe_store_filepath($path) {
	$code = wfu_create_random_string(16);
	$_SESSION['wfu_filepath_safe_storage'][$code] = $path;
	return $code;
}

function wfu_get_filepath_from_safe($code) {
	//sanitize $code
	$code = wfu_sanitize_code($code);
	if ( $code == "" ) return false;
	//return filepath from session variable, if exists
	if ( !isset($_SESSION['wfu_filepath_safe_storage'][$code]) ) return false;
	return $_SESSION['wfu_filepath_safe_storage'][$code];
}

function wfu_file_extension_restricted($filename) {
	return ( 
		substr($filename, -4) == ".php" ||
		substr($filename, -3) == ".js" ||
		substr($filename, -4) == ".pht" ||
		substr($filename, -5) == ".php3" ||
		substr($filename, -5) == ".php4" ||
		substr($filename, -5) == ".php5" ||
		substr($filename, -6) == ".phtml" ||
		substr($filename, -4) == ".htm" ||
		substr($filename, -5) == ".html" ||
		substr($filename, -9) == ".htaccess" ||
		strpos($filename, ".php.") !== false ||
		strpos($filename, ".js.") !== false ||
		strpos($filename, ".pht.") !== false ||
		strpos($filename, ".php3.") !== false ||
		strpos($filename, ".php4.") !== false ||
		strpos($filename, ".php5.") !== false ||
		strpos($filename, ".phtml.") !== false ||
		strpos($filename, ".htm.") !== false ||
		strpos($filename, ".html.") !== false ||
		strpos($filename, ".htaccess.") !== false
	);
}

function wfu_human_filesize($size, $unit = "") {
	if ( ( !$unit && $size >= 1<<30 ) || $unit == "GB" )
		return number_format($size / (1<<30), 2)."GB";
	if( ( !$unit && $size >= 1<<20 ) || $unit == "MB" )
		return number_format($size / (1<<20), 2)."MB";
	if( ( !$unit && $size >= 1<<10 ) || $unit == "KB" )
		return number_format($size / (1<<10), 2)."KB";
	return number_format($size)." bytes";
}

//********************* User Functions *****************************************************************************************************

function wfu_get_user_role($user, $param_roles) {
	$result_role = 'nomatch';
	if ( !empty( $user->roles ) && is_array( $user->roles ) ) {
		/* Go through the array of the roles of the current user */
		foreach ( $user->roles as $user_role ) {
			$user_role = strtolower($user_role);
			/* if this role matches to the roles in $param_roles or it is administrator or $param_roles allow all roles then it is approved */
			if ( in_array($user_role, $param_roles) || $user_role == 'administrator' || in_array('all', $param_roles) ) {
				/*  We approve this role of the user and exit */
				$result_role = $user_role;
				break;
			}
		}
	}
	/*  if the user has no roles (guest) and guests are allowed, then it is approved */
	elseif ( in_array('guests', $param_roles) ) {
		$result_role = 'guest';
	}
	return $result_role;		
}

function wfu_get_user_valid_role_names($user) {
	global $wp_roles;
	
	$result_roles = array();
	if ( !empty( $user->roles ) && is_array( $user->roles ) ) {
		/* get all valid roles */
		$roles = $wp_roles->get_names();
		/* Go through the array of the roles of the current user */
		foreach ( $user->roles as $user_role ) {
			$user_role = strtolower($user_role);
			/* If one role of the current user matches to the roles allowed to upload */
			if ( in_array($user_role, array_keys($roles)) ) array_push($result_roles, $user_role);
		}
	}

	return $result_roles;		
}

//*********************** DB Functions *****************************************************************************************************

//log action to database
function wfu_log_action($action, $filepath, $userid, $uploadid, $pageid, $blogid, $sid, $userdata) {
	global $wpdb;
	$table_name1 = $wpdb->prefix . "wfu_log";
	$table_name2 = $wpdb->prefix . "wfu_userdata";
	$plugin_options = wfu_decode_plugin_options(get_option( "wordpress_file_upload_options" ));

	if ( !file_exists($filepath) && substr($action, 0, 5) != 'other' ) return;
	$parts = pathinfo($filepath);
	$relativepath = wfu_path_abs2rel($filepath);
//	if ( substr($relativepath, 0, 1) != '/' ) $relativepath = '/'.$relativepath;
	
	$retid = 0;
	if ( $action == 'upload' || $action == 'include' ) {
		// calculate and store file hash if this setting is enabled from Settings
		$filehash = '';
		if ( $plugin_options['hashfiles'] == '1' ) $filehash = md5_file($filepath);
		// calculate file size
		$filesize = filesize($filepath);
		// first make obsolete records having the same file path because the old file has been replaced
		$wpdb->update($table_name1,
			array( 'date_to' => date('Y-m-d H:i:s') ),
			array( 'filepath' => $relativepath ),
			array( '%s'),
			array( '%s')
		);
		// attempt to create new log record
		$now_date = date('Y-m-d H:i:s');
		if ( $wpdb->insert($table_name1,
			array(
				'userid' 	=> $userid,
				'uploaduserid' 	=> $userid,
				'uploadtime' 	=> time(),
				'sessionid' => session_id(),
				'filepath' 	=> $relativepath,
				'filehash' 	=> $filehash,
				'filesize' 	=> $filesize,
				'uploadid' 	=> $uploadid,
				'pageid' 	=> $pageid,
				'blogid' 	=> $blogid,
				'sid' 		=> $sid,
				'date_from' 	=> $now_date,
				'date_to' 	=> 0,
				'action' 	=> $action
			),
			array(
				'%d',
				'%d',
				'%d',
				'%s',
				'%s',
				'%s',
				'%d',
				'%s',
				'%d',
				'%d',
				'%s',
				'%s',
				'%s',
				'%s'
			)) !== false ) {
			$retid = $wpdb->insert_id;
			// if new log record has been created, also create user data records
			if ( $userdata != null && $uploadid != '' ) {
				foreach ( $userdata as $userdata_key => $userdata_field ) {
					$existing = $wpdb->get_row('SELECT * FROM '.$table_name2.' WHERE uploadid = \''.$uploadid.'\' AND property = \''.$userdata_field['label'].'\' AND date_to = 0');
					if ($existing == null)
						$wpdb->insert($table_name2,
							array(
								'uploadid' 	=> $uploadid,
								'property' 	=> $userdata_field['label'],
								'propkey' 	=> $userdata_key,
								'propvalue' 	=> $userdata_field['value'],
								'date_from' 	=> $now_date,
								'date_to' 	=> 0
							),
							array(
								'%s',
								'%s',
								'%d',
								'%s',
								'%s',
								'%s'
							)
						);
				}
			}
		}
	}
	//for rename action the $action variable is of the form: $action = 'rename:'.$newfilepath; in order to pass the new file path
	elseif ( substr($action, 0, 6) == 'rename' ) {
		//get new filepath
		$newfilepath = substr($action, 7);
		$relativepath = wfu_path_abs2rel($newfilepath);
//		if ( substr($relativepath, 0, 1) != '/' ) $relativepath = '/'.$relativepath;
		//get stored file data from database without user data
		$filerec = wfu_get_file_rec($filepath, false);
		//log action only if there are previous stored file data
		if ( $filerec != null ) {
			$now_date = date('Y-m-d H:i:s');
			//make previous record obsolete
			$wpdb->update($table_name1,
				array( 'date_to' => $now_date ),
				array( 'idlog' => $filerec->idlog ),
				array( '%s' ),
				array( '%d' )
			);
			//insert new rename record
			if ( $wpdb->insert($table_name1,
				array(
					'userid' 	=> $userid,
					'uploaduserid' 	=> $filerec->uploaduserid,
					'uploadtime' 	=> $filerec->uploadtime,
					'sessionid' => $filerec->sessionid,
					'filepath' 	=> $relativepath,
					'filehash' 	=> $filerec->filehash,
					'filesize' 	=> $filerec->filesize,
					'uploadid' 	=> $filerec->uploadid,
					'pageid' 	=> $filerec->pageid,
					'blogid' 	=> $filerec->blogid,
					'sid' 		=> $filerec->sid,
					'date_from' 	=> $now_date,
					'date_to' 	=> 0,
					'action' 	=> 'rename',
					'linkedto' 	=> $filerec->idlog
				),
				array( '%d', '%d', '%d', '%s', '%s', '%s', '%d', '%s', '%d', '%d', '%s', '%s', '%s', '%s', '%d' ) ) !== false )
				$retid = $wpdb->insert_id;
		}
	}
	elseif ( $action == 'delete' ) {
		//get stored file data from database without user data
		$filerec = wfu_get_file_rec($filepath, false);
		//log action only if there are previous stored file data
		if ( $filerec != null ) {
			$now_date = date('Y-m-d H:i:s');
			//make previous record obsolete
			$wpdb->update($table_name1,
				array( 'date_to' => $now_date ),
				array( 'idlog' => $filerec->idlog ),
				array( '%s' ),
				array( '%d' )
			);
			//insert new delete record
			if ( $wpdb->insert($table_name1,
				array(
					'userid' 	=> $userid,
					'uploaduserid' 	=> $filerec->uploaduserid,
					'uploadtime' 	=> $filerec->uploadtime,
					'sessionid' => $filerec->sessionid,
					'filepath' 	=> $filerec->filepath,
					'filehash' 	=> $filerec->filehash,
					'filesize' 	=> $filerec->filesize,
					'uploadid' 	=> $filerec->uploadid,
					'pageid' 	=> $filerec->pageid,
					'blogid' 	=> $filerec->blogid,
					'sid' 		=> $filerec->sid,
					'date_from' 	=> $now_date,
					'date_to' 	=> $now_date,
					'action' 	=> 'delete',
					'linkedto' 	=> $filerec->idlog
				),
				array( '%d', '%d', '%d', '%s', '%s', '%s', '%d', '%s', '%d', '%d', '%s', '%s', '%s', '%s', '%d' )) != false )
				$retid = $wpdb->insert_id;
		}
	}
	elseif ( $action == 'download' ) {
		//get stored file data from database without user data
		$filerec = wfu_get_file_rec($filepath, false);
		//log action only if there are previous stored file data
		if ( $filerec != null ) {
			$now_date = date('Y-m-d H:i:s');
			//make previous record obsolete
			$wpdb->update($table_name1,
				array( 'date_to' => $now_date ),
				array( 'idlog' => $filerec->idlog ),
				array( '%s' ),
				array( '%d' )
			);
			//insert new download record
			if ( $wpdb->insert($table_name1,
				array(
					'userid' 	=> $userid,
					'uploaduserid' 	=> $filerec->uploaduserid,
					'uploadtime' 	=> $filerec->uploadtime,
					'sessionid' => $filerec->sessionid,
					'filepath' 	=> $filerec->filepath,
					'filehash' 	=> $filerec->filehash,
					'filesize' 	=> $filerec->filesize,
					'uploadid' 	=> $filerec->uploadid,
					'pageid' 	=> $filerec->pageid,
					'blogid' 	=> $filerec->blogid,
					'sid' 		=> $filerec->sid,
					'date_from' 	=> $now_date,
					'date_to' 	=> 0,
					'action' 	=> 'download',
					'linkedto' 	=> $filerec->idlog
				),
				array( '%d', '%d', '%d', '%s', '%s', '%s', '%d', '%s', '%d', '%d', '%s', '%s', '%s', '%s', '%d' )) != false )
				$retid = $wpdb->insert_id;
		}
	}
	//for modify action the $action variable is of the form: $action = 'modify:'.$now_date; in order to pass the exact modify date
	elseif ( substr($action, 0, 6) == 'modify' ) {
		$now_date = substr($action, 7);
		//get stored file data from database without user data
		$filerec = wfu_get_file_rec($filepath, false);
		//log action only if there are previous stored file data
		if ( $filerec != null ) {
			//make previous record obsolete
			$wpdb->update($table_name1,
				array( 'date_to' => $now_date ),
				array( 'idlog' => $filerec->idlog ),
				array( '%s' ),
				array( '%d' )
			);
			//insert new modify record
			if ( $wpdb->insert($table_name1,
				array(
					'userid' 	=> $userid,
					'uploaduserid' 	=> $filerec->uploaduserid,
					'uploadtime' 	=> $filerec->uploadtime,
					'sessionid' => $filerec->sessionid,
					'filepath' 	=> $filerec->filepath,
					'filehash' 	=> $filerec->filehash,
					'filesize' 	=> $filerec->filesize,
					'uploadid' 	=> $filerec->uploadid,
					'pageid' 	=> $filerec->pageid,
					'blogid' 	=> $filerec->blogid,
					'sid' 		=> $filerec->sid,
					'date_from' 	=> $now_date,
					'date_to' 	=> 0,
					'action' 	=> 'modify',
					'linkedto' 	=> $filerec->idlog
				),
				array( '%d', '%d', '%d', '%s', '%s', '%s', '%d', '%s', '%d', '%d', '%s', '%s', '%s', '%s', '%d' )) != false )
				$retid = $wpdb->insert_id;
		}
	}
	elseif ( substr($action, 0, 5) == 'other' ) {
		$info = substr($action, 6);
		$now_date = date('Y-m-d H:i:s');
		//insert new other type record
		if ( $wpdb->insert($table_name1,
			array(
				'userid' 	=> $userid,
				'uploaduserid' 	=> -1,
				'uploadtime' 	=> 0,
				'sessionid'	=> '',
				'filepath' 	=> $info,
				'filehash' 	=> '',
				'filesize' 	=> 0,
				'uploadid' 	=> '',
				'pageid' 	=> 0,
				'blogid' 	=> 0,
				'sid' 		=> '',
				'date_from' 	=> $now_date,
				'date_to' 	=> $now_date,
				'action' 	=> 'other',
				'linkedto' 	=> -1
			),
			array( '%d', '%d', '%d', '%s', '%s', '%s', '%d', '%s', '%d', '%d', '%s', '%s', '%s', '%s', '%d' )) != false )
			$retid = $wpdb->insert_id;
	}
	return $retid;
}

//revert previously saved action
function wfu_revert_log_action($idlog) {
	global $wpdb;
	$table_name1 = $wpdb->prefix . "wfu_log";

	$filerec = $wpdb->get_row('SELECT * FROM '.$table_name1.' WHERE idlog = '.$idlog);
	if ( $filerec != null ) {
		$prevfilerec = $wpdb->get_row('SELECT * FROM '.$table_name1.' WHERE idlog = '.$filerec->idlog);
		if ( $prevfilerec != null ) {
			$wpdb->update($table_name1,
				array( 'date_to' => date('Y-m-d H:i:s') ),
				array( 'idlog' => $filerec->idlog ),
				array( '%s' ),
				array( '%d' )
			);
			$wpdb->update($table_name1,
				array( 'date_to' => 0 ),
				array( 'idlog' => $prevfilerec->idlog ),
				array( '%s' ),
				array( '%d' )
			);
		}
	}
}

//find user by its id and return a non-empty username
function wfu_get_username_by_id($id) {
	$user = get_user_by('id', $id);
	if ( $user == false && $id > 0 ) $username = 'unknown';
	elseif ( $user == false && $id == -999 ) $username = 'system';
	elseif ( $user == false ) $username = 'guest';
	else $username = $user->user_login;
	return $username;
}

//get the most current database record for file $filepath and also include any userdata if $include_userdata is true
function wfu_get_file_rec($filepath, $include_userdata) {
	global $wpdb;
	$table_name1 = $wpdb->prefix . "wfu_log";
	$table_name2 = $wpdb->prefix . "wfu_userdata";
	$plugin_options = wfu_decode_plugin_options(get_option( "wordpress_file_upload_options" ));

	if ( !file_exists($filepath) ) return null;

	$relativepath = wfu_path_abs2rel($filepath);
//	if ( substr($relativepath, 0, 1) != '/' ) $relativepath = '/'.$relativepath;
	//if file hash is enabled, then search file based on its path and hash, otherwise find file based on its path and size
	if ( isset($plugin_options['hashfiles']) && $plugin_options['hashfiles'] == '1' ) {
		$filehash = md5_file($filepath);
		$filerec = $wpdb->get_row('SELECT * FROM '.$table_name1.' WHERE filepath = \''.$relativepath.'\' AND filehash = \''.$filehash.'\' AND date_to = 0 ORDER BY date_from DESC');
	}
	else {
		$stat = stat($filepath);
		$filerec = $wpdb->get_row('SELECT * FROM '.$table_name1.' WHERE filepath = \''.$relativepath.'\' AND filesize = '.$stat['size'].' AND date_to = 0 ORDER BY date_from DESC');
	}
	//get user data
	if ( $filerec != null && $include_userdata ) {
		$filerec->userdata = null;
		if ( $filerec->uploadid != '' ) {
			$filerec->userdata = $wpdb->get_results('SELECT * FROM '.$table_name2.' WHERE uploadid = \''.$filerec->uploadid.'\' AND date_to = 0');
		}
	}
	return $filerec;
}

//reassign file hashes for all valid files in the database
function wfu_reassign_hashes() {
	global $wpdb;
	$table_name1 = $wpdb->prefix . "wfu_log";
	$plugin_options = wfu_decode_plugin_options(get_option( "wordpress_file_upload_options" ));
	if ( $plugin_options['hashfiles'] == '1' ) {
		$filerecs = $wpdb->get_results('SELECT * FROM '.$table_name1.' WHERE filehash = \'\' AND date_to = 0');
		foreach( $filerecs as $filerec ) {
			//calculate full file path
			$filepath = wfu_path_rel2abs($filerec->filepath);
			if ( file_exists($filepath) ) {
				$filehash = md5_file($filepath);
				$wpdb->update($table_name1,
					array( 'filehash' => $filehash ),
					array( 'idlog' => $filerec->idlog ),
					array( '%s' ),
					array( '%d' )
				);
			}
		}
	}
}

//update database to reflect the current status of files
function wfu_sync_database() {
	global $wpdb;
	$table_name1 = $wpdb->prefix . "wfu_log";
	$plugin_options = wfu_decode_plugin_options(get_option( "wordpress_file_upload_options" ));

	$filerecs = $wpdb->get_results('SELECT * FROM '.$table_name1.' WHERE action <> \'other\' AND date_to = 0');
	$obsolete_count = 0;
	foreach( $filerecs as $filerec ) {
		$obsolete = true;
		//calculate full file path
		$filepath = wfu_path_rel2abs($filerec->filepath);
		if ( file_exists($filepath) ) {
			if ( $plugin_options['hashfiles'] == '1' ) {
				$filehash = md5_file($filepath);
				if ( $filehash == $filerec->filehash ) $obsolete = false;
			}
			else {
				$filesize = filesize($filepath);
				if ( $filesize == $filerec->filesize ) $obsolete = false;
			}
		}
		if ( $obsolete ) {
			$now_date = date('Y-m-d H:i:s');
			//make previous record obsolete
			$wpdb->update($table_name1,
				array( 'date_to' => $now_date ),
				array( 'idlog' => $filerec->idlog ),
				array( '%s' ),
				array( '%d' )
			);
			$obsolete_count ++;
		}
	}
	return $obsolete_count;
}

function wfu_get_recs_of_user($userid) {
	global $wpdb;
	$table_name1 = $wpdb->prefix . "wfu_log";
	$table_name2 = $wpdb->prefix . "wfu_userdata";
	$plugin_options = wfu_decode_plugin_options(get_option( "wordpress_file_upload_options" ));

	//if $userid starts with 'guest' then retrieval of records is done using sessionid and uploaduserid is zero (for guests)
	if ( substr($userid, 0, 5) == 'guest' )
		$filerecs = $wpdb->get_results('SELECT * FROM '.$table_name1.' WHERE action <> \'other\' AND uploaduserid = 0 AND sessionid = \''.substr($userid, 5).'\' AND date_to = 0');
	else
		$filerecs = $wpdb->get_results('SELECT * FROM '.$table_name1.' WHERE action <> \'other\' AND uploaduserid = '.$userid.' AND date_to = 0');
	$out = array();
	foreach( $filerecs as $filerec ) {
		$obsolete = true;
		//calculate full file path
		$filepath = wfu_path_rel2abs($filerec->filepath);
		if ( file_exists($filepath) ) {
			if ( $plugin_options['hashfiles'] == '1' ) {
				$filehash = md5_file($filepath);
				if ( $filehash == $filerec->filehash ) $obsolete = false;
			}
			else {
				$filesize = filesize($filepath);
				if ( $filesize == $filerec->filesize ) $obsolete = false;
			}
		}
		if ( $obsolete ) {
			$now_date = date('Y-m-d H:i:s');
			//make previous record obsolete
			$wpdb->update($table_name1,
				array( 'date_to' => $now_date ),
				array( 'idlog' => $filerec->idlog ),
				array( '%s' ),
				array( '%d' )
			);
		}
		else {
			$filerec->userdata = null;
			if ( $filerec->uploadid != '' ) 
				$filerec->userdata = $wpdb->get_results('SELECT * FROM '.$table_name2.' WHERE uploadid = \''.$filerec->uploadid.'\' AND date_to = 0');
			array_push($out, $filerec);
		}
	}
	
	return $out;
}

function wfu_get_filtered_recs($filter) {
	global $wpdb;
	$table_name1 = $wpdb->prefix . "wfu_log";
	$table_name2 = $wpdb->prefix . "wfu_userdata";
	$plugin_options = wfu_decode_plugin_options(get_option( "wordpress_file_upload_options" ));

	$queries = array();
	// add default filters
	array_push($queries, 'action <> \'other\'');
	array_push($queries, 'date_to = 0');
	// construct user filter
	if ( isset($filter['user']) ) {
		if ( $filter['user']['all'] ) {
			if ( $filter['user']['guests'] ) $query = 'uploaduserid >= 0';
			else $query = 'uploaduserid > 0';
		}
		elseif ( count($filter['user']['ids']) == 1 && substr($filter['user']['ids'][0], 0, 5) == 'guest' )
			$query = 'uploaduserid = 0 AND sessionid = \''.substr($filter['user']['ids'][0], 5).'\'';
		else {
			if ( $filter['user']['guests'] ) array_push($filter['user']['ids'], '0');
			if ( count($filter['user']['ids']) == 1 ) $query = 'uploaduserid = '.$filter['user']['ids'][0];
			else $query = 'uploaduserid in ('.implode(",",$filter['user']['ids']).')';
		}
		array_push($queries, $query);
	}
	// construct size filter
	if ( isset($filter['size']) ) {
		if ( isset($filter['size']['lower']) && isset($filter['size']['upper']) )
			$query = 'filesize > '.$filter['size']['lower'].' AND filesize < '.$filter['size']['upper'];
		elseif ( isset($filter['size']['lower']) ) $query = 'filesize > '.$filter['size']['lower'];
		else $query = 'filesize < '.$filter['size']['upper'];
		array_push($queries, $query);
	}
	// construct date filter
	if ( isset($filter['date']) ) {
		if ( isset($filter['date']['lower']) && isset($filter['date']['upper']) )
			$query = 'uploadtime > '.$filter['date']['lower'].' AND uploadtime < '.$filter['date']['upper'];
		elseif ( isset($filter['date']['lower']) ) $query = 'uploadtime > '.$filter['date']['lower'];
		else $query = 'uploadtime < '.$filter['date']['upper'];
		array_push($queries, $query);
	}
	// construct file pattern filter
	if ( isset($filter['pattern']) ) {
		$query = 'filepath REGEXP \''.wfu_upload_plugin_wildcard_to_mysqlregexp($filter['pattern']).'\'';
		array_push($queries, $query);
	}
	// construct page/post filter
	if ( isset($filter['post']) ) {
		if ( count($filter['post']['ids']) == 1 ) $query = 'pageid = '.$filter['post']['ids'][0];
			else $query = 'pageid in ('.implode(",",$filter['post']['ids']).')';
		array_push($queries, $query);
	}
	// construct blog filter
	if ( isset($filter['blog']) ) {
		if ( count($filter['blog']['ids']) == 1 ) $query = 'blogid = '.$filter['blog']['ids'][0];
			else $query = 'blogid in ('.implode(",",$filter['blog']['ids']).')';
		array_push($queries, $query);
	}
	// construct userdata filter
	if ( isset($filter['userdata']) ) {
		if ( $filter['userdata']['criterion'] == "equal to" ) $valuecriterion = 'propvalue = \''.$filter['userdata']['value'].'\'';
		elseif ( $filter['userdata']['criterion'] == "starts with" ) $valuecriterion = 'propvalue LIKE \''.$filter['userdata']['value'].'%\'';
		elseif ( $filter['userdata']['criterion'] == "ends with" ) $valuecriterion = 'propvalue LIKE \'%'.$filter['userdata']['value'].'\'';
		elseif ( $filter['userdata']['criterion'] == "contains" ) $valuecriterion = 'propvalue LIKE \'%'.$filter['userdata']['value'].'%\'';
		elseif ( $filter['userdata']['criterion'] == "not equal to" ) $valuecriterion = 'propvalue <> \''.$filter['userdata']['value'].'\'';
		elseif ( $filter['userdata']['criterion'] == "does not start with" ) $valuecriterion = 'propvalue NOT LIKE \''.$filter['userdata']['value'].'%\'';
		elseif ( $filter['userdata']['criterion'] == "does not end with" ) $valuecriterion = 'propvalue NOT LIKE \'%'.$filter['userdata']['value'].'\'';
		elseif ( $filter['userdata']['criterion'] == "does not contain" ) $valuecriterion = 'propvalue NOT LIKE \'%'.$filter['userdata']['value'].'%\'';
		else $valuecriterion = 'propvalue = \''.$filter['userdata']['value'].'\'';
		$query = 'uploadid in (SELECT DISTINCT uploadid FROM '.$table_name2.' WHERE date_to = 0 AND property = \''.$filter['userdata']['field'] .'\' AND '.$valuecriterion.')';
		array_push($queries, $query);
	}
	
	$filerecs = $wpdb->get_results('SELECT * FROM '.$table_name1.' WHERE '.implode(' AND ', $queries));
	$out = array();
	foreach( $filerecs as $filerec ) {
		$obsolete = true;
		//calculate full file path
		$filepath = wfu_path_rel2abs($filerec->filepath);
		if ( file_exists($filepath) ) {
			if ( $plugin_options['hashfiles'] == '1' ) {
				$filehash = md5_file($filepath);
				if ( $filehash == $filerec->filehash ) $obsolete = false;
			}
			else {
				$filesize = filesize($filepath);
				if ( $filesize == $filerec->filesize ) $obsolete = false;
			}
		}
		if ( $obsolete ) {
			$now_date = date('Y-m-d H:i:s');
			//make previous record obsolete
			$wpdb->update($table_name1,
				array( 'date_to' => $now_date ),
				array( 'idlog' => $filerec->idlog ),
				array( '%s' ),
				array( '%d' )
			);
		}
		else {
			$filerec->userdata = null;
			if ( $filerec->uploadid != '' ) 
				$filerec->userdata = $wpdb->get_results('SELECT * FROM '.$table_name2.' WHERE uploadid = \''.$filerec->uploadid.'\' AND date_to = 0');
			array_push($out, $filerec);
		}
	}
	
	return $out;
}

function wfu_get_uncached_option($option, $default = false) {
	$GLOBALS['wp_object_cache']->delete( 'your_option_name', 'options' );
	return get_option($option, $default);
}

function wfu_get_option($option, $default) {
	global $wpdb;
	$table_name1 = $wpdb->prefix . "options";
	$val = $wpdb->get_var($wpdb->prepare("SELECT option_value FROM $table_name1 WHERE option_name = %s", $option));
	if ( $val === null && $default !== false ) $val = $default;
	elseif ( is_array($default) ) $val = wfu_decode_array_from_string($val);
	return $val;
}

function wfu_update_option($option, $value) {
	global $wpdb;
	$table_name1 = $wpdb->prefix . "options";
	if ( is_array($value) ) $value = wfu_encode_array_to_string($value);
	$wpdb->query($wpdb->prepare("INSERT INTO $table_name1 (option_name, option_value) VALUES (%s, %s) ON DUPLICATE KEY UPDATE option_value = VALUES(option_value)", $option, $value));
}

function wfu_export_uploaded_files($params) {
	global $wpdb;
	$table_name1 = $wpdb->prefix . "wfu_log";
	$table_name2 = $wpdb->prefix . "wfu_userdata";
	$plugin_options = wfu_decode_plugin_options(get_option( "wordpress_file_upload_options" ));

	$contents = "";
	$header = "Name,Path,Upload User,Upload Time,Size,Page ID,Blog ID,Shortcode ID,Upload ID,User Data";
	$contents = $header;
	$filerecs = $wpdb->get_results('SELECT * FROM '.$table_name1.' WHERE action <> \'other\' AND date_to = 0');
	foreach( $filerecs as $filerec ) {
		$obsolete = true;
		//calculate full file path
		$filepath = wfu_path_rel2abs($filerec->filepath);
		if ( file_exists($filepath) ) {
			if ( $plugin_options['hashfiles'] == '1' ) {
				$filehash = md5_file($filepath);
				if ( $filehash == $filerec->filehash ) $obsolete = false;
			}
			else {
				$filesize = filesize($filepath);
				if ( $filesize == $filerec->filesize ) $obsolete = false;
			}
		}
		//export file data if file is not obsolete
		if ( !$obsolete ) {
			$username = wfu_get_username_by_id($filerec->uploaduserid);
			$filerec->userdata = $wpdb->get_results('SELECT * FROM '.$table_name2.' WHERE uploadid = \''.$filerec->uploadid.'\' AND date_to = 0');
			$line = wfu_basename($filerec->filepath);
			$line .= ",".wfu_basedir($filerec->filepath);
			$line .= ",".$username;
			$line .= ",".( $filerec->uploadtime == null ? "" : date("Y-m-d H:i:s", $filerec->uploadtime) );
			$line .= ",".$filerec->filesize;
			$line .= ",".( $filerec->pageid == null ? "" : $filerec->pageid );
			$line .= ",".( $filerec->blogid == null ? "" : $filerec->blogid );
			$line .= ",".( $filerec->sid == null ? "" : $filerec->sid );
			$line .= ",".$filerec->uploadid;
			$line2 = "";
			foreach ( $filerec->userdata as $userdata ) {
				if ( $line2 != "" ) $line2 .= ";";
				$line2 .= $userdata->property.":".str_replace(array("\n", "\r", "\r\n"), " ", $userdata->propvalue);
			}
			$line .= ",".$line2;
			$contents .= "\n".$line;
		}
	}
	//create file
	$path = tempnam(sys_get_temp_dir(), 'wfu');
	file_put_contents($path, $contents);
	
	return $path;
}

//********************* Widget Functions ****************************************************************************************

function wfu_get_widget_obj_from_id($widgetid) {
	global $wp_registered_widgets;

	if ( !isset($wp_registered_widgets[$widgetid]) ) return false;
	if ( !isset($wp_registered_widgets[$widgetid]['callback']) ) return false;
	if ( !isset($wp_registered_widgets[$widgetid]['callback'][0]) ) return false;
	$obj = $wp_registered_widgets[$widgetid]['callback'][0];
	if ( !($obj instanceof WP_Widget) ) return false;
	
	return $obj;	
}

//********************* Shortcode Options Functions ****************************************************************************************

function wfu_shortcode_attribute_definitions_adjusted($shortcode_atts) {
	//get attribute definitions
	$defs = wfu_attribute_definitions();
	$defs_indexed = array();
	$defs_indexed_flat = array();
	foreach ( $defs as $def ) {
		$defs_indexed[$def["attribute"]] = $def;
		$defs_indexed_flat[$def["attribute"]] = $def["value"];
	}
	//get placement attribute from shortcode
	$placements = "";
	if ( isset($shortcode_atts["placements"]) ) $placements = $shortcode_atts["placements"];
	else $placements = $defs_indexed_flat["placements"];
	//get component definitions
	$components = wfu_component_definitions();
	//analyse components that can appear more than once in placements
	foreach ( $components as $component ) {
		if ( $component["multiplacements"] ) {
			$componentid = $component["id"];
			//count component occurrences in placements
			$component_occurrences = substr_count($placements, $componentid);
			if ( $component_occurrences > 1 && isset($defs_indexed[$componentid]) ) {
				//add incremented attribute definitions in $defs_indexed_flat array if occurrences are more than one
				for ( $i = 2; $i <= $component_occurrences; $i++ ) {
					foreach ( $defs_indexed[$componentid]["dependencies"] as $attribute )
						$defs_indexed_flat[$attribute.$i] = $defs_indexed_flat[$attribute];
				}
			}
		}
	}
	
	return $defs_indexed_flat;
}

function wfu_generate_current_params_index($shortcode_id, $user_login) {
	global $post;
	$cur_index_str = '||'.$post->ID.'||'.$shortcode_id.'||'.$user_login;
	$cur_index_str_search = '\|\|'.$post->ID.'\|\|'.$shortcode_id.'\|\|'.$user_login;
	$index_str = get_option('wfu_params_index');
	$index = explode("&&", $index_str);
	foreach ($index as $key => $value) if ($value == "") unset($index[$key]);
	$index_match = preg_grep("/".$cur_index_str_search."$/", $index);
	if ( count($index_match) == 1 )
		foreach ( $index_match as $key => $value )
			if ( $value == "" ) unset($index_match[$key]);
	if ( count($index_match) <= 0 ) {
		$cur_index_rand = wfu_create_random_string(16);
		array_push($index, $cur_index_rand.$cur_index_str);
	}
	else {
		reset($index_match);
		$cur_index_rand = substr(current($index_match), 0, 16);
		if ( count($index_match) > 1 ) {
			$index_match_keys = array_keys($index_match);
			for ($i = 1; $i < count($index_match); $i++) {
				$ii = $index_match_keys[$i];
				unset($index[array_search($index_match[$ii], $index, true)]);
			}
		}
	}
	if ( count($index_match) != 1 ) {
		$index_str = implode("&&", $index);
		update_option('wfu_params_index', $index_str);
	}
	return $cur_index_rand;
}

function wfu_get_params_fields_from_index($params_index) {
	$fields = array();
	$index_str = get_option('wfu_params_index');
	$index = explode("&&", $index_str);
	$index_match = preg_grep("/^".$params_index."/", $index);
	if ( count($index_match) == 1 )
		foreach ( $index_match as $key => $value )
			if ( $value == "" ) unset($index_match[$key]);
	if ( count($index_match) > 0 ) {
		reset($index_match);
		list($fields['unique_id'], $fields['page_id'], $fields['shortcode_id'], $fields['user_login']) = explode("||", current($index_match));
	}
	return $fields; 
}

function wfu_safe_store_shortcode_data($data) {
	$code = wfu_create_random_string(16);
	$_SESSION['wfu_shortcode_data_safe_storage'][$code] = $data;
	return $code;
}

function wfu_get_shortcode_data_from_safe($code) {
	//sanitize $code
	$code = wfu_sanitize_code($code);
	if ( $code == "" ) return '';
	//return shortcode data from session variable, if exists
	if ( !isset($_SESSION['wfu_shortcode_data_safe_storage'][$code]) ) return '';
	return $_SESSION['wfu_shortcode_data_safe_storage'][$code];
}

function wfu_clear_shortcode_data_from_safe($code) {
	//sanitize $code
	$code = wfu_sanitize_code($code);
	if ( $code == "" ) return;
	//clear shortcode data from session variable, if exists
	if ( !isset($_SESSION['wfu_shortcode_data_safe_storage'][$code]) ) return;
	unset($_SESSION['wfu_shortcode_data_safe_storage'][$code]);
}

function wfu_decode_dimensions($dimensions_str) {
	$components = wfu_component_definitions();
	$dimensions = array();

	foreach ( $components as $comp ) {
		if ( $comp['dimensions'] == null ) $dimensions[$comp['id']] = "";
		else foreach ( $comp['dimensions'] as $dimraw ) {
			list($dim_id, $dim_name) = explode("/", $dimraw);
			$dimensions[$dim_id] = "";
		}
	}
	$dimensions_raw = explode(",", $dimensions_str);
	foreach ( $dimensions_raw as $dimension_str ) {
		$dimension_raw = explode(":", $dimension_str);
		$item = strtolower(trim($dimension_raw[0]));
		foreach ( array_keys($dimensions) as $key ) {
			if ( $item == $key ) $dimensions[$key] = trim($dimension_raw[1]);
		}
	}
	return $dimensions;
}

function wfu_placements_remove_item($placements, $item) {
	$itemplaces = explode("/", $placements);
	$newplacements = array();
	foreach ( $itemplaces as $section ) {
		$items_in_section = explode("+", trim($section));
		$newsection = array();
		foreach ( $items_in_section as $item_in_section ) {
			$item_in_section = strtolower(trim($item_in_section));
			if ( $item_in_section != "" && $item_in_section != $item ) array_push($newsection, $item_in_section);
		}
		if ( count($newsection) > 0 ) array_push($newplacements, implode("+", $newsection));
	}
	if ( count($newplacements) > 0 ) return implode("/", $newplacements);
	else return "";
}

//********************* Plugin Design Functions ********************************************************************************************

function wfu_add_div() {
	$items_count = func_num_args();
	if ( $items_count == 0 ) return "";
	$items_raw = func_get_args();
	//get fit mode
	$fitmode = $items_raw[0];
	unset($items_raw[0]);
	$items = array( );
	foreach ( $items_raw as $item_raw ) {
		if ( is_array($item_raw) ) array_push($items, $item_raw);
	}
	$items_count = count($items);
	if ( $items_count == 0 ) return "";
	$div = "";
	if ( $fitmode == "fixed" ) {
		$div .= "\n\t".'<div class="file_div_clean">';  
		$div .= "\n\t\t".'<table class="file_table_clean">';
		$div .= "\n\t\t\t".'<tbody>';
		$div .= "\n\t\t\t\t".'<tr>';  
	}
	for ( $i = 0; $i < $items_count; $i++ ) {
		$style = "";
		if ( $fitmode == "fixed" ) {
			$div .= "\n\t\t\t\t\t".'<td class="file_td_clean"';  
			if ( $i < $items_count - 1 ) $div .= ' style="padding: 0 4px 0 0"';
			$div .= '>';
		}
		if ( $items[$i]["width"] != "" ) $style .= 'width: '.$items[$i]["width"].'; ';
		if ( $items[$i]["hidden"] ) $style .= 'display: none; ';
		if ( $style != "" ) $style = ' style="'.$style.'"';
		$div .= "\n\t\t\t\t\t\t".'<div id="'.$items[$i]["title"].'" class="file_div_clean'.( $fitmode == "responsive" ? '_responsive' : '' ).'"'.$style.'>';  
		$item_lines_count = count($items[$i]) - 3;
		for ( $k = 1; $k <= $item_lines_count; $k++ ) {
			if ( $items[$i]["line".$k] != "" ) $div .= "\n\t\t\t\t\t\t\t".$items[$i]["line".$k];
		}
		$div .= "\n\t\t\t\t\t\t\t".'<div class="file_space_clean"></div>';  
		$div .= "\n\t\t\t\t\t\t".'</div>';  
		if ( $fitmode == "fixed" ) $div .= "\n\t\t\t\t\t".'</td>';  
	}
	if ( $fitmode == "responsive" ) $div .= "\n\t".'<br />';
	else {
		$div .= "\n\t\t\t\t".'</tr>';  
		$div .= "\n\t\t\t".'</tbody>';
		$div .= "\n\t\t".'</table>';
		$div .= "\n\t".'</div>';  
	}
	return $div;
}

function wfu_add_loading_overlay($dlp, $code) {
	$echo_str = $dlp.'<div id="wfu_'.$code.'_overlay" style="margin:0; padding: 0; width:100%; height:100%; position:absolute; left:0; top:0; border:none; background:none; display:none;">';
	$echo_str .= $dlp."\t".'<div style="margin:0; padding: 0; width:100%; height:100%; position:absolute; left:0; top:0; border:none; background-color:rgba(255,255,255,0.8); z-index:1;""></div>';
	$echo_str .= $dlp."\t".'<table style="margin:0; padding: 0; table-layout:fixed; width:100%; height:100%; position:absolute; left:0; top:0; border:none; background:none; z-index:2;"><tbody><tr><td align="center" style="border:none;">';
	$echo_str .= $dlp."\t\t".'<img src="'.WFU_IMAGE_OVERLAY_LOADING.'" /><br /><span>loading...</span>';
	$echo_str .= $dlp."\t".'</td></tr></tbody></table>';
	$echo_str .= $dlp.'</div>';
	
	return $echo_str;
}

function wfu_add_pagination_header($dlp, $code, $curpage, $pages, $nonce = false) {
	if ($nonce === false) $nonce = wp_create_nonce( 'wfu-'.$code.'-page' );
	$echo_str = $dlp.'<div style="float:right;">';
	$echo_str .= $dlp."\t".'<label id="wfu_'.$code.'_first_disabled" style="margin:0 4px; font-weight:bold; opacity:0.5; cursor:default; display:'.( $curpage == 1 ? 'inline' : 'none' ).';">&#60;&#60;</label>';
	$echo_str .= $dlp."\t".'<label id="wfu_'.$code.'_prev_disabled" style="margin:0 4px; font-weight:bold; opacity:0.5; cursor:default; display:'.( $curpage == 1 ? 'inline' : 'none' ).';">&#60;</label>';
	$echo_str .= $dlp."\t".'<a id="wfu_'.$code.'_first" href="javascript:wfu_goto_'.$code.'_page(\''.$nonce.'\', \'first\');" style="margin:0 4px; font-weight:bold; display:'.( $curpage == 1 ? 'none' : 'inline' ).';">&#60;&#60;</a>';
	$echo_str .= $dlp."\t".'<a id="wfu_'.$code.'_prev" href="javascript:wfu_goto_'.$code.'_page(\''.$nonce.'\', \'prev\');" style="margin:0 4px; font-weight:bold; display:'.( $curpage == 1 ? 'none' : 'inline' ).';">&#60;</a>';
	$echo_str .= $dlp."\t".'<label style="margin:0 0 0 4px; cursor:default;">Page</label>';
	$echo_str .= $dlp."\t".'<select id="wfu_'.$code.'_pages" style="margin:0 4px;" onchange="wfu_goto_'.$code.'_page(\''.$nonce.'\', \'sel\');">';
	for ( $i = 1; $i <= $pages; $i++ )
		$echo_str .= $dlp."\t\t".'<option value="'.$i.'"'.( $i == $curpage ? ' selected="selected"' : '' ).'>'.$i.'</option>';
	$echo_str .= $dlp."\t".'</select>';
	$echo_str .= $dlp."\t".'<label style="margin:0 4px 0 0; cursor:default;">of '.$pages.'</label>';
	$echo_str .= $dlp."\t".'<label id="wfu_'.$code.'_next_disabled" style="margin:0 4px; font-weight:bold; opacity:0.5; cursor:default; display:'.( $curpage == $pages ? 'inline' : 'none' ).';">&#62;</label>';
	$echo_str .= $dlp."\t".'<label id="wfu_'.$code.'_last_disabled" style="margin:0 4px; font-weight:bold; opacity:0.5; cursor:default; display:'.( $curpage == $pages ? 'inline' : 'none' ).';">&#62;&#62;</label>';
	$echo_str .= $dlp."\t".'<a id="wfu_'.$code.'_next" href="javascript:wfu_goto_'.$code.'_page(\''.$nonce.'\', \'next\');" style="margin:0 4px; font-weight:bold; display:'.( $curpage == $pages ? 'none' : 'inline' ).';">&#62;</a>';
	$echo_str .= $dlp."\t".'<a id="wfu_'.$code.'_last" href="javascript:wfu_goto_'.$code.'_page(\''.$nonce.'\', \'last\');" style="margin:0 4px; font-weight:bold; display:'.( $curpage == $pages ? 'none' : 'inline' ).';">&#62;&#62;</a>';
	$echo_str .= $dlp.'</div>';
	
	return $echo_str;
}

function wfu_add_bulkactions_header($dlp, $code, $actions) {
	$echo_str = $dlp.'<div style="float:left;">';
	$echo_str .= $dlp."\t".'<select id="wfu_'.$code.'_bulkactions">';
	$echo_str .= $dlp."\t\t".'<option value="" selected="selected">'.( substr($code, 0, 8) == "browser_" ? WFU_BROWSER_BULKACTION_TITLE : "Bulk Actions").'</option>';
	foreach ( $actions as $action )
		$echo_str .= $dlp."\t\t".'<option value="'.$action["name"].'">'.$action["title"].'</option>';
	$echo_str .= $dlp."\t".'</select>';
	$echo_str .= $dlp."\t".'<input type="button" class="button action" value="'.( substr($code, 0, 8) == "browser_" ? WFU_BROWSER_BULKACTION_LABEL : "Apply").'" onclick="wfu_apply_'.$code.'_bulkaction();" />';
	$echo_str .= $dlp."\t".'<img src="'.WFU_IMAGE_OVERLAY_LOADING.'" style="display:none;" />';
	$echo_str .= $dlp.'</div>';
	
	return $echo_str;
}

//********************* Email Functions ****************************************************************************************************

function wfu_send_notification_email($user, $only_filename_list, $target_path_list, $attachment_list, $userdata_fields, $params) {
	global $blog_id;
	
	//apply wfu_before_email_notification filter
	$changable_data['recipients'] = $params["notifyrecipients"];
	$changable_data['subject'] = $params["notifysubject"];
	$changable_data['message'] = $params["notifymessage"];
	$changable_data['headers'] = $params["notifyheaders"];
	$changable_data['user_data'] = $userdata_fields;
	$changable_data['filename'] = $only_filename_list;
	$changable_data['filepath'] = $target_path_list;
	$changable_data['error_message'] = '';
	$additional_data['shortcode_id'] = $params["uploadid"];
	$ret_data = apply_filters('wfu_before_email_notification', $changable_data, $additional_data);
	
	if ( $ret_data['error_message'] == '' ) {
		$notifyrecipients = $ret_data['recipients'];
		$notifysubject = $ret_data['subject'];
		$notifymessage = $ret_data['message'];
		$notifyheaders = $ret_data['headers'];
		$userdata_fields = $ret_data['user_data'];
		$only_filename_list = $ret_data['filename'];
		$target_path_list = $ret_data['filepath'];

		if ( 0 == $user->ID ) {
			$user_login = "guest";
			$user_email = "";
		}
		else {
			$user_login = $user->user_login;
			$user_email = $user->user_email;
		}
		$search = array ('/%useremail%/', '/%n%/', '/%dq%/', '/%brl%/', '/%brr%/');	 
		$replace = array ($user_email, "\n", "\"", "[", "]");
		foreach ( $userdata_fields as $userdata_key => $userdata_field ) { 
			$ind = 1 + $userdata_key;
			array_push($search, '/%userdata'.$ind.'%/');  
			array_push($replace, $userdata_field["value"]);
		}   
//		$notifyrecipients =  trim(preg_replace('/%useremail%/', $user_email, $params["notifyrecipients"]));
		$notifyrecipients =  preg_replace($search, $replace, $notifyrecipients);
		$search = array ('/%n%/', '/%dq%/', '/%brl%/', '/%brr%/');	 
		$replace = array ("\n", "\"", "[", "]");
		$notifyheaders =  preg_replace($search, $replace, $notifyheaders);
		$search = array ('/%username%/', '/%useremail%/', '/%filename%/', '/%filepath%/', '/%blogid%/', '/%pageid%/', '/%pagetitle%/', '/%n%/', '/%dq%/', '/%brl%/', '/%brr%/');	 
		$replace = array ($user_login, ( $user_email == "" ? "no email" : $user_email ), $only_filename_list, $target_path_list, $blog_id, $params["pageid"], get_the_title($params["pageid"]), "\n", "\"", "[", "]");
		foreach ( $userdata_fields as $userdata_key => $userdata_field ) { 
			$ind = 1 + $userdata_key;
			array_push($search, '/%userdata'.$ind.'%/');  
			array_push($replace, $userdata_field["value"]);
		}   
		$notifysubject = preg_replace($search, $replace, $notifysubject);
		$notifymessage = preg_replace($search, $replace, $notifymessage);

		if ( $params["attachfile"] == "true" ) {
			$attachments = explode(",", $attachment_list);
			$notify_sent = wp_mail($notifyrecipients, $notifysubject, $notifymessage, $notifyheaders, $attachments); 
		}
		else {
			$notify_sent = wp_mail($notifyrecipients, $notifysubject, $notifymessage, $notifyheaders); 
		}
		return ( $notify_sent ? "" : WFU_WARNING_NOTIFY_NOTSENT_UNKNOWNERROR );
	}
	else return $ret_data['error_message'];
}

function wfu_notify_admin($subject, $message) {
	$admin_email = get_option("admin_email");
	if ( $admin_email === false ) return;
	wp_mail($admin_email, $subject, $message);
}

//********************* Media Functions ****************************************************************************************************

// function wfu_process_media_insert contribution from Aaron Olin with some corrections regarding the upload path
function wfu_process_media_insert($file_path, $userdata_fields, $page_id){
	$wp_upload_dir = wp_upload_dir();
	$filetype = wp_check_filetype( wfu_basename( $file_path ), null );

	$attachment = array(
		'guid'           => $wp_upload_dir['url'] . '/' . wfu_basename( $file_path ), 
		'post_mime_type' => $filetype['type'],
		'post_title'     => preg_replace( '/\.[^.]+$/', '', wfu_basename( $file_path ) ),
		'post_content'   => '',
		'post_status'    => 'inherit'
	);

	$attach_id = wp_insert_attachment( $attachment, $file_path, $page_id ); 
	
	// If file is an image, process the default thumbnails for previews
	require_once(ABSPATH . 'wp-admin/includes/image.php');
	$attach_data = wp_generate_attachment_metadata( $attach_id, $file_path );
	// Add userdata as attachment metadata
	foreach ( $userdata_fields as $userdata_field )
		$attach_data["WFU User Data"][$userdata_field["label"]] = $userdata_field["value"];
	$update_attach = wp_update_attachment_metadata( $attach_id, $attach_data );

	return $attach_id;	
}

//********************* Form Fields Functions ****************************************************************************************************

function wfu_preg_replace_callback_func($matches) {
	return str_replace("[/]", "/", $matches[0]);
}

// function wfu_parse_userdata_attribute parses the shortcode attribute to a form field array
function wfu_parse_userdata_attribute($value){
	$fields = array();
	//read defaults
	$definitions_unindexed = wfu_formfield_definitions();
	$defaults = array();
	foreach ( $definitions_unindexed as $def ) {
		$default = array();
		$default["type"] = $def["type"];
		$default["label"] = "";
		$default["labelposition"] = substr($def["labelposition"], 5);
		$default["required"] = ( substr($def["required"], 5) == "true" );
		$default["donotautocomplete"] = ( substr($def["donotautocomplete"], 5) == "true" );
		$default["validate"] = ( substr($def["validate"], 5) == "true" );
		$default["typehook"] = ( substr($def["typehook"], 5) == "true" );
		$default["hintposition"] = substr($def["hintposition"], 5);
		$default["default"] = substr($def["default"], 5);
		$default["data"] = substr($def["data"], 5);
		$default["group"] = substr($def["group"], 5);
		$default["format"] = substr($def["format"], 5);
		$defaults[$def["type"]] = $default;
	}
//	$fields_arr = explode("/", $value);
	$value = str_replace("/", "[/]", $value);
	$value = preg_replace_callback("/\(.*\)/", "wfu_preg_replace_callback_func", $value);
	$fields_arr = explode("[/]", $value);
	//parse shortcode attribute to $fields
	foreach ( $fields_arr as $field_raw ) {
		$field_raw = trim($field_raw);
		$fieldprops = $defaults["text"];
		//read old default attribute
		if ( substr($field_raw, 0, 1) == "*" ) {
			$fieldprops["required"] = true;
			$field_raw = substr($field_raw, 1);
		}
		$field_parts = explode("|", $field_raw);
		//proceed if the first part, which is the label, is non-empty
		if ( trim($field_parts[0]) != "" ) {
			//get type, if exists, in order to adjust defaults
			$type_key = -1;
			$new_type = "";
			foreach ( $field_parts as $key => $part ) {
				$part = ltrim($part);
				$flag = substr($part, 0, 2);
				$val = substr($part, 2);
				if ( $flag == "t:" && $key > 0 && array_key_exists($val, $defaults) ) {
					$new_type = $val;
					$type_key = $key;
					break;
				}
			}
			if ( $new_type != "" ) {
				$fieldprops = $defaults[$new_type];
				unset($field_parts[$type_key]);
			}
			//store label
			$fieldprops["label"] = trim($field_parts[0]);
			unset($field_parts[0]);
			//get other properties
			foreach ( $field_parts as $part ) {
				$part = ltrim($part);
				$flag = substr($part, 0, 2);
				$val = substr($part, 2);
				if ( $flag == "s:" ) $fieldprops["labelposition"] = $val;
				elseif ( $flag == "r:" ) $fieldprops["required"] = ( $val == "1" );
				elseif ( $flag == "a:" ) $fieldprops["donotautocomplete"] = ( $val == "1" );
				elseif ( $flag == "v:" ) $fieldprops["validate"] = ( $val == "1" );
				elseif ( $flag == "d:" ) $fieldprops["default"] = $val;
				elseif ( $flag == "l:" ) $fieldprops["data"] = $val;
				elseif ( $flag == "g:" ) $fieldprops["group"] = $val;
				elseif ( $flag == "f:" ) $fieldprops["format"] = $val;
				elseif ( $flag == "p:" ) $fieldprops["hintposition"] = $val;
				elseif ( $flag == "h:" ) $fieldprops["typehook"] = ( $val == "1" );
			}
			array_push($fields, $fieldprops);
		}
	}

	return $fields;	
}

//********************* Javascript Related Functions ****************************************************************************************************

// function wfu_inject_js_code generates html code for injecting js code and then erase the trace
function wfu_inject_js_code($code){
	$id = 'code_'.wfu_create_random_string(8);
	$html = '<div id="'.$id.'" style="display:none;"><script type="text/javascript">'.$code.'</script><script type="text/javascript">var div = document.getElementById("'.$id.'"); div.parentNode.removeChild(div);</script></div>';

	return $html;	
}

//********************* Browser Functions ****************************************************************************************************

function wfu_safe_store_browser_params($params) {
	$code = wfu_create_random_string(16);
	$_SESSION['wfu_browser_actions_safe_storage'][$code] = $params;
	return $code;
}

function wfu_get_browser_params_from_safe($code) {
	//sanitize $code
	$code = wfu_sanitize_code($code);
	if ( $code == "" ) return false;
	//return params from session variable, if exists
	if ( !isset($_SESSION['wfu_browser_actions_safe_storage'][$code]) ) return false;
	return $_SESSION['wfu_browser_actions_safe_storage'][$code];
}

//********************* POST/GET Requests Functions ****************************************************************************************************

function wfu_post_request($url, $params, $verifypeer = false, $internal_request = false) {
	$plugin_options = wfu_decode_plugin_options(get_option( "wordpress_file_upload_options" ));
	if ( isset($plugin_options['postmethod']) && $plugin_options['postmethod'] == 'curl' ) {
		// POST request using CURL
		$ch = curl_init($url);
		$options = array(
			CURLOPT_POST => true,
			CURLOPT_POSTFIELDS => http_build_query($params),
			CURLOPT_HTTPHEADER => array(
				'Content-Type: application/x-www-form-urlencoded'
			),
			CURLINFO_HEADER_OUT => false,
			CURLOPT_HEADER => false,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYPEER => $verifypeer
		);
		//for internal requests to /wp-admin area that is password protected
		//authorization is required
		if ( $internal_request && WFU_VAR("WFU_DASHBOARD_PROTECTED") == "true" ) {
			$options[CURLOPT_HTTPAUTH] = CURLAUTH_ANY;
			$options[CURLOPT_USERPWD] = WFU_VAR("WFU_DASHBOARD_USERNAME").":".WFU_VAR("WFU_DASHBOARD_PASSWORD");
		}
		curl_setopt_array($ch, $options);
		$result = curl_exec($ch);
		curl_close ($ch);
		return $result;
	}
	elseif ( isset($plugin_options['postmethod']) && $plugin_options['postmethod'] == 'socket' ) {
		// POST request using sockets
		$scheme = "";
		$port = 80;
		$timeout = null;
		$errno = 0;
        $errstr = '';
		$url = parse_url($url);
		$host = $url['host'];
		$path = $url['path'];
		if ( $url['scheme'] == 'https' ) { 
			$scheme = "ssl://";
			$port = 443;
			$timeout = 30;
		}
		elseif ( $url['scheme'] != 'http' ) return '';
		$handle = fsockopen($scheme.$host, $port, $errno, $errstr, (is_null($timeout) ? ini_get("default_socket_timeout") : $timeout));
		if ( $errno !== 0 || $errstr !== '' ) $handle = false;
		if ( $handle !== false ) {
			$content = http_build_query($params);
			$request = "POST " . $path . " HTTP/1.1\r\n";
            $request .= "Host: " . $host . "\r\n";
            $request .= "Content-Type: application/x-www-form-urlencoded\r\n";
			//for internal requests to /wp-admin area that is password protected
			//authorization is required
			if ( $internal_request && WFU_VAR("WFU_DASHBOARD_PROTECTED") == "true" ) {
				$request .= "Authorization: Basic ".base64_encode(WFU_VAR("WFU_DASHBOARD_USERNAME").":".WFU_VAR("WFU_DASHBOARD_PASSWORD"))."\r\n";
			}
           $request .= "Content-length: " . strlen($content) . "\r\n";
            $request .= "Connection: close\r\n\r\n";
            $request .= $content . "\r\n\r\n";
			fwrite($handle, $request, strlen($request));
			$response = '';
			while ( !feof($handle) ) {
                $response .= fgets($handle, 4096);
            }
			fclose($handle);
			if (0 === strpos($response, 'HTTP/1.1 200 OK')) {
                $parts = preg_split("#\n\s*\n#Uis", $response);
                return $parts[1];
            }
			return '';
		}
		return '';
	}
	else {
		// POST request using file_get_contents
		if ( $internal_request && WFU_VAR("WFU_DASHBOARD_PROTECTED") == "true" ) {
			$url = preg_replace("/^(http|https):\/\//", "$1://".WFU_VAR("WFU_DASHBOARD_USERNAME").":".WFU_VAR("WFU_DASHBOARD_PASSWORD")."@", $url);
		}
		$peer_key = version_compare(PHP_VERSION, '5.6.0', '<') ? 'CN_name' : 'peer_name';
		$http_array = array(
			'method'  => 'POST',
			'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
			'content' => http_build_query($params)
		);
		//for internal requests to /wp-admin area that is password protected
		//authorization is required
		if ( $internal_request && WFU_VAR("WFU_DASHBOARD_PROTECTED") == "true" ) {
			$http_array['header'] .= "Authorization: Basic ".base64_encode(WFU_VAR("WFU_DASHBOARD_USERNAME").":".WFU_VAR("WFU_DASHBOARD_PASSWORD"))."\r\n";
		}
		if ( $verifypeer ) {
			$http_array['verify_peer'] = true;
			$http_array[$peer_key] = 'www.google.com';
		}
		$context_params = array( 'http' => $http_array );
		$context = stream_context_create($context_params);
		return file_get_contents($url, false, $context);
	}
}

?>
