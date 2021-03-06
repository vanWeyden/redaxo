<?php

/**
 * Manager class for packages
 */
abstract class rex_package_manager extends rex_factory_base
{
  const
    PACKAGE_FILE = 'package.yml',
    CONFIG_FILE = 'config.inc.php',
    INSTALL_FILE = 'install.inc.php',
    INSTALL_SQL = 'install.sql',
    UNINSTALL_FILE = 'uninstall.inc.php',
    UNINSTALL_SQL = 'uninstall.sql',
    ASSETS_FOLDER = 'assets';

  /**
   * @var rex_package
   */
  protected $package;

  protected $generatePackageOrder = true;

  private $i18nPrefix;

  private $message;

  /**
   * Constructor
   *
   * @param rex_package $package    Package
   * @param string      $i18nPrefix Prefix for i18n
   */
  protected function __construct(rex_package $package, $i18nPrefix)
  {
    $this->package = $package;
    $this->i18nPrefix = $i18nPrefix;
  }

  /**
   * Creates the manager for the package
   *
   * @param rex_package $package Package
   *
   * @return rex_package_manager
   */
  static public function factory(rex_package $package)
  {
    if (get_called_class() == __CLASS__) {
      $class = $package instanceof rex_plugin ? 'rex_plugin_manager' : 'rex_addon_manager';
      return $class::factory($package);
    }
    $class = static::getFactoryClass();
    return new $class($package);
  }

  /**
   * Returns the message
   *
   * @return string
   */
  public function getMessage()
  {
    return $this->message;
  }

  /**
   * Installs a package
   *
   * @param $installDump When TRUE, the sql dump will be importet
   *
   * @return boolean TRUE on success, FALSE on error
   */
  public function install($installDump = true)
  {
    $state = true;

    $install_dir  = $this->package->getBasePath();
    $package_file = $install_dir . self::PACKAGE_FILE;
    $install_file = $install_dir . self::INSTALL_FILE;
    $install_sql  = $install_dir . self::INSTALL_SQL;
    $config_file  = $install_dir . self::CONFIG_FILE;
    $files_dir    = $install_dir . self::ASSETS_FOLDER;

    // Pruefen des Addon Ornders auf Schreibrechte,
    // damit das Addon spaeter wieder geloescht werden kann
    if (!rex_dir::isWritable($install_dir)) {
      $state = $this->i18n('dir_not_writable', $install_dir);
    }

    if ($state === true) {
      if (!is_readable($package_file)) {
        $state = $this->i18n('missing_yml_file');
      } else {
        $packageId = $this->package->getProperty('package');
        if ($packageId === null) {
          $state = $this->i18n('missing_id', $this->package->getPackageId());
        } elseif ($packageId != $this->package->getPackageId()) {
          $parts = explode('/', $packageId, 2);
          $state = $this->wrongPackageId($parts[0], isset($parts[1]) ? $parts[1] : null);
        } elseif ($this->package->getVersion() === null) {
          $state = $this->i18n('missing_version');
        }
      }
    }

    // check if requirements are met
    if ($state === true) {
      $state = $this->checkRequirements();
    }

    $this->package->setProperty('install', true);

    // check if install.inc.php exists
    if ($state === true && is_readable($install_file)) {
      rex_autoload::addDirectory($this->package->getBasePath('lib'));
      rex_autoload::addDirectory($this->package->getBasePath('vendor'));
      try {
        static::includeFile($this->package, self::INSTALL_FILE);
        // Wurde das "install" Flag gesetzt?
        // Fehlermeldung ausgegeben? Wenn ja, Abbruch
        if (($instmsg = $this->package->getProperty('installmsg', '')) != '') {
          $state = $instmsg;
        } elseif (!$this->package->isInstalled()) {
          $state = $this->i18n('no_reason');
        }
      } catch (rex_functional_exception $e) {
        $state = $e->getMessage();
      } catch (rex_sql_exception $e) {
        $state = 'SQL error: ' . $e->getMessage();
      }
    }

    if ($state === true && $installDump === true && is_readable($install_sql)) {
      $state = rex_sql_util::importDump($install_sql);

      if ($state !== true)
        $state = 'Error found in install.sql:<br />' . $state;
    }

    // Installation ok
    if ($state === true) {
      $this->saveConfig();
    }

    // Dateien kopieren
    if ($state === true && is_dir($files_dir)) {
      if (!rex_dir::copy($files_dir, $this->package->getAssetsPath())) {
        $state = $this->i18n('install_cant_copy_files');
      }
    }

    if ($state !== true) {
      $this->package->setProperty('install', false);
      $state = $this->i18n('no_install', $this->package->getName()) . '<br />' . $state;
    }

    $this->message = $state === true ? $this->i18n('installed', $this->package->getName()) : $state;

    return $state === true;
  }

