<?php

namespace Drupal\portland_smartsheet\Plugin\views\field;

use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Unicode;
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
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['trim_filename_chars'] = ['default' => 0];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['trim_filename_chars'] = [
      '#type' => 'number',
      '#title' => $this->t('Trim filenames to a maximum amount of characters'),
      '#description' => $this->t('If greater than 0, filenames will be trimmed to this amount of chars (including ellipsis), with an ellipsis appended.'),
      '#default_value' => $this->options['trim_filename_chars'],
      '#required' => false
    ];
  }

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
    $trim_filename_chars = $this->options['trim_filename_chars'];
    return [
      '#theme' => 'item_list',
      '#list_type' => 'ul',
      '#items' => array_map(
        fn($attachment) => [
          '#theme' => 'cloudy_file_link',
          '#file_display_name' =>
            $trim_filename_chars > 0
              ? Unicode::truncate($attachment->name, $trim_filename_chars, false, true)
              : $attachment->name,
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
