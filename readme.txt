=== Media Categories ===
Contributors: eddiemoya
Donate link: http://eddiemoya.com
Tags: media categories, media, category, categories, attachment categories, taxonomy, category metabox, metabox, admin, media library, media editor, attachment editor, attachment, images, gallery shortcode, gallery, shortcode, gallery category, filter, media taxonomy, post tags
Requires at least: 3.0
Tested up to: 3.3.2
Stable tag: 1.4.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Easily assign categories to media with a clean, simple, and searchable category meta box. Then use the gallery shortcode to display category galleries

== Description ==

Allows users to assign categories (or other taxonomy terms) to items in their Media Library with a clean and simplified, searchable version of the standard category meta box. 
The "Search Categories" field allows you to narrow your search for a category as you type - this functionality is not native to WordPress but is instead borrowed from Jason Corradino's 
[Searchable Categories](http://wordpress.org/extend/plugins/searchable-categories/) plugin. If you would like to enable this feature for your posts
[download his plugin here](http://wordpress.org/extend/plugins/searchable-categories/)


= Updates =
* **NEW! Since version 1.4 : This plugin allows for **multiple metaboxes** to be created for any number of taxonomies.
* Since version 1.3 : A **filter** has been added to allow developers to modify which taxonomy is being used. See 'Other Notes' > 'Taxonomy Filter Usage' for details
* Since version 1.2 : This plugin extends the native **[gallery] shortcode** of WordPress so that it has a 'category' parameter. See the "Shortcode Usage" under "Other Notes" for more details. 


== Shortcode Usage ==

= Normal Shortcode Usage =

This plugin takes advantage of the existing `[gallery]` shortcode for showing images by adding the `'category'` parameter. 
The value passed to the `'category'` parameter can be either the `category` `slug`, or the `term_id`.

`[gallery category="my-category-slug"]
OR
[gallery category="12"]`

Its important to note that when passing the `'category'` parameter, the `[gallery]` shortcode will by default **ignore the current post
and simply try to include all images from the category**. The syntax above will retrieve any images that are assigned 
to `'my-category-slug'` a.k.a term id `#12`, regardless of whether or not those images are attached to the current post.

To query within a post (even the current post), you'll need to explicitly add the post id as such...

`[gallery category="my-category-slug" id="43"]`

This shortcode will retrieve any images attached to post `#43` that are categorized as `'my-slug-category'`.

Aside from this behavior, the [gallery] shortcode should behave exactly as it does by default with the built-in shortcode. 
The `id` parameter will behave as normal when the `category` parameter is not invoked.
For more information on using the built-in [gallery shortcode checkout the codex page](http://codex.wordpress.org/Gallery_Shortcode).



= Other Taxonomy Shortcode Usage =

If a developer implementing this plugin has made use of the `mc_taxonomy` filter to modify which taxonomy
this plugin uses for attachments, then the name of that particular taxonomy will need to be used in place of `category` 
as the shortcode parameter. For example, if you applied 'Post Tags' to your images then users should use the `post_tag` parameter
in the Gallery Shortcode.

`[gallery post_tag="my-tag-slug"]
OR
[gallery post_tag="12"]`


*[Warning: nerdy developer stuff ahead]*

== Multiple Taxonomy Metaboxes  *NEW!* ==

Since 1.4 this plugin allows developers to create metaboxes for any number of taxonomies. While previous the previous version allowed 
developers to change the taxonomy being used, it still only allowed a single taxonomy metabox to be generated. With 1.4, that has changed.

All a developer needs to do, is create a new instance of the Media_Categories class and pass their desired taxonomy as an argument.

`
$my_custom_media_metabox = new Media_Categories('my_custom_taxonomy');
`

Thats it!, nothing else to it, the plugin will take care of the rest. You can create as many instances as you like - just make sure to be careful
when doing this in conjunction with the `mc_taxonomy` filter - always check the current taxonomy.

Obviously this works with any taxonomy, including built-in taxonomies such as 'post_tag', 'link_categories', 
and yes, even 'nav_menu'. I'll leave it to you developers out uses for that.


== Taxonomy Filter Usage: 'mc_taxonomy'  ==

**Note**: Since 1.4, this plugin allows developers to generate any number of metaboxes, for any number of different taxonomies. Because of this,
it is important that when filtering the taxonomy, developers conditionally check the current taxonomy before returning a different - otherwise
the filter would override *all* instances of the plugin's metaboxes with the same taxonomy. The examples below have been changes accordingly

Since version 1.3, the Media Categories plugin includes a filter allowing developers to modify the taxonomy being used. 
Changing the taxonomy will automatically change all the labels used around the metabox, and change the way the Gallery Shortcode
works so that it accommodates whatever taxonomy has been chosen.

The tag for this filter is `'mc_taxonomy'`, and usage could not be simpler.

`
add_filter('mc_taxonomy', 'mc_filter_taxonomy');

function mc_filter_taxonomy($taxonomy){

    if($taxonomy == 'category'){
        $taxonomy = 'post_tag';
    }

    return $taxonomy
}
`
The above code will swap out all references to 'category' with appropriate (properly pluralized) references to the 'post_tag' taxonomy.

It will also change the way the Gallery Shortcode works to use your chosen taxonomy.

= Important (potential gotchas) =
* The `category` parameter for the Gallery Shortcode will be changed by using this filter, so that instead of `category` is will by `your_taxonomy`. In the case above with tags,
you would write a shortcode as such. `[gallery post_tag="my-tag"]` OR `[gallery post_tag="43"]`.
* If using a Custom Taxonomy with this plugin, be sure to assign values to the labels for proper pluralization and context


== Related Plugin ==

Checkout this great plugin for Searchable Categories by Jason Corradino, whose javascript I use in this plugin. 
I believe this very simple functionality should be a part of the standard categories metabox in core. 
While I do not employ the plugin directly, the javascript used for filtering/searching is in fact derived with 
consent, and a few modifications from that plugin. To enable this feature on all your category metaboxes, install the 
[Searchable Categories](http://wordpress.org/extend/plugins/searchable-categories/) plugin.

* [Searchable Categories](http://wordpress.org/extend/plugins/searchable-categories/) by Jason Corradino

== Screenshots ==

1. This plugin will include clean, simple to use, searchable categories to your Media Editor page.
2. Use categories in much the same way you use them on your posts and pages.
3. Filter categories as you type, very useful if you have a lot of categories to look through (thanks to Jason Corradino's "Searchable Categories" plugin)

== Installation ==

1. Upload `/media-categories-2/` to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==

= Can this plugin work with a custom taxonomy, or a built-in taxonomy other than Categories? =

Yes, modify the taxonomy used by a metabox by making use of the `mc_taxonomy` filter.
They can also create additional metaboxes for other taxonomies by creating new instances
of the Media_Categories class.

See the 'Multiple Taxonomy Metaboxes' section (a.k.a 'Other Notes') for more details on how
this is done.

= Can any of this customization explained above be done without writing any code? =

No. Currently there is no way to change the taxonomy, or create additional taxonomy metaboxes
in the Media Library without adding a little bit of PHP (preferably to you theme).

Have no fear however, I do have intentions to add magical plugins options pages to allow
as much as possible to be done without development - sorry but I have no timeline for when this
might happen.

= Is there any way to see all the attachment/media items associated to a category/taxonomy term, the way we can with Posts? =

Not yet. The next major effort in this plugins development is going to be the addition of taxonomy listing pages. 

= I found a bug, or I would like to suggest/request a feature, can I submit it? =

Of course, thats great! I love hearing how people want to use my plugins, and if you look though my blog or
this plugins support forum, you'll see I have a tendency of fulfilling people's requests ( but no promises :p )

Bug reports are extremely helpful! Most of my plugins have been developed while at work, and publicly submitted bug reports help
prove the point that Open Source is the way to go. It amounts to free public quality assurance testing. So please please please report
any bugs to you see. Preferably on the WordPress plugin directory, but if you feel so inclined you may report them on my blog http:://eddiemoya.com


== Changelog ==

= 1.4.1 =
* Missing javascript update for the 1.4 update.

= 1.4 =
* New feature! Add multiple metaboxes for media, one for any desired taxonomy by a developer.

= 1.3.1 =
* Bug Fix: Workaround to conflicts caused by the way WordPress handles attachments with taxonomies enabled, causing terms with the same name of conflict.
* Relevant [Trac Ticket](http://core.trac.wordpress.org/ticket/20765)

= 1.3 =
* Added a filter to allow developers to change the taxonomy being used for their media.
* Modified the Gallery Shortcode functionality to work with any chosen taxonomy.

= 1.2 =
* Added `'category'` parameter to `[gallery]` shortcode.
* Modified the Searchable Categories script to make the search field case insensitive.
* Fixed styling problem on Media Library modal windows - the filter and styling were not working.

= 1.1 = 
* Changed jQuery to use `.live()` rather than `.on()` for compatability with WordPress earlier than 3.3 - jQuery 1.7 was only added in v3.3
* Removed superfluous file which was accidentally included from a different plugin of mine. Would cause fatal errors if both plugins were turned on at the same time.

= 1.0 =
* Initial commit.

== Upgrade Notice ==

= 1.4.1 =
Bug Fix! Fixed javascript when using multiple taxonomy metaboxes.

= 1.4 = 
New Feature! Developers can now generate metaboxes for media for any number of desired taxonomies

= 1.3.1 =
Bug fix (workaround): Taxonomy terms with the same name were causing conflicts - see related [trac ticket](http://core.trac.wordpress.org/ticket/20765)

= 1.1 =
For compatibility with WordPress versions earlier than 3.3, upgrade to version 1.1 of this plugin or later. 
