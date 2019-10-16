# Portland Migrations

A module for managing migrations on portland.gov.

## Migrations

This module includes these migration configurations.

- eudaly_news - imports content from the included eudaly_news.csv source into the news entity
- eudaly_news_group_content - creates group_content entities that associate the imported news nodes with Commissioner Eudaly's group
- category_documents - imports content from the included category_documents.csv source into the document media entity
- category_documents_group_content - creates group_content entities that associate the imported document media entities with Commissioner Eudaly's group
- parks - imports parks.csv. Creates Park Facility content items that other parks migrations depend on.
- park_amenities - import park amenities into three taxonomy vocabularies in POWR: Park Location Type, Park amenities/activities, Reservations Available.
- park_photos - download images and associate them with the matching park.
- park_documents - download documents and associate them with the matching park.

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

* Create new column to the right of SUMMARY_TEXT. Copy the contents of SUMMARY_TEXT into the new empty column and change the header to POLICY_NUMBER.
* Manually scan through the SUMMARY_TEXT column and delete any value that is not summary text.
* Manually scan through the POLICY_NUMBER column and delete or clean up any value that is not in the policy number format: BCP-ADM-1.01 (there are a few cases where the authors felt the need to prefix the policy number with the bureau name).

##### Supplemental file: policies_categories.csv

This is a simple list of 2nd level categories in its own csv file. The list was manually generated due to the relatively low number of items and the
difficulty in generating it dyanmically. The list is not expected to change prior to final migration. The 3rd level categories are inclueded in the
main policies datafile, and are created as children of their parent 2nd level categories and linked to the content using a custom process plugin.

WARNING: the Finance (FIN) category has been omitted from the list becasue it already exists in the live beta database and causes a duplicate entry
when the migration is run.

#### Supplemental file: policies_types.csv

This file was manually generated. Unless a new policy type is implemented before final migration (highly unlikely), the file can be used as-is from the repository.
It includes 3 columns: TYPE_NAME, TYPE_CODE, and DESCRIPTION.

### Running the migrations

The Migrate Tools module provides drush commands to run the migrations. The order of commands is important! When running the migrations on remote servers, such as multidev or Dev/Test/Live, use the terminus commands. Example:

```
lando terminus drush [environment] migrate:import [migration_id]
```

For multidev environments, the environment id is formatted like this: portlandor.powr-1234.

#### Timeouts

Long migrations run through terminus may exceed the Pantheon timeout and be terminated with a message such as, "Connection to appserver.powr-1284.5c6715db-abac-4633-ada8-1c9efe354629.drush.in closed by remote host." There is no way to increase this timeout, but a workaround exists. The migration may be reset and restarted. It will pick up where it left off. This may need to be done multiple times for long migrations.


#### Eudaly news

##### Local
```
drush migrate:import eudaly_news
drush migrate:import eudaly_news_group_content
```
##### On Pantheon
```
lando terminus remote:drush portlandor.powr-[ID] -- migrate:import eudaly_news
lando terminus remote:drush portlandor.powr-[ID] -- migrate:import eudaly_news_group_content
```

#### Category documents for Eudaly news

##### Local
```
drush migrate:import category_documents
drush migrate:import category_documents_group_content
```
##### On Pantheon
```
lando terminus remote:drush portlandor.powr-[ID] -- migrate:import category_documents
lando terminus remote:drush portlandor.powr-[ID] -- migrate:import category_documents_group_content
```

#### City charter

##### Local
```
drush migrate:import city_charter_chapters
drush migrate:import city_charter_articles
drush migrate:import city_charter_sections
```
##### On Pantheon
```
lando terminus remote:drush portlandor.powr-[ID] -- migrate:import city_charter_chapters
lando terminus remote:drush portlandor.powr-[ID] -- migrate:import city_charter_articles
lando terminus remote:drush portlandor.powr-[ID] -- migrate:import city_charter_sections
```

#### Parks

##### Local
```
drush migrate:import parks
drush migrate:import park_amenities
drush migrate:import park_documents
drush migrate:import park_photos
```
##### On Pantheon
```
lando terminus remote:drush portlandor.powr-[ID] -- migrate:import parks
lando terminus remote:drush portlandor.powr-[ID] -- migrate:import park_amenities
lando terminus remote:drush portlandor.powr-[ID] -- migrate:import park_documents
lando terminus remote:drush portlandor.powr-[ID] -- migrate:import park_photos
```

#### City Code

##### Local
```
drush migrate:import city_code_titles
drush migrate:import city_code_chapters
drush migrate:import city_code_sections
```
##### On Pantheon
```
lando terminus remote:drush portlandor.powr-[ID] -- migrate:import city_code_titles
lando terminus remote:drush portlandor.powr-[ID] -- migrate:import city_code_chapters
lando terminus remote:drush portlandor.powr-[ID] -- migrate:import city_code_sections
```

#### City policies

##### Local
```
drush migrate:import policies_categories
drush migrate:import policies_types
drush migrate:import policies
```
##### On Pantheon
```
lando terminus remote:drush portlandor.powr-[ID] -- migrate:import policies_categories
lando terminus remote:drush portlandor.powr-[ID] -- migrate:import policies_types
lando terminus remote:drush portlandor.powr-[ID] -- migrate:import policies
```

If the migration does not complete due to timeout on a Pantheon environment, you may need to reset the status of a migration to then continue the migration with a second command.

```
lando terminus remote:drush portlandor.powr-[ID] -- migrate:status
lando terminus remote:drush portlandor.powr-[ID] -- mrs policies
```
"mrs" is shorthand for migrate:reset-status. You can do this for a specific migration or all active migrations.

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
