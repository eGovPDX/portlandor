# Portland Migrations

A module for migrating content from POG to portland.gov.

## Instructions

- Verify that the CSV files are in the proper location. See instructions below.
- Enable the Portland Migrations (`portland_migrations`) module.
- Run migrations using drush. The commands for running each migration are listed below. In most cases it is imperative to run the migrations in the order listed due to interdependencies.
- After running the migrations, visit /admin/content to view the imported content.

### Running migrations

The Migrate Tools module provides drush commands to run the migrations. The order of commands is important! When running the migrations on remote servers, such as multidev or Dev/Test/Live, use the terminus commands. For multidev environments, the environment id is formatted like this: portlandor.powr-1234. Example:

```
lando terminus drush [environment] -- migrate:import [migration_id]
```

Some migrations have interdependencies, such as eudaly_news, eudaly_news_group_content, and eudaly_news_redirects. You can automatically run all migrations in a group with:

```
lando terminus drush [environment] -- migrate:import --group=[migration_group]
```

After editing an existing migration, you need to run `lando drush cr` before it will pick up the changes.

If the migration reports that any items failed, you can see what the failed items were with `lando drush mmsg`

### Rolling back migrations

To roll back a migration, use the `migrate:rollback [migration_name]` command, and roll back migrations in the reverse order than they were originally rolled.

Some migrations have interdependencies, such as eudaly_news, eudaly_news_group_content, and eudaly_news_redirects. Interdependent migrations must all be rolled back together. This can be done automatically with:

```
lando terminus drush [environment] migrate:rollback --group=[migration_group]
```

### Timeouts

Long migrations run through terminus may exceed the Pantheon timeout and be terminated with a message such as, "Connection to appserver.powr-1284.5c6715db-abac-4633-ada8-1c9efe354629.drush.in closed by remote host." There is no way to increase this timeout, but a workaround exists. The migration may be reset and restarted. It will pick up where it left off. This may need to be done multiple times for long migrations.

For example, the policies migration is lengthy due to custom processing and regularly times out. Use the following commands to reset and restart the migration:

```
lando terminus remote:drush portlandor.powr-[ID] -- migrate:reset-status policies
lando terminus remote:drush portlandor.powr-[ID] -- migrate:import policies
```

You can also check the status of the migration using the `migrate:status` command:

```
lando terminus remote:drush portlandor.powr-[ID] -- migrate:status
```

## CSV files

The `path` configuration for the CSV source plugin accepts an absolute path or relative path from the Drupal installation folder.

The examples use a relative path and it is assumed that you place this module in a `modules/custom` folder. Therefore, the CSV files will be located at `modules/custom/portland_migrations/sources/`.

Not having the source CSV files in the proper location would trigger and error similar to:

```
[error]  Migration failed with source plugin exception: File path (modules/custom/portland_migrations/sources/eudaly_news.csv) does not exist.
```

If you want to place the files in a different location, you need to update the path in the corresponding configuration files. That is the `source:path` setting in the migration files.

### CSV files manual modifications

For some of the content migrations, the exported data may have been massaged to avoid complex migration routines. The necessary updates are described in the migrations sections below.

Windows users: When manually editing CSV files beware of file encodings. UTF-8 is the standard encoding, but Excel exports a CSV with the encoding "UTF-8 with BOM" which can cause errors during the import such as:

```
In Condition.php line 105:
Query condition 'taxonomy_term_field_data.name IN ()' cannot be empty.
```

### Python processing

Some migrations, such as the city charter and city code migrations, have Python scripts that perform post processing on the raw CSV exported from POG to generate the actual CSV used by the migration script.

#### Instructions to install and run Python in Lando

```
lando ssh -u root
apt update
apt install python3
apt install python3-pip
pip3 install pandas
pip3 install bs4
cd web/modules/custom/portland_migrations/sources/
python3 [script_name.py]
```

## Migrations

In addition to the primary content migrations, there are two supplemental migrations that are run for some content types: redirects and group_content.

Redirects migrations write entities to the redirects table. This is used for creating the Portland.gov legacy paths functionality, where the path from POG is linked to the corresponding page in the new site. Redirects migrations are named with the suffix "_redirects." *Example: eudaly_news_redirects*

Group content migrations are used to add content to a group by creating a group content entity. These migrations are named with the suffix "_group_content." *Example: eudaly_news_group_content*

### Migrations in this module

- eudaly_news - imports content from the included eudaly_news.csv source into the news entity
- category_documents - imports content from the included category_documents.csv source into the document media entity for the Eudaly news migration
- parks - imports parks.csv. Creates Park Facility content items that other parks migrations depend on.
- park_amenities - import park amenities into three taxonomy vocabularies in POWR: Park Location Type, Park amenities/activities, Reservations Available.
- park_photos - download images and associate them with the matching park.
- park_documents - download documents and associate them with the matching park.
- city_charter_chapters
- city_charter_articles
- city_charter_sections
- city_code_titles
- city_code_chapters
- city_code_sections
- policies_categories - imports taxonomy categories for city policies
- policies_types - imports taxonomy policy types
- policies
- wheeler_blog - Mayor Wheeler blog migration
- wheeler_press_releases - Mayor Wheeler press releases migration
- parks_news
- bds_news
- bds_service_updates
- bds_plans_examiner

