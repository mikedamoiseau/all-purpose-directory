# Performance Review Checklist

Use this checklist when reviewing code for performance issues.

## Database
- [ ] Queries are indexed appropriately
- [ ] N+1 query problems are avoided
- [ ] Large datasets are paginated
- [ ] Expensive queries are cached where appropriate
- [ ] Transactions are scoped minimally

## API & Network
- [ ] Responses are paginated for large datasets
- [ ] Unnecessary data is not fetched
- [ ] Batch operations are used instead of loops
- [ ] Caching headers are set appropriately
- [ ] Payloads are reasonably sized

## Frontend (if applicable)
- [ ] Large lists are virtualized
- [ ] Images are optimized and lazy-loaded
- [ ] Bundle size impact is considered
- [ ] Re-renders are minimized (React: memo, useMemo, useCallback)
- [ ] No expensive operations in render loops

## Algorithms & Data Structures
- [ ] Time complexity is appropriate for the use case
- [ ] No unnecessary iterations over large collections
- [ ] Appropriate data structures are used (Map vs Object, Set vs Array)
- [ ] Early returns are used where possible

## Memory
- [ ] Large objects are cleaned up when done
- [ ] Event listeners are removed on cleanup
- [ ] No obvious memory leaks (circular references, etc.)
- [ ] Streams are used for large data processing

## Common Red Flags
- Nested loops over large datasets
- Synchronous operations that should be async
- Missing pagination on list endpoints
- Fetching full objects when only IDs are needed
- Missing database indexes on frequently queried fields
- Loading entire files into memory
