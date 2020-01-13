<?php

namespace Drupal\portland_migrations\Plugin\migrate\process;

use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Xss;
use Drupal\media\Entity\Media;
use Drupal\file\Entity\File;

/**
 * The MigrateLinkedMedia plugin parses content and looks for images and links to documents.
 * If found, the source URL is used to download and store the file as a media entity, and the
 * link or image tag is converted to an entity-embed element.
 *
 * @MigrateProcessPlugin(
 *   id = "migrate_wheeler_body_content"
 * )
 */
class MigrateWheelerBodyContent extends ProcessPluginBase {
  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {

    preg_match_all('/<a [^>]+>|<img [^>]+>/i', $value, $downloaded_file);
    if (!empty($downloaded_file[0])) {

      // migrated page title and POG URL, in case we need to report an error.
      $page_title = $row->getSourceProperty('CONTENT_NAME');
      $pog_url = $row->getSourceProperty('URL');

      $dom = Html::load($value);
      $xpath = new \DOMXPath($dom);

      $download_dir_uri = $this->generateDownloadDirectoryUri();

      // reusable db connection
      if (is_null($_SESSION['policies_dbConn'])) {
        $_SESSION['policies_dbConn'] = \Drupal::database();
      }

      // look for links with an href and save the linked file
      foreach ($xpath->query('//a[@href]|//img[@src]') as $link) {

        // parse url from link; it will be in an href or src attribute.
        $url = $link->getAttribute('href');
        if (is_null($url) || $url == "") {
          $url = $link->getAttribute('src');
        }

        // if url is empty or null, skip and continue
        if (is_null($url) || $url == "") {
          continue;
        }

        // if url is relative, prefix it with the POG domain; for downloading files,
        // we require fully qualified URLs. but we want to re-link to relative URLs.
        if (substr($url, 0, 1) == "/") {
          $url = "https://www.portlandoregon.gov" . $url;
        }

        // skip external links and leave the link tag alone
        $internal_link = $this->isInternalLink($url);
        if (!$internal_link) continue;

        // build filename/uri
        $arr_filename = $this->buildPogFilename($url);
        $filename = $arr_filename[0];
        $content_id = $arr_filename[1];

        // if buildPogFilename returns false, that means either the URL didn't have a
        // Content-Disposition header (not a binary file), the URL returned 404, or it was
        // a link to the homepage/root.
        if ($arr_filename === false) {
          continue;
        }
        $destination_uri = $download_dir_uri . "/" . $filename;

        $media_type = $this->getMediaType($filename);

        // FIND OR CREATE MEDIA ENTITY ////////////////
        // using the uri, see if the media entity already exists. if so, reference it. if not, create it.
        // we need to make sure we're not creating duplicate files. need separate queries for images and
        // documents based on $media_type

        if ($media_type == "document") {
          $query = "SELECT entity_id FROM file_managed FM 
                    INNER JOIN media__field_document FD on FM.fid = FD.field_document_target_id
                    WHERE uri = '$destination_uri'";
        } else {
          $query = "SELECT entity_id FROM file_managed FM 
                    INNER JOIN media__image IMG on FM.fid = IMG.image_target_id
                    WHERE uri = '$destination_uri'";
        }
        $query = $_SESSION['policies_dbConn']->query($query);
        $result = $query->fetchAll();

        if (!isset($result) || !is_array($result) || count($result) < 1) {
          // before saving new file, search for possible duplicates.
          // search criteria: same filename (sans POG contentid), same filesize
          $realpath_dir = drupal_realpath($download_dir_uri);
          $filename_search = str_replace($content_id, '*', $filename);

          $files = glob($realpath_dir . '/' . $filename_search);

          if ($files === false) {
            // an error occurred searching for files
            // do something?
            $halt = true;
          }

          if (is_array($files) && count($files) > 0) {
            // file may already exist, log it for review
            $message = "Possible duplicate file from POG found--same filename with different content id.<br><br>File: $realpath_dir/$filename_search<br>Page: $page_title<br>POG URL: $pog_url";
            \Drupal::logger('portland_migrations')->warning($message);
          }

          // media entity doesn't exist yet; download file and create entity...

          // download and save managed file
          try {
            $downloaded_file = system_retrieve_file($url, $destination_uri, TRUE);
          }
          catch (Exception $e) {
            $message = "Error occurred while trying to download URL target at " . $url . " and create managed file. Skipping file. Page: $page_title. Exception: " . $e->getMessage();
            \Drupal::logger('portland_migrations')->error($message);
            continue;
          }

          // when running this in multidev, an error is trown:
          // Error: Call to a member function id() on bool. Most likely occurring because $downloaded_file is false.
          // check for that condition and report it.
          if ($downloaded_file === FALSE) {
            $message = "Error retrieving newly downloaded file, likely 404.<br><br>Page: $page_title<br>URL: $pog_url";
            \Drupal::logger('portland_migrations')->error($message);
            continue;
          } else {
            // create media entity for file (image or document)
            if ($media_type == "document") {
              $media = Media::create([
                'bundle' => 'document',
                'uid' => 1,
                'langcode' => \Drupal::languageManager()->getDefaultLanguage()->getId(),
                'name' => $link->nodeValue,
                'status' => 1,
                'field_document' => [
                  'target_id' => $downloaded_file->id()
                ],
              ]);
              $plugin_id = "group_media:document";
            } else if ($media_type == "image") {
              $media = Media::create([
                'bundle' => 'image',
                'uid' => 1,
                'langcode' => \Drupal::languageManager()->getDefaultLanguage()->getId(),
                'name' => $filename,
                'status' => 1,
                'image' => [
                  'target_id' => $downloaded_file->id()
                ],
              ]);
              $plugin_id = "group_media:image";
            }
            $media->save();
            $media->setPublished(TRUE)
                  ->save();

            // now create group content entity to link this media item to a group.
            // Mayor Wheeler is group 71.
            // TODO: Is there a good way to dynamically get the group?
            $group = \Drupal\group\Entity\Group::load(71);
            $group->addContent($media, $plugin_id);
          }
        } else {
          // media entity already exists, get it
          $entity_id = $result[0]->entity_id;
          $media = Media::load($entity_id);
        }

        if ($downloaded_file && $media_type == "document") {
          $file_uri = $media->get('field_document')->entity->getFileUri();
          $file_url = file_url_transform_relative(file_create_url($file_uri));
          $link->setAttribute("href", $file_url);
        }

        // NOTE: Keep the original link tag but change the link href.
        // uuid is needed to create entity-embed tag
        $uuid = $media->uuid();
        // replace <a> or <img> with <entity-embed>
        $embed_tag_node = $dom->createElement("drupal-entity");
        $newnode = $dom->appendChild($embed_tag_node);
        $newnode = $this->buildEmbedTagNode($newnode, $embed_tag_node, $uuid, $media_type);
        $link->parentNode->replaceChild($newnode, $link);
      }
      $output = Html::serialize($dom);
      $value = $output;
    }

    return $value;
  }

