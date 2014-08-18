<?php
/**
 * Plugin Name.
 *
 * @package   Alink_Tap
 * @author    Alain Sanchez <asanchezg@inetzwerk.com>
 * @license   GPL-2.0+
 * @link      http://www.inetzwerk.com
 * @copyright 2014 Alain Sanchez
 */

/**
 * Plugin class. This class should ideally be used to work with the
 * public-facing side of the WordPress site.
 *
 * If you're interested in introducing administrative or dashboard
 * functionality, then refer to `class-alink-tap-admin.php`
 *
 * @package Alink_Tap
 * @author  Alain Sanchez <asanchezg@inetzwerk.com>
 */
class Alink_Tap {

	/**
	 * Plugin version, used for cache-busting of style and script file references.
	 *
	 * @since   1.0.0
	 *
	 * @var     string
	 */
	const VERSION = '1.0.0';

	/**
	 *
	 * Unique identifier for your plugin.
	 *
	 *
	 * The variable name is used as the text domain when internationalizing strings
	 * of text. Its value should match the Text Domain file header in the main
	 * plugin file.
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	protected $plugin_slug = 'alink-tap';

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $instance = null;

    private $default_options;

	/**
	 * Initialize the plugin by setting localization and loading public scripts
	 * and styles.
	 *
	 * @since     1.0.0
	 */
	private function __construct() {

		// Load plugin text domain
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

		// Activate plugin when new blog is added
		add_action( 'wpmu_new_blog', array( $this, 'activate_new_site' ) );

		// Load public-facing style sheet and JavaScript.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

        $this->default_options = array(
            'domain' => $_SERVER["HTTP_HOST"],
            'url_sync_link' => 'http://www.todoapuestas.org/syncKbLink.php',
            'url_get_country_from_ip' => 'http://www.todoapuestas.org/getCountryFromIP.php',
            'plurals' => 1,
        );

		/* Define custom functionality.
		 * Refer To http://codex.wordpress.org/Plugin_API#Hooks.2C_Actions_and_Filters
		 *
		 * add_action ( 'hook_name', 'your_function_name', [priority], [accepted_args] );
		 *
		 * add_filter ( 'hook_name', 'your_filter', [priority], [accepted_args] );
		 */
        add_action( 'sync_hourly_event', array( $this, 'sync_remote_server' ) );
        add_action( 'wp' , array( $this, 'active_sync_remote_server'));

        add_filter( 'the_content', array( $this, 'execute_linker' ), 9 );

	}

	/**
	 * Return the plugin slug.
	 *
	 * @since    1.0.0
	 *
	 * @return    Plugin slug variable.
	 */
	public function get_plugin_slug() {
		return $this->plugin_slug;
	}

	/**
	 * Return an instance of this class.
	 *
	 * @since     1.0.0
	 *
	 * @return    object    A single instance of this class.
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Fired when the plugin is activated.
	 *
	 * @since    1.0.0
	 *
	 * @param    boolean    $network_wide    True if WPMU superadmin uses
	 *                                       "Network Activate" action, false if
	 *                                       WPMU is disabled or plugin is
	 *                                       activated on an individual blog.
	 */
	public static function activate( $network_wide ) {

		if ( function_exists( 'is_multisite' ) && is_multisite() ) {

			if ( $network_wide  ) {

				// Get all blog ids
				$blog_ids = self::get_blog_ids();

				foreach ( $blog_ids as $blog_id ) {

					switch_to_blog( $blog_id );
					self::single_activate();

					restore_current_blog();
				}

			} else {
				self::single_activate();
			}

		} else {
			self::single_activate();
		}

	}

	/**
	 * Fired when the plugin is deactivated.
	 *
	 * @since    1.0.0
	 *
	 * @param    boolean    $network_wide    True if WPMU superadmin uses
	 *                                       "Network Deactivate" action, false if
	 *                                       WPMU is disabled or plugin is
	 *                                       deactivated on an individual blog.
	 */
	public static function deactivate( $network_wide ) {

		if ( function_exists( 'is_multisite' ) && is_multisite() ) {

			if ( $network_wide ) {

				// Get all blog ids
				$blog_ids = self::get_blog_ids();

				foreach ( $blog_ids as $blog_id ) {

					switch_to_blog( $blog_id );
					self::single_deactivate();

					restore_current_blog();

				}

			} else {
				self::single_deactivate();
			}

		} else {
			self::single_deactivate();
		}

	}

