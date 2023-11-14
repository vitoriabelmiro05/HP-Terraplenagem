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
define( 'AUTH_KEY',          'lSE(YToAcm@,8tgypPdvGF96X2E&3[KTpWE`d)D0GFxEvI5Zr([IX]*wc6]<3-K2' );
define( 'SECURE_AUTH_KEY',   '&U2#R@!L4NfBwHnvNe-4ol(1t@*,CBo7%KDOqFWOy]pB34+yF|]z2~>48GP(t8eB' );
define( 'LOGGED_IN_KEY',     'MIbwvGj5<Fk~w1U)MwF/GgzMuT|]yZ9D.b%]wL[TWKAD!CY%R=Y3MoEi_xr8LY~V' );
define( 'NONCE_KEY',         'VdmF9Okl5a=XzNzS-Ryv^rGw 8X-lR-K5ETUf`{PF(L9E*J&]ZzsqL%=(Y0&*6)U' );
define( 'AUTH_SALT',         'JEL5QWo+tB~#QqP0;q]>o0$Etw`2ZN<E8zL+:71+%937?rs~>0Co(*:<,B0VYoOJ' );
define( 'SECURE_AUTH_SALT',  'xx-]R`6|)Xax/uo6Tg WeAR9jOw.Olbgn8UxSk1{aiOz4j55^!/+SgeMO(5nUt4_' );
define( 'LOGGED_IN_SALT',    '(Z~S}YP+3.3.brfhCqE]iU3[e@S!~:MhIpD0yz(@m]iLV;H 1F+0oi#bD1QER%6C' );
define( 'NONCE_SALT',        'Y`v[3TJ+x^k}@MU$uI=B9`=$Lb;w1<wsfeM4$a=Qj){psr]&_8ne,&Tm >0W7/~5' );
define( 'WP_CACHE_KEY_SALT', 'F7r}j9[P491!+SaT>lRVg65pBr:}M*0h&q61k2#iU>4[a `:FEG(!U&/Ke9{4nJZ' );


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
