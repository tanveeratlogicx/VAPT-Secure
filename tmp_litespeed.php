<?php
$file = 't:/~/Local925 Sites/hermasnet/app/public/wp-content/plugins/VAPT-Secure/data/enforcer_pattern_library_v2.0.json';
$json = file_get_contents($file);
$data = json_decode($json, true);

foreach ($data['patterns'] as $id => $val) {
  if (isset($val['htaccess']) && !isset($val['litespeed'])) {
    $data['patterns'][$id]['litespeed'] = [
      "implementation_type" => "litespeed_htaccess",
      "litespeed_snippet" => $val['htaccess']['wrapped_code'],
      "verification" => $val['htaccess']['verification']['command'] ?? "curl -sI https://yoursite.com | grep HTTP",
      "note" => "Litespeed natively supports Apache .htaccess directives."
    ];
  }
}

$newJson = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
file_put_contents($file, $newJson);
echo "SUCCESS: Injected Litespeed into Pattern Library.\n";
