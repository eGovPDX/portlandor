## 2.1.0
* Security updated JSON API to 1.14 (Issue #2955026 and SA-CONTRIB-2018-016)

## 2.0.0
* Updated JSON API to 1.12.
* Updated core to 8.5.x and patched Simple OAuth to make it compatible.

## 1.0.0-rc3
* Lightning API will only set up developer-specific settings when our internal
  developer tools are installed.
* Our internal Entity CRUD test no longer tries to write to config entities via
  the JSON API because it is insecure and unsupported, at least for now.

## 1.0.0-rc2
* Security updated JSON API to version 1.10.0. (SA-CONTRIB-2018-15)  
  **Note:** This update has caused parts of our Config Entity CRUD test to fail
  so you might have trouble interacting with config entities via tha API.  

## 1.0.0-rc1
* Update JSON API to 1.7.0 (Issue #2933279)

## 1.0.0-alpha1
* Initial release
