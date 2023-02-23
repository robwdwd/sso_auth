<?php

namespace SSOAuth;

use Dapphp\Radius\Radius as DRadius;

/**
 * SSO Authentication Library.
 **/
class Auth
{
    private $validProviders = ['radius'];

    private $authProviders = ['order' => ['radius'], 'radius' => ['servers' => []]];

    /**
     * Constructor.
     *
     * @param string $sessionEncryptionKey
     * @param string $sessionName
     * @param string $sessionDomain
     * @param string $sessionPath
     * @param int    $sessionIdleTime
     * @param int    $sessionLifetime
     * @param bool   $sessionSecure
     */
    public function __construct(
        private readonly string $sessionEncryptionKey = 'eaChey8quoo1ahch0en9eebe',
        private readonly string $sessionName = 'ssoauth',
        private readonly string $sessionDomain = '',
        private readonly string $sessionPath = '/',
        private readonly int $sessionIdleTime = 3600,
        private readonly int $sessionLifetime = 0,
        private readonly bool $sessionSecure = false
    ) {
    }

    /**
     * Login.
     *
     * @param string $username
     * @param string $password
     */
    public function login(string $username, string $password)
    {
        // Try the providers/servers in order of preference.
        //
        foreach ($this->authProviders['order'] as $provider) {
            if (!isset($this->authProviders[$provider]['servers']) || empty($this->authProviders[$provider]['servers'])) {
                throw new AuthException(sprintf('No servers found for provider: %s', $provider));
            }
            $result = $this->doLogin($provider, $username, $password);

            // Found a valid login, create session.
            //
            if ($result) {
                $this->loginSuccess($username, $result);

                return true;
            }
        }

        return false;
    }

    /**
     * Logout.
     */
    public function logout()
    {
        session_name($this->sessionName);

        // Unset all of the session variables.
        $_SESSION = [];

        // If it's desired to kill the session, also delete the session cookie.
        // Note: This will destroy the session, and not just the session data!
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }

        session_destroy();

        unset($_SESSION);

        return true;
    }

    /**
     * Check for valid login.
     */
    public function checkLogin()
    {
        // Make sure we save to files and set up encryption on the sessions files.
        ini_set('session.save_handler', 'files');
        $handler = new EncryptedSessionHandler($this->sessionEncryptionKey);
        session_set_save_handler($handler, true);

        // Set session name and set up the session cookie values.
        session_name($this->sessionName);
        session_set_cookie_params($this->sessionLifetime, $this->sessionPath, $this->sessionDomain, $this->sessionSecure);

        // Start the Session.
        session_start();

        // Check session says we are logged in.
        //
        if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']) {
            // Compare the signature on the session.
            if (isset($_SESSION['signature']) && $this->compareSignature()) {
                // Make sure the idle time has not expired.
                if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] < $this->sessionIdleTime)) {
                    // Update activity time.
                    $_SESSION['LAST_ACTIVITY'] = time();

                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Get IP Address from request.
     */
    public function getIp()
    {
        // Check IP address either in X-Forward-For or Remote-Addr
        //
        if (true === isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        }
        if (true === isset($_SERVER['REMOTE_ADDR'])) {
            return $_SERVER['REMOTE_ADDR'];
        }

        return '(unknown_ip_address)';
    }

    /**
     * Get token key for form field name.
     */
    public function getFormTokenKey()
    {
        return md5($_SERVER['PHP_SELF']);
    }

    /**
     * Get the form token.
     */
    public function getFormToken()
    {
        return md5(uniqid($_SERVER['PHP_SELF'], true));
    }

    /**
     * Set the authenticion provider order.
     *
     * @param array $order
     */
    public function setProviderOrder(array $order)
    {
        $invalidProviders = array_diff($order, $this->validProviders);
        if (count($invalidProviders)) {
            throw new AuthException('Unknown authentication providers found: '.join(', ', $invalidProviders));
        }

        $this->authProviders['order'] = $order;
    }

    /**
     * Compare the user signatre with the session signature.
     */
    private function compareSignature()
    {
        if (isset($_SESSION['signature'])) {
            $signature = $this->generateSignature($_SESSION['signature']);
            if ($signature === $_SESSION['signature']) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generate the session signater.
     *
     * @param string $salt
     */
    private function generateSignature(string $salt = null)
    {
        $useragent = md5($_SERVER['HTTP_USER_AGENT']);
        $ip = md5($this->getIp());

        return $this->generateHash($ip.$useragent, $salt);
    }

    /**
     * Generate hash for signature.
     *
     * @param string $text
     * @param string $salt
     * @param int    $length
     */
    private function generateHash(string $text, string $salt = null, int $length = 24)
    {
        if (null === $salt) {
            $salt = bin2hex(random_bytes($length / 2));
        // $salt = substr(md5(uniqid(rand(), true)), 0, $length);
        } else {
            $salt = substr($salt, 0, $length);
        }

        return $salt.hash('sha512', $salt.$text);
    }

    /**
     * Add radius server to auth providers.
     *
     * @param string $hostname
     * @param int    $port
     * @param string $secret
     * @param int    $timeout
     * @param int    $max_tries
     */
    public function addRadiusServer(string $hostname, int $port = 1812, string $secret = 'secret', int $timeout = 5, int $max_tries = 3)
    {
        $this->authProviders['radius']['servers'][$hostname] = ['port' => $port, 'secret' => $secret, 'timeout' => $timeout, 'max_tries' => $max_tries];
    }

    /**
     * Setup session on sucessful login.
     *
     * @param string $username
     */
    private function loginSuccess(string $username)
    {
        session_regenerate_id();
        $_SESSION['username'] = $username;
        $_SESSION['logged_in'] = true;
        $_SESSION['LAST_ACTIVITY'] = time();
        $_SESSION['signature'] = $this->generateSignature();

        return true;
    }

    /**
     * Do login against all providers.
     *
     * @param string $provider
     * @param string $username
     * @param string $password
     */
    private function doLogin(string $provider, string $username, string $password)
    {
        if ('radius' === $provider) {
            return $this->loginRadius($username, $password);
        }
        throw new AuthException('Authentication provider not supported.');
    }

    /**
     * Login to the radius server.
     *
     * @param string $username
     * @param string $password
     */
    private function loginRadius(string $username, string $password)
    {
        $radius = new DRadius();

        foreach ($this->authProviders['radius']['servers'] as $hostname => $server) {
            $radius->setServer($hostname)->setSecret($server['secret']);

            $result = $radius->accessRequest($username, $password, $server['timeout']);

            if (true === $result) {
                return true;
            } elseif (DRadius::TYPE_ACCESS_REJECT === $radius->getErrorCode()) {
                throw new AuthException('Authentication failed.');
            }
            throw new AuthException('Unknown authentication provider error.');
        }

        return false;
    }
}
