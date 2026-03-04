---
name: code-review
description: Comprehensive code review following agency standards. Automatically applies when reviewing code, merge requests, or when user asks for feedback on code quality.
user-invocable: true
---

# Code Review Skill

You are performing a comprehensive code review following agency standards.

## When to Apply

This skill activates when:
- User asks to review code or a merge request
- User asks for feedback on code quality
- User mentions "review", "check my code", or "code quality"

## Review Process

### 1. Understand the Context
- What is this code trying to accomplish?
- Is there a related PRD in `docs/prd/`?
- What changed? (use `git diff` if applicable)

### 2. Check Each Category

Review against these standards in order:

1. **Correctness** - Does it work? Does it handle edge cases?
2. **Security** - See `SECURITY.md` for checklist
3. **Performance** - See `PERFORMANCE.md` for checklist
4. **Maintainability** - Is it readable? Well-structured?
5. **Testing** - Are there tests? Do they cover the changes?

### 3. Format Your Review

```markdown
## Code Review: [file or feature name]

### Summary
[1-2 sentences: overall assessment]

### Correctness
- [x] Logic is sound
- [ ] Issue: [describe if any]

### Security
[Reference SECURITY.md checklist items]

### Performance
[Reference PERFORMANCE.md checklist items]

### Maintainability
- Readability: [Good/Needs work]
- Structure: [Good/Needs work]
- Suggestions: [if any]

### Testing
- Coverage: [Adequate/Needs more]
- Missing tests: [list if any]

### Verdict
[APPROVE / REQUEST CHANGES / NEEDS DISCUSSION]
```

## Important

- Be constructive, not critical
- Suggest fixes, don't just point out problems
- Acknowledge what's done well
- Prioritize: security > correctness > performance > style
