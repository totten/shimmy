# Mixins

This repository contains a set of *extension mixins*.

## Development

```bash
## Deploy an example extension (all known mixins)
./bin/mixer create ~/buildkit/build/dmaster/web/sites/all/modules/civicrm/ext/myexample

## Deploy an example extension (specific mixins)
./bin/mixer create ~/buildkit/build/dmaster/web/sites/all/modules/civicrmext/myexample setting-php@1.0

## Deploy an example extension and run the corresponding tests (all known mixins)
./bin/mixer test ~/buildkit/build/dmaster/web/sites/all/modules/civicrm

## Deploy an example extension and run the corresponding tests (specific mixins)
./bin/mixer test ~/buildkit/build/dmaster/web/sites/all/modules/civicrm setting-php@1.0
```
