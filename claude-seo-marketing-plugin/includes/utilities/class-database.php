<?php
/**
 * Database utility helper.
 *
 * @package    Claude_SEO
 * @subpackage Claude_SEO/includes/utilities
 */

/**
 * Provides database helper functions.
 */
class Claude_SEO_Database {

    /**
     * Get table name with prefix.
     *
     * @param string $table Table name without prefix.
     * @return string Full table name with prefix.
     */
    public static function get_table_name($table) {
        global $wpdb;
        return $wpdb->prefix . 'claude_seo_' . $table;
    }

    /**
     * Insert or update a record.
     *
     * @param string $table  Table name.
     * @param array  $data   Data to insert/update.
     * @param array  $where  Where clause for update.
     * @param array  $format Data format.
     * @return int|false Number of rows affected or false on error.
     */
    public static function upsert($table, $data, $where = array(), $format = null) {
        global $wpdb;

        $table_name = self::get_table_name($table);

        if (empty($where)) {
            return $wpdb->insert($table_name, $data, $format);
        }

        return $wpdb->update($table_name, $data, $where, $format);
    }

    /**
     * Insert a record.
     *
     * @param string $table  Table name.
     * @param array  $data   Data to insert.
     * @param array  $format Data format.
     * @return int|false Insert ID or false on error.
     */
    public static function insert($table, $data, $format = null) {
        global $wpdb;

        $result = $wpdb->insert(self::get_table_name($table), $data, $format);

        if ($result === false) {
            return false;
        }

        return $wpdb->insert_id;
    }

    /**
     * Update records.
     *
     * @param string $table  Table name.
     * @param array  $data   Data to update.
     * @param array  $where  Where clause.
     * @param array  $format Data format.
     * @return int|false Number of rows updated or false on error.
     */
    public static function update($table, $data, $where, $format = null) {
        global $wpdb;

        return $wpdb->update(self::get_table_name($table), $data, $where, $format);
    }

    /**
     * Delete records.
     *
     * @param string $table  Table name.
     * @param array  $where  Where clause.
     * @param array  $format Where format.
     * @return int|false Number of rows deleted or false on error.
     */
    public static function delete($table, $where, $format = null) {
        global $wpdb;

        return $wpdb->delete(self::get_table_name($table), $where, $format);
    }

    /**
     * Get a single row.
     *
     * @param string $table Table name.
     * @param array  $where Where clause.
     * @return object|null Row object or null if not found.
     */
    public static function get_row($table, $where) {
        global $wpdb;

        $table_name = self::get_table_name($table);
        $where_clause = self::build_where_clause($where);

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$table_name} WHERE {$where_clause['sql']}",
                ...$where_clause['values']
            )
        );
    }

    /**
     * Get multiple rows.
     *
     * @param string $table   Table name.
     * @param array  $where   Where clause.
     * @param string $order_by Order by clause.
     * @param int    $limit   Limit.
     * @param int    $offset  Offset.
     * @return array Array of row objects.
     */
    public static function get_results($table, $where = array(), $order_by = '', $limit = 0, $offset = 0) {
        global $wpdb;

        $table_name = self::get_table_name($table);
        $sql = "SELECT * FROM {$table_name}";
        $values = array();

        if (!empty($where)) {
            $where_clause = self::build_where_clause($where);
            $sql .= " WHERE {$where_clause['sql']}";
            $values = $where_clause['values'];
        }

        if (!empty($order_by)) {
            $sql .= " ORDER BY " . esc_sql($order_by);
        }

        if ($limit > 0) {
            $sql .= " LIMIT %d";
            $values[] = $limit;

            if ($offset > 0) {
                $sql .= " OFFSET %d";
                $values[] = $offset;
            }
        }

        if (!empty($values)) {
            return $wpdb->get_results($wpdb->prepare($sql, ...$values));
        }

        return $wpdb->get_results($sql);
    }

    /**
     * Get count of records.
     *
     * @param string $table Table name.
     * @param array  $where Where clause.
     * @return int Count of records.
     */
    public static function count($table, $where = array()) {
        global $wpdb;

        $table_name = self::get_table_name($table);
        $sql = "SELECT COUNT(*) FROM {$table_name}";
        $values = array();

        if (!empty($where)) {
            $where_clause = self::build_where_clause($where);
            $sql .= " WHERE {$where_clause['sql']}";
            $values = $where_clause['values'];
        }

        if (!empty($values)) {
            return (int) $wpdb->get_var($wpdb->prepare($sql, ...$values));
        }

        return (int) $wpdb->get_var($sql);
    }

    /**
     * Build WHERE clause from array.
     *
     * @param array $where Where conditions.
     * @return array SQL and values.
     */
    private static function build_where_clause($where) {
        $conditions = array();
        $values = array();

        foreach ($where as $column => $value) {
            if (is_null($value)) {
                $conditions[] = esc_sql($column) . ' IS NULL';
            } elseif (is_array($value)) {
                $placeholders = implode(',', array_fill(0, count($value), '%s'));
                $conditions[] = esc_sql($column) . " IN ({$placeholders})";
                $values = array_merge($values, $value);
            } else {
                $conditions[] = esc_sql($column) . ' = %s';
                $values[] = $value;
            }
        }

        return array(
            'sql' => implode(' AND ', $conditions),
            'values' => $values
        );
    }

    /**
     * Truncate table.
     *
     * @param string $table Table name.
     * @return bool True on success.
     */
    public static function truncate($table) {
        global $wpdb;

        $table_name = self::get_table_name($table);

        return $wpdb->query("TRUNCATE TABLE {$table_name}") !== false;
    }

    /**
     * Check if table exists.
     *
     * @param string $table Table name.
     * @return bool True if exists.
     */
    public static function table_exists($table) {
        global $wpdb;

        $table_name = self::get_table_name($table);

        return $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") === $table_name;
    }
}
