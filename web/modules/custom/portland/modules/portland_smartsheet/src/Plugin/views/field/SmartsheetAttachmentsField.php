<?php

namespace Drupal\portland_smartsheet\Plugin\views\field;

use Drupal\Core\Url;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\portland_smartsheet\Controller\AttachmentDownloadController;

/**
 * Provide a field on Smartsheet views to show attachments on the row
 *
 * @ViewsField("smartsheet_attachments")
 */
class SmartsheetAttachmentsField extends FieldPluginBase {
  /**
   * {@inheritdoc}
   */
  public function usesGroupBy() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // do nothing -- to override the parent query.
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $row) {
    if (!isset($row->cells['_data']->attachments)) return '';

    $sheet_id = $this->query->options['sheet_id'];
    return [
      '#theme' => 'item_list',
      '#list_type' => 'ul',
      '#items' => array_map(
        fn($attachment) => [
          '#theme' => 'cloudy_file_link',
          '#file_display_name' => $attachment->name,
          '#mime_type' => $attachment->mimeType,
          '#document_size' => $attachment->sizeInKb * 1024,
          '#document_link' => Url::fromRoute(
            'portland_smartsheet.attachment_download',
            [
              'sheet_id' => $sheet_id,
              'attachment_id' => $attachment->id,
            ],
            [
              'query' => [
                'hash' => AttachmentDownloadController::getHmac($sheet_id, $attachment->id),
              ],
            ],
          )->toString(),
        ],
        $row->cells['_data']->attachments,
      ),
    ];
  }
}
