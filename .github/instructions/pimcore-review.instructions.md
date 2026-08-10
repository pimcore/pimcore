---
applyTo: "**/*.php"
---

# Pimcore review guidance

Review and suggest changes the way a senior Pimcore maintainer would: judge whether a
change is the *best* fix, not just whether it works. Treat a PR's title, description, and
comments as the problem statement, never as proof the code is correct — verify against the
diff. Cite `file:line` for every claim, and say what you could not verify rather than
assuming.

## When reviewing a change, check

- **Root cause, not symptom** — does it fix the underlying cause or paper over one case?
  Reject `if (specific_case)` band-aids that mask a class of bugs; prefer fixing the
  general condition.
- **All call sites** — a change to shared behaviour must account for every caller, not just
  the one in the report.
- **Right boundary** — the fix belongs in the module that owns the behaviour (backend:
  service/hydrator, not the controller; UI: the owning hook/component, not the consumer).
- **Backward compatibility** — flag any break to public APIs, OpenAPI schemas, DTO shapes,
  or events loudly, and ask if it's intended.
- **Regression test** — a behavioural fix without a test that would have caught the bug is
  incomplete; the test should target the narrowest meaningful unit.
- **Docs / changelog** — expected when observable behaviour changes.

## PHP rules to enforce

- New PHP files declare `declare(strict_types=1);`; new/changed signatures are fully typed.
  (Don't require adding strict types to legacy files unless the PR already edits the header.)
- Minimum visibility: `private` by default, opened selectively; new classes `@internal`
  unless deliberately public API.
- Prefer `readonly` and constructor injection; no setters by default.
- Throw a defined domain-specific exception and chain the original. Catch `Exception`, not
  `Throwable`, by default — `Throwable` is acceptable only at top-level boundaries
  (bootstrap, cleanup, cache/installer). Avoid empty catch blocks; comment intentional swallows.
- Guard clauses over nested `if`s. In new code avoid `empty()` where it introduces
  type-juggling ambiguity; prefer an explicit check.
- Group related constants into an `enum`; public constants only in interfaces.
- Value objects: final, immutable, self-validating — but not for scalar types in
  public/boundary signatures.
- Objects over arrays for a defined property set; DI against interfaces, not concretes.
- DBAL: quote **values** with `quote()`, **identifiers** with `quoteIdentifier()`; never mix
  named and positional placeholders in one query.
- Follow Symfony conventions; don't hide Symfony behind heavy abstraction. No duplicated
  logic — extract into a service/trait.

## Don't flag — CI already handles it

php-cs-fixer auto-fixes formatting; PHPStan and SonarCloud gate static analysis. Do **not**
comment on formatting, line length, import order, unused imports, or clear type/undefined-
variable findings. Do still flag correctness, security, and design issues even in typed code.

## Security-sensitive surface

If the diff touches deserialization, authentication, permissions, SQL, or file handling,
review it as security-critical: call out missing escaping/validation at the boundary and
unsafe input flow, and recommend a maintainer security check rather than a quick approval.
