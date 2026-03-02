<?php
$json = file_get_contents('t:/~/Local925 Sites/hermasnet/app/public/wp-content/plugins/VAPT-Secure/data/enforcer_pattern_library_v2.0.json');
$data = json_decode($json, true)['patterns'];
$total = count($data);
$htaccessCount = 0;
$nginxCount = 0;
$iisCount = 0;

foreach ($data as $riskId => $details) {
  if (isset($details['htaccess'])) $htaccessCount++;
  if (isset($details['nginx'])) $nginxCount++;
  if (isset($details['iis'])) $iisCount++;
}

echo "Total Risks: $total\n";
echo "Htaccess: $htaccessCount\n";
echo "Nginx: $nginxCount\n";
echo "IIS: $iisCount\n";
