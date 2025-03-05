<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the web site, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * Localized language
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'local' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', 'root' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',          '1**&*+Af?3GI~.rB#L!?U/3no@6/GA`[d|d.p0J6<{p`$GSlQ67i ?eIQm)_HLf|' );
define( 'SECURE_AUTH_KEY',   '=:XMjjMz]M3J]lRHW-P=rUAX7*`p*yHJpT:`kZ(D|.6JMa>?,eut4(|AeJT`t>C&' );
define( 'LOGGED_IN_KEY',     'wUY9I[OhlY}7x@T1;6=][+sme%d:k2t9x7][e(s|6Uo:0n$y)PLm7Z-W/O7REVP[' );
define( 'NONCE_KEY',         'SKf/G.{PUochS}8HR=Q_1gD~8io}-k& B/o>Iaz)Q*G6e@=.>mSP&Q8#f?sjVdV&' );
define( 'AUTH_SALT',         'eJrJ30!_-5cXbO]C(|:,U~z&W?/V]K/nCt-4n*E!q3?Vq1-/ c4Zz@)@BGtC#iAZ' );
define( 'SECURE_AUTH_SALT',  '{,CcJrQbW |XKsGYRB+ivwCB[30DVqL>yfV)qTh,l2dj?_>Gc#j$<x?D6c(6Zob0' );
define( 'LOGGED_IN_SALT',    '^[tMsF}O5@>Qxw4U/-9z`^M100$BM~}|tz +~..G+R]u1XKIilnIYXQ#qG~L-ZJi' );
define( 'NONCE_SALT',        'ISV-e0  Ti,aY[xk$x##!/9P|g!;[ijrAI>#zep4/mu d84ZmwVa$Ujt>@V+<y$z' );
define( 'WP_CACHE_KEY_SALT', '-#EAUOJJCK3g!*a<nL$g78)(fDRei?$>IsSYj[?KFwjF%`8XjgZP$3F~fS:IG;Q1' );


/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';


/* Add any custom values between this line and the "stop editing" line. */



/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
 */
if ( ! defined( 'WP_DEBUG' ) ) {
	define( 'WP_DEBUG', false );
}

define( 'WP_ENVIRONMENT_TYPE', 'local' );
/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
