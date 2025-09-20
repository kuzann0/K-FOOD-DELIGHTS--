# Form Validation Documentation

## Overview

This document describes the form validation implementation in the K-FOOD customer checkout process. All validations are designed to provide immediate feedback and ensure data integrity before submission.

## Validation Rules

### Customer Information

#### Name Field

- Required field
- Must be non-empty after trimming
- Validated on blur and form submission

#### Email Field

- Required field
- Must match format: username@domain.tld
- Validated using regex: `/^[^\s@]+@[^\s@]+\.[^\s@]+$/`
- Checked on blur and form submission

#### Phone Field

- Required field
- Must be numbers only, optionally starting with +
- Must be at least 10 digits
- Validated using regex: `/^\+?\d{10,}$/`
- Real-time validation during input

#### Address Field

- Required field
- Must be non-empty after trimming
- Validated on blur and form submission

#### Delivery Instructions

- Optional field
- Trimmed before submission
- No special validation rules

### Payment Validation

#### Payment Method Selection

- Required selection
- Radio button group validation
- Checks for presence of payment options
- Validates selected value exists

#### GCash Payment

When GCash is selected:

- GCash Number:
  - Required field
  - Must be exactly 11 digits
  - Validated using regex: `/^\d{11}$/`
  - Real-time format validation
- Reference Number:
  - Required field
  - Must be alphanumeric
  - Minimum 6 characters
  - Validated using regex: `/^[A-Za-z0-9]{6,}$/`
  - Case-sensitive validation

### Cart Validation

#### Cart Items

- Must have at least one item
- Each item must have:
  - Valid product_id
  - Positive quantity
  - Valid price
- Total amount must be greater than 0

#### Discount Validation

If discounts applied:

- Senior Citizen:
  - Requires valid ID number
  - Validates discount percentage
- PWD:
  - Requires valid ID number
  - Validates discount percentage

## Validation Process

1. Field-Level Validation

   - Runs on blur events
   - Provides immediate feedback
   - Updates UI with error states
   - Shows inline error messages

2. Form-Level Validation

   - Runs before form submission
   - Checks all required fields
   - Validates data relationships
   - Ensures complete order data

3. Submit-Time Validation
   - Final validation before API call
   - Comprehensive data check
   - Server-side validation preparation
   - Error collection and reporting

## Error Handling

### Visual Feedback

- Invalid fields highlighted in red
- Error messages shown below fields
- Clear error states on valid input
- Form-level error summary if needed

### Error Messages

- Clear, actionable feedback
- Specific validation requirements
- User-friendly language
- Includes correction guidance

## Recovery Mechanisms

1. Field Recovery

   - Clear error on valid input
   - Real-time validation
   - Immediate feedback

2. Form Recovery
   - Maintains valid data
   - Clear error summary
   - Easy error location
   - Simple correction process

## Best Practices

1. User Experience

   - Immediate feedback
   - Clear error messages
   - Simple correction process
   - Maintained form state

2. Data Integrity

   - Complete validation
   - Type checking
   - Format validation
   - Relationship validation

3. Security
   - Input sanitization
   - XSS prevention
   - CSRF protection
   - Safe data handling