	/**
	 * Fired when a new site is activated with a WPMU environment.
	 *
	 * @since    1.0.0
	 *
	 * @param    int    $blog_id    ID of the new blog.
	 */
	public function activate_new_site( $blog_id ) {

		if ( 1 !== did_action( 'wpmu_new_blog' ) ) {
			return;
		}

		switch_to_blog( $blog_id );
		self::single_activate();
		restore_current_blog();

	}

	/**
	 * Get all blog ids of blogs in the current network that are:
	 * - not archived
	 * - not spam
	 * - not deleted
	 *
	 * @since    1.0.0
	 *
	 * @return   array|false    The blog ids, false if no matches.
	 */
	private static function get_blog_ids() {

		global $wpdb;

		// get an array of blog ids
		$sql = "SELECT blog_id FROM $wpdb->blogs
			WHERE archived = '0' AND spam = '0'
			AND deleted = '0'";

		return $wpdb->get_col( $sql );

	}

	/**
	 * Fired for each blog when the plugin is activated.
	 *
	 * @since    1.0.0
	 */
	private static function single_activate() {
		add_option('alink_tap_linker_remote_info', self::get_instance()->default_options);
        add_option('alink_tap_linker_remote', null);
	}

	/**
	 * Fired for each blog when the plugin is deactivated.
	 *
	 * @since    1.0.0
	 */
	private static function single_deactivate() {
		delete_option('alink_tap_linker_remote_info');
        delete_option('alink_tap_linker_remote');

        remove_action( 'sync_hourly_event', array( self::$instance, 'sync_remote_server' ) );
        remove_action( 'wp' , array( self::$instance, 'active_sync_remote_server'));

        remove_filter( 'the_content', array( self::$instance, 'execute_linker' ) );
	}

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		$domain = $this->plugin_slug;
		$locale = apply_filters( 'plugin_locale', get_locale(), $domain );

		load_textdomain( $domain, trailingslashit( WP_LANG_DIR ) . $domain . '/' . $domain . '-' . $locale . '.mo' );
		load_plugin_textdomain( $domain, FALSE, basename( plugin_dir_path( dirname( __FILE__ ) ) ) . '/languages/' );

	}

	/**
	 * Register and enqueue public-facing style sheet.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		wp_enqueue_style( $this->plugin_slug . '-plugin-styles', plugins_url( 'assets/css/public.css', __FILE__ ), array(), self::VERSION );
	}

	/**
	 * Register and enqueues public-facing JavaScript files.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( $this->plugin_slug . '-plugin-script', plugins_url( 'assets/js/public.js', __FILE__ ), array( 'jquery' ), self::VERSION );
	}

	/**
	 * NOTE:  Actions are points in the execution of a page or process
	 *        lifecycle that WordPress fires.
	 *
	 *        Actions:    http://codex.wordpress.org/Plugin_API#Actions
	 *        Reference:  http://codex.wordpress.org/Plugin_API/Action_Reference
	 *
	 * @since    1.0.0
	 */
