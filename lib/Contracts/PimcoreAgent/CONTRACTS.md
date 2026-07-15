# Pimcore PimcoreAgent Contracts — BC Policy

Contracts in this namespace (`Pimcore\Contracts\PimcoreAgent\*`) are the stable interface between the `pimcore/pimcore-agent-bundle` bundle and its optional consumers (currently `pimcore/collab-bundle`).

## Additive-only until v2

Until `Pimcore\Contracts\PimcoreAgent\v2\*` exists:

- **No method removals** from any interface.
- **No method signature changes** — parameter types, return types, parameter names in interfaces (PHP treats them as significant for named args).
- **No field additions** to existing `final readonly` DTOs. A field added means the caller signature changes for every construction site — that's a break.
- **No field renames or type changes** on existing DTOs.
- **New behaviour** ships as a new interface, a new DTO subclass, or a new method on a new interface — never as a modification of an existing one.

## Deprecations

Add `@deprecated since <pimcore-version>, use <replacement>` in the docblock. Do NOT remove the deprecated member during v1. Cleanup happens in v2 (a separate release-cycle decision).

## What counts as an addition (allowed)

- New interfaces in the namespace.
- New DTO classes.
- New enum cases on existing enums (backed string enums are additive-safe for consumers doing `->value`; still document in the release notes).
- New constants on interfaces (allowed by PHP; consumers reading them opt in).

## What is explicitly outside this promise

- Concrete implementations in `pimcore/pimcore-agent-bundle` — those track the bundle's own semver, not this contract's.
- Studio-ui rendering of the `deeplink` field on `AgentTaskInfo` — implementation detail on the frontend side.

Consumers can pin to `pimcore/pimcore: ^<version>` and rely on the members that existed at pin time remaining callable for the whole v1 lifetime.
