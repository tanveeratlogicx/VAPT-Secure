<?php
$json = file_get_contents('t:/~/Local925 Sites/hermasnet/app/public/wp-content/plugins/VAPT-Secure/data/enforcer_pattern_library_v2.0.json');
$data = json_decode($json, true)['patterns'];
$cloudflareCount = 0;
$litespeedCount = 0;

foreach ($data as $riskId => $details) {
  if (isset($details['cloudflare'])) $cloudflareCount++;
  if (isset($details['litespeed'])) $litespeedCount++;
}

echo "Cloudflare: $cloudflareCount\n";
echo "Litespeed: $litespeedCount\n";
