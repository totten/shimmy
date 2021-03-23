# shimmy

The `shimmy` extension is a demonstration which uses extension-mixins.  This technique is amenable to auto-registering
the parts of an extension - and caching the registration to minimize performance impact.

This [info.xml](info.xml) enables a few mixins. We have examples using each:

* [civix-register-files@2.0.0](mixin/civix-register-files@2.0.0.mixin.php): Registers small files (`*.mgd.php`,
  `xml/Menu/*.xml`, etc) using the same file-naming conventions that as the current civix release.
    * __Example__: This mixin autoloads [xml/Menu/shimmy.xml](xml/Menu/shimmy.xml)
* [civi-auto-hook@0.1](mixin/civi-auto-hook@0.1.mixin.php): Any class which implements `Civi\HookMixinV0\HookInterface`
  will be automatically scanned for methods named `hook_civicrm_foo()`. Whenever any listed hook fires, the class will be
  instantiated, and the method will be invoked.
    * __Example__: This mixin autoloads [Civi\Shimmy\ExtraFooter](Civi/Shimmy/ExtraFooter.php) and [CRM_Shimmy_AnotherFooter](CRM/Shimmy/AnotherFooter.php).
* [civi-auto-subscriber@0.1](mixin/civi-auto-subscriber@0.1.mixin.php): Any class which implements 
  `Civi\SubscriberMixinV0\SubscriberInterface` will register for events. One must implement `getCiviSubscribers()`
  to provide a list of event-names, method-names, and priorities.
    * __Example__: This mixin autoloads [CRM_Shimmy_BasDePageSubscriber](CRM/Shimmy/BasDePageSubscriber.php).

Some notable characteristics:

* If 10 extensions all include the same mixin (eg `civi-auto-hook@0.1`), the mixin file will only be loaded once.
  That means one file-read, one set of byte-code, better CPU/RAM caching, etc.
* If there is a critical update to a mixin (eg compatibility or security), we can ship it in `civicrm-core`
  and override any old/bundled mixins.
* It abides by SemVer -- so minor-upgrades and patch-upgrades are considered to be valid substitutions.
  (Thus: `civi-auto-hook@0.1` can be substituted implicitly with `civi-auto-hook@0.2`). However, major-upgrades
  are not substituted -- instead, they coexist. (Thus: `civi-auto-hook@1.5` and `civi-auto-hook@0.3` can coexist.)
* Developers have wide latitude to iterate on mixins. For example, if you want a mixin to work differently, then you
  can rename it and ship with your extension (even if there's no core support). Your mixin can coexist with the original. Moreover, you can copy/paste/share mixins. When debugging or patching, you can inspect directly (without layers of templating).

The full benefits of mixins require core support (draft: https://github.com/civicrm/civicrm-core/pull/19865).
However, `shimmy` includes a polyfill `shimmy.mixin.php`. This polyfill will load on older versions of
Civi, but it's not quite as good. (In particular, it only dedupes mixins with exactly the same version -- there
is no upgrading. Additionally, the `$bootCache` produces more file I/O. And the polyfill itself has to be
duplicated.)
