<?php /* #?ini charset="utf-8"?

[Keys]
# Visit https://dash.cloudflare.com/ to signup and get your own API keys for Turnstile
#
# If you are using in a single site setup you can simply set SiteKey &
# SecretKey to the key values. e.g.
#   SiteKey=1x00000000000000000000AA
#   SecretKey=1x0000000000000000000000000000000AA
#
# If you are running multiple sites and have multiple keys define the keys as
# arrays with the hostname a the array key. e.g.
#
# SiteKey[test.example.com]=1x00000000000000000000AA
# SecretKey[test.example.com]=1x0000000000000000000000000000000AA
#
# SiteKey[www.example.com]=2x00000000000000000000AA
# SecretKey[www.example.com]=2x0000000000000000000000000000000AA
#
#SiteKey[hostname]=Enter your Site Key here for hostname
#SiteKey[localhost]=Enter your Site Key here for localhost
SiteKey=Enter your Site Key here

#SecretKey[hostname]=Enter your Secret Key here for hostname
#SecretKey[localhost]=Enter your Secret Key here for localhost
SecretKey=Enter your Secret Key here

[PublishSettings]
# Allows to use Turnstile only on newly created objects and to ignore it on objects that are re-edited.
# Usefull if you want to use Turnstile only for user/register and not on user/edit
# Another use would be to use Turnstile only when adding comments and not when editing them.
NewObjectsOnly=false

*/ ?>
