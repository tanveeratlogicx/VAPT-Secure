<?php
$file = 't:/~/Local925 Sites/hermasnet/app/public/wp-content/plugins/VAPT-Secure/data/enforcer_pattern_library_v2.0.json';
$json = file_get_contents($file);
$data = json_decode($json, true);

$inject = [
  'RISK-027' => [
    "implementation_type" => "nginx_location_block",
    "nginx_snippet" => "# BEGIN VAPT RISK-027\nlocation ~* /install\\.php$ { deny all; return 403; }\n# END VAPT RISK-027",
    "verification" => "curl -sI https://yoursite.com/install.php | grep HTTP"
  ],
  'RISK-028' => [
    "implementation_type" => "nginx_location_block",
    "nginx_snippet" => "# BEGIN VAPT RISK-028\nlocation ~* /upgrade\\.php$ { deny all; return 403; }\n# END VAPT RISK-028",
    "verification" => "curl -sI https://yoursite.com/upgrade.php | grep HTTP"
  ],
  'RISK-029' => [
    "implementation_type" => "nginx_location_block",
    "nginx_snippet" => "# BEGIN VAPT RISK-029\nlocation ~* \\.(bak|backup|old|orig|save|swp|sql|dump)$ { deny all; return 403; }\n# END VAPT RISK-029",
    "verification" => "curl -sI https://yoursite.com/test.bak | grep HTTP"
  ],
  'RISK-030' => [
    "implementation_type" => "nginx_location_block",
    "nginx_snippet" => "# BEGIN VAPT RISK-030\nlocation ~* \\.(log|error_log|access_log)$ { deny all; return 403; }\n# END VAPT RISK-030",
    "verification" => "curl -sI https://yoursite.com/error.log | grep HTTP"
  ],
  'RISK-031' => [
    "implementation_type" => "nginx_header",
    "nginx_snippet" => "# BEGIN VAPT RISK-031\nadd_header Cross-Origin-Resource-Policy \"same-origin\" always;\n# END VAPT RISK-031",
    "verification" => "curl -sI https://yoursite.com | grep -i 'Cross-Origin-Resource-Policy'"
  ],
  'RISK-032' => [
    "implementation_type" => "nginx_header",
    "nginx_snippet" => "# BEGIN VAPT RISK-032\nadd_header Cross-Origin-Embedder-Policy \"require-corp\" always;\n# END VAPT RISK-032",
    "verification" => "curl -sI https://yoursite.com | grep -i 'Cross-Origin-Embedder-Policy'"
  ],
  'RISK-033' => [
    "implementation_type" => "nginx_header",
    "nginx_snippet" => "# BEGIN VAPT RISK-033\nadd_header Cross-Origin-Opener-Policy \"same-origin\" always;\n# END VAPT RISK-033",
    "verification" => "curl -sI https://yoursite.com | grep -i 'Cross-Origin-Opener-Policy'"
  ],
  'RISK-034' => [
    "implementation_type" => "nginx_location_block",
    "nginx_snippet" => "# BEGIN VAPT RISK-034\nlocation ~* /debug\\.log$ { deny all; return 403; }\n# END VAPT RISK-034",
    "verification" => "curl -sI https://yoursite.com/debug.log | grep HTTP"
  ],
  'RISK-035' => [
    "implementation_type" => "nginx_header",
    "nginx_snippet" => "# BEGIN VAPT RISK-035\nadd_header X-Frame-Options \"SAMEORIGIN\" always;\n# END VAPT RISK-035",
    "verification" => "curl -sI https://yoursite.com | grep -i 'X-Frame-Options'"
  ]
];

foreach ($inject as $id => $nginxData) {
  if (isset($data['patterns'][$id])) {
    // We want to insert 'nginx' right before 'caddy' if possible to maintain aesthetic order, 
    // but PHP associative arrays maintain insertion order. We will reconstruct the risk array.
    $newRisk = [];
    foreach ($data['patterns'][$id] as $k => $v) {
      if ($k === 'caddy') {
        $newRisk['nginx'] = $nginxData;
      }
      $newRisk[$k] = $v;
    }
    if (!isset($newRisk['nginx'])) {
      $newRisk['nginx'] = $nginxData;
    }
    $data['patterns'][$id] = $newRisk;
  }
}

$newJson = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
file_put_contents($file, $newJson);
echo "SUCCESS: Saved to $file\n";
