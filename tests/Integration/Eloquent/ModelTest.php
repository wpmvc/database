<?php

namespace WpMVC\Database\Tests\Integration\Eloquent;

use WpMVC\Database\Tests\Framework\TestCase;
use WpMVC\Database\Tests\Framework\Models\TestUser;
use WpMVC\Database\Tests\Framework\Models\TestPost;
use WpMVC\Database\Tests\Framework\Models\TestAuditModel;
use WpMVC\Database\Eloquent\Model;
use WpMVC\Database\Schema\Schema;
use WpMVC\Database\Schema\Blueprint;

class ModelTest extends TestCase {
    public function setUp(): void {
        parent::setUp();
        
        Schema::drop_if_exists( 'test_users' );
        Schema::create(
            'test_users', function( Blueprint $table ) {
                $table->big_increments( 'id' );
                $table->string( 'name' );
                $table->string( 'email' );
                $table->boolean( 'is_active' )->default( true );
                $table->text( 'meta' )->nullable();
                $table->timestamps();
            } 
        );
    }

    public function tearDown(): void {
        Schema::drop_if_exists( 'test_users' );
        parent::tearDown();
    }

    public function test_it_can_create_and_retrieve_a_model() {
        $user = TestUser::create(
            [
                'name'  => 'John Doe',
                'email' => 'john@example.com',
            ] 
        );

        $this->assertNotNull( $user->id );
        $this->assertEquals( 'John Doe', $user->name );

        $found = TestUser::find( $user->id );
        $this->assertInstanceOf( TestUser::class, $found );
        $this->assertEquals( 'john@example.com', $found->email );
    }

    public function test_it_can_update_a_model() {
        $user = TestUser::create(
            [
                'name'  => 'Jane Doe',
                'email' => 'jane@example.com',
            ] 
        );

        $user->name = 'Jane Smith';
        $user->save();

        $found = TestUser::find( $user->id );
        $this->assertEquals( 'Jane Smith', $found->name );
    }

    public function test_it_can_delete_a_model() {
        $user = TestUser::create(
            [
                'name'  => 'Delete Me',
                'email' => 'delete@example.com',
            ] 
        );

        $id = $user->id;
        $user->delete();

        $found = TestUser::find( $id );
        $this->assertNull( $found );
    }

    public function test_it_can_cast_attributes() {
        $user = TestUser::create(
            [
                'name'      => 'John Doe',
                'email'     => 'john@example.com',
                'is_active' => '1', // string in DB
                'meta'      => ['theme' => 'dark'], // array to be json_encoded
            ] 
        );

        $this->assertIsBool( $user->is_active );
        $this->assertTrue( $user->is_active );
        $this->assertIsArray( $user->meta );
        $this->assertEquals( 'dark', $user->meta['theme'] );

        // Check DB raw value
        global $wpdb;
        $table = $wpdb->prefix . 'test_users';
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `$table` WHERE id = %d", $user->id ) );
        $this->assertEquals( '{"theme":"dark"}', $row->meta );
    }

    /** @test */
    public function test_fillable_only_allows_fillable_attributes() {
        Model::reguard();
        $model = new TestAuditModel();
        $model->fill(
            [
                'name'     => 'John',
                'is_admin' => true, // Guarded
            ] 
        );

        $this->assertEquals( 'JOHN', $model->name ); // Mutator applied
        $this->assertArrayNotHasKey( 'is_admin', $model->get_attributes() );
    }

    /** @test */
    public function test_guarded_blocks_guarded_attributes() {
        Model::reguard();
        $model = new TestAuditModel();
        $model->fill(
            [
                'name'           => 'John',
                'internal_notes' => 'Secret', // Guarded
            ] 
        );

        $this->assertArrayNotHasKey( 'internal_notes', $model->get_attributes() );
    }

    /** @test */
    public function test_unguard_allows_everything() {
        Model::unguard();
        $model = new \WpMVC\Database\Tests\Framework\Models\TestAuditModel();
        $model->fill(
            [
                'is_admin'       => true,
                'internal_notes' => 'Secret',
            ] 
        );

        $this->assertTrue( (bool) $model->is_admin );
        $this->assertEquals( 'Secret', $model->internal_notes );
        Model::reguard();
    }

