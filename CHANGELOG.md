# Changelog

## 0.1.0 (2026-10-31)


### ⚠ BREAKING CHANGES

* code calling filterRaw(), sortRaw(), pageRaw(), with() or fields() on a Query or Projection subclass that doesn't re-declare them no longer compiles. Generated builders re-declare every one their contract permits.

### Features

* add the albums reference artefact and the resource runtime behind it ([#3](https://github.com/haddowg/json-api-client/issues/3)) ([6ea09f5](https://github.com/haddowg/json-api-client/commit/6ea09f59641041472e598fad424ef805dc1d2a5d))
* add the bases for typed declared links ([#21](https://github.com/haddowg/json-api-client/issues/21)) ([ec5d665](https://github.com/haddowg/json-api-client/commit/ec5d66599a176973b33905f28f086824dcb63481))
* add the Laravel and Symfony bridges ([#26](https://github.com/haddowg/json-api-client/issues/26)) ([d2bb37e](https://github.com/haddowg/json-api-client/commit/d2bb37ed86ed15d75061d911bcdcde69478a7200))
* add the runtime primitives generated code will build on ([#2](https://github.com/haddowg/json-api-client/issues/2)) ([0571dc0](https://github.com/haddowg/json-api-client/commit/0571dc00f23a52cff4238a5435a04127920e77dd))
* encode multipart and urlencoded action bodies ([#17](https://github.com/haddowg/json-api-client/issues/17)) ([433e11e](https://github.com/haddowg/json-api-client/commit/433e11ede95aaf41c211cf3dda099fa8710cad2a))
* fill the runtime gaps generated code needs ([#8](https://github.com/haddowg/json-api-client/issues/8)) ([d3341f8](https://github.com/haddowg/json-api-client/commit/d3341f82a4696602685bbb229acab4cb3e0d2060))
* filter and sort a to-many's related read ([#30](https://github.com/haddowg/json-api-client/issues/30)) ([0280ea0](https://github.com/haddowg/json-api-client/commit/0280ea0587cd4b138f57a2f26ad0560e60323972))
* let a fetched resource reach its id-handle ([#28](https://github.com/haddowg/json-api-client/issues/28)) ([ae1c91d](https://github.com/haddowg/json-api-client/commit/ae1c91dda7dc4670763a0912e43039ac372c3d4c))
* open the loose doors only where the contract permits them ([#9](https://github.com/haddowg/json-api-client/issues/9)) ([2dc1277](https://github.com/haddowg/json-api-client/commit/2dc12774347d0a046597e313f900b057e26fa557))
* read a linkage member's meta.pivot through a typed Pivot base ([#13](https://github.com/haddowg/json-api-client/issues/13)) ([b87d917](https://github.com/haddowg/json-api-client/commit/b87d917311933db21155fb1942a5ab6ea72e43ea))
* read raw action responses and send a chosen Accept ([#16](https://github.com/haddowg/json-api-client/issues/16)) ([d723dbf](https://github.com/haddowg/json-api-client/commit/d723dbfdef7caa69a45a3df0ad227609db799ddc))
* read the URL an action answers with into an ActionLocation ([#23](https://github.com/haddowg/json-api-client/issues/23)) ([1b52d56](https://github.com/haddowg/json-api-client/commit/1b52d56bd8b9a4b1141c3ebe0436a3f6db3d2f7b))
* replace page parameters when a menu strategy is picked ([#10](https://github.com/haddowg/json-api-client/issues/10)) ([f07e28d](https://github.com/haddowg/json-api-client/commit/f07e28dc215e50f8647f490b6ff152bb401c1e0d))
* run atomic operations through a typed batch recorder ([#25](https://github.com/haddowg/json-api-client/issues/25)) ([4ea6b42](https://github.com/haddowg/json-api-client/commit/4ea6b42247e661ab7ac335aa1f1307d3b7504bb1))
* send a list form field as one bracketed part per item ([#19](https://github.com/haddowg/json-api-client/issues/19)) ([79bfe43](https://github.com/haddowg/json-api-client/commit/79bfe4376706f81412a256611211b279cbc6dfee))
* shape an included to-many with a Relationship Query ([#29](https://github.com/haddowg/json-api-client/issues/29)) ([9a04b0d](https://github.com/haddowg/json-api-client/commit/9a04b0d15598176964ea53806059026dcd97b96d))
* ship the INSUFFICIENT_SCOPE error code ([#22](https://github.com/haddowg/json-api-client/issues/22)) ([03a3b3d](https://github.com/haddowg/json-api-client/commit/03a3b3d27d76da726119571f1b4685277ca471fb))
* throw a class per catalogued error code ([#6](https://github.com/haddowg/json-api-client/issues/6)) ([d2d6846](https://github.com/haddowg/json-api-client/commit/d2d6846a151aa8a2a009a16b0b7d41de53724078))
* validate responses against the server's OpenAPI document ([#27](https://github.com/haddowg/json-api-client/issues/27)) ([6d7785b](https://github.com/haddowg/json-api-client/commit/6d7785be044a8122b953d386e309c274236ba49d))


### Bug Fixes

* only follow links on the base URL's origin ([#14](https://github.com/haddowg/json-api-client/issues/14)) ([c4a205c](https://github.com/haddowg/json-api-client/commit/c4a205c80e3e31c5f484ecc56fcc91cc69229eaf))
* send the JSON:API content type on every POST ([#15](https://github.com/haddowg/json-api-client/issues/15)) ([2ec7eb8](https://github.com/haddowg/json-api-client/commit/2ec7eb87643c4a9765c4dbb58b06cfa87a6f9b75))
* ship core 1.12's error codes with their descriptions ([#18](https://github.com/haddowg/json-api-client/issues/18)) ([7e7477c](https://github.com/haddowg/json-api-client/commit/7e7477c08a8bd250f6c4ab04ea66511911daa2e4))
* ship core 1.3's error codes with their descriptions ([#11](https://github.com/haddowg/json-api-client/issues/11)) ([83b5ba6](https://github.com/haddowg/json-api-client/commit/83b5ba62fc2819042ade3dc75ae57554b80750fc))


### Miscellaneous Chores

* **phpstan:** analyse as PHP 8.4 and settle two narrowed assertions ([#4](https://github.com/haddowg/json-api-client/issues/4)) ([5bad6c9](https://github.com/haddowg/json-api-client/commit/5bad6c939fc95fa99f7fed6da2d922c3fe8417bc))
* scaffold package ([d04ae7d](https://github.com/haddowg/json-api-client/commit/d04ae7d8754436a0cdbeb1556671a3187b390dfa))
