<?php
foreach ($this->get_option_names() as $optName) {
delete_option($this->get_option_name($optName));
}
global $wpdb;
include $this->get_plugin_dir() . 'include' . DIRECTORY_SEPARATOR . 'schema.php';
foreach (array_keys($ti_db_schema) as $tableName) {
$wpdb->query('DROP TABLE IF EXISTS `'. $this->get_tablename($tableName) .'`');
}
?>