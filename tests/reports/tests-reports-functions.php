<?php
namespace EDD\Tests\Reports;

use EDD\Tests\PHPUnit\EDD_UnitTestCase;
use EDD\Reports\Init as ReportsInit;
use EDD\Reports;
use EDD\Tests\Helpers\EDD_Helper_Download;
new ReportsInit();


/**
 * Tests for the Endpoint object.
 *
 * @group edd_reports
 * @group edd_reports_endpoints
 * @group edd_reports_functions
 * @group edd_objects
 */
class Functions extends EDD_UnitTestCase {

	/**
	 * Date fixture.
	 *
	 * @var \EDD\Utils\Date
	 */
	protected static $date;

	/**
	 * Set up fixtures once.
	 */
	public static function wpSetUpBeforeClass() {
		self::$date = EDD()->utils->date();
	}

	/**
	 * Runs after every test method.
	 */
	public function tearDown(): void {
		unset( $_REQUEST['view'] );
		unset( $_REQUEST['filter_from'] );
		unset( $_REQUEST['filter_to'] );
		unset( $_REQUEST['range'] );

		/** @var \EDD\Reports\Data\Report_Registry|\WP_Error $registry */
		$registry = EDD()->utils->get_registry( 'reports' );
		$registry->exchangeArray( array() );

		parent::tearDown();
	}

	/**
	 * @covers \EDD\Reports\get_current_report()
	 */
	public function test_get_current_report_should_use_the_value_of_the_tab_var_when_set() {
		$_REQUEST['view'] = 'overview';

		$this->assertSame( 'overview', Reports\get_current_report() );
	}

	/**
	 * @covers \EDD\Reports\get_current_report()
	 */
	public function test_get_current_report_should_default_to_overview() {
		$this->assertSame( 'overview', Reports\get_current_report() );
	}

	/**
	 * @covers \EDD\Reports\get_current_report()
	 */
	public function test_get_current_report_should_respect_filter() {
		add_filter( 'edd_default_report_view', array( $this, 'filter_default_report_to_customers' ), 10, 1 );
		$this->assertSame( 'customers', Reports\get_current_report() );
		remove_filter( 'edd_default_report_view', array( $this, 'filter_default_report_to_customers' ), 10, 1 );
	}

	/**
	 * @covers \EDD\Reports\get_current_report()
	 */
	public function test_get_current_report_should_use_the_sanitized_value_of_the_tab_var_when_set() {
		$_REQUEST['view'] = 'sales/figures';

		$this->assertSame( 'salesfigures', Reports\get_current_report() );
	}

	/**
	 * @covers \EDD\Reports\get_endpoint()
	 * @group edd_errors
	 */
	public function test_get_endpoint_with_invalid_endpoint_id_should_return_WP_Error() {
		$result = Reports\get_endpoint( 'fake', 'tile' );

		$this->assertWPError( $result );
	}

	/**
	 * @covers \EDD\Reports\get_endpoint()
	 * @group edd_errors
	 */
	public function test_get_endpoint_with_invalid_endpoint_id_should_return_WP_Error_code_invalid_endpoint() {
		$result = Reports\get_endpoint( 'fake', 'tile' );

		$this->assertSame( 'invalid_endpoint', $result->get_error_code() );
	}

	/**
	 * @covers \EDD\Reports\get_endpoint()
	 * @throws \EDD_Exception
	 */
	public function test_get_endpoint_with_valid_endpoint_id_valid_type_should_return_an_Endpoint_object() {
		Reports\register_endpoint( 'foo', array(
			'label' => 'Foo',
			'views' => array(
				'tile' => array(
					'display_args'     => array( 'some_value' ),
					'display_callback' => '__return_false',
					'data_callback'    => '__return_false',
				),
			),
		) );

		$registry = EDD()->utils->get_registry( 'reports:endpoints' );

		$result = Reports\get_endpoint( 'foo', 'tile' );

		$this->assertInstanceOf( 'EDD\Reports\Data\Endpoint', $result );
	}

	/**
	 * @covers \EDD\Reports\get_endpoint()
	 * @group edd_errors
	 * @throws \EDD_Exception
	 */
	public function test_get_endpoint_with_valid_endpoint_id_invalid_type_should_return_WP_Error_including_invalid_view_error_code() {
		Reports\register_endpoint( 'foo', array(
			'label' => 'Foo',
			'views' => array(
				'tile' => array(
					'display_args'     => array( 'some_value' ),
					'display_callback' => '__return_false',
					'data_callback'    => '__return_false',
				),
			),
		) );

		$result = Reports\get_endpoint( 'foo', 'fake' );

		$this->assertSame( 'invalid_view', $result->get_error_code() );
	}