    /** @test */
    public function test_comprehensive_casting() {
        $model = new TestAuditModel();
        $model->fill(
            [
                'age'            => '25',
                'score'          => '95.5',
                'salary'         => '5000.789',
                'is_active'      => '1',
                'secret_code'    => 12345,
                'meta'           => ['key' => 'val'],
                'settings'       => ['theme' => 'dark'],
                'raw_data'       => '{"a":1}',
                'joined_at'      => '2023-01-01',
                'last_login_at'  => '2023-01-01 12:00:00',
                'formatted_date' => '2023-01-01 15:00:00',
            ] 
        );

        $this->assertIsInt( $model->age );
        $this->assertEquals( 25, $model->age );
        
        $this->assertIsFloat( $model->score );
        $this->assertEquals( 95.5, $model->score );
        
        $this->assertEquals( '5000.79', $model->salary ); // decimal:2
        
        $this->assertIsBool( $model->is_active );
        $this->assertTrue( $model->is_active );
        
        $this->assertIsString( $model->secret_code );
        $this->assertEquals( '12345', $model->secret_code );
        
        $this->assertIsArray( $model->meta );
        $this->assertArrayHasKey( 'updated_via_mutator', $model->meta );
        $this->assertTrue( $model->meta['updated_via_mutator'] );
        
        $this->assertIsArray( $model->settings );
        $this->assertEquals( 'dark', $model->settings['theme'] );
        
        $this->assertInstanceOf( \stdClass::class, $model->raw_data );
        $this->assertEquals( 1, $model->raw_data->a );
        
        $this->assertInstanceOf( \DateTime::class, $model->joined_at );
        $this->assertEquals( '2023-01-01', $model->joined_at->format( 'Y-m-d' ) );
        
        $this->assertInstanceOf( \DateTime::class, $model->last_login_at );
        $this->assertEquals( '2023-01-01 12:00:00', $model->last_login_at->format( 'Y-m-d H:i:s' ) );
    }

    /** @test */
    public function test_mutators_and_accessors_precedence() {
        $model = new TestAuditModel();
        
        // Mutator (name -> uppercase)
        $model->name = 'jane';
        $this->assertEquals( 'JANE', $model->get_attributes()['name'] );
        
        // Accessor (email -> lowercase)
        $model->set_attribute( 'email', 'JANE@EXAMPLE.COM' );
        $this->assertEquals( 'jane@example.com', $model->email );
        
        // Appended Accessor
        $model->name  = 'Jane';
        $model->email = 'JANE@EXAMPLE.COM';
        $this->assertEquals( 'JANE (jane@example.com)', $model->full_profile );
    }

    /** @test */
    public function test_visibility_control() {
        $model = new TestAuditModel(
            [
                'name'           => 'Visible',
                'password'       => 'secret123',
                'internal_notes' => 'Private',
                'email'          => 'visible@example.com',
            ] 
        );

        $array = $model->to_array();

        $this->assertArrayHasKey( 'name', $array );
        $this->assertArrayHasKey( 'email', $array );
        $this->assertArrayNotHasKey( 'password', $array );
        $this->assertArrayNotHasKey( 'internal_notes', $array );

        // Manual Visibility
        $model->make_visible( ['password'] );
        $array = $model->to_array();
        $this->assertArrayHasKey( 'password', $array );

        // Dynamic Hidden
        $model->make_hidden( ['email'] );
        $array = $model->to_array();
        $this->assertArrayNotHasKey( 'email', $array );
    }

    /** @test */
    public function test_is_dirty_detects_date_changes() {
        $model = new TestAuditModel();
        $date  = new \DateTime( '2023-01-01 10:00:00' );
        
        $model->last_login_at = $date;
        $model->sync_original();
        
        $this->assertFalse( $model->is_dirty( 'last_login_at' ) );
        
        // Same timestamp but different object
        $model->last_login_at = new \DateTime( '2023-01-01 10:00:00' );
        $this->assertFalse( $model->is_dirty( 'last_login_at' ), 'Should be clean if timestamp matches' );
        
        // Different timestamp
        $model->last_login_at = new \DateTime( '2023-01-01 10:00:01' );
        $this->assertTrue( $model->is_dirty( 'last_login_at' ), 'Should be dirty if timestamp changed' );
    }

    /** @test */
    public function test_serialization_respects_casting_and_appends() {
        $model = new TestAuditModel(
            [
                'age'    => '30',
                'name'   => 'Serialized',
                'email'  => 'ser@example.com',
                'salary' => '1000.5',
            ] 
        );

        $json = json_encode( $model );
        $data = json_decode( $json, true );

        $this->assertIsInt( $data['age'] );
        $this->assertEquals( 30, $data['age'] );
        $this->assertEquals( '1000.50', $data['salary'] );
        $this->assertArrayHasKey( 'full_profile', $data );
        $this->assertEquals( 'SERIALIZED (ser@example.com)', $data['full_profile'] );
    }

    public function test_it_can_track_dirty_attributes() {
        $user = TestUser::create( ['name' => 'Original', 'email' => 'orig@example.com'] );
        
        $this->assertFalse( $user->is_dirty() );

        $user->name = 'Changed';
        $this->assertTrue( $user->is_dirty() );
        $this->assertTrue( $user->is_dirty( 'name' ) );
        $this->assertFalse( $user->is_dirty( 'email' ) );

        $dirty = $user->get_dirty();
        $this->assertArrayHasKey( 'name', $dirty );
        $this->assertEquals( 'Changed', $dirty['name'] );
    }

