# Mixins

This repository contains a set of *extension mixins*.

## Development

```bash
## Deploy an example extension
./bin/mixer create-example setting-php@1.0 ~/buildkit/build/dmaster/web/sites/all/modules/civicrm

## Deploy an example extension and run the corresponding tests
./bin/mixer test-example setting-php@1.0 ~/buildkit/build/dmaster/web/sites/all/modules/civicrm

## Iteratively deploy and test each example
./bin/mixer test-all ~/buildkit/build/dmaster/web/sites/all/modules/civicrm
```
