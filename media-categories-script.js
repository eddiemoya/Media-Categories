/**
 * jQuery for Media Categories plugin
 * 
 * @author Eddie Moya
 * @since 1.1
 * 
 */




jQuery(document).ready(function($){

    /**
     * This modifies the settings for the AttachmentCompat object in media-views.js.
     * This is needed because saving on change of checkboxes is glitchy when the person clicks multiple checkboxes in a row.
     */
    delete wp.media.view.AttachmentCompat.prototype.events['change input'];
    wp.media.view.AttachmentCompat.prototype.events['change input[type!="checkbox"]'] = 'save';

    // wp.media.view.AttachmentCompat.prototype.save = function( status ) {
    //      var data = {};

    //         if ( event )
    //             event.preventDefault();

    //         _.each( this.$el.serializeArray(), function( pair ) {
    //             data[ pair.name ] = pair.value;
    //         });

    //         this.model.saveCompat( data );
    //         if(wp.media.view.AttachmentCompat.taxopen == true){
    //             $('td.field').slideDown();
    //             console.log($('td.field'))
    //         }
    //     }

    $.each(taxonomy, function(index, tax){
        
        $('.compat-field-'+tax+'_metabox').live('mouseleave', function(){
            $('.compat-item input:first').trigger('change');
          });

        $('.compat-field-'+tax+'_metabox th').live('click', function(){

            if($('.media-sidebar .categorydiv, .media-sidebar .categorydiv + .help').css('display') == 'none'){
                $(this).parent().find('td.field').hide();
                $('.media-sidebar .categorydiv, .media-sidebar .categorydiv + .help').show();
            }

            if($(this).parent().find('td.field').is(":visible")){
                $(this).parent().find('td.field').slideUp(); 
                $(this).parent().find('.arrow-up').attr('class', 'arrow-down');
                wp.media.view.AttachmentCompat.taxopen = false;
            } else {
                $(this).parent().find('td.field').slideDown();
                $(this).parent().find('.arrow-down').attr('class', 'arrow-up');
                wp.media.view.AttachmentCompat.taxopen = true;
            }
            
        });

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

        $('#' + tax + '-search').live('keyup', function() {
            var val = $('.media-sidebar #' + tax + '-search').val(); 
            var lis = $(".media-sidebar #"+ tax +"checklist li");

            if(val.length > 0){
                lis.hide();
            } else {
                lis.show();
            }
            // find li labels's containing term, then back to parent li
            var containingLabels = $(".media-sidebar #"+ tax +"checklist label:icontains('" + val + "')");

            containingLabels.closest('li').find('li').andSelf().show();
            containingLabels.parents('.media-sidebar #'+ tax +'checklist li').show();
        });
    });
})