    public function test_it_can_serialize_to_json() {
        $user = new TestUser(
            [
                'name'  => 'JSON User',
                'email' => 'json@example.com',
                'meta'  => ['key' => 'value'],
            ] 
        );

        $json  = json_encode( $user );
        $array = json_decode( $json, true );

        $this->assertEquals( 'JSON User', $array['name'] );
        $this->assertEquals( 'value', $array['meta']['key'] );
    }

    public function test_it_can_get_all_models() {
        TestUser::create( ['name' => 'User 1', 'email' => 'u1@example.com'] );
        TestUser::create( ['name' => 'User 2', 'email' => 'u2@example.com'] );

        $users = TestUser::all();
        $this->assertCount( 2, $users );
    }

    public function test_it_can_find_or_fail() {
        $user = TestUser::create( ['name' => 'Found', 'email' => 'found@example.com'] );
        
        $found = TestUser::find_or_fail( $user->id );
        $this->assertEquals( 'Found', $found->name );

        $this->expectException( \Exception::class );
        TestUser::find_or_fail( 999 );
    }

    public function test_it_can_get_first_model() {
        TestUser::create( ['name' => 'First', 'email' => 'first@example.com'] );
        TestUser::create( ['name' => 'Second', 'email' => 'second@example.com'] );

        $first = TestUser::query()->order_by( 'id', 'asc' )->first();
        $this->assertEquals( 'First', $first->name );
    }

    public function test_it_can_get_first_or_fail() {
        $user = TestUser::create( ['name' => 'First', 'email' => 'first@example.com'] );
        
        $first = TestUser::query()->where( 'id', '=', $user->id )->first_or_fail();
        $this->assertEquals( 'First', $first->name );

        $this->expectException( \Exception::class );
        TestUser::query()->where( 'id', '=', 999 )->first_or_fail();
    }

    public function test_it_can_mass_fill_attributes(): void {
        $user = new TestUser();
        $user->fill( ['name' => 'Filled', 'email' => 'filled@example.com'] );

        $this->assertEquals( 'Filled', $user->name );
        $this->assertEquals( 'filled@example.com', $user->email );
    }

    public function test_fill_ignores_non_fillable_attributes(): void {
        // TestUser fillable = ['id', 'name', 'email'] — 'is_active' is NOT in fillable
        TestUser::reguard();
        $user = new TestUser();
        $user->fill( ['name' => 'Protected', 'is_active' => true] );

        $this->assertEquals( 'Protected', $user->name );
        // is_active should not be in dirty attributes because it was not filled
        $dirty = $user->get_dirty();
        $this->assertArrayNotHasKey( 'is_active', $dirty );
        TestUser::unguard();
    }

    public function test_timestamps_are_set_on_create(): void {
        $user = TestUser::create( ['name' => 'Timestamped', 'email' => 'ts@example.com'] );

        $this->assertNotNull( $user->created_at );
        $this->assertNotNull( $user->updated_at );
        // Both should be non-empty strings (stored as current datetime)
        $this->assertNotEmpty( $user->created_at );
        $this->assertNotEmpty( $user->updated_at );
    }

    public function test_updated_at_changes_on_save(): void {
        $user   = TestUser::create( ['name' => 'UpdatedAt', 'email' => 'ua@example.com'] );
        $before = $user->updated_at;

        // Simulate time passing — sleep 1 second to guarantee a new timestamp
        sleep( 1 );

        $user->name = 'UpdatedAt Changed';
        $user->save();

        $found = TestUser::find( $user->id );
        $this->assertNotEquals( $before, $found->updated_at );
    }

    public function test_first_or_create_returns_existing_row(): void {
        TestUser::create( ['name' => 'Existing', 'email' => 'exist@example.com'] );

        $found = TestUser::query()->where( 'email', 'exist@example.com' )->first();
        $this->assertNotNull( $found );
        $this->assertEquals( 'Existing', $found->name );
    }

    public function test_first_or_create_creates_when_missing(): void {
        $user = TestUser::query()->where( 'email', 'new@example.com' )->first();
        if ( is_null( $user ) ) {
            $user = TestUser::create( ['name' => 'New', 'email' => 'new@example.com'] );
        }

        $this->assertNotNull( $user->id );
        $this->assertEquals( 'New', $user->name );
        // Second call returns same row
        $found = TestUser::query()->where( 'email', 'new@example.com' )->first();
        $this->assertEquals( $user->id, $found->id );
    }
}
