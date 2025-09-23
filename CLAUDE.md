# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Development Environment
- Minimum PHP version: 8.2
- Moodle version: 4.1+ (tested with 4.5.2+ and 4.5.3+)
- Testing: Manual testing against target Moodle versions

## Test Commands
- API test: `php blocks/tutoring_machine/api_test.php [provider]` (openai, google, or all)
- Web test: Access `/blocks/tutoring_machine/api_test_web.php` in browser (admin only)
- Debug mode: Add `?cache_buster=1` to URLs to bypass cache
- Config check: `php blocks/tutoring_machine/check_config.php`
- Direct API test: `php blocks/tutoring_machine/api_direct_test.php`

## Code Style Guidelines
- Follow Moodle coding style (PSR-2 for PHP)
- Add defined('MOODLE_INTERNAL') || die(); to all PHP files
- Indent with 4 spaces (no tabs)
- Use single quotes for PHP strings unless double quotes needed
- Include file header with Moodle reference comment
- Use camelCase for function/method names
- Prefix variables with their scope ($this->varName)
- Document all functions/methods with PHPDoc comments
- Keep lines under 132 characters
- Use array() syntax rather than short array syntax []

## Error Handling
- Use try/catch blocks for external API calls
- Log errors with error_log() for debugging
- Display user-friendly error messages in UI
- Always sanitize user input with appropriate functions (s(), PARAM_TEXT)
- Validate all parameters with PARAM_* constants
- Implement exponential backoff for transient API errors

## Security
- Never expose API keys directly in code
- Store sensitive data in Moodle configuration
- Use Moodle's built-in security functions
- Implement proper AJAX verification
- Validate all user input
- Implement rate limiting (30 requests per hour per user)
- Implement CSRF protection with session key validation