<?php

// Import interface (locale) translations
echo "Start importing interface translations...\n";
system('date +"%T"');
system('drush locale:check 2>&1');
system('drush locale:update 2>&1');
system('drush locale:import es ../translations/custom-translations.es.po --type=customized --override=all 2>&1');
system('drush locale:import ru ../translations/custom-translations.ru.po --type=customized --override=all 2>&1');
system('drush locale:import vi ../translations/custom-translations.vi.po --type=customized --override=all 2>&1');
system('drush locale:import zh-hans ../translations/custom-translations.zh-hans.po --type=customized --override=all 2>&1');
system('date +"%T"');
echo "Done importing interface translations.\n";
