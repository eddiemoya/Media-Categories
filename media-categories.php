<?php /*
Plugin Name: Media Categories
Plugin URI: http://wordpress.org/extend/plugins/media-categories-2
Description:  Allows users to assign categories to media with a clean and simplified, filterable category meta box and use shortcodes to display category galleries
Version: 1.4.1
Author: Eddie Moya
Author URL: http://eddiemoya.com
*/

class Media_Categories {

    public static $instances;
    public $taxonomy;
    
    /**
     * While normally run statically, this allows 
     * @param type $taxonomy 
     */
    public function __construct($taxonomy) {
        
        // Store each instance of this class (for use when localizing scripts)
        $this->taxonomy = $taxonomy;
        self::$instances[] = $this;
        
        add_action('init', array(&$this, 'register_media_categories'));
        add_action('init', array(&$this, 'custom_gallery_shortcode'));
        add_filter('attachment_fields_to_edit', array(&$this, 'add_media_categories_metabox'), null, 2);
        
        /* These only need to occur once */
        add_filter('attachment_fields_to_edit', array(__CLASS__, 'get_attachment_fields_to_edit'), 11, 2);
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_media_categories_scripts'));
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_media_categories_styles') );
        
        

    }

    /**
     * Enqueue javascript
     */
    function enqueue_media_categories_scripts() {
        if (is_admin()) {
            
            // Get each instance of this class, and pass each taxonomy in to javascript
            foreach (self::$instances as $instance){
                $tax[] = apply_filters('mc_taxonomy', $instance->taxonomy);
            }
                
        
            wp_register_script('media_categories_metabox_script', plugins_url('media-categories-script.js', __FILE__));
            wp_enqueue_script('media_categories_metabox_script');
            
            wp_localize_script('media_categories_metabox_script', 'taxonomy',  $tax);
        }
    }
    
    /**
     * 
     */
    function enqueue_media_categories_styles() {
        if (is_admin()) { 
            
            wp_register_style('media_categories_metabox_style', plugins_url('media-categories-style.css', __FILE__));
            wp_enqueue_style( 'media_categories_metabox_style');
        }
    }

    /**
     * This adds native support for categories to the attachment editor, however
     * instead of the standard metabox wordpress only provides a text area wich
     * the user would have to type slugs.
     */
    function register_media_categories() {
        $tax_name = apply_filters('mc_taxonomy', $this->taxonomy);
        
        register_taxonomy_for_object_type($tax_name, 'attachment');
    }

    /**
     * Here I insert a custom form field into the media editor, but instead of
     * a normal textfield, I capture the output of a custom metabox and insert it.
     */
    function add_media_categories_metabox($form_fields, $post) {

        require_once('./includes/meta-boxes.php');
        
        $tax_name = apply_filters('mc_taxonomy', $this->taxonomy);
        $taxonomy = get_taxonomy($tax_name);

        ob_start();
        
            $this->media_categories_meta_box($post, array('args' => array ('taxonomy' => $tax_name, 'tax' => $taxonomy)));
            
        $metabox = ob_get_clean();
        
        $form_slug = $this->taxonomy . '_metabox';
            
        $form_fields[$form_slug]['label'] = $taxonomy->labels->name;
        $form_fields[$form_slug]['helps'] = sprintf(__('Select a %s, use the text fields above to filter'), strtolower($taxonomy->labels->singular_name));
        $form_fields[$form_slug]['input'] = 'html';
        $form_fields[$form_slug]['html'] = $metabox;
        
        return $form_fields;
    }

