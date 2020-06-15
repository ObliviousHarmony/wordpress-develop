<?php

/**
 * @group meta
 */
class Tests_Meta_BulkAddMetadata extends WP_UnitTestCase {
	public function test_should_add_bulk_metadata() {
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

		$m = add_bulk_metadata( 'post', $metadata );

		foreach ( $metadata as $object_id => $meta ) {
			$this->assertArrayHasKey( $object_id, $m );
			$this->assertCount( count( $meta ), $m[ $object_id ] );

			$obj_m = $m[ $object_id ];
			foreach ( $meta as $meta_key => $meta_value ) {
				$this->assertArrayHasKey( $meta_key, $obj_m );
				$this->assertIsNumeric( $obj_m[ $meta_key ] );

				$t = get_metadata_by_mid( 'post', $obj_m[ $meta_key ] );

				$this->assertNotEmpty( $t );
				$this->assertEquals( $object_id, $t->post_id );
				$this->assertEquals( $meta_key, $t->meta_key );
				$this->assertEquals( $meta_value, $t->meta_value );
			}
		}
	}

	public function test_should_short_circuit_bulk_metadata() {
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

		$a = new MockAction();
		$a->add_filter_response( 'filter', 'add_bulk_post_metadata', 'test' );
		add_filter( 'add_bulk_post_metadata', array( $a, 'filter' ), 10, 5 );

		$m = add_bulk_metadata( 'post', $metadata, array( 'foo' ) );

		remove_filter( 'add_bulk_post_metadata', array( $a, 'filter' ) );

		$this->assertEquals( 'test', $m );
		$this->assertEmpty( get_metadata( 'post', 12345 ) );
		$this->assertEmpty( get_metadata( 'post', 54321 ) );

		$this->assertEquals( 1, $a->get_call_count( 'filter' ) );
		$this->assertEquals(
			array(
				'action' => 'filter',
				'tag'    => 'add_bulk_post_metadata',
				'args'   => array(
					null,
					$metadata,
					array( 'foo' ),
				),
			),
			$a->get_events()[0]
		);
	}

