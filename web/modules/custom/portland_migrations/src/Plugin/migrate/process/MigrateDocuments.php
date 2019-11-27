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
 * Using the same data source column as migrate_body_content_and_linked_media, 
 * this plugin scrapes the content in the TEXT column for documents. Each one
 * has already been saved as a managed file. We need to load each one and return
 * them as an array for insertion into the files field.
 * 
 * @MigrateProcessPlugin(
 *   id = "migrate_documents"
 * )
 */
class MigrateDocuments extends ProcessPluginBase {
  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {

    // reusable db connection
    if (is_null($_SESSION['policies_dbConn'])) {
      $_SESSION['policies_dbConn'] = \Drupal::database();
    }

    $return_value = [];

    preg_match_all('/<a [^>]+>|<img [^>]+>/i', $value, $downloaded_file);

    if (!empty($downloaded_file[0])) {
      $dom = Html::load($value);
      $xpath = new \DOMXPath($dom);

      // look for links with an href and save the linked file
      foreach ($xpath->query('//a[@href]|//img[@src]') as $link) {

        // parse url from link; it will be in an href or src attribute.
        $url = $link->getAttribute('href');
        if (is_null($url)) {
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
        $filename = $this->buildPogFilename($url);
        // if buildPogFilename returns false, that means either the URL didn't have a
        // Content-Disposition header (not a binary file), the URL returned 404, or it was
        // a link to the homepage/root.
        if ($filename === false) {
          continue;
        }

        $download_dir_uri = $this->getDownloadDirectoryUri();
        $destination_uri = $download_dir_uri . "/" . $filename;

        // get media enitity id by uri
        $query = "SELECT fid FROM file_managed WHERE uri = '$destination_uri'";
        $query = $_SESSION['policies_dbConn']->query($query);
        $result = $query->fetchAll();
        if (is_array($result) && count($result) > 1) {
          // duplicate document media entities exist! log it, but then use the first one found.
          $message = "Duplicate media entities found for $destination_uri. Using first one found, but consider removing duplicates.";
          \Drupal::logger('portland_migrations')->notice($message);
        }
        $fid = $result[0]->fid;
        //$downloaded_file = \Drupal\file\Entity\File::load($fid);

        //$entity_id = $result[0]->entity_id;

        if (!in_array($fid, $return_value)) {
          $return_value[] = $fid;
        }
      }
    }

    return $return_value;
  }

  protected function getDownloadDirectoryUri() {
    // prepare download directory
    $folder_name = "policies";
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

    return $final_filename;
  }


}