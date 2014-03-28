<?php 

require_once(plugin_dir_path(__FILE__). 'abstract-metabox.php');

class Filterable_Taxonomy_Metabox extends MC_Taxonomy_Metabox {

    /**
     * Here I insert a custom form field into the media editor, but instead of
     * a normal textfield, I capture the output of a custom metabox and insert it.
     */
    public function add_taxonomy_meta_box($post, $box = null) {

        $taxonomy = get_taxonomy($this->taxonomy);

        remove_meta_box( $this->taxonomy.'div', 'attachment', 'side');
        add_meta_box(
            $this->taxonomy.'div', 
            $taxonomy->labels->name, 
            array($this, 'taxonomy_meta_box'), 
            'attachment', 
            'side', 
            'default', 
            array( 'taxonomy' => $this->taxonomy )
        );

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
    function taxonomy_meta_box($post, $box) {

        require_once(plugin_dir_path(dirname(__FILE__)) . 'walkers/attachment-walker-category-checklist-class.php');
            
            $defaults = array('taxonomy' => 'category');
        if ( !isset($box['args']) || !is_array($box['args']) )
            $args = array();
        else
            $args = $box['args'];
        extract( wp_parse_args($args, $defaults), EXTR_SKIP );

        $tax = get_taxonomy($taxonomy);

        ?>
        <div id="taxonomy-<?php echo $taxonomy; ?>" class="categorydiv">
            <div class="taxonomy-metabox-field-container">
               <!--  <label class='category-filter' for="category-filter">Search <?php echo $tax->labels->name; ?>:</label> -->
                <input id='<?php echo $taxonomy?>-search' name="category-filter" type='text' placeholder="Filter <?php echo $tax->labels->name; ?>" />
            </div>
            <ul id="<?php echo $taxonomy; ?>-tabs" class="category-tabs">
                <li class="tabs"><a href="#<?php echo $taxonomy; ?>-all"><?php echo $tax->labels->all_items; ?></a></li>
                <li class="hide-if-no-js"><a href="#<?php echo $taxonomy; ?>-pop"><?php _e( 'Most Used' ); ?></a></li>
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
                <ul id="<?php echo $taxonomy; ?>checklist" data-wp-lists="list:<?php echo $taxonomy?>" class="categorychecklist form-no-clear">
                        <?php wp_terms_checklist( $post->ID, array('taxonomy' => $taxonomy, 'popular_cats' => $popular_ids )); ?>
                </ul>
            </div>
        <?php if ( current_user_can($tax->cap->edit_terms) ) : ?>
                <div id="<?php echo $taxonomy; ?>-adder" class="wp-hidden-children">
                    <h4>
                        <a id="<?php echo $taxonomy; ?>-add-toggle" href="#<?php echo $taxonomy; ?>-add" class="hide-if-no-js">
                            <?php
                                /* translators: %s: add new taxonomy label */
                                printf( __( '+ %s' ), $tax->labels->add_new_item );
                            ?>
                        </a>
                    </h4>
                    <p id="<?php echo $taxonomy; ?>-add" class="category-add wp-hidden-child">
                        <label class="screen-reader-text" for="new<?php echo $taxonomy; ?>"><?php echo $tax->labels->add_new_item; ?></label>
                        <input type="text" name="new<?php echo $taxonomy; ?>" id="new<?php echo $taxonomy; ?>" class="form-required form-input-tip" value="<?php echo esc_attr( $tax->labels->new_item_name ); ?>" aria-required="true"/>
                        <label class="screen-reader-text" for="new<?php echo $taxonomy; ?>_parent">
                            <?php echo $tax->labels->parent_item_colon; ?>
                        </label>
                        <?php wp_dropdown_categories( array( 'taxonomy' => $taxonomy, 'hide_empty' => 0, 'name' => 'new'.$taxonomy.'_parent', 'orderby' => 'name', 'hierarchical' => 1, 'show_option_none' => '&mdash; ' . $tax->labels->parent_item . ' &mdash;' ) ); ?>
                        <input type="button" id="<?php echo $taxonomy; ?>-add-submit" data-wp-lists="add:<?php echo $taxonomy ?>checklist:<?php echo $taxonomy ?>-add" class="button category-add-submit" value="<?php echo esc_attr( $tax->labels->add_new_item ); ?>" />
                        <?php wp_nonce_field( 'add-'.$taxonomy, '_ajax_nonce-add-'.$taxonomy, false ); ?>
                        <span id="<?php echo $taxonomy; ?>-ajax-response"></span>
                    </p>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
}