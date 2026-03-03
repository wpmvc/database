<?php

namespace WpMVC\Database\Tests\Framework\Models;

use WpMVC\Database\Eloquent\Model;
use WpMVC\Database\Resolver;

class TestAuditModel extends Model {
    protected array $fillable = ['name', 'email', 'bio'];

    protected array $guarded = ['is_admin', 'internal_notes'];

    protected array $hidden = ['password', 'internal_notes'];

    protected array $appends = ['full_profile'];

    protected array $casts = [
        'is_active'      => 'boolean',
        'age'            => 'integer',
        'score'          => 'float',
        'salary'         => 'decimal:2',
        'meta'           => 'json',
        'settings'       => 'array',
        'raw_data'       => 'object',
        'joined_at'      => 'date',
        'last_login_at'  => 'datetime',
        'formatted_date' => 'datetime:Y-m-d',
        'secret_code'    => 'string',
    ];

    public static function get_table_name(): string {
        return 'test_audit_models';
    }

    public function resolver(): Resolver {
        return new Resolver();
    }

    // Mutator
    public function set_name_attribute( $value ) {
        $this->attributes['name'] = strtoupper( $value );
    }

    // Accessor
    public function get_email_attribute( $value ) {
        return strtolower( $value );
    }

    // Accessor for appended attribute
    public function get_full_profile_attribute() {
        return $this->name . ' (' . $this->email . ')';
    }

    // Mutator for casted attribute
    public function set_meta_attribute( $value ) {
        if ( is_array( $value ) ) {
            $value['updated_via_mutator'] = true;
        }
        $this->attributes['meta'] = json_encode( $value );
    }
}