    /**
     * I'd liked to have been able to use the standard category metabox but in
     * order to make all this work, we need slugs on the list items, not id's.
     * Since there is no filter in the built-in Walker function I have to create
     * a custom walker, which in turn means I need to use it. Since there is also
     * no filter in the built-in categories metabox for the walker, I needed to 
     * to create this whole custom metabox as well - All just to switch it from
     * using ID's to using slugs.
     * 
     */
    function media_categories_meta_box($post, $box) {
        
        require_once(plugin_dir_path(__FILE__) . 'attachment-walker-category-checklist-class.php');
             
        $defaults = array('taxonomy' => apply_filters('mc_taxonomy',$this->taxonomy));
        
        if (!isset($box['args']) || !is_array($box['args']))
            $args = array();
        else
            $args = $box['args'];
        extract(wp_parse_args($args, $defaults), EXTR_SKIP);
        $tax = get_taxonomy($taxonomy);
        ?>

        <div>
            <label class='category-filter' for="category-filter">Search <?php echo $tax->labels->name; ?>:</label>
            <input id='<?php echo $taxonomy?>-search' name="category-filter" type='text' /></div>
            <div id="taxonomy-<?php echo $taxonomy; ?>" class="categorydiv">

            <ul id="<?php echo $taxonomy; ?>-tabs" class="category-tabs">
                <li class="tabs"><a href="#<?php echo $taxonomy; ?>-all" tabindex="3"><?php echo $tax->labels->all_items; ?></a></li>
                <li class="hide-if-no-js"><a href="#<?php echo $taxonomy; ?>-pop" tabindex="3"><?php _e('Most Used'); ?></a></li>
            </ul>

            <div id="<?php echo $taxonomy; ?>-pop" class="tabs-panel" style="display: none;">
                <ul id="<?php echo $taxonomy; ?>checklist-pop" class="<?php echo $taxonomy; ?>checklist form-no-clear" >
                    <?php $popular_ids = wp_popular_terms_checklist($taxonomy); ?>
                </ul>
            </div>

            <div id="<?php echo $taxonomy; ?>-all" class="tabs-panel">
                <?php
                $name = ( $taxonomy == 'category' ) ? 'post_category' : 'tax_input[' . $taxonomy . ']';
                echo "<input type='hidden' name='{$name}[]' value='0' />"; // Allows for an empty term set to be sent. 0 is an invalid Term ID and will be ignored by empty() checks.
                ?>
                
                <ul id="<?php echo $taxonomy; ?>checklist" class="list:<?php echo $taxonomy ?> <?php echo $taxonomy; ?>checklist form-no-clear">
                    <?php $custom_walker = new Attachment_Walker_Category_Checklist ?>
                    <?php wp_terms_checklist($post->ID, array('taxonomy' => $taxonomy, 'popular_cats' => $popular_ids, 'walker' => $custom_walker)) ?>
                </ul>
            </div>
            
            
        <?php if (current_user_can($tax->cap->edit_terms)) : ?>
            
             <div id="<?php echo $taxonomy; ?>-adder" class="wp-hidden-children">
                <h4>
                    <a id="<?php echo $taxonomy; ?>-add-toggle" href="#<?php echo $taxonomy; ?>-add" class="hide-if-no-js" tabindex="3">
                        <?php printf(__('+ %s'), $tax->labels->add_new_item);/* translators: %s: add new taxonomy label */ ?> 
                    </a>
                </h4>
                
                <p id="<?php echo $taxonomy; ?>-add" class="category-add wp-hidden-child">
                    
                    <label class="screen-reader-text" for="new<?php echo $taxonomy; ?>"><?php echo $tax->labels->add_new_item; ?></label>
                    <input type="text" name="new<?php echo $taxonomy; ?>" id="new<?php echo $taxonomy; ?>" class="form-required form-input-tip" value="<?php echo esc_attr($tax->labels->new_item_name); ?>" tabindex="3" aria-required="true"/>

                    <label class="screen-reader-text" for="new<?php echo $taxonomy; ?>_parent">
                        <?php echo $tax->labels->parent_item_colon; ?>
                    </label>

                    <?php wp_dropdown_categories(array('taxonomy' => $taxonomy, 'hide_empty' => 0, 'name' => 'new' . $taxonomy . '_parent', 'orderby' => 'name', 'hierarchical' => 1, 'show_option_none' => '&mdash; ' . $tax->labels->parent_item . ' &mdash;', 'tab_index' => 3)); ?>
                    <input type="button" id="<?php echo $taxonomy; ?>-add-submit" class="add:<?php echo $taxonomy ?>checklist:<?php echo $taxonomy ?>-add button category-add-sumbit" value="<?php echo esc_attr($tax->labels->add_new_item); ?>" tabindex="3" />

                    <?php wp_nonce_field('add-' . $taxonomy, '_ajax_nonce-add-' . $taxonomy, false); ?>
                    <span id="<?php echo $taxonomy; ?>-ajax-response"></span>
                </p>
            </div>
        <?php endif; ?>
        </div>
            <?php
    }

    function custom_gallery_shortcode(){
        remove_shortcode('gallery');
        add_shortcode('gallery', array(&$this,'gallery_shortcode'));
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
    function gallery_shortcode($attr) {
        global $post;

        static $instance = 0;
        $instance++;

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

        $mc_tax = apply_filters('mc_taxonomy', $this->taxonomy);
        extract(shortcode_atts(array(
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
            $mc_tax      => ''   
        ), $attr));
        
        $id = intval($id);
        if ( 'RAND' == $order )
            $orderby = 'none';
                
        if( !empty($$mc_tax) ){ 
   
            $term = ${$mc_tax};
            $term_field = (is_numeric($term)) ? 'id' : 'slug';
              
            if(!isset($attr['id']))
                $id = '';
        }
        
        if ( !empty($include) ) {
            $include = preg_replace( '/[^0-9,]+/', '', $include );
            $_attachments = get_posts( array('include' => $include, 'post_status' => 'inherit', 'post_type' => 'attachment', 'post_mime_type' => 'image', 'order' => $order, 'orderby' => $orderby, 'tax_query' => array(array('taxonomy' => $mc_tax, 'field' => $term_field, 'terms' => $term))) );

            $attachments = array();
            foreach ( $_attachments as $key => $val ) {
                $attachments[$val->ID] = $_attachments[$key];
            }
        } elseif ( !empty($exclude) ) {
            $exclude = preg_replace( '/[^0-9,]+/', '', $exclude );
            $attachments = get_children( array('post_parent' => $id, 'exclude' => $exclude, 'post_status' => 'inherit', 'post_type' => 'attachment', 'post_mime_type' => 'image', 'order' => $order, 'orderby' => $orderby, 'tax_query' => array(array('taxonomy' => $mc_tax, 'field' => $term_field, 'terms' => $term))) );
        } else {
            $attachments = get_children( array('post_parent' => $id, 'post_status' => 'inherit', 'post_type' => 'attachment', 'post_mime_type' => 'image', 'order' => $order, 'orderby' => $orderby, 'tax_query' => array(array('taxonomy' => $mc_tax, 'field' => $term_field, 'terms' => $term))) );
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
     * @linkhttp://core.trac.wordpress.org/ticket/20765
     * @link http://wordpress.org/support/topic/media-categories-2-not-saving-correctly-when-two-categories-with-same-name
     * @see /wp-admin/includes/media.php:get_attachemt_fields_to_edit()
     * 
     * @param type $form_fields
     * @param type $post
     * @return type 
     */
    function get_attachment_fields_to_edit($form_fields, $post) {

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
}

$mc_category_metabox = new Media_Categories('category');
