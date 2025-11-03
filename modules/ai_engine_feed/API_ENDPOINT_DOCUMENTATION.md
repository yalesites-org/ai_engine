# AI Engine Feed API Documentation

## Overview

The AI Engine Feed API provides a structured REST endpoint for consuming Drupal content (nodes and media) in a format optimized for AI processing, search indexing, and embeddings generation. The API exposes published content with comprehensive metadata, pagination support, and flexible filtering options.

## Endpoint Details

### Base URL
```
/api/ai/v1/content
```

### HTTP Method
`GET`

### Authentication
- **Permission Required**: `access content`
- **Public Access**: Yes (for published content)
- **Authentication Type**: Standard Drupal session/cookie authentication

### Access Control
- Only published content is returned
- Content must be accessible to anonymous users
- Respects Drupal's built-in access control system
- Content marked for AI exclusion via metadata is filtered out

## Request Parameters

### Query Parameters

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `page` | integer | No | `1` | Page number for pagination (starts at 1) |
| `entityType` | string | No | `node` | Entity type to retrieve (`node` or `media`) |
| `id` | integer | No | - | Filter by specific entity ID |

### Examples

```
# Get first page of all nodes
/api/ai/v1/content

# Get page 2 of nodes
/api/ai/v1/content?page=2

# Get all media entities
/api/ai/v1/content?entityType=media

# Get specific node by ID
/api/ai/v1/content?id=18

# Combine filters
/api/ai/v1/content?entityType=node&page=3
```

## Response Format

### Response Structure

```json
{
  "data": [/* array of content items */],
  "links": {/* pagination links */},
  "totals": {/* pagination metadata */}
}
```

### Content Item Schema

Each item in the `data` array contains different fields depending on the entity type.

#### Node Entity Example

```json
{
  "id": "yalesites-yale-edu-node-18",
  "source": "drupal",
  "documentType": "node/page",
  "documentId": 18,
  "documentTitle": "Resources and Workshops",
  "documentUrl": "https://yalesites.yale.edu/resource",
  "documentContent": "Full rendered content...",
  "metaTags": "",
  "metaDescription": "Description for AI processing",
  "dateCreated": "2023-10-12T16:09:21+00:00",
  "dateModified": "2023-11-30T16:11:18+00:00",
  "dateProcessed": "2024-01-23T16:05:38+00:00"
}
```

#### Media Entity Example

```json
{
  "id": "yalesites-yale-edu-media-42",
  "source": "drupal",
  "documentType": "media/document",
  "documentId": 42,
  "documentTitle": "Annual Report 2024",
  "documentUrl": "https://yalesites.yale.edu/sites/default/files/2024-01/annual-report-2024.pdf",
  "documentContent": "",
  "documentDescription": "Comprehensive annual report for fiscal year 2024",
  "metaTags": "",
  "metaDescription": "Financial and operational summary",
  "dateCreated": "2024-01-15T10:30:00+00:00",
  "dateModified": "2024-01-15T10:30:00+00:00",
  "dateProcessed": "2024-01-23T16:05:38+00:00"
}
```

**Key Differences:**
- **Nodes**: `documentUrl` points to the page URL; `documentContent` contains rendered HTML
- **Media**: `documentUrl` points directly to the file URL for download/access; `documentContent` is empty; includes `documentDescription` field with file metadata (alt text, description, or title)

### Field Definitions

| Field | Type | Description |
|-------|------|-------------|
| `id` | string | Unique identifier combining hostname, entity type, and entity ID (format: `{hostname}-{entityType}-{id}`) |
| `source` | string | Always `"drupal"` - identifies the source system |
| `documentType` | string | Entity type and bundle (format: `{entityType}/{bundle}`, e.g., `node/page`, `node/article`, `media/document`) |
| `documentId` | integer | Drupal entity ID |
| `documentTitle` | string | Title of the content item (node title or media entity name) |
| `documentUrl` | string | **Nodes**: Absolute canonical URL to the page<br>**Media**: Direct URL to the file for download/access |
| `documentContent` | string | **Nodes**: Fully rendered content using the default display mode (includes all processed fields)<br>**Media**: Empty string |
| `documentDescription` | string | **Media only**: File metadata (alt text, description, or title from the file)<br>**Nodes**: Not present |
| `metaTags` | string | AI-specific metadata tags for categorization |
| `metaDescription` | string | AI-specific description for enhanced context |
| `dateCreated` | string | ISO 8601 formatted creation timestamp |
| `dateModified` | string | ISO 8601 formatted last modified timestamp |
| `dateProcessed` | string | ISO 8601 formatted timestamp of when the API response was generated |

### Pagination Links

The `links` object provides navigation URLs:

