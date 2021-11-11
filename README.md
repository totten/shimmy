# Mixin Library (mixlib)

This repository contains a set of *CiviCRM extension mixins*.  Mixins are small bits of glue-code that reduce common
boilerplate.  To support early adopters, mixins may be bundled directly with an extension; additionally, to support
long-term maintenance, mixins may be updated through `civicrm-core` (or other extensions).

The "Mixin Library" supports development/distribution of mixins.  Install it if you wish to update common mixins.

## Usage

To activate a mixin, an extension should add a declaration to `info.xml`:

```xml
<extension key="...">
  <mixins>
    <mixin>my-stuff@1.0</mixin>
  </mixins>
</extension>
```

If the mixin is distributed with core, then no extra effort is required.  However, if the mixin is new or bespoke, then
you should copy it from mixlib (`$MIXLIB`) to the extension (`$MY_EXT`), eg

```
cp $MIXLIB/mixins/my-stuff@1.0/mixin.php $MY_EXT/mixins/my-stuff@1.0.0.mixin.php
```

## Versioning

Every mixin has an associated version similar to _SemVer_ (`MAJOR`.`MINOR`.`PATCH`), with additional constraints.

* `MAJOR` versions are de facto independent. Major versions may be used concurrently.
* `MINOR` versions add functionality. Minor versions have presumed forward-compatibility. (New minors supercede old minors.)
* `PATCH` versions fix bugs. Patch versions have presumed forward-compatibility. (New patches supercide old patches.)

## File layout

```
bin/
    mixer                Developer script for testing mixins

{NAME}@{VERSION}/
    mixin.php            The implementation of the mixin
    example/             Example files for an extension using this mixin.
        CRM/             Some code for the example extension
        Civi/            Some code for the example extension
        templates/       Some code for the example extension
        tests/mixin/     Unit tests to verify that the example works

COMMON/
  example/           Example files for an extension.
                     Used as a baseline for all other examples.
```

## Development

```bash
## Run all test processes
./scripts/test-all

## Deploy an example extension (all known mixins)
./scripts/mixer create ~/buildkit/build/dmaster/web/sites/all/modules/civicrm/ext/myexample

## Deploy an example extension (specific mixins)
./scripts/mixer create ~/buildkit/build/dmaster/web/sites/all/modules/civicrmext/myexample setting-php@1.0

## Deploy an example extension and run the corresponding tests (all known mixins)
./scripts/mixer test ~/buildkit/build/dmaster/web/sites/all/modules/civicrm

## Deploy an example extension and run the corresponding tests (specific mixins)
./scripts/mixer test ~/buildkit/build/dmaster/web/sites/all/modules/civicrm setting-php@1.0
```

## Wishlist

`mixer create` currently copies files to make the example extension.  It should use symlinks to make it easier to
test/iterate.
