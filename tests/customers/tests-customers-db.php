<?php
/**
 * Customers DB Tests.
 *
 * @group edd_customers_db
 * @group database
 * @group edd_customers
 */
namespace EDD\Tests\Customers;

use EDD\Tests\Helpers;
use EDD\Tests\PHPUnit\EDD_UnitTestCase;

class CustomersDB extends EDD_UnitTestCase {

	/**
	 * Customers fixture.
	 *
	 * @access protected
	 * @var array
	 */
	protected static $customers = array();

	/**
	 * Set up fixtures once.
	 */
	public static function wpSetUpBeforeClass() {
		self::$customers = parent::edd()->customer->create_many( 5 );
	}

	public function test_installed() {
		$this->assertTrue( edd_get_component_interface( 'customer', 'table' )->exists() );
	}

	public function test_insert_with_no_email_supplied_should_return_false() {
		$this->assertFalse( edd_add_customer( array(
			'name' => 'John Smith',
		) ) );
	}

	public function test_insert_with_invalid_data_should_return_false() {
		$this->assertFalse( edd_add_customer() );
	}

	public function test_update_should_return_false_if_no_row_id_supplied() {
		$this->assertFalse( edd_update_customer( 0 ) );
	}

	public function test_customer_object_after_update_should_return_false() {
		$this->assertSame( 1, edd_update_customer( self::$customers[0], array(
			'name' => 'John Smith'
		) ) );

		/** @var $customer \EDD_Customer */
		$customer = edd_get_customer( self::$customers[0] );

		$this->assertSame( 'John Smith', $customer->name );
	}

	public function test_delete_should_return_false_if_no_row_id_supplied() {
		$this->assertFalse( edd_delete_customer( 0 ) );
	}

	public function test_delete_should_return_false() {
		$this->assertSame( 1, edd_delete_customer( self::$customers[0] ) );

		$customer = edd_get_customer( self::$customers[0] );

		$this->assertFalse( $customer );
	}

	public function test_get_customers_should_return_5() {
		$customers = edd_get_customers();

		$this->assertCount( 5, $customers );
	}

	public function test_get_customers_with_number_should_return_5() {
		$customers = edd_get_customers( array(
			'number' => 10
		) );

		$this->assertCount( 5, $customers );
	}

	public function test_get_customers_with_offset_should_return_true() {
		$customers = edd_get_customers( array(
			'number' => 10,
			'offset' => 4,
		) );

		$this->assertCount( 1, $customers );
	}

	public function test_get_customers_with_orderby_name_and_order_asc_should_return_true() {
		$customers = edd_get_customers( array(
			'orderby' => 'name',
			'order'   => 'asc'
		) );

		$this->assertTrue( $customers[0]->name < $customers[1]->name );
	}

	public function test_get_customers_with_orderby_name_and_order_desc_should_return_true() {
		$customers = edd_get_customers( array(
			'orderby' => 'name',
			'order'   => 'desc'
		) );

		$this->assertTrue( $customers[0]->name > $customers[1]->name );
	}

	public function test_get_customers_with_orderby_email_and_order_asc_should_return_true() {
		$customers = edd_get_customers( array(
			'orderby' => 'email',
			'order'   => 'asc'
		) );

		$this->assertTrue( $customers[0]->email < $customers[1]->email );
	}

	public function test_get_customers_with_orderby_email_and_order_desc_should_return_true() {
		$customers = edd_get_customers( array(
			'orderby' => 'email',
			'order'   => 'desc'
		) );

		$this->assertTrue( $customers[0]->email > $customers[1]->email );
	}

	public function test_get_customers_with_id_should_be_1() {
		$customers = edd_get_customers( array(
			'id' => self::$customers[4]
		) );

		$this->assertCount( 1, $customers );
	}

	public function test_get_customers_with_email_should_be_1() {
		$customers = edd_get_customers( array(
			'email' => 'user' . \WP_UnitTest_Generator_Sequence::$incr . '@edd.test'
		) );

		$this->assertCount( 1, $customers );
	}

	public function test_get_customers_by_invalid_id_should_be_0() {
		$customers = edd_get_customers( array(
			'id' => 99999,
		) );

		$this->assertCount( 0, $customers );
	}

