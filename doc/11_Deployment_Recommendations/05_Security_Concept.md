---
title: Security Concept
description: Multi-layer security approach for Pimcore applications.
---

# Security Concept

Pimcore follows a multi-layer security concept. The framework provides built-in protections
at the core level, while project-specific security measures are the responsibility
of the integration partner.

## Pimcore Core Level

### Coding Standards and Best Practices

Pimcore follows established PHP coding standards and industry best practices to minimize
security risks. The coding standards are based on PSRs (PHP Standards Recommendations)
developed by the PHP Framework Interop Group (PHP-FIG) and are enforced during
continuous integration at GitHub.

### Dependency Management

Pimcore is built on the Symfony framework and manages all dependencies through Composer,
the standard PHP dependency management solution. This makes it straightforward to keep
all dependencies up to date and secure.

As a Symfony application, Pimcore supports all Symfony security tools, including the
[Symfony Security Checker](https://symfony.com/doc/current/security/security_checker.html).

### Content Security Policy

Pimcore Studio includes a Content Security Policy (CSP) handler that protects against
Cross-Site Scripting (XSS) and data injection attacks by adding a `Content-Security-Policy`
HTTP header with a [nonce](https://developer.mozilla.org/en-US/docs/Web/HTML/Global_attributes/nonce)
to every Studio request. CSP is **enabled by default**.

Read more about [Content Security Policy](https://developer.mozilla.org/en-US/docs/Web/HTTP/CSP).

#### Pimcore Studio CSP Configuration

Configure the Studio CSP under `pimcore_studio_ui.csp_header`.
To allow external URLs for specific directives:

```yaml
# config/config.yaml
pimcore_studio_ui:
    csp_header:
        additional_urls:
            script-src:
                - 'https://cdn.example.com/scripts/analytics.js'
            style-src:
                - 'https://fonts.googleapis.com'
            connect-src:
                - 'https://api.example.com'
```

Available directives: `default-src`, `script-src`, `style-src`, `connect-src`,
`font-src`, `img-src`, `media-src`, `frame-src`, `worker-src`

To disable CSP:

```yaml
# config/config.yaml
pimcore_studio_ui:
    csp_header:
        enabled: false
```

To exclude specific paths from CSP enforcement:

```yaml
# config/config.yaml
pimcore_studio_ui:
    csp_header:
        exclude_paths:
            - '/pimcore-studio/custom-endpoint'
```

If your bundle or custom implementation extends the Studio interface, add the generated nonce
to inline scripts:

```twig
<script {{ pimcore_studio_csp.getNonceHtmlAttribute()|raw }}>
```

For advanced CSP extension (registering external origins from bundles, dev servers, or CDNs),
see the
[Studio UI Content Security Policy documentation](https://github.com/pimcore/studio-ui-bundle/blob/2026.x/doc/03_Configuration_and_Administration/01_Configuration/01_Content_Security_Policy.md).

#### Frontend CSP

CSP for customer-facing pages is a project-level responsibility.
Configure CSP headers through your web server (Nginx, Apache) or Symfony response listeners.
Pimcore does not enforce CSP on frontend document responses automatically.

### Handling Security Issues

Pimcore handles security vulnerabilities with the following procedure:

- **Reporting**: Report issues via the
  [GitHub security advisory mechanism](https://docs.github.com/en/code-security/security-advisories/guidance-on-reporting-and-writing-information-about-vulnerabilities/privately-reporting-a-security-vulnerability#privately-reporting-a-security-vulnerability)
  of the corresponding repository, e.g.
  [core framework](https://github.com/pimcore/pimcore/security).
  Do not use the public issue tracker.

- **Resolution**:
  1. Pimcore forwards the report to the core team for verification.
  2. If confirmed, Pimcore sends an acknowledgement to the reporter.
  3. Pimcore develops a patch and obtains a CVE identifier if not already available.
  4. Pimcore publishes the patch and/or a new release.
  5. Pimcore publishes a security announcement covering affected versions, possible exploits,
     and how to patch or upgrade.


## Project-Specific Level (Integration Partner)

### HTTPS / TLS

Serve all Pimcore endpoints over HTTPS, including Pimcore Studio (`/pimcore-studio`),
API routes, and customer-facing pages. Configure TLS termination at the web server
or load balancer level.

### Coding Standards and Best Practices

Apply the same coding standards and best practices used in core development to the solution.

### Dependency Management

Run security checks for all additional solution dependencies. All Symfony tools, including the
[Symfony Security Checker](https://symfony.com/doc/current/security/security_checker.html),
are available for solution projects.

### Project-Specific Penetration Testing

Penetration testing should uncover security issues in the solution implementation.
Run tests on staging systems without additional security layers (WAF, IPS) and cover all
[OWASP Top 10 risks](https://owasp.org/www-project-top-ten/).

A third-party testing partner provides an independent assessment.

### WAF and IPS on Production Systems

Web Application Firewalls (WAF) and Intrusion Protection Systems (IPS) sit in front of the
application, analyze traffic, and filter malicious activity.

As a standard PHP application, Pimcore works with all industry-standard products such as
ModSecurity, Cloudflare WAF, and others. The choice depends on the IT infrastructure.
For Pimcore Studio, WAF rules may need to allow multipart file uploads,
WebSocket connections (used by Mercure for real-time updates), and larger request bodies.

WAF and IPS serve as the final safety net in the multi-layer security concept.
