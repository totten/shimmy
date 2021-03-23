<?php

/**
 * This example relies on `mixin/civi-auto-subscriber@0.1` to register event listeners.
 */
class CRM_Shimmy_BasDePageSubscriber implements \Civi\SubscriberMixinV0\SubscriberInterface {

  public static function getCiviSubscribers() {
    return [
      ['event' => 'hook_civicrm_alterContent', 'method' => 'addFooter', 'priority' => 10],
    ];
  }

  /**
   * @param \Civi\Core\Event\GenericHookEvent $e
   * @see CRM_Utils_Hook::alterContent()
   */
  public function addFooter(\Civi\Core\Event\GenericHookEvent $e) {
    if ($e->tplName === 'CRM/Shimmy/Page/FooBar.tpl') {
      $e->content .= '<p><b>Au revoir, page web!</b></p>';
    }
  }

}
