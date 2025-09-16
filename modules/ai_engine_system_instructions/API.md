# AI Engine System Instructions API Documentation

## Overview

The AI Engine System Instructions module integrates with external APIs to synchronize system instructions for AI chat functionality. This document provides comprehensive information about the API requirements, authentication flow, and implementation details.

## API Configuration Requirements

### Required Configuration Values

1. **API Endpoint**: The full URL to the API endpoint that handles system instructions
   - Example: `https://your-function-app.azurewebsites.net/api/environment-variables`

2. **Web App Name**: The name of the web application/service to manage
   - This identifies which application's environment variables to modify
   - Example: `yalesites-ai-chat-production`

3. **API Key**: Secure authentication key stored using Drupal's Key module
   - Must be configured as a Key entity in Drupal
   - Used in the `x-functions-key` header for authentication

### Configuration Steps

1. **Enable the Module**: Ensure the AI Engine System Instructions module is enabled
2. **Configure API Settings**: Navigate to `/admin/config/ai-engine/system-instructions/settings`
3. **Create API Key**: Use Drupal's Key module to securely store the API key
4. **Test Connection**: Use the sync functionality to verify the API connection

## Authentication Flow

### Authentication Method

The API uses header-based authentication with a function key:

```http
POST /api/environment-variables HTTP/1.1
Host: your-function-app.azurewebsites.net
Content-Type: application/json
x-functions-key: YOUR_FUNCTION_KEY_HERE
```

### Security Considerations

- **Key Storage**: API keys are stored securely using Drupal's Key module
- **HTTPS Required**: All API communications should use HTTPS
- **Timeout Handling**: Requests timeout after 30 seconds
- **Rate Limiting**: Built-in 10-second cooldown between API sync calls

## API Endpoints and Request/Response Format

### Get System Instructions

**Purpose**: Retrieve the current system instructions from the API

**Request Format**:
```json
{
  "action": "get",
  "web_app_name": "your-web-app-name",
  "environment_variables": ["AZURE_OPENAI_SYSTEM_MESSAGE"]
}
```

**Successful Response** (HTTP 200):
```json
{
  "AZURE_OPENAI_SYSTEM_MESSAGE": "Your system instructions content here..."
}
```

**Error Response** (HTTP 4xx/5xx):
```json
{
  "error": "Error description",
  "details": "Additional error details if available"
}
```

### Set System Instructions

**Purpose**: Update the system instructions via the API

**Request Format**:
```json
{
  "action": "set",
  "web_app_name": "your-web-app-name",
  "environment_variables": {
    "AZURE_OPENAI_SYSTEM_MESSAGE": "Updated system instructions content..."
  }
}
```

**Successful Response** (HTTP 200):
```json
{
  "success": true,
  "message": "Environment variables updated successfully"
}
```

**Error Response** (HTTP 4xx/5xx):
```json
{
  "error": "Error description",
  "details": "Additional error details if available"
}
```

## Error Handling and Retry Logic

### Graceful Degradation

The module is designed to continue functioning even when the API is unavailable:

1. **Local Storage**: Instructions are always stored locally in the database
2. **API Failures**: Local versions remain active when API calls fail
3. **User Notification**: Clear error messages inform users of API issues
4. **Manual Retry**: Users can manually retry failed operations

### Common Error Scenarios

1. **Configuration Issues**:
   - Missing API endpoint, web app name, or API key
   - Invalid API key or unauthorized access
   - Network connectivity problems

2. **API Response Issues**:
   - Malformed JSON responses
   - Missing required fields in responses
   - HTTP error status codes

3. **Timeout Issues**:
   - Requests exceeding 30-second timeout
   - Network latency or server overload

### Error Logging

All API interactions are comprehensively logged:

- **Request Details**: Endpoint, payload size, timing
- **Response Details**: Status codes, response times, content size
- **Error Details**: Exception messages, timeout information
- **User Actions**: User ID, action type, version changes

## Performance Monitoring

### Metrics Tracked

