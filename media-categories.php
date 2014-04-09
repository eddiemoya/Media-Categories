<?php /*
Plugin Name: Media Categories
Plugin URI: http://wordpress.org/extend/plugins/media-categories-2
Description:  Allows users to assign categories to media with a clean and simplified, filterable category meta box and use shortcodes to display category galleries
Version: 1.5
Author: Eddie Moya
Author URL: http://eddiemoya.com
*/

require_once(plugin_dir_path(__FILE__) . 'metaboxes/filterable-taxonomy-faux-metabox.php');
require_once(plugin_dir_path(__FILE__) . 'metaboxes/filterable-taxonomy-metabox.php');

class Media_Categories {
    public static $version = 1.5;
    public static $instances;
    public $override_default_gallery;
    public $taxonomy;
    
    /**
     * Start your engines.
     *
     * @param type $taxonomy 
     * @return void 
     */
    public function __construct($taxonomy, $args = array()) {

        // Store each instance of this class (for use when localizing scripts)
        $this->taxonomy = $taxonomy;

        //Todo: $args should have a default and then be array merged.
        if(isset($args['override_default_gallery'])){
            $this->override_default_gallery = $args['override_default_gallery'];
        } else {
            $this->override_default_gallery = true;
        }

        add_action('init', array(&$this, 'set_instances'), 8);
        add_action('init', array(&$this, 'setup'),9);
       // print_r($this);
    }

    function set_instances(){

        $this->taxonomy = apply_filters('mc_taxonomy', $this->taxonomy);
        self::$instances[] = $this;
    }

    /**
     * Hooks are evil.
     * 
     * @since 1.6
     * @return void
     */
    public function setup(){
        global $wp_version;
        
        add_action('init', array(&$this, 'register_media_categories'));
        add_action('init', array(&$this, 'taxonomy_gallery_shortcode'));
        add_action('init', array(&$this, 'default_gallery_shortcode')); // For backward compatibility only!

        // Sort by taxonomy (for sortable columns in the Media Library)
        add_filter( 'posts_clauses', array(&$this, 'query_order_by_taxonomy'), 10, 2 );

        //Query Attachments
        add_filter('ajax_query_attachments_args', array(__CLASS__, 'query_attachments'));

        // In < 3.5 this is used for the main metabox on media admin pages - because normal metaboxes were not available
        // In 3.5 > This is used soley for the Media Modal right rail. Where there is also no normal metabox availability
        add_filter('attachment_fields_to_edit', array(new Filterable_Taxonomy_Faux_Metabox($this->taxonomy), 'add_taxonomy_meta_box'), null, 2);
        
        /* Only before WordPress 3.5 */
        if( $wp_version < 3.5 ){

            // Patch to solve this in 3.5 was accepted @see http://core.trac.wordpress.org/ticket/20765
            add_filter('attachment_fields_to_edit', array(__CLASS__, 'get_attachment_fields_to_edit'), 11, 2);

            // Loading these in this fashion no longer applies in 3.5 because of new built-in support for taxonomy metaboxes on the editor page.
            add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_media_categories_scripts'));
            add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_media_categories_styles') );

        } else {

            add_filter('admin_menu', array(new Filterable_Taxonomy_Metabox($this->taxonomy), 'add_taxonomy_meta_box'));

            add_action('restrict_manage_posts',array($this, 'restrict_manage_attachments'));
            add_filter( 'manage_upload_sortable_columns', array($this, 'sortable_columns') );

            add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_media_categories_scripts'));
            add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_media_categories_styles') );

            // add_action('wp_enqueue_media', array(__CLASS__, 'enqueue_media_categories_scripts'));
            // add_action('wp_enqueue_media', array(__CLASS__, 'enqueue_media_categories_styles') );
        }
    }

