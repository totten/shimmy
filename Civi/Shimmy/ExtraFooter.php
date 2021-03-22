<?php

namespace Civi\Shimmy;

/**
 * This example relies on `mixin/civi-auto-hook@0.1` to register event listeners.
 */
class ExtraFooter implements \Civi\HookMixinV0\HookInterface {

  /**
   * @see CRM_Utils_Hook::alterContent()
   */
  public function hook_civicrm_alterContent(&$content, $context, $tplName, &$object) {
    if ($tplName === 'CRM/Shimmy/Page/FooBar.tpl') {
      $content .= '<p><b>Have a nice day!</b></p>';
    }
  }

}
