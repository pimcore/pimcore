# Where To Store Sessions

Pimcore uses the Symfony session component to handle sessions. By default, Symfony stores sessions in the filesystem.
This is fine for most applications, but it can be a performance bottleneck for high-traffic sites.

To avoid blocking requests, you can store sessions in a database or a cache.
We strongly recommend using a cache like Redis to store sessions.