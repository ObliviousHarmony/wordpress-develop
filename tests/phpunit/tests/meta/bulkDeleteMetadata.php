<?php

/**
 * @group meta
 */
class Tests_Meta_BulkDeleteMetadata extends WP_UnitTestCase {
	public function test_should_delete_bulk_metadata() {
		add_metadata( 'post', 12345, 'foo', 'bar' );
		add_metadata( 'post', 12345, 'foo2', 'bar' );
		add_metadata( 'post', 54321, 'foo', 'bar' );
		add_metadata( 'post', 54321, 'foo2', 'bar' );

		$m = delete_bulk_metadata(
			'post',
			array(
				12345 => array(
					'foo'  => null,
					'foo2' => null,
				),
				54321 => array(
					'foo'  => null,
					'foo2' => null,
				),
			)
		);

		$this->assertEquals(
			array(
				12345 => array(
					'foo'  => true,
					'foo2' => true,
				),
				54321 => array(
					'foo'  => true,
					'foo2' => true,
				),
			),
			$m
		);
	}

	public function test_should_delete_bulk_metadata_with_value_constraint() {
		add_metadata( 'post', 12345, 'foo', 'bar' );
		add_metadata( 'post', 12345, 'foo2', 'bar' );
		add_metadata( 'post', 54321, 'foo', 'bar' );
		add_metadata( 'post', 54321, 'foo2', 'bar' );

		$m = delete_bulk_metadata(
			'post',
			array(
				12345 => array(
					'foo'  => 'bar',
					'foo2' => 'test',
				),
				54321 => array(
					'foo'  => 'bar',
					'foo2' => 'test',
				),
			)
		);

		$this->assertEquals(
			array(
				12345 => array(
					'foo'  => true,
					'foo2' => false,
				),
				54321 => array(
					'foo'  => true,
					'foo2' => false,
				),
			),
			$m
		);
	}

	public function test_should_short_circuit_bulk_metadata() {
		$a = new MockAction();
		$a->add_filter_response( 'filter', 'delete_bulk_post_metadata', 'test' );
		add_filter( 'delete_bulk_post_metadata', array( $a, 'filter' ), 10, 2 );
		$mid = add_metadata( 'post', 12345, 'foo', 'bar' );

		$metadata = array(
			12345 => array(
				'foo'  => null,
				'foo2' => null,
			),
			54321 => array(
				'foo'  => null,
				'foo2' => null,
			),
		);

		$m = delete_bulk_metadata( 'post', $metadata );

		remove_filter( 'delete_bulk_post_metadata', array( $a, 'filter' ) );

		$this->assertEquals( 'test', $m );
		$this->assertNotEmpty( get_metadata_by_mid( 'post', $mid ) );
		delete_metadata_by_mid( 'post', $mid );

		$this->assertEquals( 1, $a->get_call_count( 'filter' ) );
		$this->assertEquals(
			array(
				'action' => 'filter',
				'tag'    => 'delete_bulk_post_metadata',
				'args'   => array( null, $metadata ),
			),
			$a->get_events()[0]
		);
	}

	public function test_should_short_circuit_individual_metadata() {
		$a = new MockAction();
		$a->add_filter_response( 'filter', 'delete_post_metadata', 'test' );
		add_filter( 'delete_post_metadata', array( $a, 'filter' ), 10, 5 );
		$mid = add_metadata( 'post', 12345, 'foo', 'bar' );

		$metadata = array(
			12345 => array(
				'foo'  => 'bar',
				'foo2' => 'bar2',
			),
			54321 => array(
				'foo'  => 'bar3',
				'foo2' => 'bar4',
			),
		);

		$m = delete_bulk_metadata( 'post', $metadata );

		remove_filter( 'delete_post_metadata', array( $a, 'filter' ) );

		$this->assertEquals(
			array(
				12345 => array(
					'foo'  => 'test',
					'foo2' => 'test',
				),
				54321 => array(
					'foo'  => 'test',
					'foo2' => 'test',
				),
			),
			$m
		);
		$this->assertNotEmpty( get_metadata_by_mid( 'post', $mid ) );
		delete_metadata_by_mid( 'post', $mid );

		$this->assertEquals( 4, $a->get_call_count( 'filter' ) );
		$this->assertEquals(
			array(
				'action' => 'filter',
				'tag'    => 'delete_post_metadata',
				'args'   => array(
					null,
					12345,
					'foo',
					'bar',
					false,
				),
			),
			$a->get_events()[0]
		);
		$this->assertEquals(
			array(
				'action' => 'filter',
				'tag'    => 'delete_post_metadata',
				'args'   => array(
					null,
					12345,
					'foo2',
					'bar2',
					false,
				),
			),
			$a->get_events()[1]
		);
		$this->assertEquals(
			array(
				'action' => 'filter',
				'tag'    => 'delete_post_metadata',
				'args'   => array(
					null,
					54321,
					'foo',
					'bar3',
					false,
				),
			),
			$a->get_events()[2]
		);
		$this->assertEquals(
			array(
				'action' => 'filter',
				'tag'    => 'delete_post_metadata',
				'args'   => array(
					null,
					54321,
					'foo2',
					'bar4',
					false,
				),
			),
			$a->get_events()[3]
		);
	}