	/**
	 * @covers \EDD\Reports\parse_endpoint_views()
	 */
	public function test_get_endpoint_views_should_return_the_defaults() {
		$views = Reports\get_endpoint_views();

		$this->assertEqualSets( array( 'tile', 'chart', 'table' ), array_keys( $views ) );
	}

	/**
	 * @covers \EDD\Reports\parse_endpoint_views()
	 */
	public function test_parse_endpoint_views_with_invalid_view_should_leave_it_intact() {
		$expected = array(
			'fake' => array(
				'display_callback' => '__return_false'
			),
		);

		$this->assertEqualSetsWithIndex( $expected, Reports\parse_endpoint_views( $expected ) );
	}

	/**
	 * @covers \EDD\Reports\parse_endpoint_views()
	 */
	public function test_parse_endpoint_views_with_valid_view_should_inject_defaults() {
		$expected = array(
			'tile' => array(
				'data_callback'    => '__return_zero',
				'display_callback' => 'EDD\Reports\default_display_tile',
				'display_args'     => array(
					'type'             => '' ,
					'context'          => 'primary',
					'comparison_label' => __( 'All time', 'easy-digital-downloads' ),
				),
			),
		);

		$views = array(
			'tile' => array(
				'data_callback' => '__return_zero',
			),
		);

		$this->assertEqualSetsWithIndex( $expected, Reports\parse_endpoint_views( $views ) );
	}

	/**
	 * @covers \EDD\Reports\parse_endpoint_views()
	 */
	public function test_parse_endpoint_views_should_strip_invalid_fields() {
		$views = array(
			'tile' => array(
				'fake_field' => 'foo',
			),
		);

		$result = Reports\parse_endpoint_views( $views );

		$this->assertArrayNotHasKey( 'fake_field', $result['tile'] );
	}

	/**
	 * @covers \EDD\Reports\parse_endpoint_views()
	 */
	public function test_parse_endpoint_views_should_inject_default_display_args() {
		$expected = array(
			'type'             => 'number',
			'context'          => 'primary',
			'comparison_label' => __( 'All time', 'easy-digital-downloads' ),
		);

		$views = array(
			'tile' => array(
				'display_args' => array(
					'type' => 'number',
				)
			)
		);

		$result = Reports\parse_endpoint_views( $views );

		$this->assertEqualSetsWithIndex( $expected, $result['tile']['display_args'] );
	}

	/**
	 * @covers \EDD\Reports\validate_endpoint_view()
	 */
	public function test_validate_endpoint_view_with_valid_view_should_return_true() {
		$this->assertTrue( Reports\validate_endpoint_view( 'tile' ) );
	}

	/**
	 * @covers \EDD\Reports\validate_endpoint_view()
	 */
	public function test_validate_endpoint_view_with_invalid_view_should_return_false() {
		$this->assertFalse( Reports\validate_endpoint_view( 'fake' ) );
	}

	/**
	 * @covers \EDD\Reports\get_endpoint_handler()
	 */
	public function test_get_endpoint_handler_with_valid_view_should_return_the_handler() {
		$expected = 'EDD\Reports\Data\Tile_Endpoint';

		$this->assertSame( $expected, Reports\get_endpoint_handler( 'tile' ) );
	}

	/**
	 * @covers \EDD\Reports\get_endpoint_handler()
	 */
	public function test_get_endpoint_handler_with_invalid_view_should_return_empty() {
		$this->assertSame( '', Reports\get_endpoint_handler( 'fake' ) );
	}

	/**
	 * @covers \EDD\Reports\get_endpoint_group_callback()
	 */
	public function test_get_endpoint_group_callback_with_tile_view_should_return_that_group_callback() {
		$expected = 'EDD\Reports\default_display_tiles_group';

		$this->assertSame( $expected, Reports\get_endpoint_group_callback( 'tile' ) );
	}

	/**
	 * @covers \EDD\Reports\get_endpoint_group_callback()
	 */
	public function test_get_endpoint_group_callback_with_table_view_should_return_that_group_callback() {
		$expected = 'EDD\Reports\default_display_tables_group';

		$this->assertSame( $expected, Reports\get_endpoint_group_callback( 'table' ) );
	}

	/**
	 * @covers \EDD\Reports\get_endpoint_group_callback()
	 */
	public function test_get_endpoint_group_callback_with_invalid_view_should_return_an_empty_string() {
		$this->assertSame( '', Reports\get_endpoint_group_callback( 'fake' ) );
	}

