=== Media Categories ===
Contributors: eddiemoya
Donate link: http://eddiemoya.com
Tags: media categories, media, category, categories, attachment categories, taxonomy, category metabox, metabox, admin, media library, media editor, attachment editor, attachment, images, gallery shortcode, gallery, shortcode, gallery category
Requires at least: 3.0
Tested up to: 3.3.1
Stable tag: 1.2

Easily assign categories to media with a clean, simple, and searchable category meta box. Then use the gallery shortcode to display category galleries

== Description ==

Allows users to assign categories to items in their Media Library with a clean and simplified, searchable version of the standard category meta box. 
The "Search Categories" field allows you to narrow your search for a category as you type - this functionality is not native to WordPress but is instead borrowed from Jason Corradino's 
[Searchable Categories](http://wordpress.org/extend/plugins/searchable-categories/) plugin. If you would like to enable this feature for your posts
[download his plugin here](http://wordpress.org/extend/plugins/searchable-categories/)

NEW! Since version 1.2 this plugin extends the native [gallery] shortcode of WordPress so that it has a 'category' parameter. See the "Shortcode Usage" under "Other Notes" for more details.


== Shortcode Usage ==

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

== Related Plugins ==

As stated in the description, the search functionality in this plugin is taken from Jason Corradino's 
[Searchable Categories](http://wordpress.org/extend/plugins/searchable-categories/) plugin. While I do
not employ the plugin directly, the javascript used for filtering is in fact derived with consent from 
that plugin. To enable this feature on all you category metabox, the 
[Searchable Categories](http://wordpress.org/extend/plugins/searchable-categories/) plugin

= TL;DR =
Checkout this great plugin for Searchable Categories by Jason Corradino, whose javascript I use in this plugin. 
I believe this very simple functionality should be a part of the standard categories metabox in core.

* [Searchable Categories](http://wordpress.org/extend/plugins/searchable-categories/) by Jason Corradino

== Screenshots ==

1. This plugin will include clean, simple to use, searchable categories to your Media Editor page.
2. Use categories in much the same way you use them on your posts and pages.
3. Filter categories as you type, very useful if you have a lot of categories to look through (thanks to Jason Corradino's "Searchable Categories" plugin)

== Installation ==

1. Upload `plugin-name.php` to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress


== Changelog ==

= 1.2 =
* Added 'category' parameter to [gallery] shortcode.
* Modified the Searchable Categories script to make the search field case insensitive.
* Fixed styling problem on Media Library modal windows - the filter and styling were not working.

= 1.1 = 
* Changed jQuery to use .live() rather than .on() for compatability with WordPress earlier than 3.3 - jQuery 1.7 was only added in v3.3
* Removed superfluous file which was accidentally included from a different plugin of mine. Would cause fatal errors if both plugins were turned on at the same time.

= 1.0 =
* Initial commit.

== Upgrade Notice ==
* For compatibility with WordPress versions earlier than 3.3, upgrade to version 1.1 of this plugin or later. 
