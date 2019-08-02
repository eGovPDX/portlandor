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
 * The MigrateLinkedMedia plugin parses content and looks for images and links to documents.
 * If found, the source URL is used to download and store the file as a media entity, and the
 * link or image tag is converted to an entity-embed element.
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

    preg_match_all('/<a [^>]+>|<img [^>]+>/i', $value, $result);
    if (!empty($result[0])) {
      // load content into xpath dom so we can search it more easily
      $dom = Html::load($value);
      $xpath = new \DOMXPath($dom);
      // do we need to deal with case sensitivity?
      // $xpath->registerNamespacce('php', 'http://php.net/xpath');
      // $xpath->registerPhpFunctions();

      // prepare download directory
      $folder_name = date("Y-m") ;
      $folder_uri = file_build_uri($folder_name);
      $public_path = \Drupal::service('file_system')->realpath(file_default_scheme() . "://");
      $download_path = $public_path . "/" . $folder_name;
      $dir = file_prepare_directory($download_path, FILE_CREATE_DIRECTORY);

      // look for links with an href and save the linked file
      foreach ($xpath->query('//a[@href]|//img[@src]') as $link) {

        // parse url from link; it will be in an href or src attribute.
        $url = $link->getAttribute('href');
        if (is_null($url)) {
          $url = $link->getAttribute('src');
        }

        // if url is empty or null, skip it and continue
        if (is_null($url) || $url == "") {
          continue;
        }

        // if url is relative, prefix it with the POG domain
        if (substr($url, 0, 1) == "/") {
          $url = "https://www.portlandoregon.gov" . $url;
        }

        $url_host = parse_url($url, PHP_URL_HOST);

        // if host is anything but www.portlandoregon.gov or www.portlandonline.gov,
        // leave the link alone and continue. we don't want to download and embed
        // external link targets.
        if ($url_host != "www.portlandoregon.gov" && $url_host != "www.portlandonline.gov") {
          continue;
        } else {
          $internal_link = true;
        }

        // download and store raw file with whatever name was in the URL, or retrieve it if it already exists
        $file_uri = $folder_uri . '/' . pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_FILENAME);
        if (!file_exists($file_uri)) {
          $result = system_retrieve_file($url, $file_uri, TRUE);
        } else {
          $result = \Drupal::entityTypeManager()->getStorage('file')->loadByProperties(['uri' => $file_uri]);
          if (is_array($result)) {
            $result = reset($result);
          }
        }

        // process the file: 
        // delete it if it's not a type we want, or rename it with a more descriptive filename
        // and appropriate extension. when we saved the raw file from POG, it was saved with
        // whatever name was in the URL (typically the content id).

        $absolute_path = \Drupal::service("file_system")->realpath($file_uri);
        $mime_type = mime_content_type($absolute_path);

        switch ($mime_type) {
          case "application/pdf":
            $file_info = ["pdf", "document"]; break;
          case "application/msword":
            $file_info = ["doc", "document"]; break;
          case "application/vnd.openxmlformats-officedocument.wordprocessingml.document":
            $file_info = ["docx", "document"]; break;
          case "application/vnd.ms-excel":
            $file_info = ["xls", "document"]; break;
          case "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet":
            $file_info = ["xlsx", "document"]; break;
          case "application/vnd.ms-powerpoint":
            $file_info = ["ppt", "document"]; break;
          case "application/vnd.openxmlformats-officedocument.presentationml.presentation":
            $file_info = ["pptx", "document"]; break;
          case "image/jpeg":
            $file_info = ["jpg", "image"]; break;
          case "image/gif":
            $file_info = ["gif", "image"]; break;
          case "image/png":
            $file_info = ["png", "image"]; break;
          case "text/html":
            $file_info = ["html", null]; break;
          default:
            $file_info = null; break;
        }

        // // TODO: if url is relative and mime type is html, do we need to update the url to be from POG, or just keep it relative?
        if ($file_info[0] == "html" && $internal_link) {
          $link->setAttribute("href", $url);
        }

        // if ext is a type we have not defined, log it, delete the file, and continue the loop.
        if (is_null($file_info[1])) {
          $message = "Undefined download encountered in migration: " . $mime_type . "<br>File path: " . $absolute_path . "<br>From URL: " . $url;
          \Drupal::logger('portland_migrations')->notice($message);
          $result->delete();
          continue;
        }

        // use link text as file name.
        $link_text = $link->textContent;
        $filename = $link_text;
        if (function_exists('transliterate_filenames_transliteration')) {
          $filename = transliterate_filenames_transliteration($link_text, $source_langcode);
        }

        // rename file if renamed file doesn't alredy exist
        $file = \Drupal\file\Entity\File::load($result->fid->value);
        $new_filename = $folder_name . "/" . $filename . "." . $file_info[0];
        $stream_wrapper = \Drupal::service('file_system')->uriScheme($file->getFileUri());
        $new_filename_uri = "{$stream_wrapper}://{$new_filename}";
        if (!file_exists($new_filename_uri)) {
          $move_result = file_move($file, $new_filename_uri);
        } else {
          // renamed file already exists. delete temp file, load existing renamed file, and 
          // reference that in the embed tag.
          $file->delete();
          $existing_files = \Drupal::entityTypeManager()->getStorage('file')->loadByProperties(['uri' => $folder_uri . '/' . $filename . '.' . $file_info[0]]);
          $file = reset($existing_files); // should only ever be 1 file returned
        }

        // create media entity for file (image or document)
        if ($file_info[1] == "document") {
          $media = Media::create([
            'bundle' => 'document',
            'uid' => 1,
            'langcode' => \Drupal::languageManager()->getDefaultLanguage()->getId(),
            'name' => $link_text,
            'status' => 1,
            'field_document' => [
              'target_id' => $file->id()
            ],
          ]);
        } else if ($file_info[1] == "image") {
          $media = Media::create([
            'bundle' => 'image',
            'uid' => 1,
            'langcode' => \Drupal::languageManager()->getDefaultLanguage()->getId(),
            'name' => $link_text,
            'status' => 1,
            'image' => [
              'target_id' => $file->id()
            ],
          ]);
        }
        $media->save();
        $media->setPublished(TRUE)
              ->save();
        $uuid = $media->uuid();

        // replace <a> or <img> with <entity-embed>
        $node = $dom->createElement("drupal-entity");
        $newnode = $dom->appendChild($node);
        $newnode->setAttribute("data-entity-type", "media");
        $newnode->setAttribute("data-entity-uuid", $uuid);
        $newnode->setAttribute("data-langcode", "en");
        if ($file_info[1] == "document") {
          $newnode->setAttribute("data-embed-button", "document_browser");
          $newnode->setAttribute("data-entity-embed-display", "view_mode:media.embedded");
        } else {
          $newnode->setAttribute("data-embed-button", "image_browser");
          $newnode->setAttribute("data-entity-embed-display", "media_image");
        }
        $link->parentNode->replaceChild($newnode, $link);
      }
      $output = Html::serialize($dom);
      $value = $output;
    }

    return $value;
  }

}