	/**
	 * @covers \EDD\Reports\get_filters()
	 */
	public function test_get_filters_should_return_records_for_all_official_filters() {
		$expected = array( 'dates', 'products', 'product_categories', 'taxes', 'gateways', 'discounts', 'regions', 'countries', 'currencies', 'order_statuses' );

		$this->assertEqualSets( $expected, array_keys( Reports\get_filters() ) );
	}

	/**
	 * @covers \EDD\Reports\validate_filter()
	 */
	public function test_validate_filter_with_valid_filter_should_return_true() {
		$this->assertTrue( Reports\validate_filter( 'dates' ) );
	}

	/**
	 * @covers \EDD\Reports\validate_filter()
	 */
	public function test_validate_filter_with_invalid_filter_should_return_false() {
		$this->assertFalse( Reports\validate_filter( 'fake' ) );
	}

	/**
	 * @covers \EDD\Reports\get_filter_value()
	 */
	public function test_get_filter_value_with_invalid_filter_should_return_an_empty_string() {
		$this->assertSame( '', Reports\get_filter_value( 'fake' ) );
	}

	/**
	 * @covers \EDD\Reports\get_filter_value()
	 */
	public function test_get_filter_value_with_a_valid_filter_should_retrieve_that_filters_value() {
		$expected = array(
			'from'           => date( 'Y-m-d 00:00:00' ),
			'to'             => date( 'Y-m-d 23:59:59' ),
			'range'          => 'today',
			'relative_range' => 'previous_period',
		);

		$_GET['range']          = 'today';
		$_GET['relative_range'] = 'previous_period';
		$_GET['filter_from']    = $expected['from'];
		$_GET['filter_to']      = $expected['to'];

		$this->assertEqualSetsWithIndex( $expected, Reports\get_filter_value( 'dates' ) );
	}

	public function test_get_filter_value_with_a_valid_filter_should_retrieve_that_filters_value_filter_default() {
		add_filter( 'edd_reports_default_date_range', function() { return 'last_30_days'; }, 10, 1 );
		add_filter( 'edd_reports_default_relative_date_range', function() { return 'previous_year'; }, 10, 1 );

		$expected = array(
			'from'           => date( 'Y-m-d', strtotime( '-30 days') ),
			'to'             => date( 'Y-m-d' ),
			'range'          => 'last_30_days',
			'relative_range' => 'previous_year',
		);

		$_GET['filter_from']    = $expected['from'];
		$_GET['filter_to']      = $expected['to'];

		$this->assertEqualSetsWithIndex( $expected, Reports\get_filter_value( 'dates' ) );

		remove_filter( 'edd_reports_default_date_range', function() { return 'last_30_days'; }, 10, 1 );
		remove_filter( 'edd_reports_default_relative_date_range', function() { return 'previous_year'; }, 10, 1 );
	}

	public function test_get_filter_value_with_an_invalid_filter_should_retrieve_revert_to_core_default() {
		add_filter( 'edd_reports_default_date_range', function() { return 'foo'; }, 10, 1 );
		add_filter( 'edd_reports_default_relative_date_range', function() { return 'bar'; }, 10, 1 );

		$expected = array(
			'from'           => date( 'Y-m-d', strtotime( 'first day of this month' ) ),
			'to'             => date( 'Y-m-d' ),
			'range'          => 'this_month',
			'relative_range' => 'previous_period',
		);

		$_GET['filter_from']    = $expected['from'];
		$_GET['filter_to']      = $expected['to'];

		$this->assertEqualSetsWithIndex( $expected, Reports\get_filter_value( 'dates' ) );

		remove_filter( 'edd_reports_default_date_range', function() { return 'foo'; }, 10, 1 );
		remove_filter( 'edd_reports_default_relative_date_range', function() { return 'bar'; }, 10, 1 );
	}

	/**
	 * @covers \EDD\Reports\get_dates_filter_options()
	 * @group edd_dates
	 */
	public function test_get_dates_filter_options_should_match_defaults() {
		$expected = array(
			'other'        => __( 'Custom', 'easy-digital-downloads' ),
			'today'        => __( 'Today', 'easy-digital-downloads' ),
			'yesterday'    => __( 'Yesterday', 'easy-digital-downloads' ),
			'this_week'    => __( 'This Week', 'easy-digital-downloads' ),
			'last_week'    => __( 'Last Week', 'easy-digital-downloads' ),
			'last_30_days' => __( 'Last 30 Days', 'easy-digital-downloads' ),
			'this_month'   => __( 'Month to Date', 'easy-digital-downloads' ),
			'last_month'   => __( 'Last Month', 'easy-digital-downloads' ),
			'this_quarter' => __( 'Quarter to Date', 'easy-digital-downloads' ),
			'last_quarter' => __( 'Last Quarter', 'easy-digital-downloads' ),
			'this_year'    => __( 'Year to Date', 'easy-digital-downloads' ),
			'last_year'    => __( 'Last Year', 'easy-digital-downloads' ),
		);

		$this->assertEqualSetsWithIndex( $expected, Reports\get_dates_filter_options() );
	}

