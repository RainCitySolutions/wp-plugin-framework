<?php
namespace RainCity\WPF;

use RainCity\Logging\Logger;


final class RedirectionManager
    implements ActionHandlerInf
{
    private $logger;
    private $helper;

    public function __construct(RedirectionHelperInf $helper) {
        $this->logger = Logger::getLogger(get_class($this));
        $this->helper = $helper;
    }

    /**
     * Initialize any actions or filters for the helper.
     *
     * @param ActionFilterLoader $loader
     */
    public function loadActions(ActionFilterLoader $loader) {
        $loader->addFilter('login_redirect', $this, 'loginRedirect', 10, 3);
        $loader->addFilter('logout_redirect', $this, 'logoutRedirect', 10, 3);
    }


    /**
     * Handles login redirection
     *
     * @param string $redirectTo Default redirect
     * @param string $request Requested redirect
     * @param WP_User|WP_Error WP_User if user logged in, WP_Error otherwise
     *
     * @return string New redirect
     */
    public function loginRedirect( $redirectTo, $requestedRedirectTo, $user ) {
        $redirectUrl = $redirectTo;

        if ($user instanceof \WP_User) {
            $url = $this->helper->getLoginRedirectUrl($user, $requestedRedirectTo);
            if (isset($url)) {
                $redirectUrl = $url;
            }
        }

        return $redirectUrl;
    }

    /**
     * Handles logout redirection
     *
     * @param string $redirectTo Default redirect
     * @param string $request Requested redirect
     * @param WP_User|WP_Error WP_User if user logged in, WP_Error otherwise
     *
     * @return string New redirect
     */
    public function logoutRedirect($redirectTo, $request, $user) {
        $redirectUrl = $redirectTo;

        if ($user instanceof \WP_User) {
            $helperUrl = $this->helper->getLogoutRedirectUrl($user);

            if (isset($helperUrl)) {
                $redirectUrl = $helperUrl;
            }
        }

        return $redirectUrl;
    }
}
