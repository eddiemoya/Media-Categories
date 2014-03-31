/**
 * jQuery for Media Categories plugin
 * 
 * @author Eddie Moya
 * @since 1.5
 * 
 */

jQuery(document).ready(function($){

    /**
     * This modifies the settings for the AttachmentCompat object in media-views.js.
     * This is needed because saving on change of checkboxes is glitchy when the person clicks multiple checkboxes in a row.
     */
    delete wp.media.view.AttachmentCompat.prototype.events['change input'];
    wp.media.view.AttachmentCompat.prototype.events['change input[type!="checkbox"]'] = 'save';

    //Overriding saveCompat just so i can add the trigger.
    wp.media.model.Attachment.prototype.saveCompat = 
        function( data, options ) {
            var model = this;

            // If we do not have the necessary nonce, fail immeditately.
            if ( ! this.get('nonces') || ! this.get('nonces').update )
                return $.Deferred().rejectWith( this ).promise();

            return wp.media.post( 'save-attachment-compat', _.defaults({
                id:      this.id,
                nonce:   this.get('nonces').update,
                post_id: wp.media.model.settings.post.id
            }, data ) ).done( function( resp, status, xhr ) {
                model.set( model.parse( resp, xhr ), options );
                //trigger so i can detect the refreshing of the sidebar.
                $('.compat-attachment-fields').trigger('modal-refreshed');
            });
        }


    wp.media.view.AttachmentFilters.TaxonomyFilter = wp.media.view.AttachmentFilters.extend({
        createFilters: function() {
            var filters = {};

            _.each( terms[this.options.taxonomy] || {}, function( term ) {
                //console.log(term);
                filters[ term.slug ] = {
                    text: term.name,
                    props: {
                        tax_query: 
                        [
                            {
                                taxonomy: term.taxonomy,
                                field: 'id',
                                terms: term.term_id 
                            }, //This nested structure will be useful later if I want to have multi-tax filtering
                        ]
                    } 
                };
            });

            filters.all = {
                text:  'Select '+ this.options.taxonomy,
                props: {
                    orderby: 'date',
                    order:   'DESC'
                },
                priority: 10
            };
            this.filters = filters;
        }
    });

    $.each(taxonomy, function(index, tax){

        /**
         * Replace the media-toolbar with our own
         */
        var mc_filter = wp.media.view.AttachmentsBrowser;

        wp.media.view.AttachmentsBrowser = wp.media.view.AttachmentsBrowser.extend({
            createToolbar: function() {

                mc_filter.prototype.createToolbar.apply(this,arguments);

                this.toolbar.set( tax, new wp.media.view.AttachmentFilters.TaxonomyFilter({
                    controller: this.controller,
                    model:      this.collection.props,
                    priority:   -80,
                    taxonomy: tax,
                    className: 'media-categories-filter_' + tax
                }).render('taxonomy') );
            }
        });

        var input_class = '.compat-field-'+tax;
        var metabox_class = input_class+'_metabox';

        var $func = tax + '_slideTogle';

        wp.media.view.AttachmentCompat.prototype.events['click '+ metabox_class+'>th'] = 'slideToggle';
        wp.media.view.AttachmentCompat.prototype.slideToggle = function( event ){

            var metabox_container = $(event.target).closest('.metabox_container');

            console.log(event.target.parent);
            metabox_container.find('td.field').slideToggle();
                   
            if(metabox_container.find('.arrow-down').length > 0){
                metabox_container.find('.arrow-down').attr('class', 'arrow-up');
                wp.media.view.AttachmentCompat.prototype.metabox_status = 'open';
            } else {
                metabox_container.find('.arrow-up').attr('class', 'arrow-down');
                wp.media.view.AttachmentCompat.prototype.metabox_status = 'closed';
            }
        }

        $('.compat-attachment-fields').live('modal-refreshed', function(){
            $(input_class).hide();
            $(metabox_class).addClass('metabox_container');

            if(wp.media.view.AttachmentCompat.prototype.metabox_status == 'open'){
                $(metabox_class).find('td.field').show();
                $(metabox_class).find('.arrow-down').attr('class', 'arrow-up');
            }

        })

        $('.attachments').live('click', function(){
            $(input_class).hide();
            $(metabox_class).addClass('metabox_container');
        })

        // Trigger the 'change' event when the mouse leaves the metabox.
        $(metabox_class).live('mouseleave', function() { $('.compat-item input:first').trigger('change');});

        //Sync the checkboxes to comma delimited list in the hidden text field for the taxonomy.
        $('.media-sidebar input').live('click', function(){
    
            var form_fields = $(this).closest("tbody");

            var checked = form_fields.find(".compat-field-" + tax + "_metabox input:checked");
            var slug_list = '';
   
            checked.each(function(index){

                if(slug_list.length > 0) 
                    slug_list += ',' + $(this).val();
                else 
                    slug_list += $(this).val();
            });

            form_fields.find("tr.compat-field-"+ tax +" > td.field > input.text").val(slug_list);
            //$('.compat-item input:first').trigger('change');
        })

        $.extend($.expr[":"], {
            "icontains": function(elem, i, match, array) {
                return (elem.textContent || elem.innerText || "").toLowerCase().indexOf((match[3] || "").toLowerCase()) >= 0;
            }
        });
        /**
        * The following javascript is borrowed (with few modifications) from Jason Corradino's 
        * 'Searchable Categories' plugin. It allows the category metabox to be filtered 
        * as the user types. To do this with your category meta boxes on pages and posts, 
        * download his plugin.
        * 
        * http://wordpress.org/extend/plugins/searchable-categories/
        */

        $('#' + tax + '-search').live('keyup', function(event) {

            selector_context = ('attachment' != pagenow) ? '.media-sidebar ' : '';
                  
            var val = this.value; 
            var lis = $(selector_context + "#"+ tax +"checklist li");

            if(val.length > 0){
                lis.hide();
            } else {
                lis.show();
            }
            // find li labels's containing term, then back to parent li
            var containingLabels = $(selector_context + "#"+ tax +"checklist label:icontains('" + val + "')");

            containingLabels.closest('li').find('li').andSelf().show();
            containingLabels.parents(selector_context + '#'+ tax +'checklist li').show();
        });
    });
})