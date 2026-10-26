echo "Import interface translations..."
drush locale:check 2> /dev/null
drush locale:update 2> /dev/null
drush locale:import ar ../translations/custom-translations.ar.po --type=customized --override=all 2> /dev/null
drush locale:import es ../translations/custom-translations.es.po --type=customized --override=all 2> /dev/null
drush locale:import ru ../translations/custom-translations.ru.po --type=customized --override=all 2> /dev/null
drush locale:import vi ../translations/custom-translations.vi.po --type=customized --override=all 2> /dev/null
drush locale:import zh-hans ../translations/custom-translations.zh-hans.po --type=customized --override=all 2> /dev/null