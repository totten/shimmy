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

namespace Civi\AutoListenV0;

use Civi\Core\Event\EventScanner;
use Civi\Test\HookInterface;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

interface StaticHookInterface extends HookInterface {}
interface ObjectHookInterface extends HookInterface {}
interface StaticSubscriberInterface extends EventSubscriberInterface {}
interface ObjectSubscriberInterface extends EventSubscriberInterface {}

/**
 * Search for classes within an extension.
 *
 * @param string $extDir
 *   The base-dir of the extension.
 * @return \Generator<\ReflectionClass>
 *   List of classes in $extDir that match the interface.
 */
function findClasses(string $extDir): iterable {
  yield from [];

  $srcDirs = ['CRM' => '_', 'Civi' => '\\'];
  foreach ($srcDirs as $srcDir => $classDelim) {
    $phpFiles = \CRM_Utils_File::findFiles($extDir . DIRECTORY_SEPARATOR . $srcDir, '*.php');
    $phpFiles = preg_grep(';\.mgd\.php$;', $phpFiles, PREG_GREP_INVERT);
    foreach ($phpFiles as $phpFile) {
      $name = \CRM_Utils_File::relativize($phpFile, $extDir);
      $name = preg_replace(';\.php$;', '', $name);
      $name = trim(str_replace(DIRECTORY_SEPARATOR, '/', $name), '/');
      $name = str_replace('/', $classDelim, $name);
      $name = '\\' . $name;
      try {
        yield new \ReflectionClass($name);
      }
      catch (\ReflectionException $e) {
        error_log(__NAMESPACE__ . ': Failed to scan class file ' . $phpFile);
      }
    }
  }
}

function toServiceName(string $className) {
  return 'auto_' . $className;
}

/**
 * @param \CRM_Extension_MixInfo $mixInfo
 * @param \CRM_Extension_BootCache $bootCache
 */
return function ($mixInfo, $bootCache) {
  // File scans are expensive, but we need the info on every page-load. So use $bootCache.
  $listenerMaps = $bootCache->define(__NAMESPACE__ . ':' . $mixInfo->longName, function () use ($mixInfo) {
    $listenerMaps = [];
    foreach (findClasses($mixInfo->getPath()) as $clazz) {
      /** @var \ReflectionClass $clazz */
      if (array_intersect([StaticSubscriberInterface::class, StaticHookInterface::class], $clazz->getInterfaceNames())) {
        $listenerMaps['static'][$clazz->getName()] = EventScanner::findListeners($clazz);
      }
      elseif (array_intersect([ObjectSubscriberInterface::class, ObjectHookInterface::class], $clazz->getInterfaceNames())) {
        $listenerMaps['object'][$clazz->getName()] = EventScanner::findListeners($clazz);
      }
    }
    return $listenerMaps;
  });

  /** @var \Civi\Core\CiviEventDispatcher $dispatcher */
  $dispatcher = \Civi::dispatcher();

  foreach ($listenerMaps['static'] ?? [] as $className => $map) {
    $dispatcher->addListenerMap($className, $map);
  }
  foreach ($listenerMaps['object'] ?? [] as $className => $map) {
    $dispatcher->addSubscriberServiceMap(toServiceName($className), $map);
  }

  if (!empty($listenerMaps['object'])) {
    $dispatcher->addListener('hook_civicrm_container', function($e) use ($listenerMaps) {
      /** @var \Symfony\Component\DependencyInjection\ContainerBuilder $container */
      $container = $e->container;
      foreach ($listenerMaps['object'] as $className => $listenerMap) {
        $container->register(toServiceName($className), $className);
        // Is there a point in using FileResource if the $listenerMap comes from boot-cache?
        $container->addResource(new FileResource((new \ReflectionClass($className))->getFileName()));
      }
    });
  }

};
