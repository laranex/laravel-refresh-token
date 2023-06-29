# Changelog

All notable changes to `laravel-refresh-token` will be documented in this file.

## v1.0.1 - 2023-06-29

name: Update CHANGELOG

on:
release:
types: [created]

jobs:
update_changelog:
runs-on: ubuntu-latest

```steps:
  - name: Check out code
    uses: actions/checkout@v2

  - name: Append release note to CHANGELOG
    run: |
      echo "${{ github.event.release.body }}" >> CHANGELOG.md
      git config user.name "GitHub Actions"
      git config user.email "<>"
      git add CHANGELOG.md
      git commit -m "Update CHANGELOG for release ${{ github.event.release.tag_name }}"
      git push
```
## 1.0.0 - 2023-06-27

- initial release
