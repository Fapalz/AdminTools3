# AdminTools3

AdminTools3 brings the AdminTools 1.15.2 manager features to MODX Revolution 3:

- Favorite templates, TVs, chunks, snippets, and plugins, and view recently edited elements.
- Create shared or private notes and manage plugins by event.
- See which resources use a template and configure alternative resource permissions.
- Customize the manager theme and layout, add CSS or JavaScript, and lock the manager when idle.
- Clear only the affected resource cache, sign in through an email link, and search Cyrillic options in multi-select TVs.

This is an independent port. The original GPL v2 license and author attribution
are retained in `core/components/admintools3/docs`.

## Build

Use PHP 8.1 or newer and a configured MODX 3 installation:

```text
php build/build.php C:/path/to/modx-site
```

The transport archive is written to `packages/` in this repository. The build
reads the MODX configuration and database workspace but does not install the
package or modify site data. Install the resulting archive through MODX Package
Management.

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