    /**
     * Enqueue javascript
     *
     * @return void
     *
     * @todo Requires clean up = no need for both $tax and $terms to be localized. Just being lazy.
     */
    public static function enqueue_media_categories_scripts() {
        global $wp_version;

        if (is_admin()) {
            
            // Get each instance of this class, and pass each taxonomy in to javascript
            foreach (self::$instances as $instance){

                $taxonomy = $instance->taxonomy;
                $taxonomies[] = $taxonomy;
                $terms[$taxonomy] = self::get_non_empty_terms($taxonomy, array('attachment'));
             }
                
            $filename = ($wp_version < 3.5) ? 'media-categories-script-3.4.js' : 'media-categories-script.js';

            wp_enqueue_media();
            wp_register_script('media_categories_metabox_script', plugins_url($filename, __FILE__));
            wp_enqueue_script('media_categories_metabox_script');
            
            wp_localize_script('media_categories_metabox_script', 'taxonomy',  $taxonomies);
            wp_localize_script('media_categories_metabox_script', 'terms',  $terms);
   
        }
    }

    /**
     * Modifying the query run on the query-attachments ajax call.
     *
     * @since 1.6
     * @param array $query - the current query being generated by the ajax calls.
     *
     * @return $query - potentially modified query.
     */ 
    public function query_attachments($query){
        if(isset($_REQUEST['query']['tax_query'])) {
            $query['tax_query'] = $_REQUEST['query']['tax_query'];
        }
        return $query;
    }
    
    /**
     * @return void
     */
    public static function enqueue_media_categories_styles() {
        global $wp_version;

        if (is_admin()) { 
            
            $filename = ($wp_version < 3.5) ? 'media-categories-style-3.4.css' : 'media-categories-style.css';
            
            wp_register_style('media_categories_metabox_style', plugins_url($filename , __FILE__));
            wp_enqueue_style( 'media_categories_metabox_style');
        }
    }

    /**
     * This adds native support for categories to the attachment editor, however
     * instead of the standard metabox wordpress only provides a text area wich
     * the user would have to type slugs.
     *     
     * @return void
     */
    public function register_media_categories() {
        // $tax_name = apply_filters('mc_taxonomy', $this->taxonomy);
        
        register_taxonomy_for_object_type($this->taxonomy, 'attachment');
    }


    /**
     * If $this->override_default_gallery is set to true, this function
     * removes the default gallery shortcode and subitutes ours.
     *
     * @since 1.6
     * @return void
     */
    public function default_gallery_shortcode(){
        if($this->override_default_gallery === true){   
            remove_shortcode('gallery');
            add_shortcode('gallery', array(&$this,'gallery_shortcode'));  
        }

    }

    /**
     * Adds a custom shortcode gallery
     *
     * @since 1.6
     * @return void      
     */
    public function taxonomy_gallery_shortcode(){ 
        add_shortcode('media_gallery', array(&$this,'gallery_shortcode'));
    }
    
