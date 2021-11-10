<?php

/**
 * @param CRM_Extension_MixInfo $mixInfo
 *   On newer deployments, this will be an instance of MixInfo. On older deployments, Civix may polyfill with a work-a-like.
 * @param \CRM_Extension_BootCache $bootCache
 *   On newer deployments, this will be an instance of MixInfo. On older deployments, Civix may polyfill with a work-a-like.
 */
return function ($mixInfo, $bootCache) {

  /**
   * Auto-register "xml/case/*.xml" files.
   *
   * @param \Civi\Core\Event\GenericHookEvent $e
   * @see CRM_Utils_Hook::caseTypes()
   */
  Civi::dispatcher()->addListener('hook_civicrm_caseTypes', function ($e) use ($mixInfo) {
    // When deactivating on a polyfill/pre-mixin system, listeners may not cleanup automatically.
    if (!$mixInfo->isActive() || !is_dir(__DIR__ . '/xml/case')) {
      return;
    }

    foreach ((array) glob($mixInfo->getPath('xml/case/*.xml')) as $file) {
      $name = preg_replace('/\.xml$/', '', basename($file));
      if ($name != CRM_Case_XMLProcessor::mungeCaseType($name)) {
        $errorMessage = sprintf("Case-type file name is malformed (%s vs %s)", $name, CRM_Case_XMLProcessor::mungeCaseType($name));
        throw new CRM_Core_Exception($errorMessage);
      }
      $e->caseTypes[$name] = [
        'module' => $mixInfo->longName,
        'name' => $name,
        'file' => $file,
      ];
    }
  });

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

  /**
   * Auto-register "settings/*.settings.php" files.
   *
   * @param \Civi\Core\Event\GenericHookEvent $e
   * @see CRM_Utils_Hook::alterSettingsFolders()
   */
  Civi::dispatcher()->addListener('hook_civicrm_alterSettingsFolders', function ($e) use ($mixInfo) {
    // When deactivating on a polyfill/pre-mixin system, listeners may not cleanup automatically.
    if (!$mixInfo->isActive()) {
      return;
    }

    $settingsDir = $mixInfo->getPath('settings');
    if (!in_array($settingsDir, $e->settingsFolders) && is_dir($settingsDir)) {
      $e->settingsFolders[] = $settingsDir;
    }
  });

  /**
   * Auto-register "**.mgd.php" files.
   *
   * @param \Civi\Core\Event\GenericHookEvent $e
   * @see CRM_Utils_Hook::managed()
   */

  Civi::dispatcher()->addListener('hook_civicrm_managed', function ($event) use ($mixInfo) {
    // When deactivating on a polyfill/pre-mixin system, listeners may not cleanup automatically.
    if (!$mixInfo->isActive()) {
      return;
    }

    $mgdFiles = CRM_Utils_File::findFiles($mixInfo->getPath(), '*.mgd.php');
    sort($mgdFiles);
    foreach ($mgdFiles as $file) {
      $es = include $file;
      foreach ($es as $e) {
        if (empty($e['module'])) {
          $e['module'] = $mixInfo->longName;
        }
        if (empty($e['params']['version'])) {
          $e['params']['version'] = '3';
        }
        $event->entities[] = $e;
      }
    }
  });

};
