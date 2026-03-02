<?php
$file = 't:/~/Local925 Sites/hermasnet/app/public/wp-content/plugins/VAPT-Secure/data/enforcer_pattern_library_v2.0.json';
$json = file_get_contents($file);
$data = json_decode($json, true);

foreach (['RISK-021', 'RISK-022', 'RISK-023', 'RISK-024', 'RISK-025', 'RISK-026', 'RISK-027', 'RISK-028', 'RISK-029', 'RISK-030', 'RISK-031', 'RISK-032', 'RISK-033', 'RISK-034', 'RISK-035'] as $riskId) {
  if (isset($data['patterns'][$riskId]['htaccess'])) {
    echo "====================================\n";
    echo $riskId . "\n";
    echo $data['patterns'][$riskId]['htaccess']['code'] . "\n";
  }
}
