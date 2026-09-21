---
paths:
  - 'app/Services/Repair/**'
---

# Repair

## R2B.2 lineage APPLY needs snapshot locking
R2B.1 candidate diagnosis recomputes candidate queries across detect(), proposed(), and fingerprint generation. Before reviewed linkage APPLY in R2B.2, add transaction locking/snapshot guarantees so candidate set and fingerprint cannot drift between preview and apply.
