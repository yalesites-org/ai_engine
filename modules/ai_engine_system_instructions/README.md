# AI Engine System Instructions

This module provides system instructions management for AI Engine with versioning and API synchronization capabilities.

## Overview

This module was extracted from `ai_engine_chat` to provide a clean separation of concerns for system instructions management.

## Features

- **System Instructions Management**: Create, edit, and manage AI system instructions
- **Version Control**: Full versioning system with history and ability to revert to previous versions
- **API Integration**: Sync instructions with external Azure API endpoints
- **Access Control**: Configurable permissions and feature toggles
- **Length Controls**: Configurable character limits with warnings
- **Admin Interface**: User-friendly forms for managing instructions and settings

## Components Moved

### Services
- `SystemInstructionsApiService` - API communication for Azure system instructions
- `SystemInstructionsStorageService` - Database storage with versioning
- `SystemInstructionsManagerService` - Orchestration service

### Forms
- `SystemInstructionsForm` - Main management form with AJAX loading
- `SystemInstructionsRevertForm` - Version reversion form
- `SystemInstructionsSettingsForm` - Configuration form

### Controllers
- `SystemInstructionsController` - Version history and viewing

### Access Control
- `SystemInstructionsAccessCheck` - Feature access control based on configuration

### Database
- `ai_engine_system_instructions` table - Stores versioned system instructions

### Frontend Assets
- `system-instructions.js` - Character counting and auto-refresh functionality
- `system-instructions.css` - Admin interface styling

## Configuration

The module provides several configuration options:

- **Enable/Disable**: Toggle system instructions functionality
- **API Settings**: Configure Azure API endpoint, web app name, and authentication key
- **Length Controls**: Set maximum character limits and warning thresholds

## Usage

1. Enable the module
2. Configure API settings in the settings form
3. Use the main system instructions form to manage content
4. View version history and revert to previous versions as needed

## Dependencies

- `ai_engine` - Main AI Engine module
- `key` - Key module for API key management