	public function test_should_call_lifecycle_actions() {
		$foo1_mid = add_metadata( 'post', 12345, 'foo', 'bar' );
		$foo2_mid = add_metadata( 'post', 12345, 'foo2', 'bar2' );

		$a = new MockAction();
		add_filter( 'delete_post_meta', array( $a, 'filter' ), 10, 4 );
		add_filter( 'delete_postmeta', array( $a, 'filter' ), 10, 1 );
		add_filter( 'deleted_post_meta', array( $a, 'filter' ), 10, 4 );
		add_filter( 'deleted_postmeta', array( $a, 'filter' ), 10, 1 );

		delete_bulk_metadata(
			'post',
			array(
				12345 => array(
					'foo'  => 'bar',
					'foo2' => 'bar2',
				),
			)
		);

		remove_filter( 'delete_post_meta', array( $a, 'filter' ) );
		remove_filter( 'delete_postmeta', array( $a, 'filter' ) );
		remove_filter( 'deleted_post_meta', array( $a, 'filter' ) );
		remove_filter( 'deleted_postmeta', array( $a, 'filter' ) );
		delete_metadata_by_mid( 'post', $foo1_mid );
		delete_metadata_by_mid( 'post', $foo2_mid );

		$this->assertEquals( 8, $a->get_call_count( 'filter' ) );
		$this->assertEquals(
			array(
				array(
					'action' => 'filter',
					'tag'    => 'delete_post_meta',
					'args'   => array(
						array( $foo1_mid ),
						12345,
						'foo',
						'bar',
					),
				),
				array(
					'action' => 'filter',
					'tag'    => 'delete_postmeta',
					'args'   => array( array( $foo1_mid ) ),
				),
				array(
					'action' => 'filter',
					'tag'    => 'deleted_post_meta',
					'args'   => array(
						array( $foo1_mid ),
						12345,
						'foo',
						'bar',
					),
				),
				array(
					'action' => 'filter',
					'tag'    => 'deleted_postmeta',
					'args'   => array( array( $foo1_mid ) ),
				),
				array(
					'action' => 'filter',
					'tag'    => 'delete_post_meta',
					'args'   => array(
						array( $foo2_mid ),
						12345,
						'foo2',
						'bar2',
					),
				),
				array(
					'action' => 'filter',
					'tag'    => 'delete_postmeta',
					'args'   => array( array( $foo2_mid ) ),
				),
				array(
					'action' => 'filter',
					'tag'    => 'deleted_post_meta',
					'args'   => array(
						array( $foo2_mid ),
						12345,
						'foo2',
						'bar2',
					),
				),
				array(
					'action' => 'filter',
					'tag'    => 'deleted_postmeta',
					'args'   => array( array( $foo2_mid ) ),
				),
			),
			$a->get_events()
		);
	}

	public function test_that_input_and_output_keys_match() {
		$foo1_mid = add_metadata( 'post', 12345, 'foo', 'bar' );
		$foo2_mid = add_metadata( 'post', 12345, '"/foo"', 'bar2' );
		$foo3_mid = add_metadata( 'post', 12345, '/foo', 'bar2' );

		$m = delete_bulk_metadata(
			'post',
			array(
				12345             => array(
					'foo'      => null,
					'\"/foo\"' => null,
					'\/foo'    => null,
				),
				'12345_truncated' => array(
					'foo' => null,
				),
			)
		);

		$this->assertArrayHasKey( 12345, $m );
		$this->assertArrayHasKey( 'foo', $m[12345] );
		$this->assertTrue( $m[12345]['foo'] );
		$this->assertEmpty( get_metadata_by_mid( 'post', $foo1_mid ) );
		$this->assertArrayHasKey( '\"/foo\"', $m[12345] );
		$this->assertTrue( $m[12345]['\"/foo\"'] );
		$this->assertEmpty( get_metadata_by_mid( 'post', $foo2_mid ) );
		$this->assertArrayHasKey( '\/foo', $m[12345] );
		$this->assertTrue( $m[12345]['\/foo'] );
		$this->assertEmpty( get_metadata_by_mid( 'post', $foo3_mid ) );
		$this->assertArrayHasKey( '12345_truncated', $m );
		$this->assertArrayHasKey( 'foo', $m['12345_truncated'] );
		$this->assertFalse( $m['12345_truncated']['foo'] );
	}

	public function test_that_invalid_object_ids_give_correct_errors() {
		$m = delete_bulk_metadata(
			'post',
			array(
				'test'     => array(
					'foo' => 'bar',
				),
				'123_test' => array(
					'foo' => 'bar',
				),
			)
		);

		$this->assertArrayHasKey( 'test', $m );
		$this->assertArrayHasKey( 'foo', $m['test'] );
		$this->assertFalse( $m['test']['foo'] );
		$this->assertArrayHasKey( '123_test', $m );
		$this->assertArrayHasKey( 'foo', $m['123_test'] );
		$this->assertFalse( $m['123_test']['foo'] );
	}
}
