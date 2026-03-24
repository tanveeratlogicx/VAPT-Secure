<?php
require_once('../../../wp-load.php');
$meta = VAPTSECURE_DB::get_feature_meta('RISK-001');
echo "IMPLEMENTATION:\n";
print_r(json_decode($meta['implementation_data'], true));
echo "\nSCHEMA:\n";
$schema = json_decode($meta['generated_schema'], true);
print_r($schema['enforcement']);
echo "\nMATRIX:\n";
print_r($schema['platform_matrix']);
