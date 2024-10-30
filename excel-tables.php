<?php 
/*
Plugin Name: Excel tables
Description: This plugin allows you to import excel file into wordpress.
Version: 1.2
Author: WebArea | Vera Nedvyzhenko
*/

// -------------------------------------------
// Define param

define('EXT_PLUGIN_PATH', plugin_dir_path( __FILE__ ));
define('EXT_PLUGIN_URL', plugin_dir_url( __FILE__ ));

// -------------------------------------------

// -------------------------------------------
//Enqueue scripts & styles

function ext_admin_scripts(){
	wp_enqueue_style('ext_admin_style', plugins_url('css/admin.css', __FILE__));
	wp_enqueue_media();
	wp_enqueue_script('ext_admin_script', plugins_url('js/admin.js', __FILE__));
	wp_localize_script('ext_admin_script', 'ext_ajax_script_adm', array( 'ext_ajax_url_adm' => admin_url( 'admin-ajax.php' )));
}

add_action('admin_enqueue_scripts', 'ext_admin_scripts');

function ext_scripts(){
	wp_enqueue_style('ext_style', plugins_url('css/style.css', __FILE__));
}

add_action('login_enqueue_scripts', 'ext_scripts');
add_action('wp_enqueue_scripts', 'ext_scripts' );

// -------------------------------------------

// -------------------------------------------
// Add Post Type

function ext_post_type(){
	$labels = [
	'name' => 'Excel tables',
	'singular_name' => 'Table',
	'add_new' => 'Add table',
	'add_new_item' => 'Add table',
	'edit_item' => 'Edit table',
	'search_items' => 'Search',
	'not_found' => 'No tables found',
	'not_found_in_trash' => 'No tables found',
	'all_items' => 'All tables'
	];

	$args = [
	'labels' => $labels,
	'show_ui' => true,
	'menu_position' => 100,
	'supports' => ['title'],
	'menu_icon' => 'dashicons-editor-table',
	'has_archive' => 'extable',
	'capability_type' => 'post',
	'public' => true
	];

	register_post_type('extable', $args);
}
add_action('init', 'ext_post_type');

// -------------------------------------------

// -------------------------------------------
// Post type columns
function set_custom_edit_extable_columns($columns) {
    $columns = array(
		'cb' => '<input type="checkbox" />',
		'title' => __( 'Title' ),
		'shortcode' => 'Shortcode',
		'date' => __( 'Date' )
	);

    return $columns;
}

function custom_extable_column( $column, $post_id ) {
    switch ( $column ) {
        case 'shortcode' :
            $terms = "[exceltable id='" . $post_id . "']";
            if ( is_string( $terms ) )
                echo $terms;
            else
                echo '';
            break;
    }
}

add_filter( 'manage_extable_posts_columns', 'set_custom_edit_extable_columns' );
add_action( 'manage_extable_posts_custom_column' , 'custom_extable_column', 10, 2 );
// -------------------------------------------

// -------------------------------------------
// Add Meta Box

function extable_meta_box() {
	add_meta_box(
		'extable_file_metabox',
		'File',
		'extable_file_metabox_func',
		'extable',
		'normal',
		'high'
	);

	add_meta_box(
		'extable_prev_metabox',
		'Table Preview',
		'extable_prev_metabox_func',
		'extable',
		'normal',
		'high'
	);
}
add_action('add_meta_boxes', 'extable_meta_box');

function extable_file_metabox_func($object){
	$obj_id = $object->ID;

	wp_nonce_field(basename(__FILE__), "upload_extable-nonce");
	wp_nonce_field(basename(__FILE__), "ext_csv_separator-nonce");
	$file_upload_id = esc_attr(get_post_meta($obj_id, 'upload_extable_file', true ));
	$ext_csv_separator = esc_attr(get_post_meta($obj_id, 'ext_csv_separator', true ));
	$html .= '<input id="upload_extable" type="hidden" name="upload_extable" value="' . $file_upload_id . '" />';

	$shortcode_txt = "[exceltable id='" . $obj_id . "']";

	$html .= '<div class="ext-shortcode-txt">Table shortcode: <input type="text" value="'.$shortcode_txt.'" readonly/></div>';

	$path = get_attached_file($file_upload_id);
	$path_parts = pathinfo($path);

	if($file_upload_id == ''){
		$html .= '<p class="ext-upload-txt">Please upload file</p>';
		$html .= '<div class="ext_fileinfo"><input class="button-secondary" id="upload_ext_button" type="button" value="Upload File" /><div class="ext_fileinfo_cont"></div></div>';
	}else{
		$html .= '<p class="ext-upload-txt" style="display: none;">Please upload file</p>';
		$html .= '<div class="ext_fileinfo"><div class="ext_fileinfo_cont brd"><p><b>File ID:</b> '. $file_upload_id .'</p><p><b>File Name:</b> '. get_the_title($file_upload_id) .'</p><p><b>File URL:</b> '. wp_get_attachment_url($file_upload_id) .'</p><div class="ext_fileclose">Delete file</div></div>';
		if($path_parts['extension'] == 'csv'){
			$html .= '<div class="ext-csv-separator"><p>Columns are separated from: </p><input type="text" name="ext_csv_separator" value="' . $ext_csv_separator . '" /></div>';
		}

		$html .= '</div>';
	}

	echo $html;
}

