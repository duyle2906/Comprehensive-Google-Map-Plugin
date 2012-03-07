<?php
/*
Copyright (C) 2011  Alexander Zagniotov

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/
  
if ( !function_exists('cgmp_draw_map_placeholder') ):
		function cgmp_draw_map_placeholder($id, $width, $height, $align, $hint) {

				$toploading = ceil($height / 2) - 50;

				$map_marker_directions_hint_template = "";

				if ($hint == "true") {
					$tokens_with_values = array();
					$tokens_with_values['MARKER_DIRECTIONS_HINT_WIDTH_TOKEN'] = $width;
					$map_marker_directions_hint_template = cgmp_render_template_with_values($tokens_with_values, CGMP_HTML_TEMPLATE_MAP_MARKER_DIRECTION_HINT);
				}

				$tokens_with_values = array();
				$tokens_with_values['MAP_PLACEHOLDER_ID_TOKEN'] = $id;
				$tokens_with_values['MAP_PLACEHOLDER_WIDTH_TOKEN'] = $width;
				$tokens_with_values['MAP_PLACEHOLDER_HEIGHT_TOKEN'] = $height;
				$tokens_with_values['LOADING_INDICATOR_TOP_POS_TOKEN'] = $toploading;
				$tokens_with_values['MAP_ALIGN_TOKEN'] = $align;
				$tokens_with_values['MARKER_DIRECTIONS_HINT_TOKEN'] = $map_marker_directions_hint_template;
				$tokens_with_values['IMAGES_DIRECTORY_URI'] = CGMP_PLUGIN_IMAGES;
				$tokens_with_values['DIRECTIONS_WIDTH_TOKEN'] = ($width - 10);

				return cgmp_render_template_with_values($tokens_with_values, CGMP_HTML_TEMPLATE_MAP_PLACEHOLDER_AND_DIRECTIONS);
 	}
endif;


if ( !function_exists('cgmp_render_template_with_values') ):
	function cgmp_render_template_with_values($tokens_with_values, $template_name) {

		$map_shortcode_builder_metabox_template = file_get_contents(CGMP_PLUGIN_HTML."/".$template_name);
  		$map_shortcode_builder_metabox_template = cgmp_replace_template_tokens($tokens_with_values, $map_shortcode_builder_metabox_template);
		return $map_shortcode_builder_metabox_template;
	}
endif;



if ( !function_exists('cgmp_load_plugin_textdomain') ):
	function cgmp_load_plugin_textdomain() {
		load_plugin_textdomain(CGMP_NAME, false, dirname(CGMP_PLUGIN_BOOTSTRAP) . '/languages/');
	}
endif;


if ( !function_exists('cgmp_show_message') ):

function cgmp_show_message($message, $errormsg = false)
{
	if (!isset($message) || $message == '') {
		return;
	}
	echo '<div id="message" class="updated fade"><p><strong>'.$message.'</strong></p></div>';
}
endif;



if ( !function_exists('cgmp_map_data_injector') ):
	function cgmp_map_data_injector($map_json) {
			cgmp_map_data_hook_function( $map_json );
	}
endif;


if ( !function_exists('cgmp_map_data_hook_function') ):
	function cgmp_map_data_hook_function( $map_json ) {
		echo PHP_EOL."<object class='map-data-placeholder'><param name='json-string' value='".$map_json."' /></object> ".PHP_EOL;
	}
endif;



if ( !function_exists('cgmp_set_google_map_language') ):
	function cgmp_set_google_map_language($user_selected_language)  {

		$db_saved_language = get_option(CGMP_DB_SELECTED_LANGUAGE);

		if (!isset($db_saved_language) || $db_saved_language == '') {
			if ($user_selected_language != 'default') {
				update_option(CGMP_DB_SELECTED_LANGUAGE, $user_selected_language);
				cgmp_deregister_and_enqueue_google_api($user_selected_language);

			} else {
				if (!is_admin()) {
					wp_enqueue_script('cgmp-google-map-api', CGMP_GOOGLE_API_URL, array('jquery'), false, true);
				}
			}
		} else if (isset($db_saved_language) && $db_saved_language != '') {

			if ($user_selected_language != 'default') {
				update_option(CGMP_DB_SELECTED_LANGUAGE, $user_selected_language);
				cgmp_deregister_and_enqueue_google_api($user_selected_language);

			} else {
				cgmp_deregister_and_enqueue_google_api($db_saved_language);
			}
		}
	}
endif;

if ( !function_exists('cgmp_deregister_and_enqueue_google_api') ):
	function cgmp_deregister_and_enqueue_google_api($lang)  {
		if (!is_admin()) {
			$api = CGMP_GOOGLE_API_URL;
			$api .= "&language=".$lang;
			wp_deregister_script( 'cgmp-google-map-api' );
			wp_register_script('cgmp-google-map-api', $api, array('jquery'), false, true);
			wp_enqueue_script('cgmp-google-map-api');
		}
	}
endif;


if ( !function_exists('is_map_shortcode_present') ):
	function is_map_shortcode_present($posts)
	{
		if ( empty($posts) ) {
			return $posts;
		}

    	$found = false;

    	foreach ($posts as $post) {
        	if ( stripos($post->post_content, '[google-map-v3') !== false) {
            	$found = true;
				break;
			}
        }

		if ($found) {
			cgmp_google_map_init_scripts();
		}

		return $posts;
	}
endif;


if ( !function_exists('trim_marker_value') ):
	function trim_marker_value(&$value)
	{
    	$value = trim($value);
	}
endif;


if ( !function_exists('update_markerlist_from_legacy_locations') ):
	function update_markerlist_from_legacy_locations($latitude, $longitude, $addresscontent, $hiddenmarkers)  {

		$legacyLoc = isset($addresscontent) ? $addresscontent : "";

		if (isset($latitude) && isset($longitude)) {
			if ($latitude != "0" && $longitude != "0" && $latitude != 0 && $longitude != 0) {
				$legacyLoc = $latitude.",".$longitude;
			}
		}

		if (isset($hiddenmarkers) && $hiddenmarkers != "") {

			$hiddenmarkers_arr = explode("|", $hiddenmarkers);
			$filtered = array();
			foreach($hiddenmarkers_arr as $marker) {
				if (strpos(trim($marker), CGMP_SEP) === false) {
					$filtered[] = trim($marker.CGMP_SEP."1-default.png");
				} else {
					$filtered[] = trim($marker);
				}
			}

			$hiddenmarkers = implode("|", $filtered);
		}

		if (trim($legacyLoc) != "")  {
			$hiddenmarkers = $legacyLoc.CGMP_SEP."1-default.png".(isset($hiddenmarkers) && $hiddenmarkers != "" ? "|".$hiddenmarkers : "");
		}

		$hiddenmarkers_arr = explode("|", $hiddenmarkers );
		array_walk($hiddenmarkers_arr, 'trim_marker_value');
		$hiddenmarkers_arr = array_unique($hiddenmarkers_arr);
		return implode("|", $hiddenmarkers_arr);
	}
endif;



if ( !function_exists('cgmp_clean_kml') ):
	function cgmp_clean_kml($kml) {
		$result = '';
		if (isset($kml) && $kml != "") {

			$lowerkml = strtolower(trim($kml));
			$pos = strpos($lowerkml, "http");

			if ($pos !== false && $pos == "0") {
				$kml = strip_tags($kml);
				$kml = str_replace("&#038;", "&", $kml);
				$kml = str_replace("&amp;", "&", $kml);
				$result = trim($kml);
			}
		}
		return $result;
	}
endif;


if ( !function_exists('cgmp_clean_panoramiouid') ):
	function cgmp_clean_panoramiouid($userId) {

		if (isset($userId) && $userId != "") {
			$userId = strtolower(trim($userId));
			$userId = strip_tags($userId);
		}

		return $userId;
	}
endif;




if ( !function_exists('cgmp_create_html_select') ):
	function cgmp_create_html_select($attr) {
		$role = $attr['role'];
		$id = $attr['id'];
		$name = $attr['name'];
		$value = $attr['value'];
		$options = $attr['options'];
				
		return "<select role='".$role."' id='".$id."' style='' class='shortcodeitem' name='".$name."'>".
				cgmp_create_html_select_options($options, $value)."</select>";
	}
endif;


if ( !function_exists('cgmp_create_html_select_options') ):
	function cgmp_create_html_select_options( $options, $so ){
		$r = '';
		foreach ($options as $label => $value){
			$r .= '<option value="'.$value.'"';
			if($value == $so){
				$r .= ' selected="selected"';
			}
			$r .= '>&nbsp;'.$label.'&nbsp;</option>';
		}
		return $r;
	}
endif;


if ( !function_exists('cgmp_create_html_input') ):
	function cgmp_create_html_input($attr) {
		$role = $attr['role'];
		$id = $attr['id'];
		$name = $attr['name'];
		$value = $attr['value'];
		$class = $attr['class'];
		$style = $attr['style'];
		$elem_type = 'text';

		if (isset($attr['elem_type'])) {
			$elem_type = $attr['elem_type'];
		}
		$steps = "";
		$slider = "";
		if ($elem_type == "range") {
				$slider = "<div id='".$role."' class='slider'></div>";
				$class .= " slider-output";
		}

		if (strpos($class, "notshortcodeitem") === false) {
			$class = $class." shortcodeitem";
		}
		return $slider."<input role='".$role."' {$steps} class='".$class."' id='".$id."' name='".$name."' value='".$value."' style='".$style."' />";
	}
endif;

if ( !function_exists('cgmp_create_html_hidden') ):
		function cgmp_create_html_hidden($attr) {
				$id = $attr['id'];
				$name = $attr['name'];
				$value = $attr['value'];
				$class = $attr['class'];
				$style = $attr['style'];
			return "<input class='".$class."' id='".$id."' name='".$name."' value='".$value."' style='".$style."' type='hidden' />";
	}
endif;


if ( !function_exists('cgmp_create_html_button') ):
		function cgmp_create_html_button($attr) {
				$id = $attr['id'];
				$name = $attr['name'];
				$value = $attr['value'];
				$class = $attr['class'];
				$style = $attr['style'];
			return "<input class='".$class."' id='".$id."' name='".$name."' value='".$value."' style='".$style."' type='button' />";
	}
endif;

if ( !function_exists('cgmp_create_html_list') ):
		function cgmp_create_html_list($attr) {
				$id = $attr['id'];
				$name = $attr['name'];
				$class = $attr['class'];
				$style = $attr['style'];
			return "<ul class='".$class."' id='".$id."' name='".$name."' style='".$style."'></ul>";
	}
endif;



if ( !function_exists('cgmp_create_html_label') ):
		function cgmp_create_html_label($attr) {
			$for = $attr['for'];
			$value = $attr['value'];
		 	return "<label for=".$for.">".$value."</label>";
	}
endif;


if ( !function_exists('cgmp_create_html_geo') ):
		function cgmp_create_html_geo($attr) {
				$id = $attr['id'];
				$name = $attr['name'];
				$class = $attr['class'];
				$style = $attr['style'];

				return  "<input type='checkbox' class='".$class."' id='".$id."' name='".$name."' style='".$style."' />";
		}
endif;



if ( !function_exists('cgmp_create_html_geohidden') ):
		function cgmp_create_html_geohidden($attr) {
				$id = $attr['id'];
				$name = $attr['name'];
				$class = $attr['class'];
				$style = $attr['style'];
				$value = $attr['value'];

				return "<input type='hidden' class='' id='".$id."' name='".$name."' value='".$value ."' />";
		}
endif;


if ( !function_exists('cgmp_create_html_geobubble') ):
		function cgmp_create_html_geobubble($attr) {
				$id = $attr['id'];
				$name = $attr['name'];
				$class = $attr['class'];
				$style = $attr['style'];
				$value = $attr['value'];

				$falseselected = "checked";
				$trueselected = "";

				if ($value == "true") {
					$falseselected = "";
					$trueselected = "checked";
				}

				$elem = "<input type='radio' class='".$class."' id='".$id."-false' role='".$name."' name='".$name."' ".$falseselected." value='false' />&nbsp;";
				$elem .= "<label for='".$id."-false'>Display Geo address and lat/long in the marker info bubble</label><br />";
				$elem .= "<input type='radio' class='".$class."' id='".$id."-true' role='".$name."' name='".$name."' ".$trueselected." value='true' />&nbsp;";
				$elem .= "<label for='".$id."-true'>Display linked title and excerpt of the original blog post in the marker info bubble</label>";
				return $elem;
		}
endif;



if ( !function_exists('cgmp_create_html_custom') ):
		function cgmp_create_html_custom($attr) {
				$id = $attr['id'];
				$name = $attr['name'];
				$class = $attr['class'];
				$style = $attr['style'];
				$start =  "<ul class='".$class."' id='".$id."' name='".$name."' style='".$style."'>";

				$markerDir = CGMP_PLUGIN_IMAGES_DIR . "/markers/";

				$items = "<div id='".$id."' class='".$class."' style='margin-bottom: 15px; padding-bottom: 10px; padding-top: 10px; padding-left: 30px; height: 200px; overflow: auto; border-radius: 4px 4px 4px 4px; border: 1px solid #C9C9C9;'>";
				if (is_readable($markerDir)) {

					if ($dir = opendir($markerDir)) {

						$files = array();
						while ($files[] = readdir($dir));
						sort($files);
						closedir($dir);

						$extensions = array("png", "jpg", "gif", "jpeg");

						foreach ($files as $file) {
							$ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));

							if (!in_array($ext, $extensions)) {
								continue;
							}

							if ($file != "shadow.png") {
									$class = "";
									$style = "";
									$sel = "";
									$iconId = "";
									$radioId = "";
									$src = CGMP_PLUGIN_IMAGES."/markers/".$file;
									if ($file == "1-default.png") {
											$class = "selected-marker-image nomarker";
											$style = "cursor: default; ";
											$sel = "checked='checked'";
											$iconId = "default-marker-icon";
											$radioId = $iconId."-radio";
									} else if ($file == "2-default.png" || $file == "3-default.png") {
											$class = "nomarker";
									}

									$items .= "<div style='float: left; text-align: center; margin-right: 8px;'><a href='javascript:void(0);'><img id='".$iconId."' style='".$style."' class='".$class."' src='".$src."' border='0' /></a><br /><input ".$sel." type='radio' id='".$radioId."' value='".$file."' style='' name='custom-icons-radio' /></div>";

							}
        				}
					}
				}

			return $items."</div>";
	}
endif;


if ( !function_exists('cgmp_replace_template_tokens') ):
	function cgmp_replace_template_tokens($token_values, $template)  {
		foreach ($token_values as $key => $value) {
			$template = str_replace($key, $value, $template);
		}
		return $template;
	}
endif;


if ( !function_exists('cgmp_build_template_values') ):
		function cgmp_build_template_values($settings) {


		$template_values = array();

		foreach($settings as $setting) {
			$func_type = $setting['type'];
			$token = $setting['token'];
			$attr = $setting['attr'];

			$pos = strrpos($func_type, "@");

			if ($pos !== 0) {
				$pieces = explode("@", $func_type);
				$func_type = $pieces[0];
				$attr['elem_type'] = $pieces[1];
			}


			$func =  "cgmp_create_html_".$func_type;
			//echo $token. " " .$func."<br />";
			$template_values[strtoupper($func_type)."_".strtoupper($token)] = $func($attr);
		}
		return $template_values;
	}
endif;


if ( !function_exists('cgmp_set_values_for_html_rendering') ):
	function cgmp_set_values_for_html_rendering(&$settings, $params) {

		$html_element_select_options = array();
		$html_element_select_options['show_hide'] = array("Show" => "true", "Hide" => "false");
		$html_element_select_options['enable_disable_xor'] = array("Enable" => "false", "Disable" => "true");
		$html_element_select_options['enable_disable'] = array("Enable" => "true", "Disable" => "false");
		$html_element_select_options['map_types'] = array("Roadmap"=>"ROADMAP", "Satellite"=>"SATELLITE", "Hybrid"=>"HYBRID", "Terrain" => "TERRAIN");
		$html_element_select_options['animation_types'] = array("Drop"=>"DROP", "Bounce"=>"BOUNCE");
		$html_element_select_options['map_aligns'] = array("Center"=>"center", "Right"=>"right", "Left" => "left");
		$html_element_select_options['languages'] = array("Default" => "default", "Arabic" => "ar", "Basque" => "eu", "Bulgarian" => "bg", "Bengali" => "bn", "Catalan" => "ca", "Czech" => "cs", "Danish" => "da", "English" => "en", "German" => "de", "Greek" => "el", "Spanish" => "es", "Farsi" => "fa", "Finnish" => "fi", "Filipino" => "fil", "French" => "fr", "Galician" => "gl", "Gujarati" => "gu", "Hindi" => "hi", "Croatian" => "hr", "Hungarian" => "hu", "Indonesian" => "id", "Italian" => "it", "Hebrew" => "iw", "Japanese" => "ja", "Kannada" => "kn", "Korean" => "ko", "Lithuanian" => "lt", "Latvian" => "lv", "Malayalam" => "ml", "Marathi" => "mr", "Dutch" => "nl", "Norwegian" => "no", "Oriya" => "or", "Polish" => "pl", "Portuguese" => "pt", "Romanian" => "ro", "Russian" => "ru", "Slovak" => "sk", "Slovenian" => "sl", "Serbian" => "sr", "Swedish" => "sv", "Tagalog" => "tl", "Tamil" => "ta", "Telugu" => "te", "Thai" => "th", "Turkish" => "tr", "Ukrainian" => "uk", "Vietnamese" => "vi", "Chinese (simpl)" => "zh-CN", "Chinese (tradi)" => "zh-TW");


		if (isset($params['htmlLabelValue']) && trim($params['htmlLabelValue']) != "") {
			$settings[] = array("type" => "label", "token" => $params['templateTokenNameSuffix'], 
				"attr" => array("for" => $params['dbParameterId'], "value" => $params['htmlLabelValue'])); 
		}

		$settings[] = array("type" => $params['htmlElementType'], "token" => $params['templateTokenNameSuffix'],
				"attr"=> array("role" => $params['templateTokenNameSuffix'],
				"id" => $params['dbParameterId'],
				"name" => $params['dbParameterName'],
				"value" => (isset($params['dbParameterValue']) ? $params['dbParameterValue'] : ""),
				"class" => (isset($params['cssClasses']) ? $params['cssClasses'] : ""),
				"style" => (isset($params['inlineCss']) ? $params['inlineCss'] : ""),
				"options" => (isset($params['htmlSelectOptionsKey']) ? $html_element_select_options[$params['htmlSelectOptionsKey']] : array()))); 
	}
endif;



if ( !function_exists('cgmp_google_map_deregister_scripts') ):
function cgmp_google_map_deregister_scripts() {
	$handle = '';
	global $wp_scripts;

	if (isset($wp_scripts->registered) && is_array($wp_scripts->registered)) {
		foreach ( $wp_scripts->registered as $script) {

			if (strpos($script->src, 'http://maps.googleapis.com/maps/api/js') !== false && $script->handle != 'cgmp-google-map-api') {

				if (!isset($script->handle) || $script->handle == '') {
					$script->handle = 'remove-google-map-duplicate';
				}

				unset($script->src);
				$handle = $script->handle;

				if ($handle != '') {
					$wp_scripts->remove( $handle );
					$handle = '';
					break;
				}
			}
		}
	}
}
endif;




if ( !function_exists('cgmp_invalidate_published_post_marker') ):
		function cgmp_invalidate_published_post_marker($postID)  {

			$db_markers = get_option(CGMP_DB_PUBLISHED_POST_MARKERS);

			if (!isset($db_markers) || $db_markers == '') {
				$db_markers = array();
			} else if ($db_markers != '') {
				$db_markers = unserialize(base64_decode($db_markers));
			}

			if (is_array($db_markers) && count($db_markers) > 0) {
				if (isset($db_markers[$postID])) {
					update_option(CGMP_DB_POST_COUNT, -1);
				}
			}
		}
endif;



if ( !function_exists('cgmp_cleanup_markers_from_published_posts') ):

		function cgmp_cleanup_markers_from_published_posts()  {
			update_option(CGMP_DB_PUBLISHED_POST_MARKERS, '');
			update_option(CGMP_DB_POST_COUNT, '');
		}
endif;

if ( !function_exists('cgmp_plugin_row_meta') ):
	function cgmp_plugin_row_meta($links, $file) {
		$plugin =  plugin_basename(CGMP_PLUGIN_BOOTSTRAP);

		if ($file == $plugin) {

			$links = array_merge( $links,
				array( sprintf( '<a href="admin.php?page=cgmp-documentation">%s</a>', __('Documentation'), 'cgmp' ) ),
				array( sprintf( '<a href="admin.php?page=cgmp-shortcodebuilder">%s</a>', __('Shortcode Builder'), 'cgmp' ) ),
				array( sprintf( '<a href="admin.php?page=cgmp-settings">%s</a>', __('Settings'), 'cgmp' ) ),
				array( '<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=CWNZ5P4Z8RTQ8" target="_blank">' . __('Donate') . '</a>' )
			);
		}
		return $links;
}

endif;



if ( !function_exists('cgmp_extract_markers_from_published_posts') ):

		function cgmp_extract_markers_from_published_posts()  {

			$serial = get_option(CGMP_DB_PUBLISHED_POST_MARKERS);
			$db_markers = '';
			if ($serial != '') {
				$db_markers = unserialize(base64_decode($serial));
			}
			$first_time_run = false;
			$invalidation_needed = false;

			if (!isset($db_markers) || $db_markers == '') {
				$first_time_run = true;
				//echo "Running marker location extraction for the first time<br />";
			} else if (is_array($db_markers) && count($db_markers) > 0) {
				$total_post_count = get_option(CGMP_DB_POST_COUNT);

				if (isset($total_post_count) && $total_post_count != '') {
					$count_posts = wp_count_posts();
					$published_posts = $count_posts->publish;

					if ($total_post_count != $published_posts) {
						$invalidation_needed = true;
						//echo "Current total of saved posts is not equal to the actual number of published posts<br />";
					}
				} else {
					$invalidation_needed = true;
					//echo "Dont have current total of saved posts<br />";
				}

			} else if (is_array($db_markers) && count($db_markers) == 0) {
				$invalidation_needed = true;
				//echo "Array of saved marker locations is empty<br />";
			} else {
				//$invalidation_needed = true;
			}

				if ($invalidation_needed || $first_time_run) {

					$db_markers = extract_locations_from_all_posts();

					if (sizeof($db_markers) > 0) {
						$serial = base64_encode(serialize($db_markers)); 
						update_option(CGMP_DB_PUBLISHED_POST_MARKERS, $serial);
						update_option(CGMP_DB_POST_COUNT, count($posts));
					}
				} else {
					//echo "Not extracting for the first time and no invalidation needed, simply outputing existing array<br />";
				}

				//echo "Marker list: " .print_r($db_markers, true);
			//exit;

				return $db_markers;
        	}
endif;


if ( !function_exists('extract_locations_from_all_posts') ):
		function extract_locations_from_all_posts()  {
			$args = array(
					'numberposts'     => 120,
	    		    'orderby'         => 'post_date',
	    			'order'           => 'DESC',
	   	 		    'post_type'       => 'post',
					'post_status'     => 'publish' );

				$posts = get_posts( $args );

				$db_markers = array();
				foreach($posts as $post) {

					//echo "Extracted list: " .print_r($post, true)."<br /><br />";

					$post_content = $post->post_content;
					$extracted = extract_locations_from_post_content($post_content);
					//echo "Extracted list: " .print_r($extracted, true)."<br /><br />";
					if (count($extracted) > 0) {
							$post_title = $post->post_title;
							$post_title = str_replace("'", "", $post_title);
							$post_title = str_replace("\"", "", $post_title);
							$post_title = preg_replace("/\r\n|\n\r|\n|\r/", "", $post_title);
							$db_markers[$post->ID]['markers'] = $extracted;
							$db_markers[$post->ID]['title'] = $post_title;
							$db_markers[$post->ID]['permalink'] = $post->guid;
							$db_markers[$post->ID]['excerpt'] = '';

						$clean = "";
						if (isset($post->post_excerpt) && strlen($post->post_excerpt) > 0) {
							$clean = clean_excerpt($post->post_excerpt);
						} else {
							$clean = clean_excerpt($post_content);
						}
						
						//Dont consider text that has shortcodes of some sort
						if ( strlen($clean) > 0 ) {
							$excerpt = substr($clean, 0, 130);
							$db_markers[$post->ID]['excerpt'] = $excerpt."..";
						} 
					}
				}
				//echo "Extracted list: " .print_r(json_decode(json_encode($db_markers)), true)."<br /><br />";
				return $db_markers;

	}
endif;

if ( !function_exists('clean_excerpt') ):
	function clean_excerpt($content)  {

		if (!isset($content) || $content == "") {
			return $content;
		}
	
		$content = preg_replace ('@<[^>]*>@', '', $content);
		$start = strpos($content, "[");

		if ($start !== false) {

				if ($start > 0) {
					$content = substr($content, 0, $start - 1);
					$content = str_replace("'", "", $content);
					$content = str_replace("\"", "", $content);
					$content = preg_replace("/\r\n|\n\r|\n|\r/", "", $content);
				} else {
					$content = "";
				}
		} else {
			$content = str_replace("'", "", $content);
			$content = str_replace("\"", "", $content);
			$content = preg_replace("/\r\n|\n\r|\n|\r/", "", $content);
		}
		return trim($content);
	}
endif;


if ( !function_exists('extract_locations_from_post_content') ):
	function extract_locations_from_post_content($post_content)  {

		$arr = array();

		if (isset($post_content) && $post_content != '') {
				
			if (strpos($post_content, "addresscontent") !== false) {
				$pattern = "/addresscontent=\"(.*?)\"/";
				$found = find_for_regex($pattern, $post_content); 

				if (count($found) > 0) {
					$arr = array_merge($arr, $found);
				}
			}

			if (strpos($post_content, "addmarkerlist") !== false) {
				$pattern = "/addmarkerlist=\"(.*?)\"/";
				$found = find_for_regex($pattern, $post_content); 

				if (count($found) > 0) {
					$arr = array_merge($arr, $found);
				}
			}

			if (strpos($post_content, "latitude") !== false) {

				$pattern = "/latitude=\"(.*?)\"(\s{0,})longitude=\"(.*?)\"/";

				preg_match_all($pattern, $post_content, $matches);

				if (is_array($matches)) {

					if (isset($matches[1]) && is_array($matches[1]) &&
						isset($matches[3]) && is_array($matches[3])) {

						for ($idx = 0; $idx < sizeof($matches[1]); $idx++) {
							
							if (isset($matches[1][$idx]) && isset($matches[3][$idx])) {
								$lat = $matches[1][$idx];
								$lng = $matches[3][$idx];

								if (trim($lat) != "0" && trim($lng) != "0") {
									$coord = trim($lat).",".trim($lng);
									$arr[$coord] = $coord;
								}
							}
						}
					}
				}
			}

			$arr = array_unique($arr);
		}
		return $arr;
	}

endif;


if ( !function_exists('find_for_regex') ):

	function find_for_regex($pattern, $post_content)  {
			$arr = array();
			preg_match_all($pattern, $post_content, $matches);

			if (is_array($matches)) {
				if (isset($matches[1]) && is_array($matches[1])) {

					foreach($matches[1] as $key => $value) {
						if (isset($value) && trim($value) != "") {

							if (strpos($value, "|") !== false) {
								$value_arr = explode("|", $value);
								foreach ($value_arr as $value) {
									$arr[$value] = $value;
								}
							} else {
								$arr[$value] = $value;
							}
						}
					}
				}
			}

		return $arr;
	}
endif;



if ( !function_exists('make_marker_geo_mashup') ):

function make_marker_geo_mashup()   {

	$db_markers = cgmp_extract_markers_from_published_posts();

	//echo "Extracted list: " .print_r($db_markers, true)."<br /><br />";
	//exit;

	if (is_array($db_markers) && count($db_markers) > 0) {

		$filtered = array();
		foreach($db_markers as $postID => $post_data) {

			//echo "Extracted list: " .print_r($post_data, true)."<br /><br />";
			//exit;

			$title = $post_data['title'];
			$permalink = $post_data['permalink'];
			$markers = $post_data['markers'];
			$excerpt = $post_data['excerpt'];

			$markers = implode("|", $markers);
			$addmarkerlist = update_markerlist_from_legacy_locations(0, 0, "", $markers);
			$markers = explode("|", $addmarkerlist);

			foreach($markers as $full_loc) {

				$tobe_filtered_loc = $full_loc;
				if (strpos($full_loc, CGMP_SEP) !== false) {
					$loc_arr = explode(CGMP_SEP, $full_loc);
					$tobe_filtered_loc = $loc_arr[0];
				}

				if (!isset($filtered[$tobe_filtered_loc])) {
					$filtered[$tobe_filtered_loc]['addy'] = $full_loc;
					$filtered[$tobe_filtered_loc]['title'] = $title;
					$filtered[$tobe_filtered_loc]['permalink'] = $permalink;
					$filtered[$tobe_filtered_loc]['excerpt'] = $excerpt;
				}
			}
		}

		return json_encode($filtered);
	}
}
endif;

?>
