# Security Review Checklist

Use this checklist when reviewing code for security issues.

## Input Validation
- [ ] All user input is validated before use
- [ ] Input length limits are enforced
- [ ] Input types are verified (string, number, etc.)
- [ ] Special characters are escaped or rejected where appropriate

## Authentication & Authorization
- [ ] Authentication is required for protected routes
- [ ] Authorization checks verify user has permission
- [ ] Session tokens are properly validated
- [ ] Password handling follows best practices (hashing, no plaintext)

## Data Protection
- [ ] Sensitive data is not logged
- [ ] Sensitive data is encrypted at rest
- [ ] Sensitive data is encrypted in transit (HTTPS)
- [ ] PII is handled according to privacy requirements

## Injection Prevention
- [ ] SQL queries use parameterized statements
- [ ] No raw SQL string concatenation
- [ ] HTML output is escaped (XSS prevention)
- [ ] Command execution avoids shell injection
- [ ] File paths are sanitized (path traversal prevention)

## API Security
- [ ] Rate limiting is implemented
- [ ] CORS is properly configured
- [ ] API keys are not exposed in client code
- [ ] Error messages don't leak sensitive information

## Dependencies
- [ ] No known vulnerable dependencies
- [ ] Dependencies are from trusted sources
- [ ] Dependency versions are pinned

## Common Red Flags
- `eval()` or dynamic code execution
- Hardcoded credentials or API keys
- `dangerouslySetInnerHTML` without sanitization
- Disabled security features (CSRF, etc.)
- Overly permissive file permissions
- Unvalidated redirects
