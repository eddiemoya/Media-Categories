<?php 

require_once(plugin_dir_path(__FILE__). 'abstract-metabox.php');

class Filterable_Taxonomy_Faux_Metabox extends MC_Taxonomy_Metabox {

    /**
     * Here I insert a custom form field into the media editor, but instead of
     * a normal textfield, I capture the output of a custom metabox and insert it.
     */
    public function add_taxonomy_meta_box($form_fields, $post = null) {
        global $wp_version;
        global $pagenow;

        require_once('./includes/meta-boxes.php');
        
        $tax_name = $this->taxonomy;
        $taxonomy = get_taxonomy($tax_name);

        ob_start();
        
            $this->taxonomy_meta_box($post, array('args' => array ('taxonomy' => $tax_name, 'tax' => $taxonomy)));
            
        $metabox = ob_get_clean();
        
        $form_slug = $this->taxonomy . '_metabox';
            
        $form_fields[$form_slug]['label'] = $taxonomy->labels->name . "<div class='arrow-down'></div>";
        $form_fields[$form_slug]['helps'] = sprintf(__('Select a %s, use the text fields above to filter'), strtolower($taxonomy->labels->singular_name));
        $form_fields[$form_slug]['input'] = 'html';
        $form_fields[$form_slug]['html'] = $metabox;

        // After 3.5 this will make sure the metabox only loads on the modal.
        $form_fields[$form_slug]['show_in_edit'] = false;

        
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
    function taxonomy_meta_box($post, $box ) {
        
        require_once(plugin_dir_path(dirname(__FILE__)) . 'walkers/attachment-walker-category-checklist-class.php');
             
        $defaults = array('taxonomy' => $this->taxonomy);
        
        if (!isset($box['args']) || !is_array($box['args']))
            $args = array();
        else
            $args = $box['args'];
        extract(wp_parse_args($args, $defaults), EXTR_SKIP);
        $tax = get_taxonomy($taxonomy);
        ?>
        
        <div id="taxonomy-<?php echo $taxonomy; ?>" class="categorydiv">
        <div class="taxonomy-metabox-field-container">
            <!-- <label class='category-filter' for="category-filter">Filter <?php // echo $tax->labels->name; ?>:</label> -->
            <input id='<?php echo $taxonomy?>-search' name="category-filter" type='text' placeholder="Type to Filter <?php echo $tax->labels->name; ?>"/>
        </div>
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

}