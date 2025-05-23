<?php
/**
 * Batch Downloads Export Class
 *
 * This class handles download products export
 *
 * @package     EDD
 * @subpackage  Admin/Reporting/Export
 * @copyright   Copyright (c) 2018, Easy Digital Downloads, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.5
 */

namespace EDD\Admin\Exports\Exporters;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use EDD\Admin\Exports\Legacy\BatchExport;

/**
 * Downloads Class
 *
 * @since 2.5
 */
class Downloads extends BatchExport {

	/**
	 * Our export type. Used for export-type specific filters/actions
	 *
	 * @var string
	 * @since 2.5
	 */
	public $export_type = 'downloads';

	/**
	 * Set the CSV columns.
	 *
	 * @since 2.5
	 *
	 * @return array $cols All the columns.
	 */
	public function csv_cols() {
		return array(
			'ID'                     => __( 'ID', 'easy-digital-downloads' ),
			'post_name'              => __( 'Slug', 'easy-digital-downloads' ),
			'post_title'             => __( 'Name', 'easy-digital-downloads' ),
			'post_date'              => __( 'Date Created', 'easy-digital-downloads' ),
			'post_author'            => __( 'Author', 'easy-digital-downloads' ),
			'post_content'           => __( 'Description', 'easy-digital-downloads' ),
			'post_excerpt'           => __( 'Excerpt', 'easy-digital-downloads' ),
			'post_status'            => __( 'Status', 'easy-digital-downloads' ),
			'categories'             => __( 'Categories', 'easy-digital-downloads' ),
			'tags'                   => __( 'Tags', 'easy-digital-downloads' ),
			'edd_price'              => __( 'Price', 'easy-digital-downloads' ),
			'_edd_files'             => __( 'Files', 'easy-digital-downloads' ),
			'_edd_download_limit'    => __( 'File Download Limit', 'easy-digital-downloads' ),
			'_thumbnail_id'          => __( 'Featured Image', 'easy-digital-downloads' ),
			'edd_sku'                => __( 'SKU', 'easy-digital-downloads' ),
			'edd_product_notes'      => __( 'Notes', 'easy-digital-downloads' ),
			'_edd_download_sales'    => __( 'Sales', 'easy-digital-downloads' ),
			'_edd_download_earnings' => __( 'Earnings', 'easy-digital-downloads' ),
		);
	}

	/**
	 * Get the export data.
	 *
	 * @since 2.5
	 *
	 * @return array $data The data for the CSV file.
	 */
	public function get_data() {
		$data = array();

		$meta = array(
			'edd_price',
			'_edd_files',
			'_edd_download_limit',
			'_thumbnail_id',
			'edd_sku',
			'edd_product_notes',
			'_edd_download_sales',
			'_edd_download_earnings',
		);

		$args = array(
			'post_type'      => 'download',
			'posts_per_page' => 30,
			'paged'          => $this->step,
			'orderby'        => 'ID',
			'order'          => 'ASC',
		);

		if ( 0 !== $this->download ) {
			$args['post__in'] = array( $this->download );
		}

		$downloads = new \WP_Query( $args );

		if ( $downloads->posts ) {
			foreach ( $downloads->posts as $download ) {
				$row = array();

				foreach ( $this->csv_cols() as $key => $value ) {

					// Setup default value
					$row[ $key ] = '';

					if ( in_array( $key, $meta ) ) {
						switch ( $key ) {
							case '_thumbnail_id':
								$image_id    = get_post_thumbnail_id( $download->ID );
								$row[ $key ] = wp_get_attachment_url( $image_id );
								break;

							case 'edd_price':
								if ( edd_has_variable_prices( $download->ID ) ) {
									$prices = array();
									foreach ( edd_get_variable_prices( $download->ID ) as $price ) {
										$prices[] = $price['name'] . ': ' . $price['amount'];
									}

									$row[ $key ] = implode( ' | ', $prices );
								} else {
									$row[ $key ] = edd_get_download_price( $download->ID );
								}
								break;

							case '_edd_files':
								$files = array();

								foreach ( edd_get_download_files( $download->ID ) as $file ) {
									$f = $file['file'];

									if ( edd_has_variable_prices( $download->ID ) ) {
										$condition = isset( $file['condition'] ) ? $file['condition'] : 'all';
										$f        .= ';' . $condition;
									}

									$files[] = $f;

									unset( $file );
								}

								$row[ $key ] = implode( ' | ', $files );
								break;

							default:
								$row[ $key ] = get_post_meta( $download->ID, $key, true );
								break;
						}
					} elseif ( isset( $download->$key ) ) {
						switch ( $key ) {
							case 'post_author':
								$row[ $key ] = get_the_author_meta( 'user_login', $download->post_author );
								break;

							default:
								$row[ $key ] = $download->$key;
								break;
						}
					} elseif ( 'tags' == $key ) {
						$terms = get_the_terms( $download->ID, 'download_tag' );

						if ( $terms ) {
							$terms       = wp_list_pluck( $terms, 'name' );
							$row[ $key ] = implode( ' | ', $terms );
						}
					} elseif ( 'categories' == $key ) {
						$terms = get_the_terms( $download->ID, 'download_category' );

						if ( $terms ) {
							$terms       = wp_list_pluck( $terms, 'name' );
							$row[ $key ] = implode( ' | ', $terms );
						}
					}
				}

				$data[] = $row;
			}

			$data = apply_filters( 'edd_export_get_data', $data );
			$data = apply_filters( 'edd_export_get_data_' . $this->export_type, $data );

			return $data;
		}

		return false;
	}

	/**
	 * Return the calculated completion percentage.
	 *
	 * @since 2.5
	 *
	 * @return int Percentage complete.
	 */
	public function get_percentage_complete() {
		$args = array(
			'post_type'      => 'download',
			'posts_per_page' => - 1,
			'post_status'    => 'any',
			'fields'         => 'ids',
		);

		if ( 0 !== $this->download ) {
			$args['post__in'] = array( $this->download );
		}

		$downloads  = new \WP_Query( $args );
		$total      = (int) $downloads->post_count;
		$percentage = 100;

		if ( $total > 0 ) {
			$percentage = ( ( 30 * $this->step ) / $total ) * 100;
		}

		if ( $percentage > 100 ) {
			$percentage = 100;
		}

		return $percentage;
	}

	/**
	 * Set the properties specific to the downloads export.
	 *
	 * @since 3.0
	 *
	 * @param array $request Form data passed into the batch processor.
	 */
	public function set_properties( $request ) {
		$this->download = isset( $request['download_id'] ) ? absint( $request['download_id'] ) : null;
	}
}
