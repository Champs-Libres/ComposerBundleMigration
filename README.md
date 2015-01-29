# ComposerBundleMigration

Migrate Doctrine Migrations files from installed bundles to the root package.

Installation
-------------

In your root package :

`composer require champs-libres/composer-bundle-migration ~1.0`

Add the post-install-cmd and post-update-cmd in your root composer.json : 

```
"scripts": {
        "post-install-cmd": [
            "ComposerBundleMigration\\Composer\\Migrations::synchronizeMigrations"
        ],
        "post-update-cmd": [
            "ComposerBundleMigration\\Composer\\Migrations::synchronizeMigrations"
        ]      
    }
```

Configure migration directory
-----------------------------

In your **root package**, you may configure the directory where migrations files 
are copied.

In `composer.json`:

```
"extra": {
    "appMigrationsDir": "path/to/my/dir"
}
```

Default is `app/DoctrineMigrations` (the default for doctrine-migrations-bundle).

Configure migration source dir
------------------------------

In the installed packages, you may also configure the directory where migrations files
are located. 

```
"extra": {
    "migration-source": "path/to/my/source/folder"
}
```

Default is `Resources/migrations`.