	public function test_count_should_be_5() {
		$this->assertSame( 5, edd_count_customers() );
		$this->assertSame( 5, edd_count_customers( array( 'fields' => 'ids' ) ) );
	}

	public function test_count_with_id_should_be_1() {
		$this->assertSame( 1, edd_count_customers( array(
			'id' => self::$customers[2]
		) ) );
	}

	public function test_count_with_email_should_be_1() {
		$this->assertSame( 1, edd_count_customers( array(
			'email' => 'user' . \WP_UnitTest_Generator_Sequence::$incr . '@edd.test'
		) ) );
	}

	public function test_get_customer_by() {
		$customer = edd_get_customer_by( 'email', 'user' . \WP_UnitTest_Generator_Sequence::$incr . '@edd.test' );

		$this->assertSame( 'user' . \WP_UnitTest_Generator_Sequence::$incr . '@edd.test', $customer->email );
	}

	public function test_update_customer_email_on_user_update() {
		$user_id = wp_insert_user( array(
			'user_login' => 'user' . ( \WP_UnitTest_Generator_Sequence::$incr - 1 ),
			'user_email' => 'user' . ( \WP_UnitTest_Generator_Sequence::$incr - 1 ) . '@edd.test',
			'user_pass'  => wp_generate_password()
		) );

		edd_update_customer( self::$customers[3], array(
			'user_id' => $user_id
		) );

		wp_update_user( array(
			'ID' => $user_id,
			'user_email' => 'user' . \WP_UnitTest_Generator_Sequence::$incr . '-updated@edd.test',
		) );

		$updated_customer = new \EDD_Customer( 'user' . \WP_UnitTest_Generator_Sequence::$incr . '-updated@edd.test' );

		$this->assertEquals( self::$customers[3], $updated_customer->id );
	}

	public function test_querying_customer_by_secondary_email_should_return_customer() {
		$customer_id   = self::$customers[4];
		edd_add_customer_email_address(
			array(
				'customer_id' => $customer_id,
				'email'       => 'totallynewemail@edd.test',
				'type'        => 'secondary',
			)
		);

		$customers = edd_get_customers(
			array(
				'email' => 'totallynewemail@edd.test',
			)
		);
		$customer = reset( $customers );

		$this->assertEquals( $customer_id, $customer->id );
	}

	public function test_querying_customer_by_secondary_email_in_should_return_customers() {
		edd_add_customer_email_address(
			array(
				'customer_id' => self::$customers[3],
				'email'       => 'totallynewemail3@edd.test',
				'type'        => 'secondary',
			)
		);

		edd_add_customer_email_address(
			array(
				'customer_id' => self::$customers[4],
				'email'       => 'totallynewemail4@edd.test',
				'type'        => 'secondary',
			)
		);

		$customers = edd_get_customers(
			array(
				'email__in' => array( 'totallynewemail3@edd.test', 'totallynewemail4@edd.test' ),
			)
		);

		$found_customer_ids = array_map( 'absint', wp_list_pluck( $customers, 'id' ) );

		$this->assertTrue( in_array( self::$customers[3], $found_customer_ids, true ) );
		$this->assertTrue( in_array( self::$customers[4], $found_customer_ids, true ) );
	}

	public function test_querying_customer_by_secondary_email_not_in_should_not_return_customer() {
		$customer_id = self::$customers[4];
		$customer    = edd_get_customer( $customer_id );

		edd_add_customer_email_address(
			array(
				'customer_id' => $customer_id,
				'email'       => 'totallynewemail@edd.test',
				'type'        => 'secondary',
			)
		);

		$customers = edd_get_customers(
			array(
				'email__not_in' => array( 'totallynewemail@edd.test', $customer->email ),
			)
		);

		$found_customer_ids = array_map( 'absint', wp_list_pluck( $customers, 'id' ) );

		$this->assertFalse( in_array( $customer_id, $found_customer_ids, true ) );
	}