	/**
	 * @covers \EDD\Reports\get_dates_filter()
	 * @group edd_dates
	 */
	public function test_get_dates_filter_should_return_strings() {
		$expected = array(
			'start' => self::$date->copy()->startOfMonth()->toDateTimeString(),
			'end'   => self::$date->copy()->endOfDay()->toDateTimeString(),
			'range' => 'this_month',
		);

		$result = Reports\get_dates_filter();

		// Explicitly strip seconds in case the test is slow.
		$expected = $this->strip_seconds( $expected );
		$result   = $this->strip_seconds( $result );

		$this->assertEqualSetsWithIndex( $expected, $result );
	}

	/**
	 * @covers \EDD\Reports\get_dates_filter()
	 * @group edd_dates
	 */
	public function test_get_dates_filter_objects_as_values_should_return_objects() {
		$expected = array(
			'start' => self::$date->copy()->subDay( 30 )->startOfDay(),
			'end'   => self::$date->copy()->endOfDay(),
		);

		$result = Reports\get_dates_filter( 'objects' );

		$this->assertInstanceOf( '\EDD\Utils\Date', $result['start'] );
		$this->assertInstanceOf( '\EDD\Utils\Date', $result['end'] );
	}

	/**
	 * @covers \EDD\Reports\parse_dates_for_range()
	 * @group edd_dates
	 */
	public function test_parse_dates_for_range_with_this_month_range_should_return_those_dates() {
		$expected = array(
			'start' => self::$date->copy()->startOfMonth()->toDateTimeString(),
			'end'   => self::$date->copy()->endOfDay()->toDateTimeString(),
			'range' => 'this_month',
		);

		$result = Reports\parse_dates_for_range( 'this_month' );

		// Explicitly strip seconds in case the test is slow.
		$expected = $this->strip_seconds( $expected );
		$result   = $this->strip_seconds( $this->objects_to_date_strings( $result ) );

		$this->assertEqualSetsWithIndex( $expected, $result );
	}

	/**
	 * @covers \EDD\Reports\parse_dates_for_range()
	 * @group edd_dates
	 */
	public function test_parse_dates_for_range_with_last_month_range_should_return_those_dates() {
		$expected = array(
			'start' => self::$date->copy()->subMonthNoOverflow( 1 )->startOfMonth()->toDateTimeString(),
			'end'   => self::$date->copy()->subMonthNoOverflow( 1 )->endOfMonth()->toDateTimeString(),
			'range' => 'last_month',
		);

		$result = Reports\parse_dates_for_range( 'last_month' );

		// Explicitly strip seconds in case the test is slow.
		$expected = $this->strip_seconds( $expected );
		$result   = $this->strip_seconds( $this->objects_to_date_strings( $result ) );

		$this->assertEqualSetsWithIndex( $expected, $result );
	}

	/**
	 * @covers \EDD\Reports\parse_dates_for_range()
	 * @group edd_dates
	 */
	public function test_parse_dates_for_range_with_overflow_last_month_range_should_return_those_dates() {
		$overflow_day  = '2020-03-30 00:00:00';
		$overflow_date = EDD()->utils->date( $overflow_day );

		$expected = array(
			'start' => ( new \DateTime( '2020-02-01 00:00:00' ) )->format( 'Y-m-d H:i' ),
			'end'   => ( new \DateTime( '2020-02-29 23:59:59' ) )->format( 'Y-m-d H:i' ),
			'range' => 'last_month',
		);

		$result = Reports\parse_dates_for_range( 'last_month', $overflow_day );

		// Explicitly strip seconds in case the test is slow.
		$result = $this->strip_seconds( $this->objects_to_date_strings( $result ) );

		$this->assertEqualSetsWithIndex( $expected, $result );
	}

