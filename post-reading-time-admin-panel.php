<?php
/*
Plugin Name: Post Reading Time Admin Panel
Plugin URI: http://longtail-marketing.com/
Description: Allows admin to see an estimate of the time it would take a user to read their post.
Author: Longtail Marketing
Version: 1.0.0
Author URI: http://longtail-marketing.com/
*/

/*
    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License version 2, 
    as published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.
*/

// First we register all the functions
register_activation_hook(__FILE__, 'prtap_post_estimated_reading_time_install');

register_deactivation_hook(__FILE__, 'prtap_post_estimated_reading_time_uninstall');

// Options when activating the plugin
function prtap_post_estimated_reading_time_install() {
    add_option('_prtap_post_estimated_reading_time', '230', '', 'yes'); // Add the option for prefix to string
}

// Options when deactivating the plugin (delete the options from DB)
function prtap_post_estimated_reading_time_uninstall() {
    delete_option('_prtap_post_estimated_reading_time');
}	

// The actual function that does the work and output the string of the estimated reading time of the post 
function prtap_post_estimated_reading_time() {

    $words_per_second_option = absint( get_option('_prtap_post_estimated_reading_time') );
    $prefix = "It would take about ";
    $suffix = " to read this post.";
    $time = 2; //1 minutes // 2 minutes and seconds

    $post_id = get_the_ID();

    $content = apply_filters('the_content', get_post_field('post_content', $post_id));
    $num_words = str_word_count(strip_tags($content));
    $minutes = floor($num_words / $words_per_second_option);
    $seconds = floor($num_words % $words_per_second_option / ($words_per_second_option / 60));
    $estimated_time = $prefix;
	
    if($time == "1") {
        if($seconds >= 30) {
            $minutes = $minutes + 1;
        }
        $estimated_time = $estimated_time.' '.$minutes . ' minute'. ($minutes == 1 ? '' : 's');
    }
    else {
        $estimated_time = $estimated_time.' '.$minutes . ' minute'. ($minutes == 1 ? '' : 's') . ', ' . $seconds . ' second' . ($seconds == 1 ? '' : 's');		
    }

    if($minutes < 1) {
        $estimated_time = $estimated_time; 
    }

    $estimated_time = $estimated_time.$suffix;

    echo $estimated_time;

}

// The actual function that does the work and output the string of the estimated reading time of the post 
function prtap_post_column_estimated_reading_time() {

    $words_per_second_option = absint( get_option('_prtap_post_estimated_reading_time') );
    $time = 2; //1 minutes // 2 minutes and seconds

    $post_id = get_the_ID();

    $content = apply_filters('the_content', get_post_field('post_content', $post_id));
    $num_words = str_word_count(strip_tags($content));
    $minutes = floor($num_words / $words_per_second_option);
    $seconds = floor($num_words % $words_per_second_option / ($words_per_second_option / 60));

    if($time == "1") {
        if($seconds >= 30) {
            $minutes = $minutes + 1;
        }
        $estimated_time = $estimated_time.' '.$minutes . ' minute'. ($minutes == 1 ? '' : 's');
    }
    else {
        $estimated_time = $estimated_time.' '.$minutes . ' minute'. ($minutes == 1 ? '' : 's') . ', ' . $seconds . ' second' . ($seconds == 1 ? '' : 's');		
    }
    if($minutes < 1) {
        $estimated_time = $estimated_time; //." Less than a minute";
    }

    return $estimated_time;

}

add_action( 'add_meta_boxes', 'prtap_add_posts_metaboxes' );

// Add the Posts Meta Boxes

function prtap_add_posts_metaboxes() {
    add_meta_box('prtap_prtap_post_estimated_reading_time', 'Estimated Reading Time', 'prtap_prtap_post_estimated_reading_time', 'post', 'side', 'high');
}

// The Post Estimated Reading Time Metabox

function prtap_prtap_post_estimated_reading_time() {
    global $post;

    echo '<input type="hidden" name="prtap_postmeta_noncename" id="prtap_postmeta_noncename" value="' . 
    wp_create_nonce( plugin_basename(__FILE__) ) . '" />';

    $prtap_post_estimated_reading_time = absint( get_option('_prtap_post_estimated_reading_time') );

    echo '<label style="padding: 15px 0 30px 0;">Reading speed (words per minute):</label><input type="text" pattern="[0-9]{1,5}" name="_prtap_post_estimated_reading_time" value="' . $prtap_post_estimated_reading_time  . '" class="widefat" /><br/><br/>';

    prtap_post_estimated_reading_time();

}

// Save the Metabox Data

function prtap_save_posts_meta($post_id, $post) {
	
    if ( !wp_verify_nonce( sanitize_text_field( $_POST['prtap_postmeta_noncename'] ), plugin_basename(__FILE__) )) {
        return $post->ID;
    }

    if ( !current_user_can( 'edit_post', $post->ID )){
        return $post->ID;
    }

    $prtap_post_estimated_reading_time = sanitize_text_field( $_POST['_prtap_post_estimated_reading_time'] );
    
    $prtap_post_estimated_reading_time = intval( $prtap_post_estimated_reading_time );
    
    if ( ! $prtap_post_estimated_reading_time ) {
        $prtap_post_estimated_reading_time = 230;
    }
    
    update_option('_prtap_post_estimated_reading_time', $prtap_post_estimated_reading_time);

}

add_action('save_post', 'prtap_save_posts_meta', 1, 2); // save the custom fields

add_filter('manage_post_posts_columns' , 'prtap_add_post_columns');
 
function prtap_add_post_columns($columns) {
    
    return array_merge($columns,
        array('prtap' => 'Estimated Reading Time'));
}
 
add_action('manage_posts_custom_column' , 'prtap_post_custom_columns', 10, 2 );
 
function prtap_post_custom_columns( $column, $post_id ) {
    switch ( $column ) { 
        case 'prtap' :
        echo prtap_post_column_estimated_reading_time();
        break;
    }
}

?>
