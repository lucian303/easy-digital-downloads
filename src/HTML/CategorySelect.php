<?php
/**
 * Category Select
 *
 * @package EDD
 * @subpackage HTML
 */

namespace EDD\HTML;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

/**
 * Class CategorySelect
 *
 * @since 3.2.0
 * @package EDD\HTML
 */
class CategorySelect {

	/**
	 * @var array
	 */
	private $args;

	/**
	 * CategorySelect constructor.
	 *
	 * @since 3.2.0
	 * @param array $args Arguments for the category select.
	 */
	public function __construct( $args ) {
		$this->args = $this->parse_args( $args );
	}

	/**
	 * Gets the HTML for the category select.
	 *
	 * @since 3.2.0
	 * @return string
	 */
	public function get() {
		if ( empty( $this->args['options'] ) ) {
			$this->args['options'] = $this->get_categories();
		}

		$select = new Select( $this->args );

		return $select->get();
	}

	/**
	 * Parses the arguments for the category select.
	 *
	 * @since 3.2.0
	 * @param array $args Arguments for the category select.
	 * @return array
	 */
	private function parse_args( $args ) {
		$category_labels = edd_get_taxonomy_labels( 'download_category' );

		return wp_parse_args(
			$args,
			array(
				'name'             => 'edd_categories',
				'selected'         => '',
				'multiple'         => false,
				/* translators: %s: Download Category taxonomy name */
				'show_option_all'  => sprintf( _x( 'All %s', 'plural: Example: "All Categories"', 'easy-digital-downloads' ), $category_labels['name'] ),
				'show_option_none' => false,
				'data'             => array(
					/* translators: %s: Download Category taxonomy name */
					'placeholder'        => sprintf( _x( 'Search %s', 'plural: Example: "Search Download Categories"', 'easy-digital-downloads' ), $category_labels['name'] ),
					'search-type'        => 'download_category',
					/* translators: %s: Download Category taxonomy name */
					'search-placeholder' => sprintf( _x( 'Search %s', 'plural: Example: "Search Download Categories"', 'easy-digital-downloads' ), $category_labels['name'] ),
				),
				/* translators: %s: Download Category taxonomy name */
				'placeholder'      => sprintf( _x( 'Choose %s', 'plural: Example: "Choose one or more Download Categories"', 'easy-digital-downloads' ), $category_labels['name'] ),
			)
		);
	}

	/**
	 * Gets the categories as options.
	 *
	 * @since 3.2.0
	 * @return array
	 */
	private function get_categories() {
		$categories = get_terms( $this->get_category_args() );
		$options    = array();

		foreach ( $categories as $category ) {
			$options[ absint( $category->term_id ) ] = esc_html( $category->name );
		}

		return $options;
	}

	/**
	 * Gets the arguments for the category query.
	 *
	 * @since 3.2.0
	 * @return array
	 */
	private function get_category_args() {
		$args                 = apply_filters( 'edd_category_dropdown', array() );
		$args['taxonomy']     = 'download_category';
		$args['hierarchical'] = false;
		if ( ! empty( $this->args['number'] ) ) {
			$args['number'] = $this->args['number'];
		}

		return $args;
	}
}
