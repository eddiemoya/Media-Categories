<?php
 
/**
 * A walker class to use that extends wp_dropdown_categories and allows it to use the term's slug as a value rather than ID.
 *
 * See http://core.trac.wordpress.org/ticket/13258
 *
 * Usage, as normal:
 * wp_dropdown_categories($args);
 *
 * But specify the custom walker class, and (optionally) a 'id' or 'slug' for the 'value' parameter:
 * $args=array('walker'=> new SH_Walker_TaxonomyDropdown(), 'value'=>'slug', .... );
 * wp_dropdown_categories($args);
 * 
 * If the 'value' parameter is not set it will use term ID for categories, and the term's slug for other taxonomies in the value attribute of the term's <option>.
 *
 * @author Stephen Harris - https://gist.github.com/stephenh1988
 * @link https://gist.github.com/stephenh1988/2902509
 *
 */
 
class SH_Walker_TaxonomyDropdown extends Walker_CategoryDropdown{
 
    function start_el(&$output, $category, $depth = 0, $args = array(), $id = 0) {
        $pad = str_repeat('&nbsp;', $depth * 3);
        $cat_name = apply_filters('list_cats', $category->name, $category);
 
        if( !isset($args['value']) ){
            $args['value'] = ( $category->taxonomy != 'category' ? 'slug' : 'id' );
        }
 
        $value = ($args['value']=='slug' ? $category->slug : $category->term_id );
 
        $output .= "\t<option class=\"level-$depth\" value=\"".$value."\"";
        if ( $value === (string) $args['selected'] ){ 
            $output .= ' selected="selected"';
        }
        $output .= '>';
        $output .= $pad.$cat_name;
        if ( $args['show_count'] )
            $output .= '&nbsp;&nbsp;('. $category->count .')';
 
        $output .= "</option>\n";
        }
 
}