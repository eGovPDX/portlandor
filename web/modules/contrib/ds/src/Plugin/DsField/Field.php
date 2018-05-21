<?php

namespace Drupal\ds\Plugin\DsField;

use Drupal\Core\Template\Attribute;

/**
 * The base plugin to create DS fields.
 */
abstract class Field extends DsFieldBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();

    // Initialize output.
    $output = '';

    // Basic string.
    $entity_render_key = $this->entityRenderKey();

    if (isset($config['link text'])) {
      $output = $this->t($config['link text']);
    }
    elseif (!empty($entity_render_key) && isset($this->entity()->{$entity_render_key})) {
      if ($this->getEntityTypeId() == 'user' && $entity_render_key == 'name') {
        $output = $this->entity()->getUsername();
      }
      else {
        $output = $this->entity()->{$entity_render_key}->value;
      }
    }

    if (empty($output)) {
      return [];
    }

    $template = <<<TWIG
{% if wrapper %}
<{{ wrapper }}{{ attributes }}>
{% endif %}
{% if is_link %}
  {{ link(output, entity_url) }}
{% else %}
  {{ output }}
{% endif %}
{% if wrapper %}
</{{ wrapper }}>
{% endif %}
TWIG;

    // Sometimes it can be impossible to make a link to the entity, because it
    // has no id as it has not yet been saved, e.g. when previewing an unsaved
    // inline entity form.
    $is_link = FALSE;
    $entity_url = NULL;
    if (!empty($this->entity()->id())) {
      $is_link = !empty($config['link']);
      $entity_url = $this->entity()->toUrl();
      if (!empty($config['link class'])) {
        $entity_url->setOption('attributes', ['class' => explode(' ', $config['link class'])]);
      }
    }

    // Build the attributes.
    $attributes = new Attribute();
    if (!empty($config['class'])) {
      $attributes->addClass($config['class']);
    }

    return [
      '#type' => 'inline_template',
      '#template' => $template,
      '#context' => [
        'is_link' => $is_link,
        'wrapper' => !empty($config['wrapper']) ? $config['wrapper'] : '',
        'attributes' => $attributes,
        'entity_url' => $entity_url,
        'output' => $output,
      ],
    ];
  }

  /**
   * Returns the entity render key for this field.
   */
  protected function entityRenderKey() {
    return '';
  }

}
