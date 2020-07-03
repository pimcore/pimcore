# Security Concept
We at Pimcore take security very seriously and recommend a multi-layer security concept to keep Pimcore-based solutions 
safe. 

As Pimcore is a framework and not an out-of-the box solution, this multi-layer security concept has parts that are 
provided by the core framework itself, and parts that need to be provided by the solution partner, 
that delivers the solution. 


## Pimcore Core Level 

### Following Coding Standards & Best Practices
To minimize the risk of security issues, we follow established and proven php coding standards as well as industry best 
practises. The coding standards are based on so called PSRs (PHP Standards Recommendations) developed by the 
PHP Framework Interop Group (PHP-FIG) and are enforced during our continuous integration process at Github. 

Pimcore is also a voting member of the PHP-FIG.
 

### Dependency Management
Pimcore is based on the Symfony framework (PHP industry standard framework) and multiple additional components and 
dependencies. All Dependencies are managed through Composer (the standard PHP dependency management solution) which makes 
it easy and comfortable to keep all dependencies of Pimcore and your project up-to-date and safe. 

Since Pimcore is a Symfony application, it can utilize all Symfony tools, like the 
[Symfony Security Checker](https://symfony.com/doc/current/security/security_checker.html). 


### Handling Security Issues
In the case of a security issue/vulnerability in the Pimcore core framework, we handle them with the following procedure: 
- **Reporting Issue**: 
Report issue via [Pimcore Security form](https://pimcorehq.wufoo.com/forms/pimcore-security-report/), not via public 
issue tracker (according guidelines also available at public issue tracker). 

- **Resolving Issue**: 
  - Reported issue is forwarded directly forwarded to Pimcore core team, verified and if confirmed resolved in following steps
  - Send an acknowledgement to the reporter with an official statement
  - Work on a patch
  - If not already available, get a CVE identifier 
  - Publish patch and/or new release of Pimcore
  - Publish security announcement with at least affected versions, possible exploits and how to patch/upgrade


## Project specific Level (provided by Integration Partner) 

### Following Coding Standards & Best Practices
Same as for the core development, we recommend applying all the coding standards and best practises for solution development 
too. 

### Dependency Management
Same as for the core development, we also recommend security checks for all the additional solution dependencies. Also 
for solutions, all Symfony tools, like the [Symfony Security Checker](https://symfony.com/doc/current/security/security_checker.html) 
can be utilized.

### Project Specific Penetration Testing
Project specific penetration testing should uncover possible security issues in solution implementation. This testing 
should be done on staging systems, without additional security layers such as WAF (Web Application Firewall) or IPS 
(Intrusion Protection Systems) and should cover all [OWASP Top 10 risks](https://www.owasp.org/index.php/Top_10_2010-Main). 

Best results are expected when this testing is done by a third-party partner. 


### WAF & IPS on production systems 
Web Application Firewalls and Intrusion Protection Systems are systems that sit in front of the application, analyse all 
traffic and try to filter malicious activities.
 
As standard php application, Pimcore supports all industry standard products such as ModSecurity, WAF safeguards of 
Cloud Flare and others. Which to use is depending on the actual IT infrastructure. For Pimcore backend usage, some 
additional rules might be necessary. 

WAF and IPS on production systems should be the last safety net in the multi-layer security concept. 
