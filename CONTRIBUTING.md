# Contributing guidelines

Thank you very much for your contributions!

Remember that we are an open source organisation that will always accept any external contribution and/or help (if it follows this guidelines).

## Table of Contents

- [Hacktoberfest](#hacktoberfest)
- [How to Contribute](#how-to-contribute)
- [Development Workflow](#development-workflow)
- [Git Guidelines](#git-guidelines)
- [Release Process (internal team only)](#release-process-internal-team-only)

## Hacktoberfest

This is our first [Hacktoberfest](https://hacktoberfest.com)!!! 🌶️ 🔥

We appreciate ALL contributions very much with a big thank you to any contributor that is looking to help us!

1. We will follow the quality standards set by the organizers of Hacktoberfest (see detail on their [website](https://hacktoberfest.com/participation/#spam)). We **WILL NOT** consider any PR that doesn’t match that standard.
2. PRs might be reviewed at any time (some weekends included) as we're an open source organisation working for multiple projects (commercial and non-commercial), we are based at EU so our timezone is based in CEST.
3. We won't assign tasks labeled as hacktoberfest, so whoever make it first and right will be merged.

## How to Contribute

1. We must first see the idea you had behind, **if you found [already an issue](https://github.com/open-southeners/laravel-apiable/issues) go ahead**, otherwise **comunicate first** via [Github issue](https://github.com/open-southeners/laravel-apiable/issues/new/choose) or [Discord](https://discord.gg/tyMUxvMnvh).
2. Once approved the idea (and opened the issue), [fork this repository](https://github.com/open-southeners/laravel-apiable/fork).
3. Read and make sure that the [Development Workflow](#development-workflow) is applied properly.
4. [Submit the branch as a Pull Request](https://help.github.com/en/github/collaborating-with-issues-and-pull-requests/creating-a-pull-request-from-a-fork) pointing to the `main` branch of this repository.
5. All done! Now wait until we review the changes of your Pull Request.

## Development workflow

Share an idea, voting that idea then, finally, implement it: Code it, test it. That's our methodology.

### Code style

We enforce to use [Laravel Pint](https://laravel.com/docs/9.x/pint) with laravel as preset, please remember this before sending your contributions, otherwise send them fixed later 💅.

If you're using VSCode you can also check our own extension integrating Laravel Pint: https://marketplace.visualstudio.com/items?itemName=open-southeners.laravel-pint.

### Testing

**All tests must pass** and we might consider writing some more tests if the contribution requires.

**Any aditional test adding more coverage will be more than welcome!**

## Git Guidelines

We do not enforce many rules on 

### Using branches

**We do not enforce this**, but its recommended. Otherwise **make sure you are contributing from your own forked** version of this repository.

We do not enforce any branch naming style, but please use something descriptive of your changes.

### Descriptive commit messages

We do not enforce any rule (commitlint) or anything to this repository.

But being descriptive in the commit messages **is a must**.

## Release Process (internal team only)

This is only for us, you should not perform neither take care of any of this.

### Changelog generation

We do this manually by writing down carefully all the parts added, removed, fixed, changed, etc...

Just take a look at the standard: https://keepachangelog.com/en/1.0.0/
