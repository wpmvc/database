<?php

namespace WpMVC\Database\Schema;

defined( "ABSPATH" ) || exit;

class ForeignKey {
    protected string $column;

    protected string $reference_column = 'id';

    protected string $reference_table = '';

    protected ?string $on_delete = null;

    protected ?string $on_update = null;

    public function __construct( string $column ) {
        $this->column = $column;
    }

    public function references( string $reference_column ): self {
        $this->reference_column = $reference_column;
        return $this;
    }

    public function on( string $reference_table ): self {
        $this->reference_table = $reference_table;
        return $this;
    }

    public function on_delete( string $action ): self {
        $this->on_delete = strtoupper( $action );
        return $this;
    }

    public function on_update( string $action ): self {
        $this->on_update = strtoupper( $action );
        return $this;
    }

    public function get_column(): string {
        return $this->column;
    }

    public function get_reference_table(): string {
        return $this->reference_table;
    }

    public function get_reference_column(): string {
        return $this->reference_column;
    }

    public function get_on_delete(): ?string {
        return $this->on_delete;
    }

    public function get_on_update(): ?string {
        return $this->on_update;
    }
}