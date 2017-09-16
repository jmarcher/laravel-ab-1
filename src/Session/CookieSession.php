<?php

namespace Jenssegers\AB\Session;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cookie;

class CookieSession implements SessionInterface {

    /**
     * The name of the cookie.
     *
     * @var string
     */
    protected $cookieName = 'ab';

    /**
     * A copy of the session data.
     *
     * @var array
     */
    protected $data = null;

    /**
     * Cookie lifetime.
     *
     * @var integer
     */
    protected $minutes = 60;

    /**
     * Constructor.
     */
    public function __construct()
    {
        // Set the minutes based on the config.
        if (Config::get('ab.lifetime')) {
            $this->minutes = Config::get('ab.lifetime');
        }

        // Set the cookieName based on the config.
        if (Config::get('ab.cookie')) {
            $this->cookieName = Config::get('ab.cookie');
        }

        $this->data = Cookie::get($this->cookieName, []);
    }

    /**
     * {@inheritdoc}
     */
    public function get($name, $default = null)
    {
        return array_get($this->data, $name, $default);
    }

    /**
     * {@inheritdoc}
     */
    public function set($name, $value)
    {
        $this->data[$name] = $value;

        return Cookie::queue($this->cookieName, $this->data, $this->minutes);
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        return Cookie::queue(Cookie::forget($this->cookieName));
    }

}