  /**
   * Uninstalls a package
   *
   * @param $installDump When TRUE, the sql dump will be importet
   *
   * @return boolean TRUE on success, FALSE on error
   */
  public function uninstall($installDump = true)
  {
    $state = true;

    $install_dir    = $this->package->getBasePath();
    $uninstall_file = $install_dir . self::UNINSTALL_FILE;
    $uninstall_sql  = $install_dir . self::UNINSTALL_SQL;

    $isActivated = $this->package->isActivated();
    if ($isActivated) {
      $state = $this->deactivate();
      if ($state !== true) {
        return $state;
      }
    }

    // start un-installation
    $this->package->setProperty('install', false);

    // check if uninstall.inc.php exists
    if ($state === true && is_readable($uninstall_file)) {
      try {
        static::includeFile($this->package, self::UNINSTALL_FILE);
        // Wurde das "install" Flag gesetzt?
        // Fehlermeldung ausgegeben? Wenn ja, Abbruch
        if (($instmsg = $this->package->getProperty('installmsg', '')) != '') {
          $state = $instmsg;
        } elseif ($this->package->isInstalled()) {
          $state = $this->i18n('no_reason');
        }
      } catch (rex_functional_exception $e) {
        $state = $e->getMessage();
      } catch (rex_sql_exception $e) {
        $state = 'SQL error: ' . $e->getMessage();
      }
    }

    if ($state === true && $installDump === true && is_readable($uninstall_sql)) {
      $state = rex_sql_util::importDump($uninstall_sql);

      if ($state !== true)
        $state = 'Error found in uninstall.sql:<br />' . $state;
    }

    $mediaFolder = $this->package->getAssetsPath();
    if ($state === true && is_dir($mediaFolder)) {
      if (!rex_dir::delete($mediaFolder)) {
        $state = $this->i18n('install_cant_delete_files');
      }
    }

    if ($state === true) {
      rex_config::removeNamespace($this->package->getPackageId());
    }

    if ($state !== true) {
      // Fehler beim uninstall -> Addon bleibt installiert
      $this->package->setProperty('install', true);
      if ($isActivated) {
        $this->package->setProperty('status', true);
      }
      $this->saveConfig();
      $state = $this->i18n('no_uninstall', $this->package->getName()) . '<br />' . $state;
    } else {
      $this->saveConfig();
    }

    $this->message = $state === true ? $this->i18n('uninstalled', $this->package->getName()) : $state;

    return $state === true;
  }

  /**
   * Activates a package
   *
   * @return boolean TRUE on success, FALSE on error
   */
  public function activate()
  {
    try {
      if ($this->package->isInstalled()) {
        $state = $this->checkRequirements();

        if ($state === true) {
          $this->package->setProperty('status', true);
          if (!rex::isSetup()) {
            if (is_readable($this->package->getBasePath(self::CONFIG_FILE))) {
              rex_autoload::addDirectory($this->package->getBasePath('lib'));
              rex_autoload::addDirectory($this->package->getBasePath('vendor'));
              static::includeFile($this->package, self::CONFIG_FILE);
            }
          }
          $this->saveConfig();
        }
        if ($state === true && $this->generatePackageOrder) {
          self::generatePackageOrder();
        }
      } else {
        $state = $this->i18n('not_installed', $this->package->getName());
      }
    }
    // addon-code which will be included might throw exception
    catch (Exception $e) {
      $state = $e->getMessage();
    }

    if ($state !== true) {
      // error while config generation, rollback addon status
      $this->package->setProperty('status', false);
      $state = $this->i18n('no_activation', $this->package->getName()) . '<br />' . $state;
    }

    $this->message = $state === true ? $this->i18n('activated', $this->package->getName()) : $state;

    return $state === true;
  }

