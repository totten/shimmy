<?php

/**
 * @param CRM_Extension_MixInfo $mixInfo
 *   On newer deployments, this will be an instance of MixInfo. On older deployments, Civix may polyfill with a work-a-like.
 * @param \CRM_Extension_BootCache $bootCache
 *   On newer deployments, this will be an instance of MixInfo. On older deployments, Civix may polyfill with a work-a-like.
 */
return function ($mixInfo, $bootCache) {

  /**
   * Auto-register "ang/*.ang.php" files.
   *
   * @param \Civi\Core\Event\GenericHookEvent $e
   * @see CRM_Utils_Hook::angularModules()
   */
  Civi::dispatcher()->addListener('hook_civicrm_angularModules', function ($e) use ($mixInfo) {
    // When deactivating on a polyfill/pre-mixin system, listeners may not cleanup automatically.
    if (!$mixInfo->isActive() || !is_dir($mixInfo->getPath('ang'))) {
      return;
    }

    $files = (array) glob($mixInfo->getPath('ang/*.ang.php'));
    foreach ($files as $file) {
      $name = preg_replace(':\.ang\.php$:', '', basename($file));
      $module = include $file;
      if (empty($module['ext'])) {
        $module['ext'] = $mixInfo->longName;
      }
      $e->angularModules[$name] = $module;
    }
  });

  /**
   * Auto-register "*.theme.php" files.
   *
   * @param \Civi\Core\Event\GenericHookEvent $e
   * @see CRM_Utils_Hook::themes()
   */
  Civi::dispatcher()->addListener('hook_civicrm_themes', function ($e) use ($mixInfo) {
    // When deactivating on a polyfill/pre-mixin system, listeners may not cleanup automatically.
    if (!$mixInfo->isActive()) {
      return;
    }
    $files = (array) glob($mixInfo->getPath('*.theme.php'));
    foreach ($files as $file) {
      $themeMeta = include $file;
      if (empty($themeMeta['name'])) {
        $themeMeta['name'] = preg_replace(':\.theme\.php$:', '', basename($file));
      }
      if (empty($themeMeta['ext'])) {
        $themeMeta['ext'] = $mixInfo->longName;
      }
      $e->themes[$themeMeta['name']] = $themeMeta;
    }
  });

};