The module logs the following performance metrics:

1. **Response Times**: API call duration in milliseconds
2. **Payload Sizes**: Request and response content sizes
3. **Success Rates**: API call success/failure rates
4. **User Activity**: Frequency of user actions and operations

### Log Analysis

Logs can be analyzed to monitor:

- API performance trends
- User adoption and usage patterns
- Error rates and common failure modes
- System load and capacity planning

## Troubleshooting Guide

### Common Issues and Solutions

1. **"API configuration is incomplete"**
   - Verify all required configuration values are set
   - Check that the API key is properly configured in the Key module
   - Ensure the feature is enabled in module settings

2. **"API request failed: Connection timeout"**
   - Check network connectivity to the API endpoint
   - Verify the API endpoint URL is correct and accessible
   - Consider increasing timeout if server response is slow

3. **"Invalid API response format"**
   - Verify the API endpoint returns properly formatted JSON
   - Check that the response includes expected fields
   - Ensure the API implementation matches the expected contract

4. **"API returned status code: 401"**
   - Verify the API key is correct and not expired
   - Check that the x-functions-key header is being sent properly
   - Ensure the API key has appropriate permissions

### Debugging Steps

1. **Check Logs**: Review Drupal logs for detailed error information
2. **Test Configuration**: Use the manual sync feature to test API connectivity
3. **Verify Permissions**: Ensure users have the "manage ai system instructions" permission
4. **Check Module Status**: Verify the module and its dependencies are properly enabled

## Security Best Practices

### API Key Management

1. **Key Rotation**: Regularly rotate API keys
2. **Access Control**: Limit API key access to necessary personnel
3. **Environment Separation**: Use different keys for development/staging/production
4. **Monitoring**: Monitor API key usage for unusual patterns

### Content Security

1. **Input Validation**: System instructions are validated for length and format
2. **Content Filtering**: Inappropriate content should be filtered at the API level
3. **Version Control**: All changes are tracked with user attribution
4. **Audit Trail**: Comprehensive logging enables security auditing

### Network Security

1. **HTTPS Only**: Never use HTTP for API communications
2. **IP Restrictions**: Consider restricting API access by IP address
3. **Rate Limiting**: Implement additional rate limiting at the API level
4. **Monitoring**: Monitor for unusual traffic patterns or abuse

## Integration Examples

### Basic Configuration

```php
// Example of programmatic configuration (for reference)
$config = \Drupal::configFactory()->getEditable('ai_engine_system_instructions.settings');
$config->set('system_instructions_enabled', TRUE);
$config->set('system_instructions_api_endpoint', 'https://your-api.com/endpoint');
$config->set('system_instructions_web_app_name', 'your-app-name');
$config->set('system_instructions_api_key', 'your_key_entity_id');
$config->save();
```

### Manual API Testing

You can test the API integration manually using curl:

```bash
# Test GET request
curl -X POST "https://your-api.com/endpoint" \
  -H "Content-Type: application/json" \
  -H "x-functions-key: YOUR_API_KEY" \
  -d '{
    "action": "get",
    "web_app_name": "your-app-name",
    "environment_variables": ["AZURE_OPENAI_SYSTEM_MESSAGE"]
  }'

# Test SET request
curl -X POST "https://your-api.com/endpoint" \
  -H "Content-Type: application/json" \
  -H "x-functions-key: YOUR_API_KEY" \
  -d '{
    "action": "set",
    "web_app_name": "your-app-name",
    "environment_variables": {
      "AZURE_OPENAI_SYSTEM_MESSAGE": "Test instructions"
    }
  }'
```

## Changelog and Versioning

### Version 1.0

- Initial API integration
- Basic authentication with function keys
- Get/Set operations for system instructions
- Error handling and graceful degradation
- Comprehensive logging and audit trails

### Future Enhancements

- Batch operations for multiple environment variables
- Webhook support for real-time synchronization
- Advanced authentication methods (OAuth, JWT)
- Multi-environment support with configuration profiles