#### Eudaly news
##### Local
```
lando drush migrate:import eudaly_news
lando drush migrate:import eudaly_news_group_content
lando drush migrate:import eudaly_news_redirects
```
##### On Pantheon
```
lando terminus remote:drush portlandor.powr-[ID] -- migrate:import eudaly_news
lando terminus remote:drush portlandor.powr-[ID] -- migrate:import eudaly_news_group_content
lando terminus remote:drush portlandor.powr-[ID] -- migrate:import eudaly_news_redirects
```

#### Category documents for Eudaly news
##### Local
```
lando drush migrate:import category_documents
lando drush migrate:import category_documents_group_content
```
##### On Pantheon
```
lando terminus remote:drush portlandor.powr-[ID] -- migrate:import category_documents
lando terminus remote:drush portlandor.powr-[ID] -- migrate:import category_documents_group_content
```

#### Parks
##### Local
```
lando drush migrate:import parks
lando drush migrate:import parks_redirects
lando drush migrate:import park_amenities
lando drush migrate:import park_documents
lando drush migrate:import park_photos
```
##### On Pantheon
```
lando terminus remote:drush portlandor.powr-[ID] -- migrate:import parks
lando terminus remote:drush portlandor.powr-[ID] -- migrate:import parks_redirects
lando terminus remote:drush portlandor.powr-[ID] -- migrate:import park_amenities
lando terminus remote:drush portlandor.powr-[ID] -- migrate:import park_documents
lando terminus remote:drush portlandor.powr-[ID] -- migrate:import park_photos
```

#### City charter
##### Local
```
lando drush migrate:import city_charter_chapters
lando drush migrate:import city_charter_chapters_redirects
lando drush migrate:import city_charter_articles
lando drush migrate:import city_charter_articles_redirects
lando drush migrate:import city_charter_sections
lando drush migrate:import city_charter_sections_redirects
```
##### On Pantheon
```
lando terminus remote:drush portlandor.powr-[ID] -- migrate:import city_charter_chapters
lando terminus remote:drush portlandor.powr-[ID] -- migrate:import city_charter_chapters_redirects
lando terminus remote:drush portlandor.powr-[ID] -- migrate:import city_charter_articles
lando terminus remote:drush portlandor.powr-[ID] -- migrate:import city_charter_articles_redirects
lando terminus remote:drush portlandor.powr-[ID] -- migrate:import city_charter_sections
lando terminus remote:drush portlandor.powr-[ID] -- migrate:import city_charter_sections_redirects
```

#### City code
##### Local
```
lando drush migrate:import city_code_titles
lando drush migrate:import city_code_titles_redirects
lando drush migrate:import city_code_chapters
lando drush migrate:import city_code_chapters_redirects
lando drush migrate:import city_code_sections
lando drush migrate:import city_code_sections_redirects
```
##### On Pantheon
```
lando terminus remote:drush portlandor.powr-[ID] -- migrate:import city_code_titles
lando terminus remote:drush portlandor.powr-[ID] -- migrate:import city_code_titles_redirects
lando terminus remote:drush portlandor.powr-[ID] -- migrate:import city_code_chapters
lando terminus remote:drush portlandor.powr-[ID] -- migrate:import city_code_chapters_redirects
lando terminus remote:drush portlandor.powr-[ID] -- migrate:import city_code_sections
lando terminus remote:drush portlandor.powr-[ID] -- migrate:import city_code_sections_redirects
```
##### Manual migrations for city code
The following city code sections have images that need to be manually migrated. There are few enough that it's not worth creating the custom plugin to handle them.
* 1.04.010 Description
* 11.40.050 Private Tree Permit Standards and Review Factors
* 11.60.030 Tree Protection Specifications
* 11.80.020 Definitions and Measurements
* 14A.50.030 Sidewalk Use
* 24.55.210 Major Residential Alterations and Additions
* 24.70.090 Setbacks (linked image)

#### City policies

##### Modifications to policies.csv
* Create new column to the right of SUMMARY_TEXT. Copy the contents of SUMMARY_TEXT into the new empty column and change the header to POLICY_NUMBER.
  * Manually scan through the SUMMARY_TEXT column and delete any value that is not summary text.
  * Manually scan through the POLICY_NUMBER column and delete or clean up any value that is not in the policy number format: BCP-ADM-1.01 (there are a few cases where the authors felt the need to prefix the policy number with the bureau name).
* Create a new column to the right of POLICY_NUMBER named numeric_order.
  * Fill this colum with the numeric part of the POLICY_NUMBER. You can use this formula in a spreadsheet to get that value: `=IF(LEN(J2), ABS(VALUE(REPLACE(J2, 1, 8, ""))), "")`
