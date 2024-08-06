<?php
require_once(ABSPATH . 'wp-admin' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'upgrade.php');
global $wpdb;
$wpdb->hide_errors();
include $this->get_plugin_dir() . 'include' . DIRECTORY_SEPARATOR . 'schema.php';
$notCreatedTables = [];
$mysqlError = "";
foreach (array_keys($ti_db_schema) as $tableName) {
if (!$this->is_table_exists($tableName)) {
dbDelta($ti_db_schema[ $tableName ]);
}
if ($wpdb->last_error) {
$mysqlError = $wpdb->last_error;
}
if (!$this->is_table_exists($tableName)) {
$notCreatedTables []= $tableName;
}
}
if ($notCreatedTables) {
$this->loadI18N();
deactivate_plugins(plugin_basename($this->plugin_file_path));
$sqlsToRun = array_map(function($tableName) use($ti_db_schema) {
return trim($ti_db_schema[ $tableName ]);
}, $notCreatedTables);
$preStyle = 'background: #eee; padding: 10px 20px; word-wrap: break-word; white-space: pre-wrap';
wp_die(
'<strong>' . self::___('Plugin activation is failed because the required database tables could not created!') . '</strong><br /><br />' .
self::___('We got the following error from %s:', [ self::___('database') ]) .
'<pre style="'. $preStyle .'">'. $mysqlError .'</pre>' .
'<strong>' . self::___('Run the following SQL codes in your database administration interface (e.g. PhpMyAdmin) to create the tables or contact your system administrator:') . '</strong>' .
'<pre style="'. $preStyle .'">' . implode('</pre><pre style="'. $preStyle .'">', $sqlsToRun) . '</pre>' .
'<strong>' . self::___('Then try activate the plugin again.') . '</strong>'
);
}
update_option($this->get_option_name('active'), '1');
update_option($this->get_option_name('version'), $this->version);
?>