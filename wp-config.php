<?php
define('WP_CACHE', true); // Added by SpeedyCache
define('WP_HOME', 'https://wp.saifinance.com.au');
define('WP_SITEURL', 'https://wp.saifinance.com.au');
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the website, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'saifinan_wp711' );

/** Database username */
define( 'DB_USER', 'saifinan_wp711' );

/** Database password */
define( 'DB_PASSWORD', ']]up.7WO.GSzM3.6' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

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
define( 'AUTH_KEY',         'khw7s6wporio6ekand6pjgq03umxrscbghvwygvvrdwnt9kqhbpxp5ykikleovfx' );
define( 'SECURE_AUTH_KEY',  'i11vjcvixpf27erdyemhq5ht6siy95l085myoj64zvnyxkluhcreciioh3qeysqc' );
define( 'LOGGED_IN_KEY',    'xgkfw7rwhrbe1bu7qzvashkt6aqu1znn0xtt3k6gmviwl4h4bgvj9lfjguwp8psq' );
define( 'NONCE_KEY',        'wlf9xjhdgdpzcdxaxpsroykdlk8nosnl5d4bzekqvuxmsebjhqe76wcrgzymsvxd' );
define( 'AUTH_SALT',        '9pszglwxul0xapj7m6ef0ioguynhorzx7t8tydhsdit5o9sy1a5mwmxplexfpqnh' );
define( 'SECURE_AUTH_SALT', 'jryg7tz0kpzjmdinfevp1ebz1c7vkcope750ydxkhhhoteukynebphc2nnii9j3j' );
define( 'LOGGED_IN_SALT',   'bqxtjxifuezqyfrtrtbryxt3vwqtighqcnyric1yknbvsdkmbtaogadqicc8ci6j' );
define( 'NONCE_SALT',       'jqlcdnol3emml5xkpqhhevmb2hs4ul2ixjrpztm7bigu3maxirkvgikt45jszinf' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 *
 * At the installation time, database tables are created with the specified prefix.
 * Changing this value after WordPress is installed will make your site think
 * it has not been installed.
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#table-prefix
 */
$table_prefix = 'wpms_';

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
 * @link https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/
 */
define( 'WP_DEBUG', false );

/* Add any custom values between this line and the "stop editing" line. */
define('SF_CF_HOOK_STAGING', '');
define('SF_CF_HOOK_PRODUCTION', 'https://api.cloudflare.com/client/v4/pages/webhooks/deploy_hooks/f1788a75-b6d5-4372-8189-1b96ff59e4fa');
define('SF_CF_REBUILD_DELAY', 120);

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
