<?php
/**
 * Sanitizes the Stripe section.
 *
 * @since 3.3.5
 * @package EDD\Admin\Settings\Sanitize\Tabs\Gateways
 */

namespace EDD\Admin\Settings\Sanitize\Tabs\Gateways;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use EDD\Admin\Settings\Sanitize\Tabs\Section;
use EDD\Settings\Setting;

/**
 * Sanitizes the Stripe section.
 *
 * @since 3.3.5
 */
class EddStripe extends Section {

	/**
	 * Handle the changes to the Stripe section, specifically the payment methods.
	 *
	 * @since 3.3.5
	 * @param array $input The array of settings for the settings tab.
	 * @return array
	 */
	protected static function additional_processing( $input ) {
		if ( empty( $input['stripe_payment_methods'] ) ) {
			return $input;
		}
		self::maybe_update_configurations( $input['stripe_payment_methods'] );

		unset( $input['stripe_payment_methods'] );

		return $input;
	}

	/**
	 * MAybe update the payment method configurations.
	 *
	 * @since 3.3.5
	 * @param array $input The input array.
	 */
	private static function maybe_update_configurations( $input ) {
		$configuration = \EDD\Gateways\Stripe\PaymentMethods::get_base_configuration();
		if ( ! $configuration ) {
			return;
		}

		if ( ! self::update_configuration( $configuration, $input ) ) {
			return;
		}

		delete_option( $configuration->id );

		$recurring_configurations = array( 'subscriptions', 'trials' );
		foreach ( $recurring_configurations as $id ) {
			$configuration_id = \EDD\Gateways\Stripe\PaymentMethods::get_configuration_id( $id );
			if ( ! $configuration_id ) {
				continue;
			}

			try {
				$configuration = edds_api_request(
					'PaymentMethodConfiguration',
					'retrieve',
					$configuration_id
				);

				self::update_configuration( $configuration, $input );
			} catch ( \Exception $e ) {
				edd_debug_log( $e->getMessage(), true );
				continue;
			}
		}
	}

	/**
	 * Update the configuration.
	 *
	 * @since 3.3.5
	 * @param object $configuration The configuration object.
	 * @param array  $input        The input array.
	 * @return bool
	 */
	private static function update_configuration( $configuration, $input ) {
		$args = array();
		foreach ( $input as $method => $enabled ) {
			if ( ! isset( $configuration->$method ) || 'card' === $method ) {
				continue;
			}
			if ( empty( $configuration->$method->display_preference->overridable ) ) {
				continue;
			}
			$new_value = $enabled ? 'on' : 'off';
			if ( $new_value === $configuration->$method->display_preference->preference ) {
				continue;
			}
			$args[ $method ]['display_preference']['preference'] = $new_value;
		}

		if ( empty( $args ) ) {
			return false;
		}

		try {
			edds_api_request(
				'PaymentMethodConfiguration',
				'update',
				$configuration->id,
				$args
			);
		} catch ( \Exception $e ) {
			edd_debug_log( $e->getMessage(), true );
			return false;
		}

		return true;
	}
}