* Windows users: Make sure the CSV file uses UTF-8 encoding (not UTF-8 with BOM) as described in the "CSV files manual modifications" section.

##### Supplemental file: policies_categories.csv
This is a simple list of 2nd level categories in its own csv file. The list was manually generated due to the relatively low number of items and the difficulty in generating it dynamically. The list is not expected to change prior to final migration. The 3rd level categories are included in the main policies datafile and are created as children of their parent 2nd level categories and linked to the content using a custom process plugin.

##### Supplemental file: policies_types.csv
This file was manually generated. Unless a new policy type is implemented before final migration (highly unlikely), the file can be used as-is from the repository. It includes 3 columns: TYPE_NAME, TYPE_CODE, and DESCRIPTION.

##### Local
```
lando drush migrate:import policies_categories
lando drush migrate:import policies_categories_redirects
lando drush migrate:import policies_types
lando drush migrate:import policies
lando drush migrate:import policies_redirects
```
##### On Pantheon
```
lando terminus remote:drush portlandor.powr-[ID] -- migrate:import policies_categories
lando terminus remote:drush portlandor.powr-[ID] -- migrate:import policies_categories_redirects
lando terminus remote:drush portlandor.powr-[ID] -- migrate:import policies_types
lando terminus remote:drush portlandor.powr-[ID] -- migrate:import policies
lando terminus remote:drush portlandor.powr-[ID] -- migrate:import policies_redirects
```

#### Wheeler blog
NOTE: A duplicate entry SQL error is thrown on wheeler_press_releases_redirects due to one of the content items having been manually migrated to the live site for demo purposes. The page is titled, "City and County leaders pledge to move to 100 percent renewable energy." The error is a known issue, but make sure the page isn't duplicated after migration, and that the redirect exists.
##### Local
```
lando drush migrate:import wheeler_blog
lando drush migrate:import wheeler_blog_redirects
lando drush migrate:import wheeler_blog_group_content
```
##### On Pantheon
```
lando terminus remote:drush portlandor.powr-[ID] -- migrate:import wheeler_blog
lando terminus remote:drush portlandor.powr-[ID] -- migrate:import wheeler_blog_redirects
lando terminus remote:drush portlandor.powr-[ID] -- migrate:import wheeler_blog_group_content
```

#### Wheeler press releases
##### Local
```
lando drush migrate:import wheeler_press_releases
lando drush migrate:import wheeler_press_releases_redirects
lando drush migrate:import wheeler_press_releases_group_content
```
##### On Pantheon
```
lando terminus remote:drush portlandor.powr-[ID] -- migrate:import wheeler_press_releases
lando terminus remote:drush portlandor.powr-[ID] -- migrate:import wheeler_press_releases_redirects
lando terminus remote:drush portlandor.powr-[ID] -- migrate:import wheeler_press_releases_group_content
```

#### Parks news
WARNING: There are 3 rows in the parks_news.csv datafile that cause the import to break. The body content
of these 3 items has been deleted and will need to be migrated manually:
* 2018	687408	https://www.portlandoregon.gov/parks/article/687408	Gabriel Park playground to become PP&R's newest inclusive play area
* 2018	686668	https://www.portlandoregon.gov/parks/article/686668	PP&R, partners, celebrate start of Couch Park playground construction
* 2017	645700	https://www.portlandoregon.gov/parks/article/645700 PP&R, Comcast, present the 2017 Summer Free For All Field Day Celebration Kickoff

##### Local
```
lando drush migrate:import parks_news
lando drush migrate:import parks_news_group_content
lando drush migrate:import parks_news_redirects
```
##### On Pantheon
```
lando terminus remote:drush portlandor.powr-[ID] -- migrate:import parks_news
lando terminus remote:drush portlandor.powr-[ID] -- migrate:import parks_news_group_content
lando terminus remote:drush portlandor.powr-[ID] -- migrate:import parks_news_redirects
```

#### BDS News
##### Local
```
lando drush migrate:import bds_news
lando drush migrate:import bds_news_redirects
```
##### On Pantheon
```
lando terminus remote:drush portlandor.powr-[ID] -- migrate:import bds_news
lando terminus remote:drush portlandor.powr-[ID] -- migrate:import bds_news_redirects
```

#### BDS Service Updates
##### Local
```
lando drush migrate:import bds_service_updates
lando drush migrate:import bds_service_updates_redirects
```
##### On Pantheon
```
lando terminus remote:drush portlandor.powr-[ID] -- migrate:import bds_service_updates
lando terminus remote:drush portlandor.powr-[ID] -- migrate:import bds_service_updates_redirects
```

#### BDS Plans Examiner
##### Local
```
lando drush migrate:import bds_plans_examiner
lando drush migrate:import bds_plans_examiner_redirects
```
##### On Pantheon
```
lando terminus remote:drush portlandor.powr-[ID] -- migrate:import bds_plans_examiner
lando terminus remote:drush portlandor.powr-[ID] -- migrate:import bds_plans_examiner_redirects
```