function extable_file_save_meta_fields( $post_id ) {
	if (!isset($_POST["upload_extable-nonce"]) || !wp_verify_nonce($_POST["upload_extable-nonce"], basename(__FILE__))){
        return $post_id;
	}

	if (!isset($_POST["ext_csv_separator-nonce"]) || !wp_verify_nonce($_POST["ext_csv_separator-nonce"], basename(__FILE__))){
        return $post_id;
	}

    if(!current_user_can("edit_post", $post_id)){
        return $post_id;
    }

    if(defined("DOING_AUTOSAVE") && DOING_AUTOSAVE){
        return $post_id;
    }

    if(isset($_POST["upload_extable"])){
    	$upload_extable = sanitize_text_field($_POST["upload_extable"]);
    	update_post_meta($post_id, "upload_extable_file", $upload_extable);
    }

    if(isset($_POST["ext_csv_separator"])){
    	$ext_csv_separator = sanitize_text_field($_POST["ext_csv_separator"]);
    	if($ext_csv_separator != ''){
    		update_post_meta($post_id, "ext_csv_separator", $ext_csv_separator);
    	}else{
    		update_post_meta($post_id, "ext_csv_separator", ';');
    	}
    }
}
add_action('save_post', 'extable_file_save_meta_fields' );
add_action('new_to_publish', 'extable_file_save_meta_fields' );

// -------------------------------------------

// -------------------------------------------
// Preview table function
function extable_prev_metabox_func($object){
	$file_upload_id = esc_attr(get_post_meta($object->ID, 'upload_extable_file', true ));
	$ext_csv_separator = esc_attr(get_post_meta($object->ID, 'ext_csv_separator', true ));

	echo '<div class="ext-preview">';
	if($file_upload_id != ''){
		echo ext_get_file_content($file_upload_id, $ext_csv_separator);
	}
	echo '</div>';
}
// -------------------------------------------

// -------------------------------------------
// Get CSV file content

function ext_include_class(){
	include( plugin_dir_path( __FILE__ ) . 'inc/simplexlsx.class.php');
}

add_action('init', 'ext_include_class');

function ext_get_file_content($fileID, $separator){
	$url = wp_get_attachment_url($fileID);
	$path = get_attached_file($fileID);
	$path_parts = pathinfo($path);

	if($path_parts['extension'] == 'csv'){
		$row = 1;
		$table = '';
		if($url != ''){
		$table .= '<table>';
		if (($handle = fopen($url, "r")) !== FALSE) {
		    while (($data = fgetcsv($handle, 1000, $separator)) !== FALSE) {
			$table .= '<tr>';
		        $num = count($data);
		        for ($c=0; $c < $num; $c++) {
		        	if($row == 1){
		        		$call = 'th';
		        	}else{
		        		$call = 'td';
		        	}

					$table .= '<'. $call .'>' . $data[$c] . '</'. $call .'>';
		        }
		        $row++;
			$table .= '</tr>';
		    }
		    fclose($handle);
		}
		$table .= '</table>';
		}
	}else{
		$data_url = file_get_contents($url);
		
		if ($xlsx = SimpleXLSX::parse( $data_url, true) ) {
			$table .= '<table>';
			$ext_count = 1;
			foreach( $xlsx->rows() as $r ) {
				if($ext_count == 1){
		        	$excall = 'th';
		        }else{
		       		$excall = 'td';
		        }

				$table .= '<tr><'.$excall.'>'.implode('</'.$excall.'><'.$excall.'>', $r ).'</'.$excall.'></tr>';
			$ext_count++;
			}
			$table .= '</table>';
		} else {
			$table .= SimpleXLSX::parse_error();
		}
	}

	return $table;
}

// -------------------------------------------

// -------------------------------------------
// Shortcode

function exceltable_shortcode($atts){
	$shortcode_result = '';

    extract(shortcode_atts(array(
		'id' => '',
	), $atts));

    $file_upload_id = esc_attr(get_post_meta($id, 'upload_extable_file', true ));
	$ext_csv_separator = esc_attr(get_post_meta($id, 'ext_csv_separator', true ));

	if($file_upload_id != ''){
		$shortcode_result .= '<div class="extable-content">';
    	$shortcode_result .= ext_get_file_content($file_upload_id, $ext_csv_separator); 
		$shortcode_result .= '</div>';
	}

	return $shortcode_result;
}
add_shortcode('exceltable', 'exceltable_shortcode');

// -------------------------------------------

?>