```json
{
  "links": {
    "first": "https://yalesites.yale.edu/api/ai/v1/content?page=1",
    "prev": "https://yalesites.yale.edu/api/ai/v1/content?page=1",
    "self": "https://yalesites.yale.edu/api/ai/v1/content?page=2",
    "next": "https://yalesites.yale.edu/api/ai/v1/content?page=3",
    "last": "https://yalesites.yale.edu/api/ai/v1/content?page=5"
  }
}
```

**Link Descriptions:**
- `first`: URL to the first page
- `prev`: URL to the previous page (empty string if on first page)
- `self`: URL to the current page
- `next`: URL to the next page (empty string if on last page)
- `last`: URL to the last page

### Pagination Metadata

The `totals` object provides overview information:

```json
{
  "totals": {
    "total_records": 235,
    "total_pages": 5
  }
}
```

| Field | Description |
|-------|-------------|
| `total_records` | Total number of content items matching the query |
| `total_pages` | Total number of pages available |

## Pagination

### Configuration
- **Records Per Page**: 50
- **Page Numbering**: Starts at 1
- **Default Page**: 1 (if not specified)

### Pagination Workflow

1. Make initial request to `/api/ai/v1/content`
2. Check `totals.total_pages` to determine total pages
3. Use `links.next` to navigate to subsequent pages
4. Continue until `links.next` is an empty string

### Example Pagination Loop

```python
import requests

base_url = "https://yalesites.yale.edu/api/ai/v1/content"
all_content = []

response = requests.get(base_url)
data = response.json()

# Process first page
all_content.extend(data['data'])

# Process remaining pages
while data['links']['next']:
    response = requests.get(data['links']['next'])
    data = response.json()
    all_content.extend(data['data'])

print(f"Retrieved {len(all_content)} total items")
```

## Usage Recommendations

### Polling Frequency

**Recommended Polling Schedule:**
- **High-frequency sites**: Twice daily (every 12 hours)
- **Medium-frequency sites**: Once daily
- **Low-frequency sites**: Weekly
- **Archival sites**: Monthly or yearly

### Optimization Strategies

1. **Incremental Updates**
   - Use `dateModified` field to identify changed content
   - Store the last processed `dateModified` timestamp
   - Filter results client-side for content newer than last sync
   - This reduces processing of unchanged content

2. **Delta Processing**
   ```python
   last_sync = "2024-01-23T16:00:00+00:00"

   for item in data['data']:
       if item['dateModified'] > last_sync:
           # Process updated/new content
           process_content(item)
   ```

3. **Caching Strategy**
   - Cache the full dataset locally
   - Use `id` field as unique key
   - Update cache with modified content based on `dateModified`
   - Remove items from cache that no longer appear in the feed

4. **Rate Limiting**
   - Implement exponential backoff for errors
   - Add delays between page requests (e.g., 1-2 seconds)
   - Monitor server response times and adjust accordingly

5. **Error Handling**
   - Check HTTP status codes
   - Handle network timeouts gracefully
   - Implement retry logic with backoff
   - Log failed requests for debugging

### Content Processing

1. **Node Content Processing**
   - `documentContent` contains fully rendered HTML
   - May include embedded media, links, and formatting
   - Strip HTML tags for plain text processing
   - Preserve structure for enhanced context
   - `documentUrl` provides link to the page for reference

2. **Media Content Processing**
   - `documentContent` is always empty for media entities
   - `documentUrl` provides the direct file URL for download/access
   - Use `documentUrl` to retrieve the actual document file (typically PDF)
   - `documentDescription` contains file metadata (description or title)
   - `documentType` will be `media/document` for document files
   - Download and process PDF files separately based on your use case

3. **Metadata Usage**
   - `metaTags`: Use for categorization and filtering
   - `metaDescription`: Provides curated summary for AI context
   - `documentType`: Identify content type for specialized processing

4. **URL Canonicalization**
   - `documentUrl` provides the canonical URL (page URL for nodes, file URL for media)
   - Use for deduplication and reference linking
   - Always use absolute URLs for cross-referencing

### Monitoring and Maintenance

1. **Track API Metrics**
   - Response times
   - Error rates
   - Total records returned
   - Change frequency per content type

2. **Content Validation**
   - Verify all required fields are present
   - Check for malformed data
   - Monitor for sudden changes in record counts

3. **Version Awareness**
   - API version is in the URL path (`/v1/`)
   - Monitor for new versions or deprecation notices
   - Test against staging environments before production updates

## Error Handling

### HTTP Status Codes

| Code | Meaning | Recommended Action |
|------|---------|-------------------|
| 200 | Success | Process response data |
| 403 | Forbidden | Check authentication/permissions |
| 404 | Not Found | Verify endpoint URL |
| 500 | Server Error | Retry with exponential backoff |
| 503 | Service Unavailable | Wait and retry later |

### Empty Results

An empty `data` array with successful response (200) indicates:
- No published content matches the query
- All content is excluded via AI metadata settings
- Page number exceeds available pages

