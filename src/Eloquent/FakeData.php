<?php
/**
 * Robust FakeData generator for WpMVC.
 *
 * @package WpMVC\Database
 * @author  WpMVC
 * @license MIT
 */

namespace WpMVC\Database\Eloquent;

defined( 'ABSPATH' ) || exit;

/**
 * Class FakeData
 *
 * Provides a wide range of fake data for model factories.
 */
class FakeData {
    /**
     * The singleton instance.
     *
     * @var static|null
     */
    protected static $instance;

    /**
     * The unique proxy instance.
     *
     * @var UniqueProxy|null
     */
    protected $unique;

    /**
     * Get the singleton instance.
     *
     * @return static
     */
    public static function instance() {
        if ( ! static::$instance ) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    /**
     * Get a unique proxy instance.
     *
     * @return UniqueProxy
     */
    public function unique() {
        if ( ! $this->unique ) {
            $this->unique = new UniqueProxy( $this );
        }

        return $this->unique;
    }

    /**
     * Generate a full name.
     */
    public function name(): string {
        return $this->first_name() . ' ' . $this->last_name();
    }

    /**
     * Generate a first name.
     */
    public function first_name(): string {
        return $this->random_element(
            [
                'James', 'John', 'Robert', 'Michael', 'William', 'David', 'Richard', 'Joseph', 'Thomas', 'Charles',
                'Mary', 'Patricia', 'Jennifer', 'Linda', 'Elizabeth', 'Barbara', 'Susan', 'Jessica', 'Sarah', 'Karen',
                'Md', 'Al', 'Amin', 'Karim', 'Rahim', 'Fatima', 'Ayesha', 'Zainab', 'Omar', 'Hassan'
            ] 
        );
    }

    /**
     * Generate a last name.
     */
    public function last_name(): string {
        return $this->random_element(
            [
                'Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Garcia', 'Miller', 'Davis', 'Rodriguez', 'Martinez',
                'Khan', 'Ahmed', 'Ali', 'Hossain', 'Islam', 'Uddin', 'Chowdhury', 'Begum', 'Akter', 'Rahman'
            ] 
        );
    }

    /**
     * Generate a title.
     */
    public function title(): string {
        return $this->random_element( ['Mr.', 'Mrs.', 'Ms.', 'Dr.', 'Prof.'] );
    }

    /**
     * Generate a word.
     */
    public function word(): string {
        return $this->random_element(
            [
                'lorem', 'ipsum', 'dolor', 'sit', 'amet', 'consectetur', 'adipiscing', 'elit', 'sed', 'do',
                'eiusmod', 'tempor', 'incididunt', 'ut', 'labore', 'et', 'dolore', 'magna', 'aliqua'
            ] 
        );
    }

    /**
     * Generate a sentence.
     */
    public function sentence( int $nb_words = 6 ): string {
        $words = [];
        for ( $i = 0; $i < $nb_words; $i++ ) {
            $words[] = $this->word();
        }

        return ucfirst( implode( ' ', $words ) ) . '.';
    }

    /**
     * Generate a paragraph.
     */
    public function paragraph( int $nb_sentences = 3 ): string {
        $sentences = [];
        for ( $i = 0; $i < $nb_sentences; $i++ ) {
            $sentences[] = $this->sentence( mt_rand( 4, 10 ) );
        }

        return implode( ' ', $sentences );
    }

    /**
     * Generate text.
     */
    public function text( int $max_nb_chars = 200 ): string {
        $text = $this->paragraph( 5 );
        if ( strlen( $text ) > $max_nb_chars ) {
            $text = substr( $text, 0, $max_nb_chars );
        }
        return $text;
    }

    /**
     * Generate a slug.
     */
    public function slug( int $nb_words = 3 ): string {
        $words = [];
        for ( $i = 0; $i < $nb_words; $i++ ) {
            $words[] = $this->word();
        }
        return implode( '-', $words );
    }

    /**
     * Generate an email.
     */
    public function email(): string {
        return $this->user_name() . '@' . $this->domain_name();
    }

    /**
     * Generate a safe email.
     */
    public function safe_email(): string {
        return $this->user_name() . '@example.' . $this->random_element( ['com', 'net', 'org'] );
    }

    /**
     * Generate a username.
     */
    public function user_name(): string {
        return strtolower( $this->first_name() . mt_rand( 1, 99 ) );
    }

    /**
     * Generate a domain name.
     */
    public function domain_name(): string {
        return $this->word() . '.' . $this->random_element( ['com', 'net', 'org', 'io', 'dev'] );
    }

    /**
     * Generate a URL.
     */
    public function url(): string {
        return 'https://' . $this->domain_name() . '/' . $this->slug();
    }

    /**
     * Generate an IPv4 address.
     */
    public function ipv4(): string {
        return mt_rand( 0, 255 ) . '.' . mt_rand( 0, 255 ) . '.' . mt_rand( 0, 255 ) . '.' . mt_rand( 0, 255 );
    }

    /**
     * Generate an IPv6 address.
     */
    public function ipv6(): string {
        $parts = [];
        for ( $i = 0; $i < 8; $i++ ) {
            $parts[] = dechex( mt_rand( 0, 65535 ) );
        }
        return implode( ':', $parts );
    }

    /**
     * Generate a date (Y-m-d).
     */
    public function date( string $format = 'Y-m-d' ): string {
        return date( $format, mt_rand( 0, time() ) );
    }

    /**
     * Generate a time (H:i:s).
     */
    public function time( string $format = 'H:i:s' ): string {
        return date( $format, mt_rand( 0, time() ) );
    }

    /**
     * Generate a datetime (Y-m-d H:i:s).
     */
    public function date_time( string $format = 'Y-m-d H:i:s' ): string {
        return date( $format, mt_rand( 0, time() ) );
    }

    /**
     * Generate an ISO8601 date.
     */
    public function iso8601(): string {
        return date( 'c', mt_rand( 0, time() ) );
    }

    /**
     * Generate a timestamp.
     */
    public function timestamp(): int {
        return mt_rand( 0, time() );
    }

    /**
     * Generate an address.
     */
    public function address(): string {
        return $this->number_between( 10, 9999 ) . ' ' . $this->street_name() . ', ' . $this->city() . ', ' . $this->country();
    }

    /**
     * Generate a city.
     */
    public function city(): string {
        return $this->random_element( ['New York', 'London', 'Paris', 'Tokyo', 'Dhaka', 'Berlin', 'Madrid', 'Rome', 'Sydney', 'Toronto'] );
    }

    /**
     * Generate a street name.
     */
    public function street_name(): string {
        return $this->word() . ' ' . $this->random_element( ['St', 'Ave', 'Rd', 'Blvd', 'Lane'] );
    }

    /**
     * Generate a postcode.
     */
    public function postcode(): string {
        return (string) mt_rand( 10000, 99999 );
    }

    /**
     * Generate a country.
     */
    public function country(): string {
        return $this->random_element( ['USA', 'UK', 'France', 'Japan', 'Bangladesh', 'Germany', 'Spain', 'Italy', 'Australia', 'Canada'] );
    }

    /**
     * Generate latitude.
     */
    public function latitude(): float {
        return ( mt_rand( -90000000, 90000000 ) / 1000000 );
    }

    /**
     * Generate longitude.
     */
    public function longitude(): float {
        return ( mt_rand( -180000000, 180000000 ) / 1000000 );
    }

    /**
     * Generate a number between min and max.
     */
    public function number_between( int $min = 0, int $max = PHP_INT_MAX ): int {
        return mt_rand( $min, $max );
    }

    /**
     * Generate a random digit.
     */
    public function random_digit(): int {
        return mt_rand( 0, 9 );
    }

    /**
     * Generate a random float.
     */
    public function random_float( int $nb_decimals = 2, float $min = 0, float $max = 100 ): float {
        $factor = pow( 10, $nb_decimals );
        return mt_rand( $min * $factor, $max * $factor ) / $factor;
    }

    /**
     * Generate a UUID.
     */
    public function uuid(): string {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),
            mt_rand( 0, 0xffff ),
            mt_rand( 0, 0x0fff ) | 0x4000,
            mt_rand( 0, 0x3fff ) | 0x8000,
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
        );
    }

    /**
     * Generate a boolean.
     */
    public function boolean( int $chance_of_getting_true = 50 ): bool {
        return mt_rand( 1, 100 ) <= $chance_of_getting_true;
    }

    /**
     * Get a random element from an array.
     */
    public function random_element( array $array ) {
        if ( empty( $array ) ) {
            return null;
        }
        return $array[array_rand( $array )];
    }
}
