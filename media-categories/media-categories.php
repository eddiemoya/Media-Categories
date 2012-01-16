<?php
/*
  Plugin Name: Media Categories
  Plugin URI: http://eddiemoya.com/
  Description:  Allows users to assign categories to items in their Media Library with a clean and simplified, filterable version of the standard category metabox
  Author: Eddie Moya
  Version: 0.5
 */

class Media_Categories {

    /**
     * @author Eddie Moya
     * 
     * Start your engines.
     */
    function init() {
        add_action('admin_init', array(__CLASS__, 'register_media_categories'));
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_media_categories_scripts'));
        add_action('admin_print_styles-media.php', array(__CLASS__, 'enqueue_media_categories_styles') );
        add_filter('attachment_fields_to_edit', array(__CLASS__, 'add_media_categories_metabox'), null, 2);
    }

    function enqueue_media_categories_scripts() {
        if (is_admin()) {

            wp_register_script('media_categories_metabox_script', WP_PLUGIN_URL . '/media-categories/media-categories-script.js');
            wp_enqueue_script('media_categories_metabox_script');

        }
    }
    
    function enqueue_media_categories_styles() {
        if (is_admin()) { 
            
            wp_register_style('media_categories_metabox_style', WP_PLUGIN_URL . '/media-categories/media-categories-style.css');
            wp_enqueue_style( 'media_categories_metabox_style');
        }
	
    }

    /**
     * This adds native support for categories to the attachment editor, however
     * instead of the standard metabox wordpress only provides a text area wich
     * the user would have to type slugs.
     */
    function register_media_categories() {
        register_taxonomy_for_object_type('category', 'attachment');
        add_post_type_support('attachment', 'category');
    }

    /**
     * Here I insert a custom form field into the media editor, but instead of
     * a normal textfield, I capture the output of a custom metabox and insert it.
     */
    function add_media_categories_metabox($form_fields, $post) {

        require_once('./includes/meta-boxes.php');

        ob_start();

        self::media_categories_meta_box($post, array('taxonomy' => 'category'));

        $metabox = ob_get_clean();
        
        $form_fields['category_metabox']['label'] = __('Categories');
        $form_fields['category_metabox']['helps'] = 'Select a catgegory, use the text fields above to filter';
        $form_fields['category_metabox']['input'] = 'html';
        $form_fields['category_metabox']['html'] = $metabox;
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
        
        require_once(WP_PLUGIN_DIR . '/media-categories/attachment-walker-category-checklist-class.php');
          
        $defaults = array('taxonomy' => 'category');
        if (!isset($box['args']) || !is_array($box['args']))
            $args = array();
        else
            $args = $box['args'];
        extract(wp_parse_args($args, $defaults), EXTR_SKIP);
        $tax = get_taxonomy($taxonomy);
        ?>
        <div>
            <label class='category-filter' for="category-filter">Filter Categories:</label>
            <input id='catsearch' name="category-filter" type='text' /></div>
        <div id="taxonomy-<?php echo $taxonomy; ?>" class="categorydiv">
            <ul id="<?php echo $taxonomy; ?>-tabs" class="category-tabs">
                <li class="tabs"><a href="#<?php echo $taxonomy; ?>-all" tabindex="3"><?php echo $tax->labels->all_items; ?></a></li>
                <li class="hide-if-no-js"><a href="#<?php echo $taxonomy; ?>-pop" tabindex="3"><?php _e('Most Used'); ?></a></li>
            </ul>

            <div id="<?php echo $taxonomy; ?>-pop" class="tabs-panel" style="display: none;">
                <ul id="<?php echo $taxonomy; ?>checklist-pop" class="categorychecklist form-no-clear" >
        <?php $popular_ids = wp_popular_terms_checklist($taxonomy); ?>
                </ul>
            </div>

            <div id="<?php echo $taxonomy; ?>-all" class="tabs-panel">
        <?php
        $name = ( $taxonomy == 'category' ) ? 'post_category' : 'tax_input[' . $taxonomy . ']';
        echo "<input type='hidden' name='{$name}[]' value='0' />"; // Allows for an empty term set to be sent. 0 is an invalid Term ID and will be ignored by empty() checks.
        ?>
                <ul id="<?php echo $taxonomy; ?>checklist" class="list:<?php echo $taxonomy ?> categorychecklist form-no-clear">
                <?php $custom_walker = new Attachment_Walker_Category_Checklist ?>
                    <?php wp_terms_checklist($post->ID, array('taxonomy' => $taxonomy, 'popular_cats' => $popular_ids, 'walker' => $custom_walker)) ?>
                </ul>
            </div>
            
            
        <?php if (current_user_can($tax->cap->edit_terms)) : ?>
            
                <div id="<?php echo $taxonomy; ?>-adder" class="wp-hidden-children">
                    <h4>
                        <a id="<?php echo $taxonomy; ?>-add-toggle" href="#<?php echo $taxonomy; ?>-add" class="hide-if-no-js" tabindex="3">
            <?php
            /* translators: %s: add new taxonomy label */
            printf(__('+ %s'), $tax->labels->add_new_item);
            ?>
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

}

Media_Categories::init();