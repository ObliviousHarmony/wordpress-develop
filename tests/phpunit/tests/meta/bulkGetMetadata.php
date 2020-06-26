<?php

/**
 * @group meta
 */
class Tests_Meta_BulkGetMetadata extends WP_UnitTestCase{
	public function test_should_get_bulk_metadata() {
		add_metadata( 'post', 12345, 'foo', 'bar' );
		add_metadata( 'post', 12345, 'foo2', 'bar2' );
		add_metadata( 'post', 54321, 'foo', 'bar3' );
		add_metadata( 'post', 54321, 'foo2', 'bar4' );

		$m = get_bulk_metadata(
			'post',
			array(
				12345 => array(
					'foo',
					'foo2',
				),
				54321 => array(
					'foo',
					'foo2',
				),
			)
		);

		delete_metadata( 'post', 12345, 'foo' );
		delete_metadata( 'post', 12345, 'foo2' );
		delete_metadata( 'post', 54321, 'foo3' );
		delete_metadata( 'post', 54321, 'foo4' );

		$this->assertEquals(
			array(
				12345 => array(
					'foo'  => array( 'bar' ),
					'foo2' => array( 'bar2' ),
				),
				54321 => array(
					'foo'  => array( 'bar3' ),
					'foo2' => array( 'bar4' ),
				),
			),
			$m
		);
	}

	public function test_should_get_bulk_metadata_single() {
		add_metadata( 'post', 12345, 'foo', 'bar' );
		add_metadata( 'post', 12345, 'foo2', 'bar2' );
		add_metadata( 'post', 54321, 'foo', 'bar3' );
		add_metadata( 'post', 54321, 'foo2', 'bar4' );

		$m = get_bulk_metadata(
			'post',
			array(
				12345 => array(
					'foo',
					'foo2',
				),
				54321 => array(
					'foo',
					'foo2',
				),
			),
			true
		);

		delete_metadata( 'post', 12345, 'foo' );
		delete_metadata( 'post', 12345, 'foo2' );
		delete_metadata( 'post', 54321, 'foo3' );
		delete_metadata( 'post', 54321, 'foo4' );

		$this->assertEquals(
			array(
				12345 => array(
					'foo'  => 'bar',
					'foo2' => 'bar2',
				),
				54321 => array(
					'foo'  => 'bar3',
					'foo2' => 'bar4',
				),
			),
			$m
		);
	}
}