  /**
   * Deactivates a package
   *
   * @return boolean TRUE on success, FALSE on error
   */
  public function deactivate()
  {
    try {
      $state = $this->checkDependencies();

      if ($state === true) {
        $this->package->setProperty('status', false);
        $this->saveConfig();
      }

      if ($state === true) {
        // reload autoload cache when addon is deactivated,
        // so the index doesn't contain outdated class definitions
        rex_autoload::removeCache();

        if ($this->generatePackageOrder) {
          self::generatePackageOrder();
        }
      } else {
        $state = $this->i18n('no_deactivation', $this->package->getName()) . '<br />' . $state;
      }
    }
    // addon-code which will be included might throw exception
    catch (Exception $e) {
      $state = $e->getMessage();
    }

    $this->message = $state === true ? $this->i18n('deactivated', $this->package->getName()) : $state;

    return $state === true;
  }

  /**
   * Deletes a package
   *
   * @return boolean TRUE on success, FALSE on error
   */
  public function delete()
  {
    $state = $this->_delete();
    self::synchronizeWithFileSystem();
    return $state;
  }

  /**
   * Deletes a package
   *
   * @param boolean $ignoreState
   *
   * @return boolean TRUE on success, FALSE on error
   */
  protected function _delete($ignoreState = false)
  {
    try {
      if (!$ignoreState && $this->package->isSystemPackage())
        return $this->i18n('systempackage_delete_not_allowed');

      // zuerst deinstallieren
      // bei erfolg, komplett löschen
      $state = true;
      $state = ($ignoreState || $state) && (!$this->package->isInstalled() || $this->uninstall());
      $state = ($ignoreState || $state) && rex_dir::delete($this->package->getBasePath());
      $state = ($ignoreState || $state) && rex_dir::delete($this->package->getDataPath());
      if (!$ignoreState) {
        $this->saveConfig();
      }
    }
    // addon-code which will be included might throw exception
    catch (Exception $e) {
      $state = $e->getMessage();
    }

    $this->message = $state === true ? $this->i18n('deleted', $this->package->getName()) : $state;

    return $ignoreState ? true : $state === true;
  }

  /**
   * @param string $addonName
   * @param string $pluginName
   * @return string
   */
  abstract protected function wrongPackageId($addonName, $pluginName = null);

  /**
   * Checks whether the requirements are met.
   */
  public function checkRequirements()
  {
    $state = array();
    $requirements = $this->package->getProperty('requires', array());

    if (($msg = $this->checkRedaxoRequirement(rex::getVersion())) !== true) {
      return $msg;
    }

    if (isset($requirements['php']) && is_array($requirements['php'])) {
      if (($msg = $this->checkRequirementVersion('php_', $requirements['php'], PHP_VERSION)) !== true) {
        $state[] = $msg;
      }
      if (isset($requirements['php']['extensions']) && $requirements['php']['extensions']) {
        $extensions = (array) $requirements['php']['extensions'];
        foreach ($extensions as $reqExt) {
          if (is_string($reqExt) && !extension_loaded($reqExt)) {
            $state[] = $this->i18n('requirement_error_php_extension', $reqExt);
          }
        }
      }
    }

    if (empty($state) && isset($requirements['addons']) && is_array($requirements['addons'])) {
      foreach ($requirements['addons'] as $addonName => $addonAttr) {
        if (($msg = $this->checkPackageRequirement($addonName)) !== true) {
          $state[] = $msg;
        }

        if (isset($addonAttr['plugins']) && is_array($addonAttr['plugins'])) {
          foreach ($addonAttr['plugins'] as $pluginName => $pluginAttr) {
            if (($msg = $this->checkPackageRequirement($addonName . '/' . $pluginName)) !== true) {
              $state[] = $msg;
            }
          }
        }
      }
    }

    return empty($state) ? true : implode('<br />', $state);
  }

  /**
   * Checks whether the redaxo requirement is met.
   *
   * @param string $redaxoVersion REDAXO version
   */
  public function checkRedaxoRequirement($redaxoVersion)
  {
    $requirements = $this->package->getProperty('requires', array());
    if (isset($requirements['redaxo']) && is_array($requirements['redaxo'])) {
      return $this->checkRequirementVersion('redaxo_', $requirements['redaxo'], $redaxoVersion);
    }
    return true;
  }

