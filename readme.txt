=== Media Categories ===
Contributors: eddiemoya
Donate link: http://eddiemoya.com
Tags: media categories, media, category, categories, attachment categories, taxonomy, category metabox, metabox, admin, media library, media editor, attachment editor, attachment, images, gallery shortcode, gallery, shortcode, gallery category, filter, media taxonomy, post tags, modal, category filter
Requires at least: 3.3
Tested up to: 3.5
Stable tag: 1.5
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Easily assign categories to media with a clean, simple, and searchable category meta box. Then use the gallery shortcode to display category galleries

== Description ==

Allows users to assign categories (or other taxonomy terms) to items in their Media Library with a clean and simplified, searchable version of the standard category meta box, as well as in a similar form in the Media Modal.
The "Filter Categories" field allows you to narrow your search for a category as you type - this functionality is not native to WordPress but rather is derived from Jason Corradino's 
[Searchable Categories](http://wordpress.org/extend/plugins/searchable-categories/) plugin. If you would like to enable this feature for your posts
[download his plugin here](http://wordpress.org/extend/plugins/searchable-categories/)

Since WordPress 3.5 now supports attachment taxonomy, the work of adding a metabox to the attachment editor is happening entirely inside of WordPress. This is great, and we now have true metaboxes for taxonomy - they core team has also accepted my patches which caused several headaches for this plugin. Media Categories 1.5 takes advantage of the new Media Modal - with this plugin, you can now edit a images categories directly from the modal screen. I've also fixed some long standing bugs with the shortcode gallery functionality.

= Updates =
* Since version 1.6 : Supports WordPress 3.8.x * Reintroduces the category filter on metaboxes * Adds drowndown filters in the Media Library * Gallery Shortcode now accepts multiple terms.
* Since version 1.5 : Supports the new WordPress 3.5 by adding the metabox to the new Media Modal. Also fixed bugs in the gallery shorcode behavior. All while still supporting 3.3.x - 3.4.x
* Since version 1.4 : This plugin allows for **multiple metaboxes** to be created for any number of taxonomies.
* Since version 1.3 : A **filter** has been added to allow developers to modify which taxonomy is being used. See 'Other Notes' > 'Taxonomy Filter Usage' for details
* Since version 1.2 : This plugin extends the native **[gallery] shortcode** of WordPress so that it has a 'category' parameter. See the "Shortcode Usage" under "Other Notes" for more details. 

== Shortcode Usage ==

= Normal Shortcode Usage =

This plugin takes advantage of the existing `[gallery]` shortcode for showing images by adding the `'category'` parameter. 
The value passed to the `'category'` parameter can be either the `category` `slug`, or the `term_id`. 

`[gallery category="my-category-slug"]
OR
[gallery category="12"]`

Since 1.6 you can also pass multiple terms in a comma delimited list. * Thanks to [Bryan Lee Williams](https://github.com/BLWBebopKid) for the [pull request](https://github.com/eddiemoya/Media-Categories/pull/6) *

`[gallery category="term1,term2"]` 

Its important to note that when passing the `'category'` parameter, the `[gallery]` shortcode will by default **ignore the current post
and simply try to include all images from the category**. The syntax above will retrieve any images that are assigned 
to `'my-category-slug'` a.k.a term id `#12`, regardless of whether or not those images are attached to the current post.

To query within a post (even the current post), you'll need to explicitly add the post id as such...

`[gallery category="my-category-slug" id="43"]`

This shortcode will retrieve any images attached to post `#43` that are categorized as `'my-slug-category'`.

Aside from this behavior, the [gallery] shortcode should behave exactly as it does by default with the built-in shortcode. 
The `id` parameter will behave as normal when the `category` parameter is not invoked.
For more information on using the built-in [gallery shortcode checkout the codex page](http://codex.wordpress.org/Gallery_Shortcode).

You can also use `[media_gallery]` instead of `[gallery]`, which maybe necessary in some circumstances where other plugins in use are also manipulating the standard `[gallery]` shortcode. See more in the Troubleshooting section. Don't worry though, both `[media_gallery]` and `[gallery]` do exactly the same thing.

= Other Taxonomy Shortcode Usage =

If a developer implementing this plugin has made use of the `mc_taxonomy` filter to modify which taxonomy
this plugin uses for attachments, then the name of that particular taxonomy will need to be used in place of `category` 
as the shortcode parameter. For example, if you applied 'Post Tags' to your images then users should use the `post_tag` parameter
in the Gallery Shortcode.

`[gallery post_tag="my-tag-slug"]
OR
[gallery post_tag="12"]`


*[Warning: nerdy developer stuff ahead]*

== Multiple Media Taxonomies  *NEW!* ==

Since 1.4 this plugin allows developers to create metaboxes for any number of taxonomies. While previous the previous version allowed 
developers to change the taxonomy being used, it still only allowed a single taxonomy metabox to be generated. With 1.4, that has changed.

All a developer needs to do, is create a new instance of the Media_Categories class and pass their desired taxonomy as an argument.

`
$my_media_taxonomy = new Media_Categories('my_custom_taxonomy');
`

Thats it!, nothing else to it, the plugin will take care of the rest. You can create as many instances as you like - just make sure to be careful
when doing this in conjunction with the `mc_taxonomy` filter - always check the current taxonomy.

This works with any taxonomy, including built-in taxonomies such as 'post_tag', 'link_categories', 
and yes, even 'nav_menu'. I'll leave it to you developers to find uses for that.


== Troubleshooting ==

= Gallery Shortcode Conflicts =

If you have any other plugins that modify the gallery shortcode, then that plugin and this one will conflict - only one will work because they both try to override the built in `[gallery]` shortcode. As a workaround, stick to using `[media_gallery]` for this plugin - then put the following somwehere in your theme functions.php file.

`
//Disabling the Media Categories plugins ability to override the default [gallery] shortcode
global $mc_media_categories;
$mc_media_categories->override_default_gallery = false;
`
This will stop Media Categories from messing with the default shortcode, leaving the other plugin to do what it likes - and this plugin to use only `[media_gallery]`. The `[media_gallery]` shortcode works in exactly the same way as the the default does.

https://github.com/eddiemoya/Media-Categories/issues/10

= How to query for attachments =

I've had many reports from people confused as to why WP_Query doesnt return attachments. There might be a number of reasons depending on your situtation, but I believe the most common and most confusing is the `post_status` settings for attachment being different than those for posts. By default, WP_Query always assumes the post_status is 'published' unless you specify otherwise - but attachments never have that as a status. They are set to 'inherit'.

Here is an example of how change a category archive page to only return attachments.

`
add_action( 'pre_get_posts', 'category_attachments' );

function category_attachments( $wp_query ) {

	//If is category archive...
    if ( $wp_query->is_category() ) {

    	//Set the post_type to attachement
        $wp_query->set( 'post_type', 'attachment' );

        //Set the post_status to inherit
    	$wp_query->set( 'post_status', 'inherit');
    }

    return $wp_query;
}
`

Geneally speaking, theres nothing special this plugin does to help the theme "get" categorized media except for the gallery shortcode. So anything thats not working is going to be something weird going on with WordPress core where it's treating attachments differently than posts - which happens a lot.



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

    return $taxonomy;
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

Yes, by default this plugin enables the `category` taxonomy for media, but you can turn that off by making use of the `mc_taxonomy` filter.
You can then enable any taxonomy, custom or built-in, by creating a new instance of the Media_Categories() class and passing the taxonomy as the first paramater.

See the 'Multiple Taxonomy Metaboxes' section (a.k.a 'Other Notes') for more details on how
this is done.

= Can any of this be done without writing any code? =

Only the use of `[gallery]` or `[media_gallery]' can be done out of the box. Currently there is no way to change the taxonomies used, or create additional taxonomy metaboxes in the Media Library without adding a little bit of PHP (preferably to you theme).

Have no fear however, I do have intentions to add magical plugin options pages to allow
as much as possible to be done without development - sorry but I have no timeline for when this
might happen.

= Is there any way to see all the attachment/media items associated to a category/taxonomy term, the way we can with Posts? =

Yes! This was a frequent feature request, and in version Media Categories 1.6 this is finally available! Just go to the Media Library, the dropdown will be right there at the top.

= I found a bug, or I would like to suggest/request a feature, can I submit it? =

Of course, thats great! I love hearing how people want to use my plugins, and if you look though my blog or
this plugins support forum, you'll see I have a tendency of fulfilling people's requests ( but no promises :p ).

Bug reports are extremely helpful! Most of my plugins have been developed while at work, and publicly submitted bug reports help
prove the point that Open Source is the way to go. It amounts to free public quality assurance testing. So please please please report
any bugs to you see. Preferably on the WordPress plugin directory, but if you feel so inclined you may report them on my blog http://eddiemoya.com

Note: Your bug reports and feature requests have a much higher likelihood of getting my attention if you submit them to the [GitHub Issues](https://github.com/eddiemoya/Media-Categories/issues)


== Changelog ==

= 1.6 =
* New Feature - Filter media in the Media Library by term with new dropdropdown menu.
* New Feature - Filter taxonomy terms in metaboxes - "Searchable Categories" style taxonomy metaboxes are back in the real metaboxes as well as the Media Modal Faux metaboxes.
* New Feature - Taxonomy columns in Media Library are now sortable by clicking on the column header.
* Bugfix - Gallery shortcode conflicts with other plugins that also modify the gallery shortcode (JetPack). This fix leaves the [gallery] shortcode available, but allows developers to turn to use [media_gallery] instead - and even disable the [gallery] so it doesnt blow up other plugins. https://github.com/eddiemoya/Media-Categories/issues/10
* Bugfix - Toggle buttons for taxonomy boxes on media modal would toggle all taxonomy areas instead of just the one you clicked. https://github.com/eddiemoya/Media-Categories/issues/13

= 1.5 =
* Updated for WordPress 3.5
* For WordPress 3.5 - Stop showing our custom metabox in the main image editor, let users use the built in metaboxes.
* For WordPress 3.5 - Add customized metabox to the new Media Modal attachment details.
* Bugfix: Gallery Shortcode would only work if a taxonomy and term were provided. This has been solved for all supported version of WordPress.
* Bugfix: The search filter on the metabox - deleting everying in the search filter resulted in no items being found. This has been solved for all supported version of WordPress.

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

= 1.6 =
Update for WordPress 3.8 compatibility. Multiple bug fixes, several great new features!

= 1.5 =
Update for WordPress 3.5 compatibility, and important bug fixes for all versions of WordPress

= 1.4.1 =
Bug Fix! Fixed javascript when using multiple taxonomy metaboxes.

= 1.4 = 
New Feature! Developers can now generate metaboxes for media for any number of desired taxonomies

= 1.3.1 =
Bug fix (workaround): Taxonomy terms with the same name were causing conflicts - see related [trac ticket](http://core.trac.wordpress.org/ticket/20765)

= 1.1 =
For compatibility with WordPress versions earlier than 3.3, upgrade to version 1.1 of this plugin or later. 
