<?php

/**
 * Ensure that xml/Menu/*xml are loaded and effective.
 *
 * @group e2e
 * @see cv
 */
class E2E_Shimmy_LifecycleTest extends \PHPUnit\Framework\TestCase {

  /**
   * @var array
   */
  protected $mixinTests;

  protected function setUp(): void {
    parent::setUp();
    $this->mixinTests = [];
    $mixinTestFiles = (array) glob($this->getPath('/tests/mixin/*Test.php'));
    foreach ($mixinTestFiles as $file) {
      require_once $file;
      $class = '\\Civi\Shimmy\\Mixins\\' . preg_replace(';\.php$;', '', basename($file));
      $this->mixinTests[] = new $class();
    }
  }

  /**
   * Install and uninstall the extension. Ensure that various mixins+artifacts work correctly.
   */
  public function testLifecycle(): void {
    $this->assertNotEquals('UnitTests', getenv('CIVICRM_UF'), 'This is an end-to-end test involving CLI and HTTP. CIVICRM_UF should not be set to UnitTests.');

    $this->runMethods('testPreConditions');

    // Clear out anything from previous runs.
    static::cv('api3 Extension.disable key=shimmy');
    static::cv('api3 Extension.uninstall key=shimmy');

    // The main show.
    static::cv('api3 Extension.enable key=shimmy');
    $this->runMethods('testInstalled');

    // This is a duplicate - make sure things still work after an extra run.
    static::cv('api3 Extension.enable key=shimmy');
    $this->runMethods('testInstalled');

    // OK, how's the cleanup?
    static::cv('api3 Extension.disable key=shimmy');
    $this->runMethods('testDisabled');

    static::cv('api3 Extension.uninstall key=shimmy');
    $this->runMethods('testUninstalled');
  }

  protected static function cv($cmd) {
    $result = cv($cmd, 'json');
    // APIv3 calls return error status as JSON data, but others don't.
    if (isset($result['is_error']) && $result['is_error'] !== 0) {
      throw new RuntimeException("Call to cv failed\nCommand: $cmd\nResult:" . var_export($result, 1));
    }
    return $result;
  }

  protected static function getPath($suffix = ''): string {
    return dirname(__DIR__, 4) . $suffix;
  }

  protected function runMethods(string $method, ...$args) {
    if (empty($this->mixinTests)) {
      $this->fail('Cannot run methods. No mixin tests found.');
    }
    foreach ($this->mixinTests as $test) {
      $test->$method(...$args);
    }
  }

}
