<?php
    // A non-cacheable page for uptime monitoring
    header('Cache-Control: no-store, no-cache, max-age=0');
    date_default_timezone_set('America/Los_Angeles');
?>

<html>
    <head>
        <title>Pingdom Uptime Monitor</title>
    </head>
    <body>
        <p>OK <?php echo date("F j, Y, g:i:s a"); ?></p>
        <p>This monitor verifies that the PHP webserver is operational.</p>
    </body>
</html>