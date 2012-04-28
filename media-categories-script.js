


jQuery(document).ready(function($){
    
    $('tr.category_metabox input').live('click', function(){
        
        var form_fields = $(this).closest("tbody");

        var checked = form_fields.find(".category_metabox input:checked");
        var slug_list = '';
        
        checked.each(function(index){
            
            if(slug_list.length > 0) 
                slug_list += ',' + $(this).val();
            else 
                slug_list += $(this).val();
        });
        
        form_fields.find("tr.category > td.field > input.text").val(slug_list);
    })
    
    $.extend($.expr[":"], {
        "icontains": function(elem, i, match, array) {
            return (elem.textContent || elem.innerText || "").toLowerCase().indexOf((match[3] || "").toLowerCase()) >= 0;
        }
    });
    /**
     * The following javascript is borrowed from Jason Corradino's 'Searchable Categories' plugin.
     * It allows the category metabox to be filtered as the user types. To do this with your
     * category meta boxes on pages and posts, download his plugin.
     * 
     * http://wordpress.org/extend/plugins/searchable-categories/
     */
    
    $('#catsearch').keyup(function() {
        var val = $('#catsearch').val(), lis = $("#categorychecklist li");

        lis.hide();
        
        // find li labels's containing term, then back to parent li
        var containingLabels = $("#categorychecklist label:icontains('" + val + "')");
        containingLabels.closest('li').find('li').andSelf().show();
        containingLabels.parents('#categorychecklist li').show();
    });
    
})
