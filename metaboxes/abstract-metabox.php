<?php

abstract class MC_Taxonomy_Metabox {

	/**
	 * @param $taxonomy String - name of a taxonomy
	 **/
	public function __construct($taxonomy){
		$this->taxonomy = $taxonomy;
	}

	/**
	 * Note: on < 3.5 uses, the parameters are ($form_fields, $post) instead.
	 */
	abstract public function add_taxonomy_meta_box( $post, $box );

	abstract public function taxonomy_meta_box($post, $box);


}