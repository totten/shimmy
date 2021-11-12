# Mixin Library (mixlib)

This repository contains a set of *CiviCRM mixins*.  Mixins are small bits of glue-code that you may add to an extension.
For example, a typical mixin might [implement a hook and scan for related files](mixin/setting-php@1/mixin.php).

The "Mixin Library" supports development/distribution of mixins -- providing the canonical home and test harness for various
mixins. Install it if you wish to update or develope common mixins.

## Usage

Copy the mixin from mixlib (`$MIXLIB`) to your extension (`$MY_EXT`):

```sh
# Formula
cp "MIXLIB/mixins/MY-MIXIN@VERSION/mixin.php" "MY_EXTENSION/mixins/MY-MIXIN@VERSION.mixin.php"

# Example
cp "/path/to/mixlib/mixins/my-stuff@1/mixin.php" "/path/to/myext/mixins/my-stuff@1.0.0.mixin.php"

# Note that `myext/mixins/` contains a series of `*.mixin.php` files.
# The filename reveals the full version (eg `1.0.0`).
```

Activate the mixin via `info.xml`:

```xml
<extension key="...">
  <mixins>
    <mixin>my-stuff@1.0.0</mixin>
  </mixins>
</extension>
```

For compatibility with pre-existing versions of CiviCRM, you may add the [mixin polyfill](doc/polyfill.md).

## Evaluation

Mixins can replace much of the boilerplate traditionally seen in `*.civix.php` file. Like `*.civix.php`, mixins can be updated and
deployed without requiring a `civicrm-core` update. However, they has several comparative advantages:

* Mixins are simple PHP files. The files can be copied without editing. There is no need for re-editing PHP files or using meta-PHP templates.
* Mixins are incremental. You may use one service (eg `*.setting.php` scanning) without another service (eg `*.mgd.php` scanning).
* Mixins are deduplicated. If 3 extensions use the same mixin, then only one copy is loaded. (*Requires core support*)
* Mixins are versioned and updateable. If 3 extensions use the same mixin, then the newest version will be loaded. (*Requires core support*)

## Versioning

Every mixin has an associated version similar to _SemVer_ (`MAJOR`.`MINOR`.`PATCH`), with additional constraints.

* `MAJOR` versions are de facto independent. Major versions may be used concurrently.
* `MINOR` versions add functionality. Minor versions have presumed forward-compatibility. (New minors supercede old minors.)
* `PATCH` versions fix bugs. Patch versions have presumed forward-compatibility. (New patches supercide old patches.)

## File layout (mixlib)

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