//	public function action_method_name() {
//		// @TODO: Define your action hook callback here
//	}

    public function active_sync_remote_server() {

        if ( !wp_next_scheduled( 'sync_hourly_event' ) ) {

            wp_schedule_event(time(), 'hourly', 'sync_hourly_event');

        }

    }

    public function sync_remote_server() {
        // do something every hour

        $option = get_option('alink_tap_linker_remote_info', $this->default_options);

        $domain = $option['domain'];
        $url_sync_link = esc_url($option['url_sync_link']);
        $remote_plurals = $option['plurals'];

        if (preg_match('/\\b(https?|ftp):\/\/www\.(?P<domain>[-A-Z0-9.]+)\\z/i', $domain, $regs)) {
            $domain = $regs['domain'];
        }

        // Get values from TAP
        $url = $url_sync_link."?domain=". $domain;
        $atLinks = file_get_contents($url);
        $crlf = "\r\n";
        $atLinks .= $crlf;
        switch(preg_match('/^w{3}./', $domain)){
            case 1:
                $url = $url_sync_link."?domain=".preg_replace('/^w{3}./','',$domain);
                $atLinks .= " ".file_get_contents($url);
                break;
            default:
                $url = $url_sync_link."?domain=www.".$domain;
                $atLinks .= " ". file_get_contents($url);
                break;
        }

        $pairs = str_replace("\r", '', $atLinks);

        $pairs = explode("\n", $pairs);

        foreach( $pairs as $pair ){

            /**

             * Se obtiene de syncKbLink.php el siguiente formato :

             * ' ["name"]->["url"]->["urles"]->["licencia_esp"]\n '

             */

            $pair = trim( $pair ); // no leading or trailing spaces. Can mess with the "target" thing in function kb_linker()

            $pair = explode( "->", $pair );

            if ( ( '' != $pair[0] ) && ( ('' != $pair[1]) || ('' != $pair[2])) ){

                $new[ $pair[0] ] = array('url' => $pair[1], 'urles'=> $pair[2]);

            }

            if ( ( '' != $pair[0] ) && ( '' != $pair[3] ) )

                $licencia_esp[ $pair[0] ] = $pair[3];

        }//foreach

        $pairs = $new;	// contains the pairs as an array for use by the filter

        $text = $atLinks;	// contains the pairs as entered in the form for display below

        $licencias = $licencia_esp;


        $option = array( 'pairs'=>$pairs, 'text'=>$text, 'plurals'=>$remote_plurals, 'licencias'=>$licencias);	// store both versions of the option, pairs and text

        update_option( 'alink_tap_linker_remote', $option );
    }


    /**
	 * NOTE:  Filters are points of execution in which WordPress modifies data
	 *        before saving it or sending it to the browser.
	 *
	 *        Filters: http://codex.wordpress.org/Plugin_API#Filters
	 *        Reference:  http://codex.wordpress.org/Plugin_API/Filter_Reference
	 *
	 * @since    1.0.0
	 */
