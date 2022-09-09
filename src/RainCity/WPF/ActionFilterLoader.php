<?php
namespace RainCity\WPF;

/**
 * Register all actions and filters for the plugin.
 *
 * Maintain a list of all hooks that are registered throughout
 * the plugin, and register them with the WordPress API. Call the
 * run function to execute the list of actions and filters.
 *
 */
class ActionFilterLoader {
    /**
     * The array of actions registered with WordPress.
     *
     * @access   protected
     * @var      array    $actions    The actions registered with WordPress to fire when the plugin loads.
     */
    protected $actions;

    /**
     * The array of filters registered with WordPress.
     *
     * @access   protected
     * @var      array    $filters    The filters registered with WordPress to fire when the plugin loads.
     */
    protected $filters;

    /**
     * The array of short codes registered with WordPress.
     *
     * @access   protected
     * @var      array    $shortcodes    The short codes registered with WordPress to fire when the plugin loads.
     */
    protected $shortcodes;

    /**
     * The array of REST routes registered with WordPress.
     *
     * @access   protected
     * @var      array    $restRoutes    The REST routes registered with WordPress to fire when the plugin loads.
     */
    protected $restRoutes;

    private $pluginSlug;

    /**
     * Initialize the collections used to maintain the actions and filters.
     *
     * @param string $pluginSlug The slug for the plugin
     */
    public function __construct(string $pluginSlug) {
        $this->pluginSlug = $pluginSlug;

        $this->actions = array();
        $this->filters = array();
        $this->shortcodes = array();
        $this->restRoutes = array();
    }

    /**
     * Add a new action to the collection to be registered with WordPress.
     *
     * @param    string               $hook         The name of the WordPress action that is being registered.
     * @param    string|object|null   $component    A reference to a class for static methods, an object for instance methods or null.
     * @param    string|callable      $callback     The name of the method on the class or object on the $component, the name of a function or a function.
     * @param    int                  $priority     Optional. The priority at which the function should be fired. Default is 10.
     * @param    int                  $args         Optional. The number of arguments that should be passed to the $callback. Default is 1.
     */
    public function add_action( $hook, $component, $callback, $priority = 10, $args = 1 ) {
        $this->add( $this->actions, $hook, $component, $callback, $priority, $args );
    }

    /**
     * Add a new filter to the collection to be registered with WordPress.
     *
     * @param    string               $hook         The name of the WordPress action that is being registered.
     * @param    string|object|null   $component    A reference to a class for static methods, an object for instance methods or null.
     * @param    string|callable      $callback     The name of the method on the class or object on the $component, the name of a function or a function.
     * @param    int                  $priority     Optional. The priority at which the function should be fired. Default is 10.
     * @param    int                  $args         Optional. The number of arguments that should be passed to the $callback. Default is 1.
     */
    public function add_filter( $hook, $component, $callback, $priority = 10, $args = 1 ) {
        $this->add( $this->filters, $hook, $component, $callback, $priority, $args );
    }

    /**
     * Add a new short code to the collection to be registered with WordPress.
     *
     * @param    string               $hook         The name of the WordPress action that is being registered.
     * @param    string|object|null   $component    A reference to a class for static methods, an object for instance methods or null.
     * @param    string|callable      $callback     The name of the method on the class or object on the $component, the name of a function or a function.
     */
    public function add_shortcode( $hook, $component, $callback) {
        $this->add( $this->shortcodes, $hook, $component, $callback);
    }

    /**
     * Add a new REST endpoint to the collection to be registered with WordPress.
     *
     * The 'methods' element of $args defaults to 'GET'.<br>
     * The 'callback' element of $args should not be provied. It will be overridden.
     *
     * @param int                   $version    The API version number for the route.
     * @param string                $route      The route URI
     * @param string|object|null    $component  A reference to a class for static methods, an object for instance methods or null.
     * @param string|callable       $callback   The name of a method on the class or object on the $component, the name of a function or a function.
     * @param array                 $args       The arguments to be provided to register_rest_route.
     */
    public function add_endpoint(int $version, string $route, $component, $callback, array $args = array('methods' => \WP_REST_Server::READABLE)) {
        $args['callback'] = $this->getCallback($component, $callback);

        if (!isset($args['permission_callback'])) {
            $args['permission_callback'] = '__return_true';
        }

        $restRoute = new \stdClass();
        $restRoute->namespace = $this->pluginSlug . '/v' . $version;
        $restRoute->route = $route;
        $restRoute->args = $args;

        array_push($this->restRoutes, $restRoute);
    }


    /**
     * A utility function that is used to register the actions and hooks into a single
     * collection.
     *
     * @since    1.0.0
     * @access   private
     *
     * @param    array                $hooks            A reference to a collection of hooks that is being registered (that is, actions, filters, short codes).
     * @param    string               $hook             The name of the WordPress filter that is being registered.
     * @param    string|object|null   $component        A reference to the instance of the object on which the filter is defined.
     * @param    string|callable      $callback         The name of the function definition on the $component.
     * @param    int                  $priority         The priority at which the function should be fired.
     * @param    int                  $accepted_args    The number of arguments that should be passed to the $callback.
     */
    private function add(array &$hooks, string $hook, $component, $callback, int $priority = 10, int $accepted_args = 1 ) {
        $hookObj = new \stdClass();
        $hookObj->hook = $hook;
        $hookObj->callback = $this->getCallback($component, $callback);
        $hookObj->priority = $priority;
        $hookObj->accepted_args = $accepted_args;

        $hooks[] = $hookObj;
    }

    /**
     * Create a callable reference from $component and $callback.
     *
     * @param string|object|null    $component  A reference to a class for static methods, an object for instance methods or null.
     * @param string|callable       $callback   The name of a method on the class or object on the $component, the name of a function or a function.
     *
     * @throws \InvalidArgumentException    Thrown if the arguments passed cannot be combined into a callable method.
     *
     * @return callable A reference to a callable method.
     */
    private function getCallback($component, $callback): callable {
        // If the $callback parameter is callable, use that ignoring the $component argument
        if (is_callable($callback)) {
            $callbackFunc = $callback;
        }
        else {
            if (is_object($component)) {
                $callbackFunc = array($component, $callback);
            }
            else if (is_string($component)) {
                $callbackFunc = array($component, $callback);
            }
            else {
                $callbackFunc = $callback;
            }

            if (!is_callable($callbackFunc)) {
                throw new \InvalidArgumentException("Invalid callback defined.");
            }
        }

        return $callbackFunc;
    }

    /**
     * Register the filters and actions with WordPress.
     *
     * @since    1.0.0
     */
    public function run() {
        foreach ( $this->filters as $hook ) {
            add_filter( $hook->hook, $hook->callback, $hook->priority, $hook->accepted_args );
        }
        foreach ( $this->actions as $hook ) {
            add_action( $hook->hook, $hook->callback, $hook->priority, $hook->accepted_args );
        }
        foreach ( $this->shortcodes as $hook ) {
            add_shortcode( $hook->hook, $hook->callback );
        }

        add_action( 'rest_api_init', function() {
            foreach ( $this->restRoutes as $endpoint ) {
                register_rest_route($endpoint->namespace, $endpoint->route, $endpoint->args);
            }
        });

    }
}
