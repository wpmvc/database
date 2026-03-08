<?php
/**
 * UniqueProxy class for WpMVC FakeData.
 *
 * @package WpMVC\Database
 * @author  WpMVC
 * @license MIT
 */

namespace WpMVC\Database\Eloquent;

defined( 'ABSPATH' ) || exit;

use RuntimeException;

/**
 * Class UniqueProxy
 *
 * Proxies calls to FakeData while ensuring unique results.
 */
class UniqueProxy {
    /**
     * The FakeData instance.
     *
     * @var FakeData
     */
    protected $faker;

    /**
     * The generated values.
     *
     * @var array
     */
    protected $values = [];

    /**
     * Maximum number of attempts to find a unique value.
     *
     * @var int
     */
    protected $max_attempts = 10000;

    /**
     * Create a new UniqueProxy instance.
     *
     * @param  FakeData  $faker
     */
    public function __construct( FakeData $faker ) {
        $this->faker = $faker;
    }

    /**
     * Reset the generated values.
     *
     * @return void
     */
    public function reset() {
        $this->values = [];
    }

    /**
     * Proxy magic method calls to FakeData.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     *
     * @throws RuntimeException
     */
    public function __call( $method, $parameters ) {
        for ( $i = 0; $i < $this->max_attempts; $i++ ) {
            $value = $this->faker->{$method}( ...$parameters );

            if ( ! isset( $this->values[$method] ) || ! in_array( $value, $this->values[$method], true ) ) {
                $this->values[$method][] = $value;
                return $value;
            }
        }

        throw new RuntimeException( "Maximum attempts ({$this->max_attempts}) reached to generate a unique value for [{$method}]." );
    }
}
