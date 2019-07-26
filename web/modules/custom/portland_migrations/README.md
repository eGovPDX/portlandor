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

### CSV files manual modifications

For some of the content migrations, the exported data must be massaged to avoid complex migration routines.

#### City policies

##### Modifications to policies.csv

* Create 2 new columns to the right of SUMMARY_TEXT. In the top header row, add the headers POLICY_TYPE and POLICY_NUMBER.
* In the POLICY_TYPE column, starting in row 5, add the formula ```=LEFT(I5, 3)``` and copy it down to the rest of the cells in the column. This splits out the policy type code for each row. This value is used as the key to link the policies to the policy type taxonomy entity references during migration.
* In the POLICY_NUMBER column, starting in row 5, add the formula ```=I5``` and copy it down to the rest of the cells in the column. This simply copies the policy number value out of the Summary for each row. The first few rows aren't policy pages and contain actual summary text that shouldn't be copied into the policy number field, so we'll use the new derived field for migration.

##### Supplemental file: policies_categories.csv

This is a simple list of categories in its own csv file. The list can be generated using the UNIQUE formula against the CATEGORY_NAME column in policies.csv.

#### Supplemental file: policies_types.csv

This file was manually generated. Unless a new policy type is implemented before final migration (highly unlikely), the file can be used as-is from the repository.
It includes 3 columns: TYPE_NAME, TYPE_CODE, and DESCRIPTION.

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
#### City policies
```
drush migrate:import policies_catgegories
drush migrate:import policies_types
drush migrate:import policies


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
