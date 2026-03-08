<?php
/**
 * Multisite Models Integration Test
 *
 * @package WpMVC\Database\Tests\Integration\WordPress
 */

namespace WpMVC\Database\Tests\Integration\WordPress;

use WpMVC\Database\Tests\Framework\TestCase;
use WpMVC\Database\Tests\Integration\WordPress\Models\Site;
use WpMVC\Database\Tests\Integration\WordPress\Models\SiteMeta;
use WpMVC\Database\Tests\Integration\WordPress\Models\Blog;
use WpMVC\Database\Tests\Integration\WordPress\Models\BlogMeta;
use WpMVC\Database\Tests\Integration\WordPress\Models\Signup;
use WpMVC\Database\Tests\Integration\WordPress\Models\RegistrationLog;
use WpMVC\Database\Tests\Integration\WordPress\Models\BlogVersion;

/**
 * Class MultisiteModelTest
 */
class MultisiteModelTest extends TestCase {
    /**
     * Set up the test.
     */
    public function set_up(): void {
        parent::set_up();
        if ( ! is_multisite() ) {
            $this->markTestSkipped( 'Multisite is not enabled.' );
        }
    }

    /**
     * Test Site and SiteMeta.
     */
    public function test_it_can_handle_sites() {
        $site = Site::create(
            [
                'domain' => 'example.com',
                'path'   => '/',
            ]
        );

        $this->assertIsInt( $site->id );
        
        $site->meta()->create(
            [
                'meta_key'   => 'site_name',
                'meta_value' => 'Main Site',
            ]
        );

        $site->unset_relation( 'meta' );
        $this->assertCount( 1, $site->meta );
        $this->assertEquals( 'Main Site', $site->meta->first()->meta_value );
    }

    /**
     * Test Blog (Subsite) and relationship to Site.
     */
    public function test_it_can_handle_blogs() {
        $site = Site::create(
            [
                'domain' => 'network.com',
            ]
        );

        $blog = Blog::create(
            [
                'site_id' => $site->id,
                'domain'  => 'sub.network.com',
                'path'    => '/',
                'public'  => true,
            ]
        );

        $blog->unset_relation( 'site' );
        $this->assertIsInt( $blog->blog_id );
        $this->assertEquals( $site->id, $blog->site->id );
        $this->assertTrue( $blog->public );
    }

    /**
     * Test Signup model.
     */
    public function test_it_can_handle_signups() {
        $signup = Signup::create(
            [
                'domain'     => 'newsub.example.com',
                'user_login' => 'newuser',
                'user_email' => 'new@example.com',
                'meta'       => ['some' => 'data'],
            ]
        );

        $this->assertIsInt( $signup->signup_id );
        $this->assertIsArray( $signup->meta );
        $this->assertEquals( 'data', $signup->meta['some'] );
    }

    /**
     * Test RegistrationLog.
     */
    public function test_it_can_log_registrations() {
        $site = Site::create( [ 'domain' => 'logsite.com', 'path' => '/' ] );
        $blog = Blog::create(
            [
                'site_id' => $site->id,
                'domain'  => 'testlog.com',
                'path'    => '/',
            ]
        );
        
        $log = RegistrationLog::create(
            [
                'email'           => 'log@example.com',
                'IP'              => '127.0.0.1',
                'blog_id'         => $blog->blog_id,
                'date_registered' => '2026-02-27 15:00:00',
            ]
        );

        $this->assertIsInt( $log->ID );
        $this->assertEquals( $blog->blog_id, $log->blog_id );
    }

    /**
     * Test BlogVersion.
     */
    public function test_it_can_handle_blog_versions() {
        global $wpdb;
        $table = ( new Site )->resolver()->table( 'blog_versions' );
        if ( ! $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table ) ) ) {
            $this->markTestSkipped( 'blog_versions table does not exist.' );
        }

        $site = Site::create( [ 'domain' => 'versionsite.com', 'path' => '/' ] );
        $blog = Blog::create(
            [
                'site_id' => $site->id,
                'domain'  => 'version.com',
                'path'    => '/',
            ]
        );
        
        $version = BlogVersion::create(
            [
                'blog_id'      => $blog->blog_id,
                'db_version'   => '56789',
                'last_updated' => '2026-02-27 16:00:00',
            ]
        );

        // BlogVersion has no auto-increment ID, check blog_id
        $this->assertEquals( $blog->blog_id, $version->blog_id );
        $this->assertEquals( '56789', $version->db_version );
    }
}
