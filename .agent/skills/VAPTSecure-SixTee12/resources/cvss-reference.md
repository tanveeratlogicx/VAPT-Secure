# VAPTSecure SixTee12 — CVSS v3.1 Quick Reference

## Risk Score Calculation

```
Security Score = 100 - ( Σ(unprotected CVSS) / 71.9 × 100 )
Total Max CVSS = 71.9  (sum of all 12 risk CVSS scores)
```

| Security Score | Status | Action |
|---|---|---|
| 80 – 100 | 🟢 Secure | Maintain and monitor |
| 50 – 79  | 🟡 Needs Attention | Remediate within 30 days |
| 0 – 49   | 🔴 Critical | Immediate action required |

---

## CVSS Score by Risk (Sorted by Severity)

| Risk | CVSS | Level | Score Impact |
|---|---|---|---|
| RISK-001 | 7.5 | High | −10.4 pts if unprotected |
| RISK-002 | 7.5 | High | −10.4 pts if unprotected |
| RISK-007 | 7.5 | High | −10.4 pts if unprotected |
| RISK-003 | 5.3 | Medium | −7.4 pts if unprotected |
| RISK-004 | 5.3 | Medium | −7.4 pts if unprotected |
| RISK-005 | 5.3 | Medium | −7.4 pts if unprotected |
| RISK-008 | 5.3 | Medium | −7.4 pts if unprotected |
| RISK-009 | 5.3 | Medium | −7.4 pts if unprotected |
| RISK-011 | 5.3 | Medium | −7.4 pts if unprotected |
| RISK-012 | 5.3 | Medium | −7.4 pts if unprotected |
| RISK-006 | 3.7 | Low | −5.1 pts if unprotected |
| RISK-010 | 3.7 | Low | −5.1 pts if unprotected |

---

## CVSS v3.1 Severity Bands

| CVSS Range | Severity | Color Code |
|---|---|---|
| 9.0 – 10.0 | Critical | `#CC0000` |
| 7.0 – 8.9  | High     | `#FF4444` |
| 4.0 – 6.9  | Medium   | `#FFAA00` |
| 0.1 – 3.9  | Low      | `#00C896` |
| 0.0        | None     | `#9AA0A6` |

---

## Score Scenarios

### All 12 risks unprotected
```
Score = 100 - (71.9 / 71.9 × 100) = 0
Status: 🔴 Critical
```

### Only HIGH risks unprotected (RISK-001, 002, 007)
```
Score = 100 - (22.5 / 71.9 × 100) = 100 - 31.3 = 68.7
Status: 🟡 Needs Attention
```

### Only LOW risks unprotected (RISK-006, 010)
```
Score = 100 - (7.4 / 71.9 × 100) = 100 - 10.3 = 89.7
Status: 🟢 Secure
```

### Fully protected
```
Score = 100 - (0 / 71.9 × 100) = 100
Status: 🟢 Secure
```

---

## TypeScript Score Calculator

```typescript
const RISK_CVSS: Record<string, number> = {
  'RISK-001': 7.5, 'RISK-002': 7.5, 'RISK-003': 5.3,
  'RISK-004': 5.3, 'RISK-005': 5.3, 'RISK-006': 3.7,
  'RISK-007': 7.5, 'RISK-008': 5.3, 'RISK-009': 5.3,
  'RISK-010': 3.7, 'RISK-011': 5.3, 'RISK-012': 5.3,
};

const TOTAL_MAX_CVSS = 71.9;

function computeSecurityScore(protectedRiskIds: string[]): number {
  const protectedSet = new Set(protectedRiskIds);
  const unprotectedCVSS = Object.entries(RISK_CVSS)
    .filter(([id]) => !protectedSet.has(id))
    .reduce((sum, [, cvss]) => sum + cvss, 0);
  return Math.max(0, Math.round(100 - (unprotectedCVSS / TOTAL_MAX_CVSS) * 100));
}

function getScoreStatus(score: number): 'secure' | 'needs_attention' | 'critical' {
  if (score >= 80) return 'secure';
  if (score >= 50) return 'needs_attention';
  return 'critical';
}

// Example:
const score = computeSecurityScore(['RISK-001', 'RISK-003', 'RISK-010', 'RISK-011', 'RISK-012']);
console.log(score, getScoreStatus(score)); // 47, 'critical'
```
