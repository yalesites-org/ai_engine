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

### Quick Setup Guide

1. **Enable the Module**
   ```bash
   drush en ai_engine_system_instructions
   ```

2. **Create API Key (if using API sync)**
   - Navigate to `/admin/config/system/keys`
   - Create a new key entity for your API authentication key
   - Choose appropriate key type (e.g., "Configuration")

3. **Configure Module Settings**
   - Navigate to `/admin/config/ai-engine/system-instructions/settings`
   - Enable system instructions functionality
   - Configure API settings (optional for local-only usage):
     - **API Endpoint**: Full URL to your API endpoint
     - **Web App Name**: Name of the target application/service
     - **API Key**: Select the key entity created in step 2

4. **Set Content Limits**
   - **Maximum Length**: Default 4000 characters
   - **Warning Threshold**: Default 3500 characters (shows warning but allows submission)

### Configuration Options

- **Enable/Disable**: Toggle system instructions functionality
- **API Settings**: Configure external API endpoint for synchronization
  - API Endpoint URL (e.g., `https://your-app.azurewebsites.net/api/environment-variables`)
  - Web Application Name (identifies target service)
  - Authentication Key (stored securely via Key module)
- **Length Controls**: Set maximum character limits and warning thresholds
- **Access Control**: Managed via Drupal permissions system

### Permissions

The module provides the following permission:
- **Manage AI system instructions**: Required to access system instructions functionality

Grant this permission to appropriate user roles via `/admin/people/permissions`.

## Usage

### Basic Workflow

1. **Initial Setup**
   - Enable the module and configure settings
   - Grant permissions to appropriate users

2. **Managing Instructions**
   - Navigate to `/admin/config/ai-engine/system-instructions`
   - Edit system instructions in the textarea
   - Add optional version notes describing changes
   - Save to create a new version and sync with API (if configured)

3. **Version Management**
   - View version history at `/admin/config/ai-engine/system-instructions/versions`
   - Click "View" to see content of any version
   - Click "Revert" to restore a previous version as active

4. **API Synchronization**
   - **Automatic Sync**: Happens on form load and save operations
   - **Manual Sync**: Use "Sync" button to fetch latest from API
   - **Force Sync**: Use "Force Sync" to bypass 10-second cooldown

### Advanced Features

- **Character Counting**: Real-time character count with warnings
- **Auto-refresh**: Form automatically refreshes to sync with API on load
- **Audit Logging**: All actions are logged with user attribution
- **Graceful Degradation**: Works offline when API is unavailable

## API Integration

For detailed information about API integration, authentication, and troubleshooting, see [API.md](API.md).

### Local-Only Usage

The module can be used without API integration:
1. Leave API settings blank in configuration
2. System instructions will be stored and versioned locally only
3. All other functionality remains available

## Security Considerations

- **API Keys**: Always use Drupal's Key module for secure storage
- **HTTPS**: API communications must use HTTPS in production
- **Permissions**: Restrict access to appropriate user roles
- **Content Validation**: System instructions are validated for length
- **Audit Trail**: All changes are logged with user and timestamp information

## Troubleshooting

### Common Issues

1. **"API configuration is incomplete"**
   - Verify all API settings are configured
   - Ensure API key is created and selected in settings

2. **"System instruction modification is not enabled"**
   - Check that the feature is enabled in module settings
   - Verify user has required permissions

3. **API sync failures**
   - Check Drupal logs for detailed error information
   - Verify API endpoint is accessible and returns valid responses
   - Test API connection manually (see API.md for examples)

### Debug Information

- **Logs**: Check `/admin/reports/dblog` for ai_engine_system_instructions entries
- **Performance**: Response times and payload sizes are logged
- **User Actions**: All user operations are logged with full context

## Dependencies

- **ai_engine** - Main AI Engine module (required)
- **key** - Key module for secure API key management (required for API features)
- **league/commonmark** - Markdown parsing library (automatically included)