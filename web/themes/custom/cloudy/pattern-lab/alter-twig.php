<?php

/**
 * @param Twig_Environment $env - The Twig Environment - https://twig.symfony.com/api/1.x/Twig_Environment.html
 * @param $config - Config of `@basalt/twig-renderer`
 */
function addCustomExtension(\Twig_Environment &$env, $config) {
  $env->addFilter( new \Twig\TwigFilter('t', function ($string) {
    return $string;
  }));
  $env->addFilter( new \Twig\TwigFilter('render', function ($string) {
    return $string;
  }));
}
