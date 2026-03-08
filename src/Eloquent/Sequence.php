<?php
/**
 * Sequence helper for WpMVC factories.
 *
 * @package WpMVC\Database
 * @author  WpMVC
 * @license MIT
 */

namespace WpMVC\Database\Eloquent;

defined( 'ABSPATH' ) || exit;

use Countable;

/**
 * Class Sequence
 *
 * Represents a sequence of values to be used in factor batch creation.
 */
class Sequence implements Countable {
    /**
     * The sequence of values.
     *
     * @var array
     */
    protected array $sequence;

    /**
     * The current index of the sequence.
     *
     * @var int
     */
    protected int $index = 0;

    /**
     * Create a new sequence instance.
     *
     * @param  mixed  ...$sequence
     */
    public function __construct( ...$sequence ) {
        $this->sequence = ( count( $sequence ) === 1 && is_array( $sequence[0] ) ) 
            ? $sequence[0] 
            : $sequence;
    }

    /**
     * Get the next value in the sequence.
     *
     * @param  int  $count
     * @return mixed
     */
    public function next( int $count ) {
        $value = $this->sequence[$this->index % count( $this->sequence )];

        $this->index++;

        if ( is_callable( $value ) ) {
            return $value( $count );
        }

        return $value;
    }

    /**
     * Get the number of items in the sequence.
     *
     * @return int
     */
    public function count(): int {
        return count( $this->sequence );
    }
}
