<?php

// AUTO-GENERATED FILE -- Civix may overwrite any changes made to this file
// This is a polyfill to allow mixins on older versions of Civi.
//
// Of course, the whole point of mixins is to replace boilerplate like this.
// But you can't completely remove the boilerplate without a compatibility-break.
// This file should become runtime-irrelevant as sites upgrade.

use CRM_Shimmy_ExtensionUtil as E;

/**
 * Get a list of mixins to enable by defult
 * @return string[]
 */
function _shimmy_civix_mixin_defaults() {
  static $list;
  if ($list === NULL) {
    $list = array_map(
      function($f) {
        return str_replace('.mixin.php', '', basename($f));
      },
      (array) glob(__DIR__ . '/mixin/*.mixin.php')
    );
  }
  return $list;
}

/**
 * When deploying on systems that lack mixin support, fake it.
 *
 * This polyfill does some (persnickity) deduplication, but it doesn't allow upgrades or shipping replacements in core.
 *
 * @param string[] $mixins
 *   Symbolic names. Only use mixins that are shipped with this extension.
 */
function _shimmy_civix_mixin($mixins) {
  // Construct imitations of the mixin services. These cannot work as well (e.g. with respect to
  // number of file-reads, deduping, upgrading)... but they should be OK for a few months while
  // the mixin services become available.

  // Imitate CRM_Extension_MixInfo.
  $mixInfo = new class() {

    /**
     * @var string
     */
    public $longName;

    /**
     * @var string
     */
    public $shortName;

    public function getPath($relPath = NULL) {
      return E::path($relPath);
    }

    public function isActive() {
      return \CRM_Extension_System::singleton()->getMapper()->isActiveModule(E::SHORT_NAME);
    }

  };
  $mixInfo->longName = E::LONG_NAME;
  $mixInfo->shortName = E::SHORT_NAME;

  // Imitate CRM_Extension_BootCache.
  $bootCache = new class() {

    public function define($name, $callback) {
      $envId = \CRM_Core_Config_Runtime::getId();
      $oldExtCachePath = \Civi::paths()->getPath("[civicrm.compile]/CachedExtLoader.{$envId}.php");
      $stat = stat($oldExtCachePath);
      $file = Civi::paths()->getPath('[civicrm.compile]/CachedMixin.' . md5($name . ($stat['mtime'] ?? 0)) . '.php');
      if (file_exists($file)) {
        return include $file;
      }
      else {
        $data = $callback();
        file_put_contents($file, '<' . "?php\nreturn " . var_export($data, 1) . ';');
        return $data;
      }
    }

  };

  // Imitate CRM_Extension_MixinLoader::run()
  // Parse all live mixins before trying to scan any classes.
  global $_CIVIX_MIXIN_POLYFILL;
  foreach ($mixins as $mixin) {
    // If the exact same mixin is defined by multiple exts, just use the first one.
    if (!isset($_CIVIX_MIXIN_POLYFILL[$mixin])) {
      $_CIVIX_MIXIN_POLYFILL[$mixin] = include_once __DIR__ . '/mixin/' . $mixin . '.mixin.php';
    }
  }
  foreach ($mixins as $mixin) {
    // If there's trickery about installs/uninstalls/resets, then we may need to register a second time.
    if (!isset(\Civi::$statics[__FUNCTION__][$mixin])) {
      \Civi::$statics[__FUNCTION__][$mixin] = 1;
      $func = $_CIVIX_MIXIN_POLYFILL[$mixin];
      $func($mixInfo, $bootCache);
    }
  }
}
