# Portland Migrations

A module for managing migrations on portland.gov.

## Migrations

This module includes 4 migration configurations.

- eudaly_news - imports content from the included eudaly_news.csv source into the news entity
- eudaly_news_group_content - creates group_content entities that associate the imported news nodes with Commissioner Eudaly's group
- category_documents - imports content from the included category_documents.csv source into the document media entity
- category_documents_group_content - creates group_content entities that associate the imported document media entities with Commissioner Eudaly's group

## Instructions

- Verify that the CSV files are in the proper location. See instructions below.
- Enable the Portland Migrations (`portland_migrations`) module.
- Run migrations using drush. See instructions below.

## CSV files location

The `path` configuration for the CSV source plugin accepts an absolute path or relative path from the Drupal installation folder.

The examples use a relative path and it is assumed that you place this module in a `modules/custom` folder. Therefore, the CSV files will be located at `modules/custom/portland_migrations/sources/`.

Not having the source CSV files in the proper location would trigger and error similar to:

```
[error]  Migration failed with source plugin exception: File path (modules/custom/portland_migrations/sources/eudaly_news.csv) does not exist.
```

If you want to place the files in a different location, you need to update the path in the corresponding configuration files. That is the `source:path` setting in the migration files.

### Running the migrations

The Migrate Tools module provides drush commands to run the migrations. The order of commands is important!

#### Eudaly news

```
drush migrate:import eudaly_news
drush migrate:import eudaly_news_group_content
```

#### DEMO: Category docuements

```
drush migrate:import category_documents
drush migrate:import category_documents_group_content
```
#### City charter
```
drush migrate:import city_charter_chapters
drush migrate:import city_charter_articles
drush migrate:import city_charter_sections
```
#### City Code
```
drush migrate:import city_code_titles
drush migrate:import city_code_chapters
drush migrate:import city_code_sections
```

**Note:** The commands above work for Drush 9. In Drush 8 the command names and aliases are different. Execute `drush list --filter=migrate` to verify the proper commands for your version of Drush.

After the migrations are run successfully, visit /admin/content to see the content that was imported.

### Gotcha

There is an issue when rolling back a migration that depends on another one that interacts with entity revisions. This affects group_content migrations.

For this migration, if the eudaly_news is rolled back and later imported again, the group_content will not be associated with the nodes.

To fix this, the dependent migration eudaly_news_group_content also needs to be rolled back and migrated again. This can be done via the UI or with the following drush commands:

```
drush migrate:rollback eudaly_news_group_content
drush migrate:rollback eudaly_news
drush migrate:import eudaly_news
drush migrate:import eudaly_news_group_content
```

**Note:** The commands above work for Drush 9. In Drush 8 the command names and aliases are different. Execute `drush list --filter=migrate` to verify the proper commands for your version of Drush.