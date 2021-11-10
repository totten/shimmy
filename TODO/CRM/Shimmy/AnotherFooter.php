<?php

/**
 * This example relies on `mixin/civi-auto-hook@0.1` to register event listeners.
 */
class CRM_Shimmy_AnotherFooter implements \Civi\HookMixinV0\HookInterface {

  /**
   * @see CRM_Utils_Hook::alterContent()
   */
  public function hook_civicrm_alterContent(&$content, $context, $tplName, &$object) {
    if ($tplName === 'CRM/Shimmy/Page/FooBar.tpl') {
      $content .= '<p><b>Buenos dias!</b></p>';
    }
  }

}
