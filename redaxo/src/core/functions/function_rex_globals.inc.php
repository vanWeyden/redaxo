<?php
/**
 * Getter functions for the superglobals
 *
 * @package redaxo5
 */

/**
 * @see rex_request::get()
 */
function rex_get($varname, $vartype = '', $default = '')
{
  return rex_request::get($varname, $vartype, $default);
}

/**
 * @see rex_request::post()
 */
function rex_post($varname, $vartype = '', $default = '')
{
  return rex_request::post($varname, $vartype, $default);
}

/**
 * @see rex_request::request()
 */
function rex_request($varname, $vartype = '', $default = '')
{
  return rex_request::request($varname, $vartype, $default);
}

/**
 * @see rex_request::server()
 */
function rex_server($varname, $vartype = '', $default = '')
{
  return rex_request::server($varname, $vartype, $default);
}

/**
 * @see rex_request::session()
 */
function rex_session($varname, $vartype = '', $default = '')
{
  return rex_request::session($varname, $vartype, $default);
}

/**
 * @see rex_request::setSession()
 */
function rex_set_session($varname, $value)
{
  rex_request::setSession($varname, $value);
}

/**
 * @see rex_request::unsetSession()
 */
function rex_unset_session($varname)
{
  rex_request::unsetSession($varname);
}

/**
 * @see rex_request::cookie()
 */
function rex_cookie($varname, $vartype = '', $default = '')
{
  return rex_request::cookie($varname, $vartype, $default);
}

/**
 * @see rex_request::files()
 */
function rex_files($varname, $vartype = '', $default = '')
{
  return rex_request::files($varname, $vartype, $default);
}

/**
 * @see rex_request::env()
 */
function rex_env($varname, $vartype = '', $default = '')
{
  return rex_request::env($varname, $vartype, $default);
}

/**
 * @see rex_request::requestMethod()
 */
function rex_request_method()
{
  return rex_request::requestMethod();
}
