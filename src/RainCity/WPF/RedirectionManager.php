<?php
namespace RainCity\WPF;

use RainCity\Logging\Logger;
use Psr\Log\LoggerInterface;


final class RedirectionManager
    implements ActionHandlerInf
{
    private LoggerInterface $logger;
    private RedirectionHelperInf $helper;

    public function __construct(RedirectionHelperInf $helper)
    {
        $this->logger = Logger::getLogger(get_class($this));
        $this->helper = $helper;
    }

    /**
     * Initialize any actions or filters for the helper.
     *
     * @param ActionFilterLoader $loader
     */
    public function loadActions(ActionFilterLoader $loader): void
    {
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
    public function loginRedirect(string $redirectTo, string $requestedRedirectTo, \WP_User|\WP_Error $user): string
    {
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
    public function logoutRedirect(string $redirectTo, string $request, \WP_User|\WP_Error $user): string
    {
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
