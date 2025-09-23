# Changelog

## [2.0.0] - 2025-09-21

### Changed

- Migrated from WebSocket-based real-time updates to AJAX polling
- Updated order processing flow to use standardized AJAX endpoints
- Fixed order insertion issues with proper database schema alignment
- Enhanced error handling and validation in order processing

### Removed

- WebSocket server and related dependencies
- WebSocket client-side handlers and connection management
- Real-time push notifications (replaced with polling)

### Added

- New AJAX-based polling system for order updates
- Improved error recovery and retry logic
- Enhanced documentation for AJAX architecture
- Standardized database connection handling

### Fixed

- Order insertion issues with correct schema mapping
- Crew dashboard order visibility problems
- Database connection reliability
- Error logging and handling

## [1.0.0] - 2025-09-14

### Initial Release

- Basic order management system
- Real-time updates via WebSocket
- Customer, crew, and admin interfaces
- Basic authentication and authorization
