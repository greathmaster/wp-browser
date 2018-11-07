<?php

namespace Codeception\Module;

use Codeception\Exception\ModuleException;

/**
 * A Codeception module offering specific WordPress browsing methods.
 */
class WPWebDriver extends WebDriver
{
	use WPBrowserMethods;
	/**
	 * The module required fields, to be set in the suite .yml configuration file.
	 *
	 * @var array
	 */
	protected $requiredFields = ['adminUsername', 'adminPassword', 'adminPath'];

	/**
	 * The login screen absolute URL
	 *
	 * @var string
	 */
	protected $loginUrl;

	/**
	 * The admin absolute URL.
	 *
	 * @var string
	 */
	protected $adminPath;

	/**
	 * The plugin screen absolute URL
	 *
	 * @var string
	 */
	protected $pluginsPath;

	protected $loginAttempt = 0;

	/**
	 * Initializes the module setting the properties values.
	 * @return void
	 */
	public function _initialize()
	{
		parent::_initialize();

		$this->configBackCompat();

		$adminPath = $this->config['adminPath'];
		$this->loginUrl = str_replace('wp-admin', 'wp-login.php', $adminPath);
		$this->adminPath = rtrim($adminPath, '/');
		$this->pluginsPath = $this->adminPath . '/plugins.php';
	}

	protected function configBackCompat()
	{
		if (isset($this->config['adminUrl']) && !isset($this->config['adminPath'])) {
			$this->config['adminPath'] = $this->config['adminUrl'];
		}
	}

	/**
	 * Goes to the login page and logs in as the site admin.
	 *
	 * @param int $time
	 *
	 * @return array An array of login credentials and auth cookies.
	 */
	public function loginAsAdmin($time = 10)
	{
		return $this->loginAs($this->config['adminUsername'], $this->config['adminPassword'], $time);
	}

    /**
     * Goes to the login page, wait for the login form and logs in using the given credentials.
     *
     * Depending on the driven browser the login might be "too fast" and the server might have not
     * replied with valid cookies yet; in that case the method will re-attempt the login to obtain
     * the cookies.
     *
     * @param string $username
     * @param string $password
     * @param int    $timeout
     * @param int    $maxAttempts
     *
     * @return array An array of login credentials and auth cookies.
     *
     * @throws \Codeception\Exception\ModuleException If all the attempts of obtaining the cookie fail.
     */
	public function loginAs($username, $password, $timeout = 10, $maxAttempts = 5) {
        if ($this->loginAttempt === $maxAttempts) {
            throw new ModuleException(
                __CLASS__, "Could not login as [{$username}, {$password}] after {$maxAttempts} attempts."
            );
        }

        $this->debug("Trying to login, attempt {$this->loginAttempt}/{$maxAttempts}...");

        $this->amOnPage($this->loginUrl);

        $this->waitForElement('#user_login', $timeout);
        $this->waitForElement('#user_pass', $timeout);
        $this->waitForElement('#wp-submit', $timeout);

        $this->fillField('#user_login', $username);
        $this->fillField('#user_pass', $password);
        $this->click('#wp-submit');

        $authCookie = $this->grabWordPressAuthCookie();
        $loginCookie = $this->grabWordPressLoginCookie();
        $empty_cookies = empty($authCookie) && empty($loginCookie);

        if ($empty_cookies) {
            $this->loginAttempt++;
            $this->wait(1);
            return $this->loginAs($username, $password, $timeout, $maxAttempts);
        }

        $this->loginAttempt = 0;
        return [
            'username' => $username,
            'password' => $password,
            'authCookie' => $authCookie,
            'loginCookie' => $loginCookie,
        ];
	}

	/**
	 * Returns all the cookies whose name matches a regex pattern.
	 *
	 * @param string $cookiePattern
	 *
	 * @return array|null
	 */
	public function grabCookiesWithPattern($cookiePattern)
	{
		$cookies = $this->webDriver->manage()->getCookies();

		if (!$cookies) {
			return null;
		}
		$matchingCookies = array_filter($cookies, function ($cookie) use ($cookiePattern) {

			return preg_match($cookiePattern, $cookie['name']);
		});
		$cookieList = array_map(function ($cookie) {
			return sprintf('{"%s": "%s"}', $cookie['name'], $cookie['value']);
		}, $matchingCookies);

		$this->debug('Cookies matching pattern ' . $cookiePattern . ' : ' . implode(', ', $cookieList));

		return is_array($matchingCookies) ? $matchingCookies : null;
	}

	/**
	 * Waits for any jQuery triggered AJAX request to be resolved.
	 *
	 * @param int $time
	 */
	public function waitForJqueryAjax($time = 10)
	{
		return $this->waitForJS('return jQuery.active == 0', $time);
	}

	/**
	 * Grabs the current page full URL including the query vars.
	 */
	public function grabFullUrl()
	{
		return $this->executeJS('return location.href');
	}

	protected function validateConfig()
	{
		$this->configBackCompat();

		parent::validateConfig();
	}
}
