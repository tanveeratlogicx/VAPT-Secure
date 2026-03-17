<?php
/**
 * VAPT Secure Configuration for hermasnet
 * Build Version: 1.0.1
 */

if (! defined('ABSPATH') ) { exit; 
}

// Domain Locking & Licensing
define('VAPTSECURE_DOMAIN_LOCKED', 'hermasnet');
define('VAPTSECURE_BUILD_VERSION', '1.0.1');
define('VAPTSECURE_LICENSE_SCOPE', 'single');
define('VAPTSECURE_DOMAIN_LIMIT', 1);

// Active Features
define('VAPTSECURE_FEATURE_RISK_003', true);
define('VAPTSECURE_FEATURE_RISK_004', true);
define('VAPTSECURE_FEATURE_RISK_008', true);
define('VAPTSECURE_FEATURE_RISK_007', true);
define('VAPTSECURE_FEATURE_RISK_011', true);
define('VAPTSECURE_FEATURE_RISK_009', true);
define('VAPTSECURE_FEATURE_RISK_012', true);
