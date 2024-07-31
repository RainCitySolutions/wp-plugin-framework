<?php
namespace RainCity\WPF;

interface RedirectionHelperInf
{
    public function getLoginRedirectUrl(\WP_User $user, ?string $requestedRedirect): ?string;
    public function getLogoutRedirectUrl(\WP_User $user): ?string;
}
