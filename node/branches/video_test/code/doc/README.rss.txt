
SOTF-general RSS feed

http://sotf2.dsd.sztaki.hu/node/www/rss.php

Station-specific RSS feed (station=<station name>)

http://sotf2.dsd.sztaki.hu/node/www/rss.php?station=DooBeeDoo

User-specific RSS feed (user=<username>, qname=<name of saved query>)
(This is insecure, and is only for demo purposes)

http://sotf2.dsd.sztaki.hu/node/www/rss.php?user=micsik&qname=german

Run advanced query and get results as RSS
(query=<string format of serialized query expression>)

http://sotf2.dsd.sztaki.hu/node/www/rss.php?query=production_date%20DESC|Bstation|AAND|Blanguage|Bis|Bhu|Blang


Unfortunately I could not try out the Userland-specific RSS properties
(<enclosure>, <source>, etc.), which could go more towards streaming
solution. I installed Radio Userland 30-day demo, but can't find anything
connected with RSS.
