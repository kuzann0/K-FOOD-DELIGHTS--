# K-Food Delights Technical Stack

## Overview

A comprehensive breakdown of technologies, frameworks, and tools used in the K-Food Delights ordering system.

## Backend Stack

### Core Server

- **Web Server**: Apache 2.4 (XAMPP)
- **PHP Version**: 7.4+
- **Database**: MySQL 8.0 (via phpMyAdmin)
- **Session Management**: PHP Native Sessions

### Backend Technologies

- **Language**: PHP 7.4+
- **Database Access**: MySQLi (Object-Oriented)
- **API Architecture**: REST/AJAX
- **Security**:
  - CSRF Protection
  - Session-based Authentication
  - SQL Injection Prevention
  - XSS Protection

### Server Components

- **Error Handling**: Custom ErrorHandler class
- **Input Validation**: OrderValidator class
- **Notification System**: NotificationHandler class
- **Database Connection**: Pooled connections
- **SMS Integration**: Semaphore API

## Frontend Stack

### Core Technologies

- **HTML5**
- **CSS3**
- **JavaScript (ES6+)**

### CSS Framework & Styling

- **Base Framework**: Custom CSS
- **Icons**: Font Awesome 6.4.0
- **Fonts**:
  - Roboto (300, 400, 500, 700)
  - Poppins (400, 600)

### JavaScript Components

- **AJAX Handler**: Custom implementation
- **Cart Management**: CartManager class
- **Form Validation**: CheckoutValidator class
- **Order Processing**: OrderConfirmationHandler
- **Amount Calculations**: OrderAmountHandler

### UI Components

- **Modals**: Custom implementation
- **Notifications**: Toast-style system
- **Form Validation**: Real-time validation
- **Loading States**: Custom spinners
- **Responsive Design**: Mobile-first approach

## Development & Build Tools

### Version Control

- **System**: Git
- **Repository**: GitHub
- **Branch Strategy**: Feature-based branching

### Development Environment

- **Local Server**: XAMPP
- **IDE Support**: VS Code optimized
- **Debug Tools**: Custom PHP error logging

## Security Features

### Authentication

- **Session Management**: PHP Sessions
- **Password Security**:
  - Bcrypt hashing
  - Salt generation
  - Secure password policies

### Data Protection

- **CSRF Protection**: Token-based
- **SQL Injection**: Prepared Statements
- **XSS Prevention**: Output escaping
- **Input Validation**: Server & Client side

## External Integrations

### Payment Processing

- **GCash**: Direct integration
- **Cash on Delivery**: Built-in handling

### SMS Gateway

- **Provider**: Semaphore
- **Features**:
  - OTP Verification
  - Order Status Updates
  - Delivery Notifications

## Deployment Requirements

### Server Requirements

- **Web Server**: Apache 2.4+
- **PHP**: 7.4+ with extensions:
  - mysqli
  - json
  - session
  - mbstring
- **MySQL**: 8.0+
- **SSL**: Required for production

### Client Requirements

- **Browsers**:
  - Chrome 90+
  - Firefox 88+
  - Safari 14+
  - Edge 90+
- **JavaScript**: Enabled
- **Cookies**: Enabled
- **Screen Resolution**: Minimum 320px width

## Architecture Patterns

### Frontend

- **Component-Based**: Modular JS classes
- **Event-Driven**: Custom event system
- **State Management**: Local state handlers

### Backend

- **MVC-like**: Separated logic layers
- **Service-Based**: Modular services
- **Repository Pattern**: Data access

## Performance Optimizations

### Frontend

- **Resource Loading**: Async script loading
- **Image Optimization**: Compressed assets
- **CSS/JS Minification**: Production builds
- **Caching**: Browser cache utilization

### Backend

- **Database**: Indexed queries
- **Session Handling**: Optimized storage
- **Response Caching**: API response cache
- **Connection Pooling**: Database connections

## Documentation

- **API Documentation**: Markdown-based
- **Code Documentation**: PHPDoc & JSDoc
- **User Guides**: Markdown in /docs
- **Change Logs**: Versioned updates

## Testing Infrastructure

- **Unit Testing**: PHP Unit
- **Integration Testing**: Custom test suite
- **UI Testing**: Manual test cases
- **API Testing**: Postman collections