  /**
   * Checks whether the package requirement is met.
   *
   * @param string $packageId Package ID
   */
  public function checkPackageRequirement($packageId)
  {
    $requirements = $this->package->getProperty('requires', array());
    list($addonName, $pluginName) = array_pad(explode('/', $packageId), 2, null);
    $type = $pluginName === null ? 'addon' : 'plugin';
    if (!isset($requirements['addons'][$addonName]) || $type == 'plugin' && !isset($requirements['addons'][$addonName]['plugins'][$pluginName])) {
      return true;
    }
    $package = $type == 'plugin' ? rex_plugin::get($addonName, $pluginName) : rex_addon::get($addonName);
    if (!$package->isAvailable()) {
      return $this->i18n('requirement_error_' . $type, $addonName, $pluginName);
    }
    $attr = $type == 'plugin' ? $requirements['addons'][$addonName]['plugins'][$pluginName] : $requirements['addons'][$addonName];
    return $this->checkRequirementVersion($type . '_', $attr, $package->getVersion(), $addonName, $pluginName);
  }

  /**
   * Checks the version of the requirement.
   *
   * @param string $i18nPrefix Prefix for I18N
   * @param array  $attributes Requirement attributes (version, min-version, max-version)
   * @param string $version    Active version of requirement
   * @param string $addonName  Name of the required addon, only necessary if requirement is a addon/plugin
   * @param string $pluginName Name of the required plugin, only necessary if requirement is a plugin
   */
  private function checkRequirementVersion($i18nPrefix, array $attributes, $version, $addonName = null, $pluginName = null)
  {
    $i18nPrefix = 'requirement_error_' . $i18nPrefix;
    $state = true;

    // check dependency exact-version
    if (isset($attributes['version']) && rex_string::compareVersions($version, $attributes['version'], '!=')) {
      $state = $this->i18n($i18nPrefix . 'exact_version', $attributes['version'], $version, $addonName, $pluginName);
    } else {
      // check dependency min-version
      if (isset($attributes['min-version']) && rex_string::compareVersions($version, $attributes['min-version'], '<')) {
        $state = $this->i18n($i18nPrefix . 'min_version', $attributes['min-version'], $version, $addonName, $pluginName);
      }
      // check dependency max-version
      elseif (isset($attributes['max-version']) && rex_string::compareVersions($version, $attributes['max-version'], '>')) {
        $state = $this->i18n($i18nPrefix . 'max_version', $attributes['max-version'], $version, $addonName, $pluginName);
      }
    }
    return $state;
  }

  /**
   * Checks if another Addon which is activated, depends on the given addon
   */
  abstract public function checkDependencies();

  /**
   * Generates the package order
   */
  static protected function generatePackageOrder()
  {
    $early = array();
    $normal = array();
    $late = array();
    $requires = array();
    $add = function ($id) use (&$add, &$normal, &$requires) {
      $normal[] = $id;
      unset($requires[$id]);
      foreach ($requires as $rp => &$ps) {
        unset($ps[$id]);
        if (empty($ps)) {
          $add($rp);
        }
      }
    };
    foreach (rex_package::getAvailablePackages() as $package) {
      $id = $package->getPackageId();
      $load = $package->getProperty('load');
      if ($package instanceof rex_plugin
        && !in_array($load, array('early', 'normal', 'late'))
        && in_array($addonLoad = $package->getAddon()->getProperty('load'), array('early', 'late'))
      ) {
        $load = $addonLoad;
      }
      if ($load === 'early') {
        $early[] = $id;
      } elseif ($load === 'late') {
        $late[] = $id;
      } else {
        $req = $package->getProperty('requires');
        if ($package instanceof rex_plugin) {
          $req['addons'][$package->getAddon()->getName()] = true;
        }
        if (isset($req['addons']) && is_array($req['addons'])) {
          foreach ($req['addons'] as $addonId => $reqP) {
            $addon = rex_addon::get($addonId);
            if (!in_array($addon, $normal) && !in_array($addon->getProperty('load'), array('early', 'late'))) {
              $requires[$id][$addonId] = true;
            }
            if (isset($reqP['plugins']) && is_array($reqP['plugins'])) {
              foreach ($reqP['plugins'] as $pluginName => $_) {
                $plugin = $addon->getPlugin($pluginName);
                $pluginId = $plugin->getPackageId();
                if (!in_array($pluginId, $normal) && !in_array($plugin->getProperty('load'), array('early', 'late'))) {
                  $requires[$id][$pluginId] = true;
                }
              }
            }
          }
        }
        if (!isset($requires[$id])) {
          $add($id);
        }
      }
    }
    rex::setConfig('package-order', array_merge($early, $normal, array_keys($requires), $late));
  }

