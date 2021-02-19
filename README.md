# DO NOT USE THIS MASTER BRANCH FOR FORKS ETC

This master branch is an automated build artifact used specifically for playing along with https://horde-satis.maintaina.com

Branches explained:

- master: consumed by horde-satis.maintaina.com
- maintaina-bare: This is auto-rebasing on horde/components/master upstream via CI action. This branch also contains all commits which go into the master version and are intended to be SRed into horde upstream at some point
- maintaina-composerfixed: This branch takes maintaina-bare and adds composer metadata required for horde-satis.maintaina.com, NOT intended for upstream
- horde-upstream: a local tracking copy of horde/components/master. Nothing of interest here
- maintaina-bare-psr4: A stable branch for an upstream pull request. Consolidated rewrite of components to namespaced php, psr-4, some improved type hinting
- downstream: A frozen version deviating from upstream, consumed internally.

## Requests against upstream

- use maintaina-bare branch for pure code changes without touching composer.json, package.xml, changelog and with minimal edits to .horde.yml if required

## Requests against this repo

- use maintaina-bare branch for pure code changes. Do not touch metadata files.

## Upgrading this repo

- rebase horde-upstream branch on horde upstream repo
- rebase maintaina-bare on horde-upstream and fix any conflicts
- rebase maintaina-composerfixed on maintaina-bare or drop and recreate the branch. Then generate a new composer.json. You will probably need a custom components/config/bin for a satis repo or downstream git repo
- rebase master branch on maintaina-composerfixed. This should never fail as they only differ by this readme

- TODO: Script this
