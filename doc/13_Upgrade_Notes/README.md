# Upgrade Notes

## Pimcore 2026.1.0

### [General]

- Added support for PHP `8.5` and bumped minimum requirement of Symfony to `7.4`.
- Dropped support for PHP `8.3` and Symfony `6`.
 
#### [DataObjects]

- Add a new optional `$parameters` argument to `Concrete::saveVersion()` to allow passing of arguments to events.


