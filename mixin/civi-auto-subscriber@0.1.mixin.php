<?php

namespace Civi\SubscriberMixinV0;

interface SubscriberInterface {

  /**
   * @return array
   *   Ex: [['event' => 'hook_civicrm_foo', 'method' => 'runFoo', 'priority' => 100]]
   */
  public static function getCiviSubscribers();

}

/**
 * Search for classes that implement an interface.
 *
 * @param string $extDir
 *   The base-dir of the extension.
 * @param string $interface
 *   The interface that we seek.
 * @return \ReflectionClass[]
 *   List of classes in $extDir that match the interface.
 */
function findInterfaces(string $extDir, string $interface) {
  $r = [];

  $srcDirs = ['CRM' => '_', 'Civi' => '\\'];
  foreach ($srcDirs as $srcDir => $classDelim) {
    $phpFiles = \CRM_Utils_File::findFiles($extDir . DIRECTORY_SEPARATOR . $srcDir, '*.php');
    foreach ($phpFiles as $phpFile) {
      $name = \CRM_Utils_File::relativize($phpFile, $extDir);
      $name = preg_replace(';\.php$;', '', $name);
      $name = trim(str_replace(DIRECTORY_SEPARATOR, '/', $name), '/');
      $name = str_replace('/', $classDelim, $name);
      $name = '\\' . $name;
      try {
        $clazz = new \ReflectionClass($name);
        if (in_array($interface, $clazz->getInterfaceNames())) {
          $r[] = $clazz;
        }
      }
      catch (\ReflectionException $e) {
        error_log(__NAMESPACE__ . ': Failed to scan class file ' . $phpFile);
      }
    }
  }

  return $r;
}

return function($mixInfo, $bootCache) {
  // File scans are expensive, but we need the info on every page-load. So use $bootCache.
  $listenerDefns = $bootCache->define(__NAMESPACE__ . ':' . $mixInfo->longName, function () use ($mixInfo) {
    $listenerDefns = [];
    foreach (findInterfaces($mixInfo->getPath(), SubscriberInterface::class) as $clazz) {
      /**
       * @var \ReflectionClass $clazz
       */
      $get = call_user_func([$clazz->getName(), 'getCiviSubscribers']);
      foreach ($get as $listenerDefn) {
        $listenerDefn['class'] = $clazz->getName();
        $listenerDefns[] = $listenerDefn;
      }
    }
    usort($listenerDefns, function($a, $b) {
      return strnatcmp(($a['class'] ?? '') . ($a['method'] ?? ''), ($b['class'] ?? '') . ($b['method'] ?? ''));
    });
    return $listenerDefns;
  });

  $listenerObjs = [];
  foreach ($listenerDefns as $l) {
    $className = $l['class'];
    $methodName = $l['method'];
    $priority = $l['priority'] ?? 0;
    \Civi::dispatcher()->addListener($l['event'], function($e) use ($className, $methodName, &$listenerObjs) {
      // Lazy-load: Only read class-files and instantiate objects if the hook actually fires.
      if (!isset($listenerObjs[$className])) {
        $listenerObjs[$className] = new $className();
      }
      call_user_func([$listenerObjs[$className], $methodName], $e);
    }, $priority);
  }

};
