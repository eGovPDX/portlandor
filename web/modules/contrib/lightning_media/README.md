# Lightning Media
Lightning Media provides modules and configuration for great media authoring
experiences.

### Components
Lightning Media's components are not enabled by default. You can install them
like any other Drupal modules.

#### Bulk Media Upload (`lightning_media_bulk_upload`)
Leverages the [DropzoneJS](https://drupal.org/project/dropzonejs) module to
provide a form for uploading media assets in bulk.

#### Media Document (`lightning_media_document`)
Provides a Document media type, which can be used for storing files, such as
PDFs, or other types of documents.

#### Media Image (`lightning_media_image`)
Provides an Image media type, which can be used for storing and displaying
images. If the [Image Widget Crop](https://drupal.org/project/image_widget_crop)
module is installed, the Image media type will automatically integrate with
it to provide cropping functionality.

#### Media Instagram (`lightning_media_instagram`)
Provides an Instagram media type, for referencing and displaying Instagram
posts in your Drupal site using the
[Media Entity Instagram](https://drupal.org/project/media_entity_instagram)
module.

#### Media Twitter (`lightning_media_twitter`)
Provides a Tweet media type, for referencing and displaying tweets in your
Drupal site using the
[Media Entity Twitter](https://drupal.org/project/media_entity_twitter)
module.

#### Media Video (`lightning_media_video`)
Provides a Video media type for displaying remote videos (e.g., YouTube or
Vimeo) in your Drupal site using the
[Video Embed Field](https://drupal.org/project/video_embed_field) module.

### Installation
This component can only be installed using Composer. To add it to your Drupal
code base:

```
composer config repositories.drupal composer https://packages.drupal.org/8
composer require drupal/lightning_media
```

#### Updates
Lightning Media and its components use the normal Drupal database update system
as often as possible. However, there are occasionally certain updates which
touch configuration and may change the functionality of your site. These updates
are optional, and are performed by a special utility at the command line. This 
utility is compatible with both 
[Drupal Console](https://github.com/hechoendrupal/drupal-console) and
[Drush](https://drush.org) 9 or later.

To run updates using Drush 9:

`
drush update:lightning
`

With Drupal Console:

`
drupal update:lightning
`

#### Known Issues
* If you upload an image into an image field using the new image browser, you
  can set the image's alt text at upload time, but that text will not be
  replicated to the image field. This is due to a limitation of Entity Browser's
  API.
* Using the bulk upload feature in environments with a load balancer might
  result in some images not being saved.