	/**
	 * @covers \EDD\Reports\parse_dates_for_range()
	 * @group edd_dates
	 */
	public function test_parse_dates_for_range_with_today_range_should_return_those_dates() {
		$expected = array(
			'start' => self::$date->copy()->setTimezone( edd_get_timezone_id() )->startOfDay()->setTimezone( 'UTC' ),
			'end'   => self::$date->copy()->setTimezone( edd_get_timezone_id() )->endOfDay()->setTimezone( 'UTC' ),
			'range' => 'today',
		);

		$result = Reports\parse_dates_for_range( 'today' );

		// Explicitly strip seconds in case the test is slow.
		$expected = $this->strip_seconds( $expected );
		$result   = $this->strip_seconds( $this->objects_to_date_strings( $result ) );

		$this->assertEqualSetsWithIndex( $expected, $result );
	}

	/**
	 * @covers \EDD\Reports\parse_dates_for_range()
	 * @group edd_dates
	 */
	public function test_parse_dates_for_range_with_yesterday_range_should_return_those_dates() {
		$expected = array(
			'start' => self::$date->copy()->setTimezone( edd_get_timezone_id() )->subDay( 1 )->startOfDay()->setTimezone( 'UTC' ),
			'end'   => self::$date->copy()->setTimezone( edd_get_timezone_id() )->subDay( 1 )->endOfDay()->setTimezone( 'UTC' ),
			'range' => 'yesterday',
		);

		$result = Reports\parse_dates_for_range( 'yesterday' );

		// Explicitly strip seconds in case the test is slow.
		$expected = $this->strip_seconds( $expected );
		$result   = $this->strip_seconds( $this->objects_to_date_strings( $result ) );

		$this->assertEqualSetsWithIndex( $expected, $result );
	}

	/**
	 * @covers \EDD\Reports\parse_dates_for_range()
	 * @group edd_dates
	 */
	public function test_parse_dates_for_range_with_this_week_range_should_return_those_dates() {
		$expected = array(
			'start' => self::$date->copy()->startOfWeek()->toDateTimeString(),
			'end'   => self::$date->copy()->endOfDay()->toDateTimeString(),
			'range' => 'this_week',
		);

		$result = Reports\parse_dates_for_range( 'this_week' );

		// Explicitly strip seconds in case the test is slow.
		$expected = $this->strip_seconds( $expected );
		$result   = $this->strip_seconds( $this->objects_to_date_strings( $result ) );

		$this->assertEqualSetsWithIndex( $expected, $result );
	}

	/**
	 * @covers \EDD\Reports\parse_dates_for_range()
	 * @group edd_dates
	 */
	public function test_parse_dates_for_range_with_last_week_range_should_return_those_dates() {
		$expected = array(
			'start' => self::$date->copy()->subWeek( 1 )->startOfWeek()->toDateTimeString(),
			'end'   => self::$date->copy()->subWeek( 1 )->endOfWeek()->toDateTimeString(),
			'range' => 'last_week',
		);

		$result = Reports\parse_dates_for_range( 'last_week' );

		// Explicitly strip seconds in case the test is slow.
		$expected = $this->strip_seconds( $expected );
		$result   = $this->strip_seconds( $this->objects_to_date_strings( $result ) );

		$this->assertEqualSetsWithIndex( $expected, $result );
	}

	/**
	 * @covers \EDD\Reports\parse_dates_for_range()
	 * @group edd_dates
	 */
	public function test_parse_dates_for_range_with_last_30_days_range_should_return_those_dates() {
		$expected = array(
			'start' => self::$date->copy()->subDay( 30 )->startOfDay()->toDateTimeString(),
			'end'   => self::$date->copy()->endOfDay()->toDateTimeString(),
			'range' => 'last_30_days',
		);

		$result = Reports\parse_dates_for_range( 'last_30_days' );

		// Explicitly strip seconds in case the test is slow.
		$expected = $this->strip_seconds( $expected );
		$result   = $this->strip_seconds( $this->objects_to_date_strings( $result ) );

		$this->assertEqualSetsWithIndex( $expected, $result );
	}

	/**
	 * @covers \EDD\Reports\parse_dates_for_range()
	 * @group edd_dates
	 */
	public function test_parse_dates_for_range_with_this_quarter_range_should_return_those_dates() {
		$expected = array(
			'start' => self::$date->copy()->startOfQuarter()->toDateTimeString(),
			'end'   => self::$date->copy()->endOfDay()->toDateTimeString(),
			'range' => 'this_quarter',
		);

		$result = Reports\parse_dates_for_range( 'this_quarter' );

		// Explicitly strip seconds in case the test is slow.
		$expected = $this->strip_seconds( $expected );
		$result   = $this->strip_seconds( $this->objects_to_date_strings( $result ) );

		$this->assertEqualSetsWithIndex( $expected, $result );
	}

