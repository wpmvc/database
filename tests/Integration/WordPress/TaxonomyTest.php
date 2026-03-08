<?php
/**
 * Taxonomy Integration Test
 *
 * @package WpMVC\Database\Tests\Integration\WordPress
 */

namespace WpMVC\Database\Tests\Integration\WordPress;

use WpMVC\Database\Tests\Framework\TestCase;
use WpMVC\Database\Tests\Integration\WordPress\Models\Term;
use WpMVC\Database\Tests\Integration\WordPress\Models\TermTaxonomy;
use WpMVC\Database\Tests\Integration\WordPress\Models\TermMeta;
use WpMVC\Database\Tests\Integration\WordPress\Models\Post;

/**
 * Class TaxonomyTest
 */
class TaxonomyTest extends TestCase {
    /**
     * Test term creation.
     */
    public function test_it_can_create_a_term() {
        $term = Term::create(
            [
                'name' => 'News',
                'slug' => 'news',
            ]
        );

        $this->assertIsInt( $term->term_id );
        $this->assertEquals( 'News', $term->name );
    }

    /**
     * Test term taxonomy relationship.
     */
    public function test_it_links_term_to_taxonomy() {
        $term = Term::create(
            [
                'name' => 'Category A',
                'slug' => 'cat-a',
            ]
        );

        $taxonomy = TermTaxonomy::create(
            [
                'term_id'     => $term->term_id,
                'taxonomy'    => 'category',
                'description' => 'Test Category',
                'count'       => 5,
            ]
        );

        $this->assertEquals( 'category', $term->taxonomy->taxonomy );
        $this->assertEquals( $term->term_id, $taxonomy->term->term_id );
    }

    /**
     * Test term meta.
     */
    public function test_it_can_handle_termmeta() {
        $term = Term::create(
            [
                'name' => 'Meta Term',
            ]
        );

        $term->meta()->create(
            [
                'meta_key'   => 'color',
                'meta_value' => 'blue',
            ]
        );

        $this->assertCount( 1, $term->meta );
        $this->assertEquals( 'blue', $term->meta->first()->meta_value );
    }

    /**
     * Test post relationships via taxonomy.
     */
    public function test_it_links_posts_to_terms() {
        $post = Post::create(
            [
                'post_title' => 'Post with Term',
            ]
        );

        $term = Term::create(
            [
                'name' => 'Tagged',
                'slug' => 'tagged',
            ]
        );

        $taxonomy = TermTaxonomy::create(
            [
                'term_id'  => $term->term_id,
                'taxonomy' => 'post_tag',
            ]
        );

        // Manually link via relationships table (representing TermRelationship)
        ( new Post )->new_query()->from( 'term_relationships' )->insert(
            [
                'object_id'        => $post->ID,
                'term_taxonomy_id' => $taxonomy->term_taxonomy_id,
            ]
        );

        $this->assertCount( 1, $post->terms );
        $this->assertEquals( 'post_tag', $post->terms->first()->taxonomy );
    }
}