  /**
   * Translates the given key
   *
   * @param string $key Key
   *
   * @return string Tranlates text
   */
  protected function i18n()
  {
    $args = func_get_args();
    $key = $this->i18nPrefix . $args[0];
    if (!rex_i18n::hasMsg($key)) {
      $key = 'package_' . $args[0];
    }
    $args[0] = $key;

    return call_user_func_array(array('rex_i18n', 'rawMsg'), $args);
  }

  /**
   * Includes a file inside the package context
   *
   * @param rex_package $package Package
   * @param string      $file
   */
  static public function includeFile(rex_package $package, $file)
  {
    if (get_called_class() == __CLASS__) {
      $class = $package instanceof rex_plugin ? 'rex_plugin_manager' : 'rex_addon_manager';
      return $class::includeFile($package, $file);
    }
    if (static::hasFactoryClass()) {
      return static::callFactoryClass(__FUNCTION__, func_get_args());
    }
    return $package->includeFile($file);
  }

  /**
   * Saves the package config
   */
  static protected function saveConfig()
  {
    $config = array();
    foreach (rex_addon::getRegisteredAddons() as $addonName => $addon) {
      $config[$addonName]['install'] = $addon->isInstalled();
      $config[$addonName]['status'] = $addon->isActivated();
      foreach ($addon->getRegisteredPlugins() as $pluginName => $plugin) {
        $config[$addonName]['plugins'][$pluginName]['install'] = $plugin->isInstalled();
        $config[$addonName]['plugins'][$pluginName]['status'] = $plugin->isActivated();
      }
    }
    rex::setConfig('package-config', $config);
  }

  /**
   * Synchronizes the packages with the file system
   */
  static public function synchronizeWithFileSystem()
  {
    $config = rex::getConfig('package-config');
    $addons = self::readPackageFolder(rex_path::src('addons'));
    $registeredAddons = array_keys(rex_addon::getRegisteredAddons());
    foreach (array_diff($registeredAddons, $addons) as $addonName) {
      $manager = rex_addon_manager::factory(rex_addon::get($addonName));
      $manager->_delete(true);
      unset($config[$addonName]);
    }
    foreach ($addons as $addonName) {
      if (!rex_addon::exists($addonName)) {
        $config[$addonName]['install'] = false;
        $config[$addonName]['status'] = false;
        $registeredPlugins = array();
      } else {
        $addon = rex_addon::get($addonName);
        $config[$addonName]['install'] = $addon->isInstalled();
        $config[$addonName]['status'] = $addon->isActivated();
        $registeredPlugins = array_keys($addon->getRegisteredPlugins());
      }
      $plugins = self::readPackageFolder(rex_path::addon($addonName, 'plugins'));
      foreach (array_diff($registeredPlugins, $plugins) as $pluginName) {
        $manager = rex_plugin_manager::factory(rex_plugin::get($addonName, $pluginName));
        $manager->_delete(true);
        unset($config[$addonName]['plugins'][$pluginName]);
      }
      foreach ($plugins as $pluginName) {
        $plugin = rex_plugin::get($addonName, $pluginName);
        $config[$addonName]['plugins'][$pluginName]['install'] = $plugin->isInstalled();
        $config[$addonName]['plugins'][$pluginName]['status'] = $plugin->isActivated();
      }
      if (isset($config[$addonName]['plugins']) && is_array($config[$addonName]['plugins']))
        ksort($config[$addonName]['plugins']);
    }
    ksort($config);

    rex::setConfig('package-config', $config);
    rex_addon::initialize();
  }

  /**
   * Returns the subfolders of the given folder
   *
   * @param string $folder Folder
   */
  static private function readPackageFolder($folder)
  {
    $packages = array();

    if (is_dir($folder)) {
      foreach (rex_finder::factory($folder)->dirsOnly() as $file) {
        $packages[] = $file->getBasename();
      }
    }

    return $packages;
  }
}