//	public function filter_method_name() {
//        // @TODO: Define your filter hook callback here
//	}

    public function execute_linker($content) {
        global $alink_tap_special_chars,$alink_tap_title_text;
        $pairs = $text = $plurals = $licencias = null;

        $option = get_option('alink_tap_linker_remote');

        if (is_array($option)){
            extract($option);
        }

        // uncomment for testing (to override options):
//        $pairs = array( 'contributor'=>'http://google.com', 'a'=>'http://yahoo.com/', 'scripting'=>'scripting', 'don'=>'don', 'first post'=>'firstpost.org', 'first'=>'first.org', 'wp'=>'WP.ORG');

        if ( !is_array($pairs) )
            return $content;

        // let's make use of that special chars setting.

        if (is_array($alink_tap_special_chars)){

            foreach ($alink_tap_special_chars as $char => $code){

                $content = str_replace($code,$char,$content);

            }

        }

        // needed below...

        $usedUrls = array();

        $currentUrl = site_url(); // may not work on all hosting setups.

        $ip = $_SERVER['REMOTE_ADDR'];

        $remote_info = get_option('alink_tap_linker_remote_info', $this->default_options);
        $url_get_country_from_ip = esc_url($remote_info['url_get_country_from_ip']);

        $userUrl = $url_get_country_from_ip."?ip=". $ip;

        $country = trim(file_get_contents($userUrl));

        foreach ($pairs as $keyword => $url_array){

            // Compruebo si es un usuario de Spain. Si lo es, compruebo si la key es con licencia_esp, si no, paso al siguiente

            if($country == "Spain" && !empty($licencias)){

                $licenciaESP = $licencias[$keyword];

                $url = $url_array['urles'];

                if(!$licenciaESP) continue;

            } else {

                $url = $url_array['url'];

            }

            /**if (in_array( $url, $usedUrls )) // don't link to the same URL more than once

            continue;

            if (strpos( $content, $url )){ // we've already used this URL, or it was manually inserted by author into post

            $usedUrls[] = $url;

            continue;

            }*/

            if ($url == $currentUrl){ // don't link a page to itself

                $usedUrls[] = $url;

                continue;

            }

            // first, let's check whether we've got a "target" attribute specified.

            if (false!==strpos( $url, ' ' ) ){	// Let's not waste CPU resources unless we see a ' ' in the URL:

                $target = trim(   substr( $url, strpos($url,' ') )   );

                $target = ' target="'.$target.'"';

                $url = substr( $url, 0, strpos($url,' ') );

            }else{

                $target='';

            }

            // let's escape any '&' in the URL.

            $url = str_replace( '&amp;', '&', $url ); // this might seem unnecessary, but it prevents the next line from double-escaping the &

            $url = str_replace( '&', '&amp;', $url );



            // we don't want to link the keyword if it is already linked.

            // so let's find all instances where the keyword is in a link and precede it with &&&, which will be sufficient to avoid linking it. We use &&&, since WP would pass that

            // to us as &amp;&amp;&amp; (if it occured in a post), so it would never be in the $content on its own.

            // this has two steps. First, look for the keyword as linked text:

            $content = preg_replace( '|(<a[^>]+>)(.*)('.$keyword.')(.*)(</a[^>]*>)|Ui', '$1$2&&&$3$4$5', $content);



            // Next, look for the keyword inside tags. E.g. if they're linking every occurrence of "Google" manually, we don't want to find

            // <a href="http://google.com"> and change it to <a href="http://<a href="http://www.google.com">.com">

            // More broadly, we don't want them linking anything that might be in a tag. (e.g. linking "strong" would screw up <strong>).

            // if you get problems with KB linker creating links where it shouldn't, this is the regex you should tinker with, most likely. Here goes:

            $content = preg_replace( '|(<[^>]*)('.$keyword.')(.*>)|Ui', '$1&&&$2$3', $content);


            // I'm sure a true master of regular expressions wouldn't need the previous two steps, and would simply write the replacement expression (below) better. But this works for me.

            // set the title attribute:

            if (ALINK_TAP_USE_TITLES)
                $title = ' title="'.$alink_tap_title_text['before'].$alink_tap_title_text['after'].'"';

            // now that we've taken the keyword out of any links it appears in, let's look for the keyword elsewhere.

            if ( 1 != $plurals ){	 // we do basically the same thing whether we're looking for plurals or not. Let's do non-plurals option first:

                $content = preg_replace( '|(?<=[\s>;\'])('.$keyword.')(?=[\s<&,!\';:\./])|i', '<a href="'.$url.'" class="alink-tp"'.$target.$title.' rel="nofollow">$1</a>', $content/*, 1*/);	// that "1" at the end limits it to replacing the keyword only once per post => We quit this in TAP!!!!!

                /* some notes about that regular expression to make modifying it easier for you if you're new to these things:

                (?<=[\s>;"\'])

                    (?<=	marks it as a lookbehind assertion

                    to ensure that we are linking only complete words, we want keyword preceded by one of space, tag (>), entity (;) or certain kinds of punctuation (escaped with \ when necessary)

                    Note that '&' is NOT one of the allowed lookbehinds (or our '&&&' trick wouldn't work)

                (?=[\s<&.,\'";:\-])

                    (?=	marks this as a lookahead assertion

                    again, we link only complete words. Must be followed by space, tag (<), entity (&), or certain kinds of punctuation.

                    Note that some of the punctuations are escaped with \

                */



            }else{	// if they want us to look for plurals too:

                // this regex is almost identical to the non-plurals one, we just add an s? where necessary:

                $content = preg_replace( '|(?<=[\s>;\'])('.$keyword.'s?)(?=[\s<&,!\';:\./])|i', '<a href="'.$url.'" class="alink-tp"'.$target.$title.' rel="nofollow">$1</a>', $content/*, 1*/);	// that "1" at the end limits it to replacing once per post.

            }

        }

        // get rid of our '&&&' things.

        $content = str_replace( '&&&', '', $content);

        return $content;
    }

    public function get_default_options() {
        return $this->default_options;
    }
}