  protected function generateDownloadDirectoryUri() {
    // prepare download directory
    $folder_name = date("Y-m") ;
    $folder_uri = file_build_uri($folder_name);
    return $folder_uri;
  }

  protected function isInternalLink($url) {
    $url_host = parse_url($url, PHP_URL_HOST);
    // if host is anything but www.portlandoregon.gov or www.portlandonline.gov,
    // leave the link alone and continue. we don't want to download and embed
    // external link targets.
    if ($url_host != "www.portlandoregon.gov" && $url_host != "www.portlandonline.gov") {
      return false;
    } else {
      return true;
    }
  }

  protected function getContentId($url) {

  }

  protected function buildPogFilename($url) {
    // get content id from URL, might be like /bts/38249 or /image.cfm?id=38249 or /shared/cfm/slb.cfm?id=38249 or /auditor/29194?a=256111
    // this is appended to filename to make sure it's unique.
    $content_id = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_FILENAME);
    // return immediately if no content_id; that means no filename/invalid URL
    if (!$content_id) return false;

    if (strtolower($content_id) == "image" || strtolower($content_id == "slb")) {
      $querystr = parse_url($url, PHP_URL_QUERY);
      $queries = array();
      parse_str($querystr, $queries);
      $content_id = $queries['id'];
    } else {
      $querystr = parse_url($url, PHP_URL_QUERY);
      $queries = array();
      parse_str($querystr, $queries);
      if (isset($queries['a'])) {
        $content_id = $queries['a'];
      }
    }

    // get name from Content-Disposition header; if not there, that means this isn't
    // a binary file URL, so we don't want it; return false.
    $headers = get_headers($url, 1);
    if (!isset($headers['Content-Disposition'])) {
      return false;
    }
    $content_disposition = $headers['Content-Disposition']; //"inline; filename="BCP_ENB_1.03EX.pdf""
    $parts = preg_split("/; /", $content_disposition);
    $filename_parts = preg_split("/=/", $parts[1]);
    $filename = preg_replace("/\"/", "", $filename_parts[1]);
    $basename = pathinfo($filename, PATHINFO_BASENAME);
    $extension = pathinfo($filename, PATHINFO_EXTENSION);
    $basename = preg_replace("/\.".$extension."/", "", $filename);

    if ($content_id == "image" || $content_id == "slb") {
      $final_filename = $basename . "." . $extension;
    } else {
      $final_filename = $basename . "-" . $content_id . "." . $extension;
    }

    // replace underscores with hypens
    $final_filename = preg_replace('/_/', '-', $final_filename);
    
    // transliterate filename to remove spaces, punctuation, illegal characters
    if (function_exists("transliterate_filenames_transliteration")) {
      $final_filename = transliterate_filenames_transliteration($final_filename);
    }

    return [$final_filename, $content_id];
  }

  protected function getMediaType($filename) {
    $ext = pathinfo($filename, PATHINFO_EXTENSION);
    switch ($ext) {
      case "jpg":
      case "jpeg":
      case "png":
      case "gif":
      case "bmp":
      case "tif":
      case "tiff":
        return "image";
        break;
      default:
        return "document";
        break;      
    }
  }

  protected function buildEmbedTagNode($newnode, $embed_tag_node, $uuid, $media_type) {
    $newnode->setAttribute("data-entity-type", "media");
    $newnode->setAttribute("data-entity-uuid", $uuid);
    $newnode->setAttribute("data-langcode", "en");
    if ($media_type == "document") {
      $newnode->setAttribute("data-embed-button", "document_browser");
      $newnode->setAttribute("data-entity-embed-display", "view_mode:media.embedded");
    } else {
      $newnode->setAttribute("data-embed-button", "image_browser");
      $newnode->setAttribute("data-entity-embed-display", "media_image");
      $newnode->setAttribute("data-align", "right");
    }
    return $newnode;
  }
}