<?php
/**
 * VAPT Secure Configuration for wptest
 * Build Version: 1.0.1
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

// Domain Locking & Licensing
define( 'VAPTSECURE_DOMAIN_LOCKED', 'wptest' );
define( 'VAPTSECURE_BUILD_VERSION', '1.0.1' );
define( 'VAPTSECURE_LICENSE_SCOPE', 'multisite' );
define( 'VAPTSECURE_DOMAIN_LIMIT', 1 );
define( 'VAPTSECURE_LICENSE_EXPIRY', '' );

// Active Features
define( 'VAPTSECURE_FEATURE_RISK_083', true );
define( 'VAPTSECURE_FEATURE_RISK_001', true );

// License Enforcement Handlers
define( 'VAPTSECURE_OBFUSCATED_PAYLOAD', 'eyJkb21haW4iOiJ3cHRlc3QiLCJzY29wZSI6Im11bHRpc2l0ZSIsImxpbWl0IjoxLCJleHBpcnkiOiIiLCJsaWNlbnNlX2lkIjoiIiwidmVyc2lvbiI6IjEuMC4xIn0=' );
define( 'VAPTSECURE_CONFIG_SIGNATURE', '8c4132f0ff16573703ed4d307d3a589eed05a36f0ea44706d1acbc0834530ffd' );
define( 'VAPTSECURE_SALT', 'gPG?{UTca@Q(v|J(w?Kw9j)#I^O]Y@}*VRy/QHIyL:}6t]=C `7[)s[2?hur@b[#' );
