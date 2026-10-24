# Changelog

## 0.1.0 (2026-10-24)


### ⚠ BREAKING CHANGES

* code calling filterRaw(), sortRaw(), pageRaw(), with() or fields() on a Query or Projection subclass that doesn't re-declare them no longer compiles. Generated builders re-declare every one their contract permits.

### Features

* add the albums reference artefact and the resource runtime behind it ([#3](https://github.com/haddowg/json-api-client/issues/3)) ([6ea09f5](https://github.com/haddowg/json-api-client/commit/6ea09f59641041472e598fad424ef805dc1d2a5d))
* add the runtime primitives generated code will build on ([#2](https://github.com/haddowg/json-api-client/issues/2)) ([0571dc0](https://github.com/haddowg/json-api-client/commit/0571dc00f23a52cff4238a5435a04127920e77dd))
* fill the runtime gaps generated code needs ([#8](https://github.com/haddowg/json-api-client/issues/8)) ([d3341f8](https://github.com/haddowg/json-api-client/commit/d3341f82a4696602685bbb229acab4cb3e0d2060))
* open the loose doors only where the contract permits them ([#9](https://github.com/haddowg/json-api-client/issues/9)) ([2dc1277](https://github.com/haddowg/json-api-client/commit/2dc12774347d0a046597e313f900b057e26fa557))
* read a linkage member's meta.pivot through a typed Pivot base ([#13](https://github.com/haddowg/json-api-client/issues/13)) ([b87d917](https://github.com/haddowg/json-api-client/commit/b87d917311933db21155fb1942a5ab6ea72e43ea))
* replace page parameters when a menu strategy is picked ([#10](https://github.com/haddowg/json-api-client/issues/10)) ([f07e28d](https://github.com/haddowg/json-api-client/commit/f07e28dc215e50f8647f490b6ff152bb401c1e0d))
* throw a class per catalogued error code ([#6](https://github.com/haddowg/json-api-client/issues/6)) ([d2d6846](https://github.com/haddowg/json-api-client/commit/d2d6846a151aa8a2a009a16b0b7d41de53724078))


### Bug Fixes

* ship core 1.3's error codes with their descriptions ([#11](https://github.com/haddowg/json-api-client/issues/11)) ([83b5ba6](https://github.com/haddowg/json-api-client/commit/83b5ba62fc2819042ade3dc75ae57554b80750fc))


### Miscellaneous Chores

* **phpstan:** analyse as PHP 8.4 and settle two narrowed assertions ([#4](https://github.com/haddowg/json-api-client/issues/4)) ([5bad6c9](https://github.com/haddowg/json-api-client/commit/5bad6c939fc95fa99f7fed6da2d922c3fe8417bc))
* scaffold package ([d04ae7d](https://github.com/haddowg/json-api-client/commit/d04ae7d8754436a0cdbeb1556671a3187b390dfa))
