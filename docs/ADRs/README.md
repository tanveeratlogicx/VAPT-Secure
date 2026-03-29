# Architecture Decision Records (ADRs)

This directory contains Architecture Decision Records for the VAPT Secure WordPress plugin.

## What is an ADR?

An Architecture Decision Record (ADR) captures an important architectural decision made along with its context and consequences. Each ADR is immutable and describes a decision at a specific point in time.

## ADRs

| # | Title | Status | Date |
|---|-------|--------|------|
| [001](./001-transients-for-enforcement-caching.md) | Transients for Enforcement Caching | ✅ Accepted | 2024-01 |
| [002](./002-state-machine-for-feature-lifecycle.md) | State Machine for Feature Lifecycle | ✅ Accepted | 2024-01 |
| [003](./003-multi-driver-dispatch-pattern.md) | Multi-Driver Dispatch Pattern | ✅ Accepted | 2024-01 |

## Template

```markdown
# ADR XXX: Title

## Status

Proposed / Accepted / Deprecated / Superseded by [ADR YYY]

## Context

Why are we making this decision? What constraints exist?

## Decision

What decision did we make?

## Consequences

### Positive

### Negative

## Alternatives Considered

## Related Code

## Notes

---
*Last updated: Month Year | Author: Name*
```

## Contributing

When proposing a new architectural decision:

1. Create a new file: `XXX-decision-title.md`
2. Number sequentially from current highest
3. Use the template above
4. Submit for review via PR
5. Update this README with the new entry

---

*Maintained by: VAPT Secure Team*