	/**
	 * @covers \EDD\Reports\parse_dates_for_range()
	 * @group edd_dates
	 */
	public function test_parse_dates_for_range_with_last_quarter_range_should_return_those_dates() {
		$expected = array(
			'start' => self::$date->copy()->subQuarter( 1 )->startOfQuarter()->toDateTimeString(),
			'end'   => self::$date->copy()->subQuarter( 1 )->endOfQuarter()->toDateTimeString(),
			'range' => 'last_quarter',
		);

		$result = Reports\parse_dates_for_range( 'last_quarter' );

		// Explicitly strip seconds in case the test is slow.
		$expected = $this->strip_seconds( $expected );
		$result   = $this->strip_seconds( $this->objects_to_date_strings( $result ) );

		$this->assertEqualSetsWithIndex( $expected, $result );
	}

	/**
	 * @covers \EDD\Reports\parse_dates_for_range()
	 * @group edd_dates
	 */
	public function test_parse_dates_for_range_with_this_year_range_should_return_those_dates() {
		$expected = array(
			'start' => self::$date->copy()->startOfYear()->toDateTimeString(),
			'end'   => self::$date->copy()->endOfDay()->toDateTimeString(),
			'range' => 'this_year',
		);

		$result = Reports\parse_dates_for_range( 'this_year' );

		// Explicitly strip seconds in case the test is slow.
		$expected = $this->strip_seconds( $expected );
		$result   = $this->strip_seconds( $this->objects_to_date_strings( $result ) );

		$this->assertEqualSetsWithIndex( $expected, $result );
	}

	/**
	 * @covers \EDD\Reports\parse_dates_for_range()
	 * @group edd_dates
	 */
	public function test_parse_dates_for_range_with_last_year_range_should_return_those_dates() {
		$expected = array(
			'start' => self::$date->copy()->subYear( 1 )->startOfYear()->toDateTimeString(),
			'end'   => self::$date->copy()->subYear( 1 )->endOfYear()->toDateTimeString(),
			'range' => 'last_year',
		);

		$result = Reports\parse_dates_for_range( 'last_year' );

		// Explicitly strip seconds in case the test is slow.
		$expected = $this->strip_seconds( $expected );
		$result   = $this->strip_seconds( $this->objects_to_date_strings( $result ) );

		$this->assertEqualSetsWithIndex( $expected, $result );
	}

	/**
	 * @covers \EDD\Reports\parse_dates_for_range()
	 * @group edd_dates
	 */
	public function test_parse_dates_for_range_with_other_range_should_return_dates_for_request_vars() {
		$dates = array(
			'from'  => self::$date->copy()->subCentury( 2 )->startOfDay()->toDateTimeString(),
			'to'    => self::$date->copy()->addCentury( 2 )->endOfDay()->toDateTimeString(),
		);

		$_GET['range']          = 'other';
		$_GET['relative_range'] = 'previous_period';
		$_GET['filter_from']    = $dates['from'];
		$_GET['filter_to']      = $dates['to'];

		$expected = array(
			'start'          => $dates['from'],
			'end'            => $dates['to'],
			'range'          => 'other',
		);

		$result = Reports\parse_dates_for_range( 'other' );

		// Explicitly strip seconds in case the test is slow.
		$expected = $this->strip_seconds( $expected );
		$result   = $this->strip_seconds( $this->objects_to_date_strings( $result ) );

		$this->assertEqualSetsWithIndex( $expected, $result );
	}

	/**
	 * @covers \EDD\Reports\parse_dates_for_range()
	 * @group edd_dates
	 */
	public function test_parse_dates_for_range_with_invalid_range_no_report_id_no_range_var_should_use_this_month() {
		$expected = array(
			'start' => self::$date->copy()->startOfMonth()->toDateTimeString(),
			'end'   => self::$date->copy()->endOfDay()->toDateTimeString(),
			'range' => 'this_month',
		);

		$result = Reports\parse_dates_for_range( 'fake' );

		// Explicitly strip seconds in case the test is slow.
		$expected = $this->strip_seconds( $expected );
		$result   = $this->strip_seconds( $this->objects_to_date_strings( $result ) );

		$this->assertEqualSetsWithIndex( $expected, $result );
	}

	/**
	 * @covers \EDD\Reports\get_dates_filter_range()
	 * @group edd_dates
	 */
	public function test_get_dates_filter_range_with_no_preset_range_should_defualt_to_last_30_days() {
		$this->assertSame( 'this_month', Reports\get_dates_filter_range() );
	}

