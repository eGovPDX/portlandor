<?php

/**
 * @file
 * Display Suite example layout configuration.
 */

/**
 * Defines a Display Suite layout.
 */
function ds_example_layout() {
  return [
    'label' => t('Example layout'),
    'regions' => [
      'left' => t('Left'),
      'right' => t('Right'),
    ],
    // Uncomment if you want to include a CSS file for this layout (example_layout.css).
    // 'css' => TRUE,
    // Uncomment if you want to include a preview for this layout (example_layout.png).
    // 'image' => TRUE,
  ];
}