```json
{
  "data": [],
  "links": {
    "first": "",
    "prev": "",
    "self": "",
    "next": "",
    "last": ""
  },
  "totals": {
    "total_records": 0,
    "total_pages": 0
  }
}
```

## Security Considerations

1. **Data Exposure**
   - Only published content is exposed
   - Unpublished content is never returned
   - Access control is enforced at the Drupal level

2. **Input Validation**
   - All query parameters are sanitized
   - SQL injection protection via parameterized queries
   - Invalid parameters are safely ignored

3. **Rate Limiting**
   - Consider implementing client-side rate limiting
   - Respect server resources
   - Implement circuit breakers for failures

4. **Data Privacy**
   - Content may contain sensitive information
   - Implement appropriate data handling procedures
   - Ensure compliance with privacy regulations

## Integration Examples

### Python Integration

```python
import requests
from datetime import datetime

class DrupalContentFeed:
    def __init__(self, base_url):
        self.base_url = base_url
        self.last_sync = None

    def fetch_all_content(self):
        """Fetch all content with pagination"""
        content = []
        page = 1

        while True:
            response = requests.get(
                f"{self.base_url}?page={page}"
            )
            data = response.json()

            if not data['data']:
                break

            content.extend(data['data'])

            if not data['links']['next']:
                break

            page += 1

        return content

    def fetch_updated_content(self, since):
        """Fetch content modified since timestamp"""
        all_content = self.fetch_all_content()

        return [
            item for item in all_content
            if item['dateModified'] > since
        ]

# Usage
feed = DrupalContentFeed("https://yalesites.yale.edu/api/ai/v1/content")
recent_updates = feed.fetch_updated_content("2024-01-20T00:00:00+00:00")
```

### JavaScript/Node.js Integration

```javascript
const axios = require('axios');

class DrupalContentFeed {
  constructor(baseUrl) {
    this.baseUrl = baseUrl;
  }

  async fetchPage(page = 1) {
    const response = await axios.get(`${this.baseUrl}?page=${page}`);
    return response.data;
  }

  async fetchAllContent() {
    const allContent = [];
    let page = 1;
    let hasMore = true;

    while (hasMore) {
      const data = await this.fetchPage(page);
      allContent.push(...data.data);

      hasMore = data.links.next !== '';
      page++;
    }

    return allContent;
  }

  async fetchUpdatedSince(timestamp) {
    const allContent = await this.fetchAllContent();

    return allContent.filter(
      item => new Date(item.dateModified) > new Date(timestamp)
    );
  }
}

// Usage
const feed = new DrupalContentFeed('https://yalesites.yale.edu/api/ai/v1/content');
feed.fetchAllContent().then(content => {
  console.log(`Retrieved ${content.length} items`);
});
```

## Use Cases

### 1. AI/ML Training Data
- Use `documentContent` for training language models
- `documentTitle` and `metaDescription` for summarization tasks
- `metaTags` for classification and categorization

### 2. Search Index Population
- `id` field ensures unique document identification
- `documentUrl` for search result linking
- `documentContent` for full-text search indexing
- `dateModified` for incremental index updates

### 3. Embeddings Generation
- Process `documentContent` through embedding models
- Store embeddings with `id` as key
- Use `dateModified` to identify content requiring re-embedding

### 4. Content Synchronization
- Mirror Drupal content to external systems
- Use `dateModified` for delta synchronization
- `documentId` for tracking original source

### 5. Analytics and Reporting
- Track content creation patterns via `dateCreated`
- Monitor content freshness via `dateModified`
- Analyze content types via `documentType`

## Changelog and Versioning

### Current Version: v1

**Version Identifier**: `/api/ai/v1/content`

Future API changes will use a new version path (e.g., `/api/ai/v2/content`) to maintain backward compatibility.

## Support and Contact

For technical issues, feature requests, or questions about the API:
- Check the module documentation: `/modules/ai_engine_feed/README.md`
- Review the source code for implementation details
- Contact your system administrator or development team

## Appendix: Technical Implementation

### Module Information
- **Module Name**: `ai_engine_feed`
- **Controller**: `Drupal\ai_engine_feed\Controller\ContentFeed`
- **Service**: `Drupal\ai_engine_feed\Service\Sources`

### Plugin Architecture
The API uses a plugin-based system for extensibility:
- `NodePlugin`: Processes node entities
- `MediaPlugin`: Processes media entities
- Custom plugins can be added for additional entity types

### Content Processing
- Content is rendered using Drupal's default display mode
- All fields are processed through Drupal's render system
- Output is sanitized and safe for consumption

### Performance Considerations
- Queries use Drupal's Entity Query API for optimization
- Results are not cached (always fresh data)
- Large sites may have longer response times for later pages
- Consider implementing response caching on the consumer side