	/**
	 * @covers \EDD\Reports\get_dates_filter_range()
	 * @group edd_dates
	 */
	public function test_get_dates_filter_range_with_non_default_range_set_should_return_that_reports_range() {
		$_GET['range']          = 'last_quarter';
		$_GET['relative_range'] = 'previous_period';

		$this->assertSame( 'last_quarter', Reports\get_dates_filter_range() );
	}

	public function test_no_filters_should_default_to_this_month() {
		// Default to last 30 days for filter value.
		$dates = Reports\parse_dates_for_range( 'this_month' );

		$expected = array(
			'from'           => $dates['start']->format( 'Y-m-d' ),
			'to'             => $dates['end']->format( 'Y-m-d' ),
			'range'          => 'this_month',
			'relative_range' => 'previous_period',
		);

		$this->assertEqualSetsWithIndex( $expected, Reports\get_filter_value( 'dates' ) );
	}

	/**
	 * @covers \EDD\Reports\parse_relative_dates_for_range()
	 * @group edd_dates
	 */
	public function test_parse_relative_dates_for_previous_period_in_last_month_should_return_those_dates() {
		$range          = 'last_month';
		$relative_range = 'previous_period';

		$dates     = Reports\parse_dates_for_range( $range );
		$days_diff = $dates['start']->copy()->diffInDays( $dates['end'], true ) + 1;

		$expected = array(
			'start' => $dates['start']->copy()->subDays( $days_diff ),
			'end'   => $dates['end']->copy()->subDays( $days_diff ),
			'range' => $range,
		);

		$result = Reports\parse_relative_dates_for_range( $range, $relative_range );

		// Explicitly strip seconds in case the test is slow.
		$expected = $this->strip_seconds( $expected );
		$result   = $this->strip_seconds( $this->objects_to_date_strings( $result ) );

		$this->assertEqualSetsWithIndex( $expected, $result );
	}

	/**
	 * @covers \EDD\Reports\parse_relative_dates_for_range()
	 * @group edd_dates
	 */
	public function test_parse_relative_dates_for_previous_month_in_last_month_should_return_those_dates() {
		$range          = 'last_month';
		$relative_range = 'previous_month';

		$dates    = Reports\parse_dates_for_range( $range );
		$expected = array(
			'start' => $dates['start']->copy()->subMonth( 1 ),
			'end'   => $dates['end']->copy()->subMonthNoOverflow( 1 ),
			'range' => $range,
		);

		$result = Reports\parse_relative_dates_for_range( $range, $relative_range );

		// Explicitly strip seconds in case the test is slow.
		$expected = $this->strip_seconds( $expected );
		$result   = $this->strip_seconds( $this->objects_to_date_strings( $result ) );

		$this->assertEqualSetsWithIndex( $expected, $result );
	}

	/**
	 * @covers \EDD\Reports\parse_relative_dates_for_range()
	 * @group edd_dates
	 */
	public function test_parse_relative_dates_for_previous_quarter_in_last_month_should_return_those_dates() {
		$range          = 'last_month';
		$relative_range = 'previous_quarter';

		$dates    = Reports\parse_dates_for_range( $range );
		$expected = array(
			'start' => $dates['start']->copy()->subQuarter( 1 ),
			'end'   => $dates['end']->copy()->subQuarter( 1 ),
			'range' => $range,
		);

		$result = Reports\parse_relative_dates_for_range( $range, $relative_range );

		// Explicitly strip seconds in case the test is slow.
		$expected = $this->strip_seconds( $expected );
		$result   = $this->strip_seconds( $this->objects_to_date_strings( $result ) );

		$this->assertEqualSetsWithIndex( $expected, $result );
	}

	/**
	 * @covers \EDD\Reports\parse_relative_dates_for_range()
	 * @group edd_dates
	 */
	public function test_parse_relative_dates_for_previous_year_in_last_month_should_return_those_dates() {
		$range          = 'last_month';
		$relative_range = 'previous_year';

		$dates    = Reports\parse_dates_for_range( $range );
		$expected = array(
			'start' => $dates['start']->copy()->subYear( 1 ),
			'end'   => $dates['end']->copy()->subMonthNoOverflow( 0 )->subYear( 1 ),
			'range' => $range,
		);

		$result = Reports\parse_relative_dates_for_range( $range, $relative_range );

		// Explicitly strip seconds in case the test is slow.
		$expected = $this->strip_seconds( $expected );
		$result   = $this->strip_seconds( $this->objects_to_date_strings( $result ) );

		$this->assertEqualSetsWithIndex( $expected, $result );
	}

