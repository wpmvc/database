<?php

namespace WpMVC\Database\Tests\Integration\Eloquent;

use WpMVC\Database\Tests\Framework\TestCase;
use WpMVC\Database\Tests\Framework\Models\TestUser;
use WpMVC\Database\Schema\Schema;
use WpMVC\Database\Schema\Blueprint;

class ModelEventTest extends TestCase {
    protected static $event_log = [];

    public function setUp(): void {
        parent::setUp();
        self::$event_log = [];
        TestUser::flush_observers();
        
        Schema::drop_if_exists( 'test_users' );
        Schema::create(
            'test_users', function( Blueprint $table ) {
                $table->big_increments( 'id' );
                $table->string( 'name' );
                $table->timestamps();
            }
        );
    }

    public function test_it_fires_model_events_during_creation() {
        $observer = new class {
            public function saving( $model ) {
                ModelEventTest::log( 'saving' ); }

            public function creating( $model ) {
                ModelEventTest::log( 'creating' ); }

            public function created( $model ) {
                ModelEventTest::log( 'created' ); }

            public function saved( $model ) {
                ModelEventTest::log( 'saved' ); }
        };

        TestUser::observe( $observer );
        
        TestUser::create( ['name' => 'Event User'] );

        $this->assertEquals( ['saving', 'creating', 'created', 'saved'], self::$event_log );
    }

    public function test_it_can_halt_operation_via_observer() {
        $observer = new class {
            public function saving( $model ) {
                return false; // Halt
            }
        };

        TestUser::observe( $observer );
        
        $user   = new TestUser( ['name' => 'Should Not Exist'] );
        $result = $user->save();

        $this->assertFalse( $result );
        $this->assertFalse( $user->exists );
        $this->assertEmpty( TestUser::all() );
    }

    public function test_it_fires_wordpress_hooks() {
        $hook_called = false;
        // Try both general and class-specific hooks
        add_filter(
            'wpmvc_model_saving', function( $should_save, $model ) use ( &$hook_called ) {
                $hook_called = true;
                return $should_save;
            }, 10, 2
        );

        TestUser::create( ['name' => 'Hook User'] );

        $this->assertTrue( $hook_called, "The 'wpmvc_model_saving' hook should have been called." );
    }

    public function test_it_can_halt_via_wordpress_hooks() {
        add_filter(
            'wpmvc_model_creating_test_users', function() {
                return false;
            }
        );

        $user   = new TestUser( ['name' => 'Halted User'] );
        $result = $user->save();

        $this->assertFalse( $result );
        $this->assertEmpty( TestUser::all() );
        
        // Cleanup global filter to not affect other tests if they run in same process
        remove_all_filters( 'wpmvc_model_creating_test_users' );
    }

    public static function log( $event ) {
        self::$event_log[] = $event;
    }

    public function test_it_fires_updating_and_updated_events(): void {
        $observer = new class {
            public function updating( $model ) {
                ModelEventTest::log( 'updating' );
            }

            public function updated( $model ) {
                ModelEventTest::log( 'updated' );
            }
        };

        TestUser::observe( $observer );
        $user = TestUser::create( ['name' => 'UpdateEvents'] );

        self::$event_log = []; // reset log to only capture update events
        $user->name      = 'UpdateEvents Changed';
        $user->save();

        $this->assertContains( 'updating', self::$event_log );
        $this->assertContains( 'updated', self::$event_log );
    }

    public function test_it_fires_deleting_and_deleted_events(): void {
        $observer = new class {
            public function deleting( $model ) {
                ModelEventTest::log( 'deleting' );
            }

            public function deleted( $model ) {
                ModelEventTest::log( 'deleted' );
            }
        };

        TestUser::observe( $observer );
        $user = TestUser::create( ['name' => 'DeleteEvents'] );

        self::$event_log = []; // reset log to only capture delete events
        $user->delete();

        $this->assertContains( 'deleting', self::$event_log );
        $this->assertContains( 'deleted', self::$event_log );
    }

    public function test_observer_can_halt_deletion(): void {
        $observer = new class {
            public function deleting( $model ) {
                return false; // prevent deletion
            }
        };

        TestUser::observe( $observer );
        $user = TestUser::create( ['name' => 'Should Stay'] );

        $result = $user->delete();

        $this->assertFalse( $result );
        $found = TestUser::find( $user->id );
        $this->assertNotNull( $found );
        $this->assertEquals( 'Should Stay', $found->name );
    }
}
