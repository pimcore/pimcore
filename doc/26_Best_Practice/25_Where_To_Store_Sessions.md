# Where To Store Sessions

Pimcore uses the Symfony session component to handle sessions. By default, Symfony stores sessions in the filesystem.
This is fine for most applications, but it can be a performance bottleneck for high-traffic sites.

To prevent request blocking, consider storing sessions in a database or a cache. Be careful to select a solution that suits your requirements, as using alternatives like Redis may introduce race conditions or other issues.