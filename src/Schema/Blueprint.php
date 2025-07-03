<?php

namespace WpMVC\Database\Schema;

defined( "ABSPATH" ) || exit;

class Blueprint {
    protected string $table_name;

    protected string $charset_collate;

    protected array $columns = [];

    protected array $indexes = [];

    /**
     * @var ForeignKey[]
     */
    protected array $foreign_keys = [];

    protected array $drops = [];

    public function __construct( string $table_name, string $charset_collate ) {
        $this->table_name      = $table_name;
        $this->charset_collate = $charset_collate;
    }

    protected function add_column( string $definition ): void {
        $this->columns[] = $definition;
    }

    public function big_increments( string $name ): void {
        $this->add_column( "`$name` BIGINT UNSIGNED AUTO_INCREMENT" );
        $this->primary( $name );
    }

    public function unsigned_big_integer( string $name ): self {
        $this->add_column( "`$name` BIGINT UNSIGNED NOT NULL" );
        return $this;
    }

    public function integer( string $name ): self {
        $this->add_column( "`$name` INT NOT NULL" );
        return $this;
    }

    public function unsigned_integer( string $name ): self {
        $this->add_column( "`$name` INT UNSIGNED NOT NULL" );
        return $this;
    }

    public function string( string $name, int $length = 255 ): self {
        $this->add_column( "`$name` VARCHAR($length) NOT NULL" );
        return $this;
    }

    public function text( string $name ): self {
        $this->add_column( "`$name` TEXT NOT NULL" );
        return $this;
    }

    public function long_text( string $name ): self {
        $this->add_column( "`$name` LONGTEXT" );
        return $this;
    }

    public function json( string $name ): self {
        $this->add_column( "`$name` JSON NOT NULL" );
        return $this;
    }

    public function enum( string $name, array $values ): self {
        $enum_values = implode( "','", $values );
        $this->add_column( "`$name` ENUM('$enum_values') NOT NULL" );
        return $this;
    }

    public function tiny_integer( string $name ): self {
        $this->add_column( "`$name` TINYINT NOT NULL" );
        return $this;
    }

    public function timestamp( string $name ): self {
        $this->add_column( "`$name` TIMESTAMP" );
        return $this;
    }

    public function timestamps(): void {
        $this->timestamp( 'created_at' )->use_current();
        $this->timestamp( 'updated_at' )->nullable()->use_current_on_update();
    }

    public function boolean( string $name ): self {
        $this->add_column( "`$name` TINYINT(1) NOT NULL" );
        return $this;
    }

    public function nullable(): self {
        $index                 = count( $this->columns ) - 1;
        $this->columns[$index] = str_replace( 'NOT NULL', 'NULL', $this->columns[$index] );
        return $this;
    }

    public function default( $value ): self {
        $index                  = count( $this->columns ) - 1;
        $value                  = is_numeric( $value ) ? $value : "'$value'";
        $this->columns[$index] .= " DEFAULT $value";
        return $this;
    }

    public function comment( string $text ): self {
        $index                  = count( $this->columns ) - 1;
        $this->columns[$index] .= " COMMENT '$text'";
        return $this;
    }

    public function use_current(): self {
        $index                  = count( $this->columns ) - 1;
        $this->columns[$index] .= " DEFAULT CURRENT_TIMESTAMP";
        return $this;
    }

    public function use_current_on_update(): self {
        $index                  = count( $this->columns ) - 1;
        $this->columns[$index] .= " ON UPDATE CURRENT_TIMESTAMP";
        return $this;
    }

    public function after( string $column ): self {
        $index                  = count( $this->columns ) - 1;
        $this->columns[$index] .= " AFTER `$column`";
        return $this;
    }

    public function drop_column( string $name ): void {
        $this->drops[] = "DROP COLUMN `$name`";
    }

    public function drop_index( string $name ): void {
        $this->drops[] = "DROP INDEX `$name`";
    }

    /**
     * @param string|array $columns
     */
    public function primary( $columns ): void {
        $cols            = $this->wrap_column_list( $columns );
        $this->indexes[] = "PRIMARY KEY ($cols)";
    }

    /**
     * @param string|array $columns
     */
    public function unique( $columns, string $name = '' ): void {
        $cols            = $this->wrap_column_list( $columns );
        $index_name      = $name ?: 'unique_' . md5( $cols );
        $this->indexes[] = "UNIQUE KEY `$index_name` ($cols)";
    }

    /**
     * @param string|array $columns
     */
    public function index( $columns, string $name = '' ): void {
        $cols            = $this->wrap_column_list( $columns );
        $index_name      = $name ?: 'index_' . md5( $cols );
        $this->indexes[] = "KEY `$index_name` ($cols)";
    }

    /**
     * @param string|array $columns
     */
    protected function wrap_column_list( $columns ): string {
        $cols = (array) $columns;
        return implode( ', ', array_map( fn( $col ) => "`$col`", $cols ) );
    }

    public function foreign( string $column ): ForeignKey {
        $fk                   = new ForeignKey( $column );
        $this->foreign_keys[] = $fk;
        return $fk;
    }

    public function get_foreign_keys(): array {
        return $this->foreign_keys;
    }

    public function to_sql(): string {
        $definitions = array_merge( $this->columns, $this->indexes );
        $body        = implode(
            ",
    ", $definitions
        );

        return <<<SQL
CREATE TABLE `{$this->table_name}` (
    $body
) {$this->charset_collate};
SQL;
    }

    public function to_alter_sql(): string {
        $definitions = [];

        foreach ( $this->columns as $column ) {
            $definitions[] = "ADD $column";
        }

        foreach ( $this->indexes as $index ) {
            $definitions[] = "ADD $index";
        }

        $definitions = array_merge( $definitions, $this->drops );
        $body        = implode(
            ",
    ", $definitions
        );

        return <<<SQL
ALTER TABLE `{$this->table_name}`
    $body;
SQL;
    }
}