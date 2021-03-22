<?php

/**
 * Enable this mixin to allow any class to have hook functions.
 *
 * @code
 * class Foo implements Civi\HookMixinV0\HookInterface {
 *   public function hook_civicrm_alterContent($e) {...}
 * }
 * @endCode
 *
 * Additional requirements:
 *
 * - Classes must live in a "Civi/" or "CRM/" folder.
 * - All PHP files in "Civi/" and "CRM/" must be conventional Civi-style classes.
 */

namespace Civi\HookMixinV0;

/**
 * The 'HookInterface' flags a class for use with `hook_civicrm_*` functions.
 *
 * When firing hooks, the class will be instantiated with its default constructor. The same instance of the class will be
 * re-used for all hooks.
 */
interface HookInterface {
}

/**
 * Search for classes that implement an interface.
 *
 * @param string $extDir
 *   The base-dir of the extension.
 * @param string $interface
 *   The interface that we seek.
 * @return \Generator<\ReflectionClass>
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

/**
 * @param \CRM_Extension_MixInfo $mixInfo
 * @param \CRM_Extension_BootCache $bootCache
 */
return function ($mixInfo, $bootCache) {
  // File scans are expensive, but we need the info on every page-load. So use $bootCache.
  $listenerDefns = $bootCache->define(__NAMESPACE__ . ':' . $mixInfo->longName, function () use ($mixInfo) {
    $listenerDefns = [];
    foreach (findInterfaces($mixInfo->getPath(), HookInterface::class) as $clazz) {
      /**
       * @var \ReflectionClass $clazz \
       */
      foreach ($clazz->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
        if (substr($method->getName(), 0, 5) === 'hook_') {
          $listenerDefns[] = [$clazz->getName(), $method->getName()];
        }
      }
    }
    usort($listenerDefns, function ($a, $b) {
      return strnatcmp(($a[0] ?? '') . ($a[1] ?? ''), ($b[0] ?? '') . ($b[1] ?? ''));
    });
    return $listenerDefns;
  });

  $listenerObjs = [];
  foreach ($listenerDefns as $l) {
    [$className, $methodName] = $l;
    \Civi::dispatcher()->addListener($methodName, function($e) use ($className, $methodName, &$listenerObjs) {
      // Lazy-load: Only read class-files and instantiate objects if the hook actually fires.
      if (!isset($listenerObjs[$className])) {
        $listenerObjs[$className] = new $className();
      }
      /**
       * @var \Civi\Core\Event\GenericHookEvent $e
       */
      call_user_func_array([$listenerObjs[$className], $methodName], $e->getHookValues());
    });
  }
};
