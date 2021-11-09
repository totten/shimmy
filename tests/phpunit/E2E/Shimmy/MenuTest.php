<?php

/**
 * Ensure that xml/Menu/*xml are loaded and effective.
 *
 * @group e2e
 * @see cv
 */
class E2E_Shimmy_MenuTest extends \PHPUnit\Framework\TestCase {

  public function setUp(): void {
    $this->assertNotEquals('UnitTests', getenv('CIVICRM_UF'), 'This is an end-to-end test involving CLI and HTTP. CIVICRM_UF should not be set to UnitTests.');

    $this->assertFileExists(static::getPath('/xml/Menu/shimmy.xml'), 'The shimmy extension must have a Menu XML file.');

    static::cv('api3 Extension.disable key=shimmy');
    static::cv('api3 Extension.uninstall key=shimmy');
  }

  public function tearDown(): void {
    parent::tearDown();
  }

  /**
   * Is the menu item registered and unregistered appropriately?
   */
  public function testMenuDefined(): void {
    static::cv('api3 Extension.enable key=shimmy');
    $items = cv('api4 Route.get +w path=civicrm/shimmy/foobar');
    $this->assertEquals('CRM_Shimmy_Page_FooBar', $items[0]['page_callback']);

    static::cv('api3 Extension.disable key=shimmy');
    $items = cv('api4 Route.get +w path=civicrm/shimmy/foobar');
    $this->assertEmpty($items);
  }

  /**
   * Does the menu item actually work?
   */
  public function testMenuEffective(): void {
    static::cv('api3 Extension.enable key=shimmy');
    $url = static::cv('url civicrm/shimmy/foobar');
    $this->assertTrue(is_string($url));
    $response = file_get_contents($url);
    $this->assertRegExp(';hello world;', $response);

    static::cv('api3 Extension.disable key=shimmy');
    $response = file_get_contents($url, FALSE, stream_context_create(['http' => ['ignore_errors' => TRUE]]));
    $this->assertNotRegExp(';hello world;', $response);
    $this->assertNotRegExp(';HTTP.*200.*;', $http_response_header[0]);
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

}
