# ComposerBundleMigration

Migrate Doctrine Migrations files from installed bundles to the root package.

Installation
-------------

`composer require champs-libres/composer-bundle-migration`

Usage in root package
---------------------

In your **root package**, you may configure the directory where migrations files 
are copied.

In `composer.json`:

```
"extra": {
    "appMigrationsDir": "path/to/my/dir"
}
```

Default is `app/DoctrineMigrations` (the default for doctrine-migrations-bundle).

Usage in installed bundles
----------------------------

In the installed bundles, you may also configure the directory where migrations files
are located. 

```
"extra": {
    "migration-source": "path/to/my/source/folder"
}
```

Default is `Resources/migrations`.
