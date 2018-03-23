<?php
  /**
   * Pantheon drush alias file, to be placed in your ~/.drush directory or the aliases
   * directory of your local Drush home. Once it's in place, clear drush cache:
   *
   * drush cc drush
   *
   * To see all your available aliases:
   *
   * drush sa
   *
   * See http://helpdesk.getpantheon.com/customer/portal/articles/411388 for details.
   */

  $aliases['portlandor.dev'] = array(
    'uri' => 'dev-portlandor.pantheonsite.io',
    'db-url' => 'mysql://pantheon:7970089e81f64e6db130b2012d704c75@dbserver.dev.5c6715db-abac-4633-ada8-1c9efe354629.drush.in:16163/pantheon',
    'db-allows-remote' => TRUE,
    'remote-host' => 'appserver.dev.5c6715db-abac-4633-ada8-1c9efe354629.drush.in',
    'remote-user' => 'dev.5c6715db-abac-4633-ada8-1c9efe354629',
    'ssh-options' => '-p 2222 -o "AddressFamily inet"',
    'path-aliases' => array(
      '%files' => 'code/sites/default/files',
      '%drush-script' => 'drush',
     ),
  );
  $aliases['portlandor.fix-temp'] = array(
    'uri' => 'fix-temp-portlandor.pantheonsite.io',
    'db-url' => 'mysql://pantheon:6d9958380e9542b695f7e17a64f75b82@dbserver.fix-temp.5c6715db-abac-4633-ada8-1c9efe354629.drush.in:18390/pantheon',
    'db-allows-remote' => TRUE,
    'remote-host' => 'appserver.fix-temp.5c6715db-abac-4633-ada8-1c9efe354629.drush.in',
    'remote-user' => 'fix-temp.5c6715db-abac-4633-ada8-1c9efe354629',
    'ssh-options' => '-p 2222 -o "AddressFamily inet"',
    'path-aliases' => array(
      '%files' => 'code/sites/default/files',
      '%drush-script' => 'drush',
     ),
  );
  $aliases['portlandor.test'] = array(
    'uri' => 'test-portlandor.pantheonsite.io',
    'db-url' => 'mysql://pantheon:be1a99dc8ec84f7597e13d52e06d88ee@dbserver.test.5c6715db-abac-4633-ada8-1c9efe354629.drush.in:12342/pantheon',
    'db-allows-remote' => TRUE,
    'remote-host' => 'appserver.test.5c6715db-abac-4633-ada8-1c9efe354629.drush.in',
    'remote-user' => 'test.5c6715db-abac-4633-ada8-1c9efe354629',
    'ssh-options' => '-p 2222 -o "AddressFamily inet"',
    'path-aliases' => array(
      '%files' => 'code/sites/default/files',
      '%drush-script' => 'drush',
     ),
  );
  $aliases['portlandor.live'] = array(
    'uri' => 'live-portlandor.pantheonsite.io',
    'db-url' => 'mysql://pantheon:4e1dda8c4b9242e18dcd6c65abe85310@dbserver.live.5c6715db-abac-4633-ada8-1c9efe354629.drush.in:15415/pantheon',
    'db-allows-remote' => TRUE,
    'remote-host' => 'appserver.live.5c6715db-abac-4633-ada8-1c9efe354629.drush.in',
    'remote-user' => 'live.5c6715db-abac-4633-ada8-1c9efe354629',
    'ssh-options' => '-p 2222 -o "AddressFamily inet"',
    'path-aliases' => array(
      '%files' => 'code/sites/default/files',
      '%drush-script' => 'drush',
     ),
  );
  $aliases['portlandor.powr-46'] = array(
    'uri' => 'powr-46-portlandor.pantheonsite.io',
    'db-url' => 'mysql://pantheon:0da0f3ff35244bc98cc3e09f951fe6d4@dbserver.powr-46.5c6715db-abac-4633-ada8-1c9efe354629.drush.in:20594/pantheon',
    'db-allows-remote' => TRUE,
    'remote-host' => 'appserver.powr-46.5c6715db-abac-4633-ada8-1c9efe354629.drush.in',
    'remote-user' => 'powr-46.5c6715db-abac-4633-ada8-1c9efe354629',
    'ssh-options' => '-p 2222 -o "AddressFamily inet"',
    'path-aliases' => array(
      '%files' => 'code/sites/default/files',
      '%drush-script' => 'drush',
     ),
  );
  $aliases['portlandor.powr-61'] = array(
    'uri' => 'powr-61-portlandor.pantheonsite.io',
    'db-url' => 'mysql://pantheon:293e37c8c038479db48fab46c9ef2bd3@dbserver.powr-61.5c6715db-abac-4633-ada8-1c9efe354629.drush.in:14052/pantheon',
    'db-allows-remote' => TRUE,
    'remote-host' => 'appserver.powr-61.5c6715db-abac-4633-ada8-1c9efe354629.drush.in',
    'remote-user' => 'powr-61.5c6715db-abac-4633-ada8-1c9efe354629',
    'ssh-options' => '-p 2222 -o "AddressFamily inet"',
    'path-aliases' => array(
      '%files' => 'code/sites/default/files',
      '%drush-script' => 'drush',
     ),
  );
  $aliases['portlandor.powr-45'] = array(
    'uri' => 'powr-45-portlandor.pantheonsite.io',
    'db-url' => 'mysql://pantheon:da93938b70474816b580dd9d7f95989e@dbserver.powr-45.5c6715db-abac-4633-ada8-1c9efe354629.drush.in:12432/pantheon',
    'db-allows-remote' => TRUE,
    'remote-host' => 'appserver.powr-45.5c6715db-abac-4633-ada8-1c9efe354629.drush.in',
    'remote-user' => 'powr-45.5c6715db-abac-4633-ada8-1c9efe354629',
    'ssh-options' => '-p 2222 -o "AddressFamily inet"',
    'path-aliases' => array(
      '%files' => 'code/sites/default/files',
      '%drush-script' => 'drush',
     ),
  );
  $aliases['portland-alpha.dev'] = array(
    'uri' => 'dev-portland-alpha.pantheonsite.io',
    'db-url' => 'mysql://pantheon:14da607b03224ebc85f547c776e26fd6@dbserver.dev.fc59d93e-1db7-4322-9ccf-3d7b364906a9.drush.in:13998/pantheon',
    'db-allows-remote' => TRUE,
    'remote-host' => 'appserver.dev.fc59d93e-1db7-4322-9ccf-3d7b364906a9.drush.in',
    'remote-user' => 'dev.fc59d93e-1db7-4322-9ccf-3d7b364906a9',
    'ssh-options' => '-p 2222 -o "AddressFamily inet"',
    'path-aliases' => array(
      '%files' => 'code/sites/default/files',
      '%drush-script' => 'drush',
     ),
  );
  $aliases['portland-alpha.test'] = array(
    'uri' => 'test-portland-alpha.pantheonsite.io',
    'db-url' => 'mysql://pantheon:582e80e4d6e540a39a69c3bbdb57249c@dbserver.test.fc59d93e-1db7-4322-9ccf-3d7b364906a9.drush.in:13999/pantheon',
    'db-allows-remote' => TRUE,
    'remote-host' => 'appserver.test.fc59d93e-1db7-4322-9ccf-3d7b364906a9.drush.in',
    'remote-user' => 'test.fc59d93e-1db7-4322-9ccf-3d7b364906a9',
    'ssh-options' => '-p 2222 -o "AddressFamily inet"',
    'path-aliases' => array(
      '%files' => 'code/sites/default/files',
      '%drush-script' => 'drush',
     ),
  );
  $aliases['portland-alpha.live'] = array(
    'uri' => 'live-portland-alpha.pantheonsite.io',
    'db-url' => 'mysql://pantheon:a8144c3e666848b9b363ae5acacaac8c@dbserver.live.fc59d93e-1db7-4322-9ccf-3d7b364906a9.drush.in:13274/pantheon',
    'db-allows-remote' => TRUE,
    'remote-host' => 'appserver.live.fc59d93e-1db7-4322-9ccf-3d7b364906a9.drush.in',
    'remote-user' => 'live.fc59d93e-1db7-4322-9ccf-3d7b364906a9',
    'ssh-options' => '-p 2222 -o "AddressFamily inet"',
    'path-aliases' => array(
      '%files' => 'code/sites/default/files',
      '%drush-script' => 'drush',
     ),
  );
