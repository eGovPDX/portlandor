<?php

require 'autoload.php';

use Drupal\portland_webforms\Plugin\WebformElement\PortlandLocationWidget;

$widget = new PortlandLocationWidget();
var_dump($widget->getCompositeElements());
