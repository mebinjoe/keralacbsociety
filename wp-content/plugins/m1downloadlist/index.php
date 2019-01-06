<?php
/*
Plugin Name: m1.DownloadList
Plugin URI: http://maennchen1.de
Description: This plugin easily displays the folders and files from a selected directory. It can be placed by shortcode in any post.
Author: maennchen1.de
Version: 0.7
Author URI: http://maennchen1.de
*/

defined ( 'ABSPATH' ) or die ( 'ERROR: no ABSPATH is set' );
load_plugin_textdomain('m1dll', false, basename( dirname( __FILE__ ) ) . '/languages' );

$m1dll_index = 0;

/**
 * 
 * return utf8 encoded string, if needed
 */
function m1dll_utf8_encode ($f)
{
    
	if (@mb_check_encoding($f) == "1") return $f;
    else return utf8_decode($f);
    
} // m1dll_utf8_encode()

/**
 * 
 * return (empty) string
 */
function m1dll_getStr(&$value)
{

	if (!isset($value) || !is_string($value)) return '';
	return $value;

} // m1dll_getStr($value = '')


/**
 * 
 * @param Array $atts
 * @return string Shortcode
 */
function m1dll_shortcode( $atts ) {

    $m1dll_fileicon  = array(
            '*'    => plugins_url( '/icons/file.gif', __FILE__ ),
            'bz2'  => plugins_url( '/icons/rarfile.gif', __FILE__ ),
            'c'    => plugins_url( '/icons/cfile.gif', __FILE__ ),
            'cpp'  => plugins_url( '/icons/cppfile.gif', __FILE__ ),
            'doc'  => plugins_url( '/icons/docfile.gif', __FILE__ ),
            'exe'  => plugins_url( '/icons/exefile.gif', __FILE__ ),
            'h'    => plugins_url( '/icons/hfile.gif', __FILE__ ),
            'htm'  => plugins_url( '/icons/htmfile.gif', __FILE__ ),
            'html' => plugins_url( '/icons/htmfile.gif', __FILE__ ),
            'gif'  => plugins_url( '/icons/imgfile.gif', __FILE__ ),
            'gz'   => plugins_url( '/icons/zipfile.gif', __FILE__ ),
            'jpg'  => plugins_url( '/icons/imgfile.gif', __FILE__ ),
            'js'   => plugins_url( '/icons/jsfile.gif', __FILE__ ),
            'm'    => plugins_url( '/icons/mfile.gif', __FILE__ ),
            'mp3'  => plugins_url( '/icons/mpgfile.gif', __FILE__ ),
            'mpg'  => plugins_url( '/icons/mpgfile.gif', __FILE__ ),
            'pdf'  => plugins_url( '/icons/pdffile.gif', __FILE__ ),
            'png'  => plugins_url( '/icons/imgfile.gif', __FILE__ ),
            'ppt'  => plugins_url( '/icons/pptfile.gif', __FILE__ ),
            'rar'  => plugins_url( '/icons/rarfile.gif', __FILE__ ),
            'swf'  => plugins_url( '/icons/swffile.gif', __FILE__ ),
            'txt'  => plugins_url( '/icons/txtfile.gif', __FILE__ ),
            'xls'  => plugins_url( '/icons/xlsfile.gif', __FILE__ ),
            'zip'  => plugins_url( '/icons/zipfile.gif', __FILE__ ),
            'dir'  => plugins_url( '/icons/folder.gif', __FILE__ )
    );

    global $m1dll_index;

    /*---------------------------------------------------------------------------------------------------*/
    /* Output: */
    /*---------------------------------------------------------------------------------------------------*/
    
    if (m1dll_getStr($atts['path']) == '') {
        
        //no shortcode parameter? Then standard upload folder.
        $subdir = "";
        $dirname = ABSPATH . "wp-content/uploads";
        
    }
    else {
        
    	$subdir = "";
         $dirname = ABSPATH.str_replace("..","",rawurldecode(m1dll_getStr($atts['path'])));
         
    }
    
    // check if filetype filtering is enabled via the shortcode param 'filetype'
    if (strlen(m1dll_getStr($atts['filetype'])) > 0) {
    	
    	// add commaseparated filetypes to filter. 'dir' is always contained
    	$filetypeFilter = array_merge(array('dir'), explode(',', m1dll_getStr($atts['filetype'])));
    	
    }
    
    if (m1dll_getStr($_REQUEST['d']) && m1dll_getStr($_REQUEST['m1dll_index_get']) == $m1dll_index) {
        
        //path from URL
        $subdir = str_replace("..","",base64_decode(rawurldecode(m1dll_getStr($_REQUEST['d']))));
        $dirname.= $subdir;
        
    }

    if (is_dir($dirname)) {

        //breadcrumb
        if (strlen(m1dll_getStr($atts['label'])) > 0) {
            $subdir_path = '<strong>'.__('path', 'm1dll').':</strong> <a href="' . get_permalink(get_the_ID()) . '">'.__(m1dll_getStr($atts['label']), 'm1dll').'</a>';
        } else {
            $subdir_path = '<strong>'.__('path', 'm1dll').':</strong> <a href="' . get_permalink(get_the_ID()) . '">'.__('downloads', 'm1dll').'</a>';
        }

        if ($subdir)
        {
                $ptmp = explode("/", $subdir);
                $sp = array();
                foreach($ptmp as $item)
                {
                        if ($item)
                        {
                                $sp[] = $item;
                                $subdir_path.= '/<a href="' . 
                                    esc_url( 
                                        add_query_arg( 
                                            array( 
                                                'd' => base64_encode('/' . implode('/', $sp)), 
                                                'm1dll_index_get' => $m1dll_index 
                                                )
                                        )
                                    ) . '">' . m1dll_utf8_encode( $item ) . '</a>';
                        }
                }
        }

        $content = '<ul class="m1dll_filelist">';

        if ($dh = opendir($dirname)) 
        {
                $ar_content = array ();
                $i = 0;
                
                while (($file = readdir($dh)) !== false) 
                {
                        if ($file != "." && $file != "..")
                        {
                        		if (is_dir($dirname ."/". $file) && m1dll_getStr($atts['hidedirs']) != "1")
                                {
                                        //folder
                                        $href = add_query_arg( 
                                            array( 
                                                'd' =>  rawurlencode( base64_encode($subdir.'/'.$file) ),
                                                'm1dll_index_get' => $m1dll_index
                                                )
                                            );
                                        
                                        //size
                                        $printsize = '&nbsp;';
                                        
                                        //icons
                                        $endung = "dir";
                                        
                                        $target = '';
                                        
                                        $type = "d";
                                }
                                else
                                {
                                        //file
                                        $href = get_site_url() . "/" . m1dll_utf8_encode ( substr($dirname, strlen(ABSPATH)) ."/". $file );
                                        
                                        //size
                                        $size = round(filesize($dirname ."/". $file)/1000, 2);
                                        if ($size >= 1000) { $size = round($size/1000, 2); $printsize = number_format($size,2,',','.').'&nbsp;MB'; }
                                        else { $printsize = number_format($size,2,',','.').'&nbsp;kb';}
                                        
                                        //filename
                                        $endung = strtolower(substr($file, strrpos($file, ".")+1));

                                        if (m1dll_getStr($atts['target'])) {
                                            $target = ' target="'. m1dll_getStr($atts['target']) .'"';
                                        }
                                        else {
                                            $target = '';
                                        }
                                        $type = "f";
                                }
                                
                                // skip file, if filetype filtering is enabled & filetype in blacklist
                                if (strlen(m1dll_getStr($atts['filetype'])) > 0 && !in_array($endung, $filetypeFilter)) continue;
                                
                                if(array_key_exists($endung, $m1dll_fileicon) === false) $endung = "*";
                                
                                $ar_content[$i] = array (
                                		'href' => $href,
                                		'bg-url' => $m1dll_fileicon[$endung],
                                		'target' => $target,
                                		'filename' => m1dll_utf8_encode( $file ),
                                		'size' => $printsize,
                                		'type' => $type
                                );
                                $i++;
                        }
                }
                closedir($dh);
                
                if (m1dll_getStr($atts['sort']) == 'DESC') {
                	arsort($ar_content);
                }
                else {
                	asort($ar_content);
                }
                
                foreach ($ar_content as $f) {
                	$content.= '
                                <li>
                	
                                                <a href="'.$f['href'].'" class="test" style="background: url(\''. $f['bg-url'] .'\') left no-repeat; padding-left:20px;"'. $f['target'] .'>
                                                ' . ((m1dll_getStr($atts['noext'])=="1" && $f['type']=="f")?str_replace(strtolower(substr($f['filename'], strrpos($f['filename'], "."))), '', $f['filename']):$f['filename']) . '
                                                </a>
                                                '. ((m1dll_getStr($atts['nosize'])=="1")?'':$f['size']) .'
                                </li>
                                ';
                }
        }
        $content.= '
        </ul>
   		';
    }
    $m1dll_index++;
    return $content;
    
} // m1dll_shortcode ()

/**
 * add CSS to template
 */
function m1dll_css() {
	wp_enqueue_style( 'm1dll', plugins_url('main.css', __FILE__) );
} // m1dll_css()

add_action( 'wp_enqueue_scripts', 'm1dll_css' );
add_shortcode( 'm1dll', 'm1dll_shortcode' );  

?>