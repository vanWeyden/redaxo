<?php

class rex_fragment
{
  /**
   * filename of the actual fragmentfile
   * @var string
   */
  private $filename;

  /**
   * key-value pair which represents all variables defined inside the fragment
   * @var array
   */
  private $vars;

  /**
   * another fragment which can optionaly be used to decorate the current fragment
   * @var rex_fragment
   */
  private $decorator;

  /**
   * Creates a fragment with the given variables.
   *
   * @param array $params A array of key-value pairs to pass as local parameters
   */
  public function __construct(array $vars = array())
  {
    $this->vars = $vars;
  }

  /**
   * Set the variable $name to the given value.
   *
   * @param $name string The name of the variable.
   * @param $value mixed The value for the variable
   * @param $escape Flag which indicates if the value should be escaped or not.
   */
  public function setVar($name, $value, $escape = true)
  {
    if (is_null($name)) {
      throw new rex_exception(sprintf('Expecting $name to be not null!'));
    }

    if ($escape) {
      $this->vars[$name] = $this->escape($value);
    } else {
      $this->vars[$name] = $value;
    }
  }

  /**
   * Parses the variables of the fragment into the file $filename
   *
   * @param string $filename the filename of the fragment to parse.
   */
  public function parse($filename, $delete_whitespaces = true)
  {
    if (!is_string($filename)) {
      throw new rex_exception(sprintf('Expecting $filename to be a string, %s given!', gettype($filename)));
    }

    $this->filename = $filename;

    foreach (self::$fragmentDirs as $fragDir) {
      $fragment = $fragDir . $filename;
      if (is_readable($fragment)) {
        ob_start();
        if ($delete_whitespaces)
          preg_replace('/(?:(?<=\>)|(?<=\/\>))(\s+)(?=\<\/?)/', '', require $fragment);
        else
          require $fragment;

        $content =  ob_get_clean();

        if ($this->decorator) {
          $this->decorator->setVar('rexDecoratedContent', $content, false);
          $content = $this->decorator->parse($this->decorator->filename);
        }

        return $content;
      }
    }

    throw new rex_exception(sprintf('Fragmentfile "%s" not found!', $filename));
  }

  /**
   * Decorate the current fragment, with another fragment.
   * The decorated fragment receives the parameters which are passed to this method.
   *
   * @param string $filename The filename of the fragment used for decoration
   * @param array  $params   A array of key-value pairs to pass as parameters
   */
  public function decorate($filename, array $params)
  {
    $this->decorator = new self($params);
    $this->decorator->filename = $filename;
  }
  // -------------------------- in-fragment helpers

  /**
   * Escapes the value $val for proper use in the gui
   *
   * @param mixed $val the value to escape
   */
  protected function escape($val)
  {
    if (is_array($val)) {
      // iterate over the whole array
      foreach ($val as $k => $v) {
        $val[$k] = $this->escape($v);
      }
      return $val;
    } elseif (is_object($val)) {
      // iterate over all public properties
      foreach (get_object_vars($val) as $k => $v) {
        $val->$k = $this->escape($v);
      }
      return $val;
    } elseif (is_string($val)) {
      return htmlspecialchars($val);
    } elseif (is_scalar($val)) {
      return $val;
    } elseif (is_null($val)) {
      return $val;
    } else {
      throw new rex_exception(sprintf('Unexpected type for $val, "%s" given', gettype($val)));
    }
  }

  /**
   * Include a Subfragment from within a fragment.
   *
   * The Subfragment gets all variables of the current fragment, plus optional overrides from $params
   *
   * @param string $filename The filename of the fragment to use
   * @param array  $params   A array of key-value pairs to pass as local parameters
   */
  protected function subfragment($filename, array $params = array())
  {
    $fragment = new self(array_merge($this->vars, $params));
    echo $fragment->parse($filename);
  }

  /**
   * Translate the given key $key.
   *
   * @param string $key The key to translate
   */
  protected function i18n($key)
  {
    if (!is_string($key)) {
      throw new rex_exception(sprintf('Expecting $key to be a string, %s given!', gettype($key)));
    }

    // use the magic call only when more than one parameter is passed along,
    // to get best performance
    $argNum = func_num_args();
    if ($argNum > 1) {
      // pass along all given parameters
      $args = func_get_args();
      return call_user_func_array(array('rex_i18n', 'msg'), $args);
    }

    return rex_i18n::msg($key);
  }

  /**
   * Returns the config for key $key.
   * Enter description here ...
   * @param $key
   */
  protected function config($key)
  {
    if (!is_string($key)) {
      throw new rex_exception(sprintf('Expecting $key to be a string, %s given!', gettype($key)));
    }

    return rex::getProperty($key);
  }

  /**
   * Generates a url with the given parameters
   */
  protected function url(array $params = array())
  {
    if (!is_array($params)) {
      throw new rex_exception(sprintf('Expecting $params to be a array, %s given!', gettype($filename)));
    }

    if (!isset($params['page'])) {
      $page = rex_request('page');
      if ($page != null) {
        $params['page'] = $page;
      }
    }

    $url = 'index.php?';
    foreach ($params as $key => $value) {
      $url .= $key . '=' . urlencode($value) . '&';
    }
    return substr($url, 0, -1);
  }


  /**
   * Magic getter to reference variables from within the fragment.
   *
   * @param string $name The name of the variable to get.
   */
  public function __get($name)
  {
    if (array_key_exists($name, $this->vars)) {
      return $this->vars[$name];
    }

    trigger_error(sprintf('Undefined variable "%s" in rex_fragment "%s"', $name, $this->filename), E_USER_WARNING);

    return null;
  }

  /**
   * Magic method to check if a variable is set.
   *
   * @param string $name The name of the variable to check.
   * @return boolean
   */
  public function __isset($name)
  {
    return array_key_exists($name, $this->vars);
  }

  // /-------------------------- in-fragment helpers

  /**
   * array which contains all folders in which fragments will be searched for at runtime
   * @var array
   */
  static private $fragmentDirs = array();

  /**
   * Add a path to the fragment search path
   *
   * @param string $path A path to a directory where fragments can be found
   */
  static public function addDirectory($path)
  {
    // add the new directory in front of the already know dirs,
    // so a later caller can override core settings/fragments
    array_unshift(self::$fragmentDirs, $path);
  }
}