	public function test_should_short_circuit_individual_metadata() {
		$a = new MockAction();
		$a->add_filter_response( 'filter', 'add_post_metadata', 'test' );
		add_filter( 'add_post_metadata', array( $a, 'filter' ), 10, 5 );

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

		$m = add_bulk_metadata( 'post', $metadata, array( 'foo' ) );

		remove_filter( 'add_post_metadata', array( $a, 'filter' ) );

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
		$this->assertEmpty( get_metadata( 'post', 12345 ) );
		$this->assertEmpty( get_metadata( 'post', 12345 ) );

		$this->assertEquals( 4, $a->get_call_count( 'filter' ) );
		$this->assertEquals(
			array(
				'action' => 'filter',
				'tag'    => 'add_post_metadata',
				'args'   => array(
					null,
					12345,
					'foo',
					'bar',
					true,
				),
			),
			$a->get_events()[0]
		);
		$this->assertEquals(
			array(
				'action' => 'filter',
				'tag'    => 'add_post_metadata',
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
				'tag'    => 'add_post_metadata',
				'args'   => array(
					null,
					54321,
					'foo',
					'bar3',
					true,
				),
			),
			$a->get_events()[2]
		);
		$this->assertEquals(
			array(
				'action' => 'filter',
				'tag'    => 'add_post_metadata',
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
		$a = new MockAction();
		add_filter( 'add_post_meta', array( $a, 'filter' ), 10, 4 );
		add_filter( 'added_post_meta', array( $a, 'filter' ), 10, 5 );

		add_bulk_metadata(
			'post',
			array(
				12345 => array(
					'foo'  => 'bar',
					'foo2' => 'bar2',
				),
			)
		);

		remove_filter( 'add_post_meta', array( $a, 'filter' ) );
		remove_filter( 'added_post_meta', array( $a, 'filter' ) );

		$this->assertEquals( 4, $a->get_call_count( 'filter' ) );
		$this->assertEquals(
			array(
				'action' => 'filter',
				'tag'    => 'add_post_meta',
				'args'   => array(
					12345,
					'foo',
					'bar',
				),
			),
			$a->get_events()[0]
		);
		$this->assertEquals(
			array(
				'action' => 'filter',
				'tag'    => 'add_post_meta',
				'args'   => array(
					12345,
					'foo2',
					'bar2',
				),
			),
			$a->get_events()[1]
		);

		$this->assertEquals( 'added_post_meta', $a->get_events()[2]['tag'] );
		$this->assertIsNumeric( $a->get_events()[2]['args'][0] );
		$this->assertEquals( 12345, $a->get_events()[2]['args'][1] );
		$this->assertEquals( 'foo', $a->get_events()[2]['args'][2] );
		$this->assertEquals( 'bar', $a->get_events()[2]['args'][3] );

		$this->assertEquals( 'added_post_meta', $a->get_events()[3]['tag'] );
		$this->assertIsNumeric( $a->get_events()[3]['args'][0] );
		$this->assertEquals( 12345, $a->get_events()[3]['args'][1] );
		$this->assertEquals( 'foo2', $a->get_events()[3]['args'][2] );
		$this->assertEquals( 'bar2', $a->get_events()[3]['args'][3] );
	}

	public function test_that_unique_meta_keys_work() {
		add_metadata( 'post', 54321, 'foo_unique', 'bar', true );
		add_metadata( 'post', 54321, 'foo2_unique', 'bar', true );

		$m = add_bulk_metadata(
			'post',
			array(
				12345 => array(
					'foo_unique'  => 'bar',
					'foo2_unique' => 'bar2',
				),
				54321 => array(
					'foo_unique'  => 'bar3',
					'foo2_unique' => 'bar4',
				),
			),
			array( 'foo_unique', 'foo2_unique' )
		);

		$this->assertArrayHasKey( 12345, $m );
		$this->assertIsNumeric( $m[12345]['foo_unique'] );
		$this->assertArrayHasKey( 54321, $m );
		$this->assertFalse( $m[54321]['foo_unique'] );
		$this->assertArrayHasKey( 12345, $m );
		$this->assertIsNumeric( $m[12345]['foo2_unique'] );
		$this->assertArrayHasKey( 54321, $m );
		$this->assertFalse( $m[54321]['foo2_unique'] );
	}

	public function test_that_input_and_output_keys_match() {
		$mid = add_metadata( 'post', 12345, '"/foo"', 'bar', true );

		$m = add_bulk_metadata(
			'post',
			array(
				12345             => array(
					'foo'      => 'bar',
					'\"/foo\"' => 'bar',
					'\/foo'    => 'bar',
				),
				'12345_truncated' => array(
					'foo' => 'bar',
				),
			),
			array( '\"/foo\"' )
		);

		delete_metadata( 'post', 12345, 'foo' );
		delete_metadata( 'post', 12345, '\"/foo\"' );
		delete_metadata( 'post', 12345, '\/foo' );

		$this->assertArrayHasKey( 12345, $m );
		$this->assertArrayHasKey( 'foo', $m[12345] );
		$this->assertIsNumeric( $m[12345]['foo'] );
		$this->assertArrayHasKey( '\"/foo\"', $m[12345] );
		$this->assertFalse( $m[12345]['\"/foo\"'] );
		$this->assertArrayHasKey( '\/foo', $m[12345] );
		$this->assertIsNumeric( $m[12345]['\/foo'] );
		$this->assertArrayHasKey( '12345_truncated', $m );
		$this->assertArrayHasKey( 'foo', $m['12345_truncated'] );
	}

	public function test_that_invalid_object_ids_give_correct_errors() {
		$m = add_bulk_metadata(
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
