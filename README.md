# AdminTools3

Independent MODX Revolution 3 port of AdminTools 1.15.2. The project retains the
original GPL v2 license and author attribution in `core/components/admintools3/docs`.

The package adds manager favorites and element change history, notes, a plugin
event overview, alternative resource permissions, manager theme and layout
options, selective cache clearing, and email login. It also fixes Cyrillic
search in searchable multi-select TVs. The current release is `1.0.0-pl`.

## Build

Use PHP 8.1 or newer and a configured MODX 3 installation:

```text
php build/build.php C:/path/to/modx-site
```

The transport archive is written to `packages/` in this repository. The build
reads the MODX configuration and database workspace but does not install the
package or modify site data. Install the resulting archive through MODX Package
Management.

## GitHub releases

The current installable archive is stored in `dist/`; generated build files in
`packages/` are ignored by Git. To publish a new version, update the version in
`build/build.php`, build the package, and copy the resulting transport ZIP from
`packages/` to `dist/`. Commit the source and the new ZIP, then tag that commit
with the matching version (for example, `v1.0.0-pl`) and push the branch and tag.
The GitHub Actions release workflow checks the ZIP and attaches it to the GitHub
Release for that tag. A tag without a matching `dist/admintools3-VERSION.transport.zip`
fails instead of publishing the wrong archive.

## Upgrade from AdminTools

AdminTools3 has its own MODX namespace (`admintools3`), system setting keys,
assets, model classes, database tables, plugin, snippet, and chunks. The plugin,
snippet, and both chunks are installed in the `AdminTools3` category. During
the first installation on a site where the `admintools` namespace exists, the
installer copies legacy system settings, user profile preferences, notes, and
alternative permissions. Existing AdminTools data is preserved. The installer
activates AdminTools3 after migration and disables the legacy plugin if it was
enabled. A later update preserves AdminTools3's existing enabled or disabled
state. Reinstalling AdminTools3 does not overwrite migrated values.

The old email login resource may still invoke `adminLogin`. Update a customized
resource to call `adminLogin3` before uninstalling the old package. Keep the old
package installed until the manager and login flows have been verified.

The menu animation setting is intentionally absent because MODX 3 provides its
own menu behavior. AdminTools3 also fixes Cyrillic search in multi-select TV
fields in the manager; the fix applies only to those fields.

## Uninstall data

MODX's package uninstall dialog offers Preserve (the default), Uninstall, and
Restore. For AdminTools3, Preserve and Restore keep its notes, alternative
permissions, system settings, and three user profile preference keys so a later
installation can use them. Selecting Uninstall removes that data, including the
`admintools3_notes` and `admintools3_permissions` tables. It does not delete
settings or data belonging to the original AdminTools package. Package updates
never run this cleanup.

## Verification status

The `1.0.0-pl` archive builds against local MODX 3.2.4. PHP and JavaScript
syntax checks pass. Read-only processor calls and service initialization passed
for the earlier `0.1.0-alpha` build.
On an isolated copy of the site's database, the schema and migration resolvers
copied settings, notes, and alternative permissions while retaining record IDs.
The earlier transport archive installed through MODX's package installer on a
cloned site. A repeat installation preserved changes to a setting and the
plugin's disabled state. The temporary site files and databases were removed.
The uninstall cleanup resolver passed an integration check using temporary
database tables: updates and both data-preserving uninstall modes retained data;
the explicit Uninstall mode removed AdminTools3 data while retaining legacy data.

Email delivery and selective cache clearing should be checked on each target
site because they depend on its mail and cache configuration.