    /**
     * The Gallery shortcode with category parameter.
     *
     * This implements the functionality of the Gallery Shortcode for displaying
     * WordPress images on a post.
     * 
     * Almost Identical to the gallery_shortcode() function in /wp-includes/media.php
     * but adds a category parameter to the shortcode.
     *
     * @since 1.2
     * @since WordPress 2.6.0
     *
     * @param array $attr Attributes of the shortcode.
     * @return string HTML content to display gallery.
     */
    public function gallery_shortcode($attr) {
        global $wp_version;

        // Could probably just leave it as get_post(), but i'm being lazy and don't feel like testing to be sure - so im putting in this logic to avoid any possible problem.
        if($wp_version < 3.5){
            global $post;
        } else {
            $post = get_post();
        }

        static $instance = 0;
        $instance++;

        if ( ! empty( $attr['ids'] ) ) {
            // 'ids' is explicitly ordered, unless you specify otherwise.
            if ( empty( $attr['orderby'] ) )
                $attr['orderby'] = 'post__in';
            $attr['include'] = $attr['ids'];
        }
   
        // Allow plugins/themes to override the default gallery template.
        $output = apply_filters('post_gallery', '', $attr);
        if ( $output != '' )
            return $output;

        // We're trusting author input, so let's at least make sure it looks like a valid orderby statement
        if ( isset( $attr['orderby'] ) ) {
            $attr['orderby'] = sanitize_sql_orderby( $attr['orderby'] );
            if ( !$attr['orderby'] )
                unset( $attr['orderby'] );
        }

        $mc_tax_list = array_fill_keys(wp_list_pluck(self::$instances, 'taxonomy'), '');

        $ac_atts = array(
            'order'      => 'ASC',
            'orderby'    => 'menu_order ID',
            'id'         => $post->ID,
            'itemtag'    => 'dl',
            'icontag'    => 'dt',
            'captiontag' => 'dd',
            'columns'    => 3,
            'size'       => 'thumbnail',
            'include'    => '',
            'exclude'    => '',
        ) + $mc_tax_list; 

        extract(shortcode_atts(
            $ac_atts, $attr
        ));
        
        $id = intval($id);
        if ( 'RAND' == $order )
            $orderby = 'none';
                
        $tax_query = array();

        foreach($mc_tax_list as $mc_tax => $nothing){

            $tax_query['tax_query']['relation'] = 'OR';
       
            if(!empty(${$mc_tax})){

                //Split the categories on commas into an array of categories
                $terms = explode(',',${$mc_tax});
                $term_field = (is_numeric($terms)) ? 'id' : 'slug';
                $tax_query['tax_query'][] = array(
                    'taxonomy' => $mc_tax, 
                    'field' => $term_field, 
                    'terms' => $terms
                );
            }
                  
            if(!isset($attr['id']))
                $id = '';
        }
        
        if(!empty($tax_query)){
            
            if ( !empty($include) ) {
                //$include = preg_replace( '/[^0-9,]+/', '', $include ); see: http://core.trac.wordpress.org/ticket/21827
                $_attachments = get_posts( array('include' => $include, 'post_status' => 'inherit', 'post_type' => 'attachment', 'post_mime_type' => 'image', 'order' => $order, 'orderby' => $orderby) + $tax_query );

                $attachments = array();
                foreach ( $_attachments as $key => $val ) {
                    $attachments[$val->ID] = $_attachments[$key];
                }
            } elseif ( !empty($exclude) ) {
                //$exclude = preg_replace( '/[^0-9,]+/', '', $exclude ); see: http://core.trac.wordpress.org/ticket/21827
                $attachments = get_children( array('post_parent' => $id, 'exclude' => $exclude, 'post_status' => 'inherit', 'post_type' => 'attachment', 'post_mime_type' => 'image', 'order' => $order, 'orderby' => $orderby) + $tax_query );
            } else {
                $attachments = get_children( array('post_parent' => $id, 'post_status' => 'inherit', 'post_type' => 'attachment', 'post_mime_type' => 'image', 'order' => $order, 'orderby' => $orderby) + $tax_query );
            }

            if ( empty($attachments) )
                return '';

            if ( is_feed() ) {
                $output = "\n";
                foreach ( $attachments as $att_id => $attachment )
                    $output .= wp_get_attachment_link($att_id, $size, true) . "\n";
                return $output;
            }

            $itemtag = tag_escape($itemtag);
            $captiontag = tag_escape($captiontag);
            $columns = intval($columns);
            $itemwidth = $columns > 0 ? floor(100/$columns) : 100;
            $float = is_rtl() ? 'right' : 'left';

            $selector = "gallery-{$instance}";

            $gallery_style = $gallery_div = '';
            if ( apply_filters( 'use_default_gallery_style', true ) )
                $gallery_style = "
                <style type='text/css'>
                    #{$selector} {
                        margin: auto;
                    }
                    #{$selector} .gallery-item {
                        float: {$float};
                        margin-top: 10px;
                        text-align: center;
                        width: {$itemwidth}%;
                    }
                    #{$selector} img {
                        border: 2px solid #cfcfcf;
                    }
                    #{$selector} .gallery-caption {
                        margin-left: 0;
                    }
                </style>
                <!-- see gallery_shortcode() in wp-includes/media.php -->";
            $size_class = sanitize_html_class( $size );
            $gallery_div = "<div id='$selector' class='gallery galleryid-{$id} gallery-columns-{$columns} gallery-size-{$size_class}'>";
            $output = apply_filters( 'gallery_style', $gallery_style . "\n\t\t" . $gallery_div );

            $i = 0;
            foreach ( $attachments as $id => $attachment ) {
                $link = isset($attr['link']) && 'file' == $attr['link'] ? wp_get_attachment_link($id, $size, false, false) : wp_get_attachment_link($id, $size, true, false);

                $output .= "<{$itemtag} class='gallery-item'>";
                $output .= "
                    <{$icontag} class='gallery-icon'>
                        $link
                    </{$icontag}>";
                if ( $captiontag && trim($attachment->post_excerpt) ) {
                    $output .= "
                        <{$captiontag} class='wp-caption-text gallery-caption'>
                        " . wptexturize($attachment->post_excerpt) . "
                        </{$captiontag}>";
                }
                $output .= "</{$itemtag}>";
                if ( $columns > 0 && ++$i % $columns == 0 )
                    $output .= '<br style="clear: both" />';
            }

            $output .= "
                    <br style='clear: both;' />
                </div>\n";
        } else {
            $output = gallery_shortcode($attr);
        }

        return $output;
    }
    
   
    /**
     * This function serves to work around the problem explained in trac ticket 20765 and reported
     * to me in the plugin directory support forum on WordPress.org.
     * 
     * It is an exact duplication of code the `get_attachment_fields_to_edit()` function,
     * and its only purpose is to change the output of terms in attachments so that they used term slugs
     * rather than names.
     * 
     * NOTE: This is no longer necessary in WordPress 3.5 - the patch fixing this problem was committed.
     *
     * @link http://core.trac.wordpress.org/ticket/20765
     * @link http://wordpress.org/support/topic/media-categories-2-not-saving-correctly-when-two-categories-with-same-name
     * @see /wp-admin/includes/media.php:get_attachemt_fields_to_edit()
     * 
     * @param type $form_fields
     * @param type $post
     * @return type 
     */
    public function get_attachment_fields_to_edit($form_fields, $post) {

        foreach (get_attachment_taxonomies($post) as $taxonomy) {
            $t = (array) get_taxonomy($taxonomy);
            if (!$t['public'])
                continue;
            if (empty($t['label']))
                $t['label'] = $taxonomy;
            if (empty($t['args']))
                $t['args'] = array();

            $terms = get_object_term_cache($post->ID, $taxonomy);
            if (empty($terms))
                $terms = wp_get_object_terms($post->ID, $taxonomy, $t['args']);

            $values = array();

            foreach ($terms as $term)
                $values[] = $term->slug;
            $t['value'] = join(', ', $values);

            $form_fields[$taxonomy] = $t;
        }

        return $form_fields;
    }

    /**
     * Returns a list of all the terms in a given taxonomy, that have at least one item in them of any of the given post types
     *
     * @since 1.6
     * @link http://wordpress.stackexchange.com/questions/57444/get-terms-by-custom-post-type
     *
     * @param string $taxonomy - Name of the given taxonomy
     * @param array $post_types - Array of post types by which to filter taxonomy terms.
     *
     * @return array - Returns an array of all the terms in the given taxonomy that have at least one post in any of the given post types.
     *
     **/
    public static function get_non_empty_terms($taxonomy, $post_types = array() ){
        global $wpdb;

        $post_types=(array) $post_types;
        $key = 'wpse_terms'.md5($taxonomy.serialize($post_types));
        $results = wp_cache_get($key);

        if ( false === $results ) {
           $where =" WHERE 1=1";
           if( !empty($post_types) ){
                $post_types_str = implode(',',$post_types);
                $where.= $wpdb->prepare(" AND p.post_type IN(%s)", $post_types_str);
           }

           $where .= $wpdb->prepare(" AND tt.taxonomy = %s",$taxonomy);

           $query = "
              SELECT t.*, tt.taxonomy, COUNT(*)  as count
              FROM $wpdb->terms AS t 
              INNER JOIN $wpdb->term_taxonomy AS tt ON t.term_id = tt.term_id 
              INNER JOIN $wpdb->term_relationships AS r ON r.term_taxonomy_id = tt.term_taxonomy_id 
              INNER JOIN $wpdb->posts AS p ON p.ID = r.object_id 
              $where
              GROUP BY t.term_id";

           $results = $wpdb->get_results( $query );
           wp_cache_set( $key, $results );
        }        

        return $results;
    }

    /**
     * This method generates a dropdown to filter items on the Media Library page.
     *
     * @since 1.6
     * @return void
     *
     **/    
    public function restrict_manage_attachments() {
        global $pagenow;
        global $wp_query;

        require_once(plugin_dir_path(__FILE__) . 'walkers/mc-walker-taxonomy-dropdown.php');

        $terms = get_terms($this->taxonomy, array('hide_empty' => false));
        $walker = new SH_Walker_TaxonomyDropdown();

        if ('category' == $this->taxonomy) {
            $name = 'cat';
            $value = 'id';

        } else {
            $name = $this->taxonomy;
            $value = 'slug';
        }
      
        if ($pagenow=='upload.php' && !empty($terms)) {

            $taxonomy = get_taxonomy($this->taxonomy);
            $dropdown_args = array(
                'show_option_all' =>  __("Show All {$taxonomy->label}"),
                'taxonomy'        =>  $this->taxonomy,
                'name'            =>  $name,
                'orderby'         =>  'name',
                'hierarchical'    =>  true,
                'depth'           =>  3,
                'show_count'      =>  false,
                'hide_empty'      =>  false,
                'hide_if_empty'   =>  true,
                'walker'          =>  $walker,
                'value'           =>  $value
            );

            if (isset($wp_query->query[$this->taxonomy])){
                $dropdown_args['selected'] = $wp_query->query[$this->taxonomy];
            }

            return wp_dropdown_categories($dropdown_args);
        }
    }

    /**
     * Adds taxonomy column to the list of columns that should be sortable.
     *
     * @since 1.6
     * @param array $columns - Existing sortable columns
     * @return $array - modified list of sortable columns
     */
    public function sortable_columns( $columns ) {
       
        $column_id = ('category' == $this->taxonomy) ? 'categories' : 'taxonomy-'.$this->taxonomy;
        $columns[$column_id] = $this->taxonomy;

        return $columns;
    }

    /**
     * Adds to WP_Query the ability to be sorted by taxonomy terms
     *
     * @link http://scribu.net/wordpress/sortable-taxonomy-columns.html
     *
     * @param WP_Query $wp_query - The current query.
     * @param array $clauses - The current clauses in the SQL
     *
     * @return array $clauses - Modified set of clauses.
     */
    public function query_order_by_taxonomy( $clauses, $wp_query ) {
        global $wpdb;

        if ( isset( $wp_query->query['orderby'] ) && $this->taxonomy == $wp_query->query['orderby'] ) {

            $clauses['join'] .= "
                LEFT OUTER JOIN {$wpdb->term_relationships} as tr ON {$wpdb->posts}.ID=tr.object_id
                LEFT OUTER JOIN {$wpdb->term_taxonomy} as tt ON (tt.term_taxonomy_id = tr.term_taxonomy_id)
                LEFT OUTER JOIN {$wpdb->terms} USING (term_id)";

            $clauses['where'] .= " AND (taxonomy = '{$this->taxonomy}' OR taxonomy IS NULL)";
            $clauses['groupby'] = "tr.object_id";
            $clauses['orderby']  = "GROUP_CONCAT({$wpdb->terms}.name ORDER BY name ASC) ";
            $clauses['orderby'] .= ( 'ASC' == strtoupper( $wp_query->get('order') ) ) ? 'ASC' : 'DESC';
        }
        return $clauses;
    }        
}

global $mc_media_categories;

$mc_media_categories = new Media_Categories('category');