	public function test_parse_relative_dates_for_previous_month_with_fewer_days_should_return_last_day_of_last_month() {
		$range          = 'this_month';
		$relative_range = 'previous_month';
		$date           = '2018-03-31 00:00:00';
		$result         = Reports\parse_relative_dates_for_range( $range, $relative_range, $date );
		$formatted      = $this->strip_seconds( $this->objects_to_date_strings( $result ) );

		$this->assertEquals( '2018-02-28 23:59', $formatted['end'] );
	}

	public function test_parse_dates_for_range_with_last_quarter_range_should_return_those_dates_utc_plus() {
		$date = EDD()->utils->date( '2024-12-25', 'UTC', false );
		$expected = array(
			'start' => $date->copy()->subQuarter( 1 )->startOfQuarter(),
			'end'   => $date->copy()->subQuarter( 1 )->endOfQuarter(),
		);

		$date = EDD()->utils->date( '2024-12-25', 'GMT+5', false );
		$result = array(
			'start' => $date->copy()->subQuarter( 1 )->startOfQuarter(),
			'end'   => $date->copy()->subQuarter( 1 )->endOfQuarter(),
		);

		$expected = $this->strip_seconds( $expected );
		$result   = $this->strip_seconds( $this->objects_to_date_strings( $result ) );

		$this->assertEqualSetsWithIndex( $expected, $result );
	}

	public function test_parse_dates_for_range_with_last_quarter_range_should_return_those_dates_utc_negative() {
		$date = EDD()->utils->date( 'now', 'UTC', false );
		$expected = array(
			'start' => $date->copy()->subQuarter( 1 )->startOfQuarter(),
			'end'   => $date->copy()->subQuarter( 1 )->endOfQuarter(),
		);

		$date = EDD()->utils->date( 'now', 'GMT-5', false );
		$result = array(
			'start' => $date->copy()->subQuarter( 1 )->startOfQuarter(),
			'end'   => $date->copy()->subQuarter( 1 )->endOfQuarter(),
		);

		$expected = $this->strip_seconds( $expected );
		$result   = $this->strip_seconds( $this->objects_to_date_strings( $result ) );

		$this->assertEqualSetsWithIndex( $expected, $result );
	}

	public function test_gross_order_status() {
		$expected = array(
			'complete',
			'refunded',
			'partially_refunded',
			'revoked',
			'on_hold',
		);

		$this->assertSame( $expected, edd_get_gross_order_statuses() );
	}

	public function test_net_order_status() {
		$expected = array(
			'complete',
			'partially_refunded',
			'revoked',
			'on_hold',
		);

		$this->assertSame( $expected, edd_get_net_order_statuses() );
	}

	public function get_download_label_no_data_is_empty() {
		$this->assertEmpty( Reports\get_download_label() );
	}

	public function get_download_label_invalid_id_is_empty() {
		$this->assertEmpty( Reports\get_download_label( array( 'download_id' => 2346546 ) ) );
	}

	public function get_download_label_returns_post_title() {
		$download = EDD_Helper_Download::create_simple_download();

		$this->assertSame( $download->post_title, Reports\get_download_label( array( 'download_id' => $download->ID ) ) );
	}

	public function get_download_label__variable_returns_post_title_with_price_id() {
		$download = EDD_Helper_Download::create_variable_download();

		$this->assertSame( 'Variable Test Download Product (Advanced)', Reports\get_download_label( array( 'download_id' => $download->ID, 'price_id' => 1 ) ) );
	}

	/**
	 * Strips the seconds from start and end datetime strings to guard against slow tests.
	 *
	 * @param array $dates Start/end dates array.
	 * @return array Start/end dates minus their seconds.
	 */
	protected function strip_seconds( $dates ) {
		$dates['start'] = date( 'Y-m-d H:i', strtotime( $dates['start'] ) );
		$dates['end']   = date( 'Y-m-d H:i', strtotime( $dates['end'] ) );

		return $dates;
	}

	/**
	 * Converts start and end date objects to strings.
	 *
	 * @param array $dates Start/end date objects array.
	 * @return array Start/end date strings array.
	 */
	protected function objects_to_date_strings( $dates ) {
		$dates['start'] = $dates['start']->toDateTimeString();
		$dates['end']   = $dates['end']->toDateTimeString();

		return $dates;
	}

	public function filter_default_report_to_customers() {
		return 'customers';
	}

	public function filter_default_report_to_fake() {
		return 'fake';
	}
}
