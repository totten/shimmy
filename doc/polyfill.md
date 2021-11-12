# Mixin Polyfill

Mixins will have built-in support in a future version of CiviCRM (vTBD). However,
for an extension to use a mixin on an older version of CiviCRM, it should
include the polyfill ([mixin/polyfill.php](../mixin/polyfill.php)).

The polyfill will be enabled in `civix` (vTBD).  To activate the polyfill in
a bespoke extension (`myext`, `org.example.myextension`), copy `mixin/polyfill.php`.
Load this file during a few key moments:

```php
function _myext_mixin_polyfill() {
  if (!class_exists('CRM_Extension_MixInfo')) {
    $polyfill = __DIR__ . '/mixin/polyfill.php';
    (require $polyfill)('org.example.myextension', 'myext', __DIR__);
  }
}

function myext_civicrm_config() {
  _myext_mixin_polyfill();
}

function myext_civicrm_install() {
  _myext_mixin_polyfill();
}

function myext_civicrm_enable() {
  _myext_mixin_polyfill();
}
```
