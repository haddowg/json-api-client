// Run with: node --test .github/scripts/check-specs-pin.test.js
// A stub npm on PATH stands in for the registry, publishing only 2.2.0 and 0.0.0-pr.306.77ec185.
const { test } = require('node:test');
const assert = require('node:assert/strict');
const { chmodSync, mkdtempSync, writeFileSync } = require('node:fs');
const { tmpdir } = require('node:os');
const { delimiter, join } = require('node:path');
const { spawnSync } = require('node:child_process');

const guard = join(__dirname, 'check-specs-pin');
const NAME = '@haddowg/json-api-symfony-specs';

const stubBin = mkdtempSync(join(tmpdir(), 'specs-pin-bin-'));
writeFileSync(
  join(stubBin, 'npm'),
  [
    '#!/bin/sh',
    'for arg in "$@"; do',
    `  [ "$arg" = "${NAME}@2.2.0" ] && echo 2.2.0 && exit 0`,
    `  [ "$arg" = "${NAME}@0.0.0-pr.306.77ec185" ] && echo 0.0.0-pr.306.77ec185 && exit 0`,
    'done',
    'echo "npm error code E404" >&2',
    'exit 1',
    '',
  ].join('\n'),
);
chmodSync(join(stubBin, 'npm'), 0o755);

const run = ({ pin, devDependency = true, allowPrerelease = false }) => {
  const dir = mkdtempSync(join(tmpdir(), 'specs-pin-'));
  const deps = pin === undefined ? {} : { [NAME]: pin };
  writeFileSync(join(dir, 'package.json'), JSON.stringify(devDependency ? { devDependencies: deps } : { dependencies: deps }));
  return spawnSync(process.execPath, [guard, ...(allowPrerelease ? ['--allow-prerelease'] : []), dir], {
    encoding: 'utf8',
    env: { ...process.env, PATH: stubBin + delimiter + process.env.PATH },
  });
};

const failsWith = (result, pattern) => {
  assert.equal(result.status, 1, result.stdout);
  assert.match(result.stderr, pattern);
};

test('passes for an exact, published release', () => {
  const result = run({ pin: '2.2.0' });
  assert.equal(result.status, 0, result.stderr);
  assert.match(result.stdout, /2\.2\.0 is a published release/);
});

test('fails for a published prerelease without --allow-prerelease', () => {
  failsWith(run({ pin: '0.0.0-pr.306.77ec185' }), /is a prerelease/);
});

test('warns for a published prerelease with --allow-prerelease', () => {
  const result = run({ pin: '0.0.0-pr.306.77ec185', allowPrerelease: true });
  assert.equal(result.status, 0, result.stderr);
  assert.match(result.stdout, /::warning::.*is a prerelease/);
});

test('fails for a range, a dist-tag, a local path or a git URL', () => {
  failsWith(run({ pin: '^2.2.0' }), /a range/);
  failsWith(run({ pin: '~2.2.0' }), /a range/);
  failsWith(run({ pin: 'pr-306', allowPrerelease: true }), /a dist-tag/);
  failsWith(run({ pin: 'latest' }), /a dist-tag/);
  failsWith(run({ pin: 'file:../json-api-symfony/build/specs' }), /a local path/);
  failsWith(run({ pin: 'git+https://github.com/haddowg/json-api-symfony.git' }), /a git URL/);
});

test('fails when the version is not published', () => {
  failsWith(run({ pin: '2.3.0' }), /isn't published/);
  failsWith(run({ pin: '0.0.0-pr.306.0000000', allowPrerelease: true }), /isn't published/);
});

test('fails when the package is missing or in dependencies', () => {
  failsWith(run({ pin: undefined }), /doesn't pin/);
  failsWith(run({ pin: '2.2.0', devDependency: false }), /in dependencies/);
});