	public function test_legacy_attach_payment_should_return_true() {
		$payment_id = Helpers\EDD_Helper_Payment::create_simple_payment();

		// Legacy method that should be handled by Customer back-compat class.
		EDD()->customers->attach_payment( self::$customers[0], $payment_id );

		$customer    = edd_get_customer( self::$customers[0] );
		$payment_ids = array_values( explode( ',', $customer->payment_ids ) );
		$order_ids   = $customer->order_ids;

		$this->assertTrue( in_array( $payment_id, $payment_ids ) );
		$this->assertTrue( in_array( $payment_id, $order_ids ) );
	}

	public function test_legacy_remove_payment_should_return_false() {
		$payment_id = Helpers\EDD_Helper_Payment::create_simple_payment();

		// Legacy method that should be handled by Customer back-compat class.
		EDD()->customers->attach_payment( self::$customers[0], $payment_id );
		EDD()->customers->remove_payment( self::$customers[0], $payment_id );

		$customer    = edd_get_customer( self::$customers[0] );
		$payment_ids = array_map( 'absint', explode( ',', $customer->payment_ids ) );

		$this->assertFalse( in_array( $payment_id, $payment_ids ) );
	}

	/**
	 * @expectEDDeprecated EDD_Customer::increase_purchase_count
	 * @expectEDDeprecated EDD_Customer::increase_value
	 */
	public function test_legacy_increment_stats_purchase_value_should_return_10() {
		EDD()->customers->increment_stats( self::$customers[0], 10 );

		/** @var $customer \EDD_Customer */
		$customer = edd_get_customer( self::$customers[0] );

		$this->assertSame( 10.0, $customer->purchase_value );
	}

	/**
	 * @expectEDDeprecated EDD_Customer::increase_purchase_count
	 * @expectEDDeprecated EDD_Customer::increase_value
	 */
	public function test_legacy_increment_stats_purchase_count_should_return_1() {
		EDD()->customers->increment_stats( self::$customers[0], 10 );

		/** @var $customer \EDD_Customer */
		$customer = edd_get_customer( self::$customers[0] );

		$this->assertSame( 1, $customer->purchase_count );
	}

	/**
	 * @expectEDDeprecated EDD_Customer::increase_purchase_count
	 * @expectEDDeprecated EDD_Customer::increase_value
	 * @expectEDDeprecated EDD_Customer::decrease_purchase_count
	 * @expectEDDeprecated EDD_Customer::decrease_value
	 */
	public function test_legacy_decrement_stats_purchase_value_should_return_90() {
		EDD()->customers->increment_stats( self::$customers[0], 100 );
		EDD()->customers->decrement_stats( self::$customers[0], 10 );

		/** @var $customer \EDD_Customer */
		$customer = edd_get_customer( self::$customers[0] );

		$this->assertSame( 90.0, $customer->purchase_value );
	}

	/**
	 * @expectEDDeprecated EDD_Customer::increase_purchase_count
	 * @expectEDDeprecated EDD_Customer::increase_value
	 * @expectEDDeprecated EDD_Customer::decrease_purchase_count
	 * @expectEDDeprecated EDD_Customer::decrease_value
	 */
	public function test_legacy_decrement_stats_purchase_count_should_return_0() {
		EDD()->customers->increment_stats( self::$customers[0], 10 );
		EDD()->customers->decrement_stats( self::$customers[0], 10 );

		/** @var $customer \EDD_Customer */
		$customer = edd_get_customer( self::$customers[0] );

		$this->assertSame( 0, $customer->purchase_count );
	}

	public function test_updating_customer_with_empty_email_is_false() {
		$customer = edd_get_customer( self::$customers[0] );

		$this->assertFalse(
			edd_update_customer(
				self::$customers[0],
				array(
					'email' => '',
				)
			)
		);
	}

	/**
	 * @expectedDeprecated EDD\Deprecated\EmptyClass->non_existent_property
	 */
	public function test_edd_customer_db_empty_class_is_deprecated() {
		$this->assertTrue( class_exists( 'EDD_DB_Customers' ) );
		$this->assertTrue( class_exists( 'EDD_DB_Customer_Meta' ) );

		$deprecated = new \EDD_DB_Customers();
		$this->assertTrue( $deprecated instanceof \EDD\Deprecated\EmptyClass );
		$this->assertNull( $deprecated->non_existent_property );
	}
}
