<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the
 * installation. You don't have to use the web site, you can
 * copy this file to "wp-config.php" and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * MySQL settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://codex.wordpress.org/Editing_wp-config.php
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define('DB_NAME', 'u552367865_cbdb');

/** MySQL database username */
define('DB_USER', 'u552367865_admin');

/** MySQL database password */
define('DB_PASSWORD', 'E1Uptw6y28');

/** MySQL hostname */
define('DB_HOST', 'mysql.hostinger.in');

/** Database Charset to use in creating database tables. */
define('DB_CHARSET', 'utf8mb4');

/** The Database Collate type. Don't change this if in doubt. */
define('DB_COLLATE', '');

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         '+Y%/:c<*kPImy//8 ZjNNd9w6)4!++9|<m6N4u6fObzv_-f,E4{`w%^agMuslK8|');
define('SECURE_AUTH_KEY',  '!QIq)zp{jZ0F7fU,/*SdgFqIm3UHT]>6G>LYid6Av?iv__uZlyAP)Nq3ef{ksfn`');
define('LOGGED_IN_KEY',    'WqSo-SN5Ms/w?EH!RQMP5ER_i0YT;FQzEcHwH|?36ov<V]Rr|i?VErmt[:9xzo@N');
define('NONCE_KEY',        'Y>c8,)kJ0=_V5|#$U#|Ft6%^nB9]<#yM(t1O9l1ePt#N)$!,4^?cW:ZO5AI-g:N2');
define('AUTH_SALT',        '@r&@GeCPY2{8n?eVDNmipDib{0(+>BqWOuJvVG~l2,YU^}hj6gtp_FO`X8vWs;Pn');
define('SECURE_AUTH_SALT', '=D~(fgRF8JUvD=TAc${2G8oq::yTvB%..gGEPN3Kxa}f5~]vA1rU-YvZ^L$UuNyC');
define('LOGGED_IN_SALT',   '1UIB)h{a3z/z1ofzah_!eEP;lb>&U87vM8NaWaXv, tsoG;s2qUpw7=!``RY]2HC');
define('NONCE_SALT',       'a*N)c.jPK4mm,KpxS<h6nikF$t0gu_%K,OfXSzb)9?jH;pj~FVN*b9!ouZ$U8`nW');

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix  = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the Codex.
 *
 * @link https://codex.wordpress.org/Debugging_in_WordPress
 */
define('WP_DEBUG', false);

/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');
