<?php

namespace Drupal\portland_migrations\Plugin\migrate\process;

use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Xss;
use Drupal\media\Entity\Media;

/**
 * Description of what the plugin does
 *
 * @MigrateProcessPlugin(
 *   id = "migrate_linked_media"
 * )
 */
class MigrateLinkedMedia extends ProcessPluginBase {
  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {

    // when run against a HTML content field during migration, the following things happen:

    // * Look for all internal links (either start with "/" or "http*://portlandoregon.gov")
    // ** Request target; check mime-type
    // ** If HTML, try to use path for lookup if page exists in new system; create a basic link
    // ** If document, create a media entity and replace the link tag with media-embed tag in body content

    preg_match_all('/<a [^>]+>/i', $value, $result);
    if (!empty($result[0])) {
      // load content into xpath dom so we can search it more easily
      $dom = Html::load($value);
      $xpath = new \DOMXPath($dom);

      // determine download folder name (YYYY-MM)
      $folder = date("Y-m") ;
      $public_path = \Drupal::service('file_system')->realpath(file_default_scheme() . "://");
      $download_path = $public_path . "/" . $folder;
      $result = file_prepare_directory($download_path, FILE_CREATE_DIRECTORY);

      // look for links with an href
      foreach ($xpath->query('//a[@href]') as $link) {

        $link_text = $link->textContent;
        if (function_exists('transliterate_filenames_transliteration')) {
          $filename = transliterate_filenames_transliteration($link_text, $source_langcode);
        }

        // parse url from link
        $url = $link->getAttribute('href'); // /shared/cfm/image.cfm?id=25873
        if (substr(0, 26) != "https://portlandoregon.gov" && substr(0,125) != "https://www.portlandoregon.gov") {
          $url = "https://www.portlandoregon.gov" . $url;
        }

        // need uri for managed files
        $uri = file_build_uri($folder);

        // download and store file
        $orig_filename = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_FILENAME);
        if (!file_exists($uri . '/' . $orig_filename)) {
          $result = system_retrieve_file($url, $uri, TRUE);
        } else {
          $files = \Drupal::entityTypeManager()->getStorage('file')->loadByProperties(['uri' => $uri]);
          $result = $files;
        }
        if (!$result) continue;
        
        $absolute_path = \Drupal::service("file_system")->realpath($uri . "/" . $result->filename->value);

        // get mime type
        $mime_type = mime_content_type($absolute_path); // application/pdf

        // determine extension from mime type
        switch ($mime_type) {
          case "application/pdf":
            $ext = "pdf";
            $type = "document";
            break;
          case "application/msword":
            $ext = "doc"; 
            $type = "document";
            break;
          case "application/vnd.openxmlformats-officedocument.wordprocessingml.document":
            $ext = "docx"; 
            $type = "document";
            break;
          case "application/vnd.ms-excel":
            $ext = "xls"; 
            $type = "document";
            break;
          case "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet":
            $ext = "xlsx"; 
            $type = "document";
            break;
          case "application/vnd.ms-powerpoint":
            $ext = "ppt"; 
            $type = "document";
            break;
          case "application/vnd.openxmlformats-officedocument.presentationml.presentation":
            $ext = "pptx"; 
            $type = "document";
            break;
          case "image/jpeg":
            $ext = "jpg"; 
            $type = "image";
            break;
          case "image/gif":
            $ext = "gif"; 
            $type = "image";
            break;
          case "image/png":
            $ext = "png"; 
            $type = "image";
            break;
          case "text/html":
            // it's a link to a page, delete it instead of naming it
            $result->delete();
            continue 2;
          default:
            // other, log it
            $message = "Unhandled download encountered in migration: " . $mime_type . "<br>File path: " . $absolute_path . "<br>From URL: " . $url;
            \Drupal::logger('portland_migrations')->notice($message);
            continue 2;
        }

        // rename file if it doesn't already exist.
        // TODO: if it does exist, delete the temp file and don't rename.
        $file = \Drupal\file\Entity\File::load($result->fid->value);
        $new_filename = $folder . "/" . $filename . "." . $ext;
        $stream_wrapper = \Drupal::service('file_system')->uriScheme($file->getFileUri());
        $new_filename_uri = "{$stream_wrapper}://{$new_filename}";
        file_move($file, $new_filename_uri);

        // create media object for file (image or document)
        if ($type == "document") {
          $media = Media::create([
            'bundle' => 'document',
            'uid' => \Drupal::currentUser()->id(),
            'langcode' => \Drupal::languageManager()->getDefaultLanguage()->getId(),
            'name' => $link_text,
            'field_document' => [
              'target_id' => $file->id()
            ],
          ]);
          $media->setPublished();
          $media->save();
        }
        $uuid = $media->uuid();

        // replace link with entity-embed tag

        $node = $dom->createElement("drupal-entity");
        $newnode = $dom->appendChild($node);
        $newnode->setAttribute("data-entity-type", "media");
        $newnode->setAttribute("data-entity-uuid", $uuid);
        $newnode->setAttribute("data-langcode", "en");

        if ($type == "document") {
          $newnode->setAttribute("data-embed-button", "document_browser");
          $newnode->setAttribute("data-entity-embed-display", "view_mode:media.embedded");
        } else {
          $newnode->setAttribute("data-embed-button", "image_browser");
          $newnode->setAttribute("data-entity-embed-display", "media_image");
        }

        $link->parentNode->replaceChild($newnode, $link);
      }
    }
  }

  function portland_migrations_parseUrl($link) {
    return $url;
  }

  function portland_migrations_getFileType($url) {
  }
}