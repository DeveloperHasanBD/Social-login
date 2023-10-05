<?php
include("google.config.php");
include("facebook.config.php");
?>
<a href="<?php echo $client->createAuthUrl() ?>"><span> Google</span></a>
<a href="<?php echo $loginUrl; ?>"><span> Facebook</span></a>