# Multi-application setup

## Sessions

When running multiple applications on the same domain, there can be session cookie collisions which prevent you to log in to both systems at the same time.

Imagine you run a web shop app on http://example.org and Pimcore on http://pim.example.org. Then you will have 2 cookies with name `PHPSESSID` (if `session.name` in php.ini is the same for both):
| Name| Value  | Domain  |  Path | 
|---|---|---|---|
| PHPSESSID | 5a9b08750387d9e11c738a2947d93e38   |  .example.org | /  | 
| PHPSESSID | irqnjh5p96gp2i8iu743ulm32p   |  pim.example.org | /  | 

First one is from the web shop, second one from Pimcore.
When trying to log in at http://example.org/admin you will get a 403 Forbidden error.
(The reason why the web shop sets the cookie for `.example.org` instead of `example.org` probably is to also support subdomains.)

You can prevent this problem with the following config in your `config.yaml`. This way the session cookies do not conflict anymore and you will be able to log in to both applications at the same time.
```yaml
framework:
    session:
        name: "PIMCORE_SESSION_ID"
```

