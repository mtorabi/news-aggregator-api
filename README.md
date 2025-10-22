# News Aggregator API

A Laravel-based news aggregation API that fetches articles from multiple news sources using a queue-based architecture with Laravel Horizon.

## Table of Contents

- [Development Setup](#development-setup)
- [API Documentation](#api-documentation)
- [Adding News Sources](#adding-news-sources)
- [Scheduler Configuration](#scheduler-configuration)
- [Architecture Overview](#architecture-overview)

## Development Setup

This application uses Docker and Docker Compose for development. Follow these steps to get the application running locally.

### Prerequisites

- Docker Desktop installed and running
- Git

### Setup Instructions

1. **Clone the repository**

   ```bash
   git clone https://github.com/mtorabi/news-aggregator-api.git
   cd news-aggregator-api
   ```

2. **Environment Configuration**

   ```bash
   # Copy the environment file
   cp .env.example .env
   ```

3. **Configure Database and API Keys**

   Edit the `.env` file and configure the database credentials and API keys:

   ```env
   # Database Configuration (must match docker-compose.yaml)
   DB_CONNECTION=mysql
   DB_HOST=db
   DB_PORT=3306
   DB_DATABASE=news_aggregator_api
   DB_USERNAME=laravel
   DB_PASSWORD=secret
   
   # NewsAPI.org
   NEWS_API_ORG_KEY=your_newsapi_key_here
   
   # New York Times
   NY_TIMES_API_KEY=your_nytimes_key_here
   
   # The Guardian
   GUARDIAN_API_KEY=your_guardian_key_here
   ```

4. **Build and Start the Application**

   ```bash
   # Build and start all services
   docker-compose up -d
   ```

5. **Install Dependencies and Setup Laravel**

   ```bash
   # Access the workspace container
   docker-compose exec workspace /bin/sh
   
   # Install PHP dependencies
   composer install
   
   # Generate application key
   php artisan key:generate
   
   # Run database migrations
   php artisan migrate
   
   # Exit the container
   exit
   ```

6. **Verify Installation**

   - Application: <http://localhost:8080>
   - Database: MySQL on port 3306
   - Redis: Available on port 6379
   - Laravel Horizon: <http://localhost:8080/horizon>

### Docker Services

The application includes the following services:

- **app**: Main Laravel application (PHP-FPM)
- **nginx**: Web server serving the application
- **db**: MySQL 8.0 database
- **redis**: Redis server for queues and caching
- **horizon**: Laravel Horizon queue dashboard and worker
- **scheduler**: Automated task scheduler
- **workspace**: Development workspace with all tools

### Useful Commands

```bash
# View logs
docker-compose logs -f app
docker-compose logs -f scheduler
docker-compose logs -f horizon

# Access containers
docker-compose exec app /bin/sh
docker-compose exec workspace /bin/sh

# Run artisan commands
docker-compose exec app php artisan news:fetch
docker-compose exec app php artisan queue:work

# Stop all services
docker-compose down

# Rebuild services
docker-compose up -d --build
```

## API Documentation

The News Aggregator API provides comprehensive documentation using Swagger/OpenAPI 3.0. The API documentation is automatically generated from code annotations and is available at multiple endpoints.

### Accessing API Documentation

- **Swagger UI**: <http://localhost:8080/api/documentation> - Interactive documentation interface
- **JSON Format**: <http://localhost:8080/docs> - OpenAPI JSON specification

### API Endpoints

#### Articles

- `GET /api/v1/articles` - Get articles with search, filtering, and pagination
  - Query parameters: `search`, `source`, `category`, `author`, `date_from`, `date_to`, `page`, `per_page`
- `GET /api/v1/articles/{id}` - Get a specific article by ID

#### Filters

- `GET /api/v1/filters/sources` - Get unique sources for filtering
- `GET /api/v1/filters/categories` - Get unique categories for filtering  
- `GET /api/v1/filters/authors` - Get unique authors for filtering

### Regenerating Documentation

The API documentation is automatically generated in development mode. To manually regenerate:

```bash
# Using the custom documentation command (recommended)
docker-compose exec app php artisan docs:generate

# Using L5-Swagger directly
docker-compose exec app php artisan l5-swagger:generate
```

### Configuration

Swagger documentation is configured in:

- `config/l5-swagger.php` - Main L5-Swagger configuration
- Controller annotations - OpenAPI attributes in controller methods
- Model schemas - OpenAPI schema definitions in models

### Environment Variables

```env
# Auto-generate docs in development
L5_SWAGGER_GENERATE_ALWAYS=true

# Use absolute paths for assets  
L5_SWAGGER_USE_ABSOLUTE_PATH=true

# Documentation format (json or yaml)
L5_FORMAT_TO_USE_FOR_DOCS=json
```

## Adding News Sources

The news sources are configured in `config/news.php`. Each source must define how to fetch and map article data.

### Configuration Structure

```php
'resources' => [
    'source_key' => [
        'name' => 'Display Name',
        'url' => function ($from, $to) {
            // Return API endpoint URL
        },
        'items_path' => 'path.to.articles.array',
        'mapping' => [
            // Field mappings
        ],
    ],
]
```

### Adding a New Source

1. **Open the configuration file**

   ```bash
   # Edit the news configuration
   vim config/news.php
   ```

2. **Add your new source configuration**

   ```php
   'your_source_key' => [
       'name' => 'Your News Source',
       'url' => function ($from, $to) {
           return 'https://api.yournewssource.com/articles?' .
               'from=' . $from->format('Y-m-d') . 
               '&to=' . $to->format('Y-m-d') . 
               '&apikey=' . env('YOUR_SOURCE_API_KEY');
       },
       'items_path' => 'data.articles', // JSON path to articles array
       'mapping' => [
           'external_id' => function ($item) {
               return base64_encode($item['id']);
           },
           'title' => 'headline',           // Map to article title
           'url' => 'article_url',          // Map to article URL
           'body' => 'summary',             // Map to article content
           'source' => 'Your News Source',  // Static source name
           'author' => 'author_name',       // Map to author field
           'image_url' => 'featured_image', // Map to image URL
           'category' => 'section',         // Map to category
           'published_at' => 'publish_date', // Map to publication date
       ],
   ],
   ```

3. **Add the API key to your environment**

   ```env
   YOUR_SOURCE_API_KEY=your_api_key_here
   ```

4. **Test the new source**

   ```bash
   # Manually run the news fetch command
   docker-compose exec app php artisan news:fetch
   
   # Check the logs
   docker-compose logs -f horizon
   ```

### Field Mapping Options

- **String paths**: Use dot notation for nested JSON fields (e.g., `'response.data.title'`)
- **Functions**: Use closures for complex transformations:

  ```php
  'external_id' => function ($item) {
      return md5($item['url'] . $item['published_at']);
  },
  ```

- **Static values**: Use strings for constant values like source names

### Available Mapping Fields

| Field | Description | Required |
|-------|-------------|----------|
| `external_id` | Unique identifier from source | Yes |
| `title` | Article headline | Yes |
| `url` | Link to full article | Yes |
| `body` | Article summary/description | No |
| `source` | Source name | Yes |
| `author` | Article author | No |
| `image_url` | Featured image URL | No |
| `category` | Article category/section | No |
| `published_at` | Publication timestamp | Yes |

## Scheduler Configuration 

The application uses Laravel's task scheduler to automatically fetch news articles. The scheduler runs as a separate Docker container and executes scheduled tasks every minute.

### How It Works

1. **Schedule Definition**: Tasks are defined in `routes/console.php`
2. **Scheduler Container**: A dedicated Docker container runs `php artisan schedule:run` every 60 seconds. (Just for development purposes)
3. **Task Execution**: The scheduler checks for due tasks and executes them
4. **Queue Integration**: News fetching tasks are dispatched to Laravel Horizon queues

### Current Schedule

```php
// Fetch news daily at 6:00 AM UTC
Schedule::command('news:fetch')
    ->dailyAt('06:00')
    ->withoutOverlapping()     // Prevent concurrent runs
    ->runInBackground()        // Non-blocking execution
    ->timezone('UTC')          // Use UTC timezone
    ->appendOutputTo(storage_path('logs/scheduler.log'));
```

### Production Scheduler Setup

**⚠️ Important**: The Docker-based scheduler is for development only. In production, use:

1. **Host-level cron job**:

   ```bash
   # Add to crontab
   * * * * * cd /path/to/project && docker-compose exec -T app php artisan schedule:run >> /dev/null 2>&1
   ```

2. **Cloud-based schedulers**:
   - AWS EventBridge (CloudWatch Events)
   - Google Cloud Scheduler
   - Azure Logic Apps

3. **Kubernetes CronJobs**:

   ```yaml
   apiVersion: batch/v1
   kind: CronJob
   metadata:
     name: laravel-scheduler
   spec:
     schedule: "* * * * *"
     jobTemplate:
       spec:
         template:
           spec:
             containers:
             - name: laravel
               image: your-app:latest
               command: ["php", "artisan", "schedule:run"]
   ```

### Monitoring the Scheduler

1. **View scheduler logs**:

   ```bash
   docker-compose logs -f scheduler
   ```

2. **Check scheduled tasks**:

   ```bash
   docker-compose exec app php artisan schedule:list
   ```

3. **Test schedule manually**:

   ```bash
   docker-compose exec app php artisan schedule:run
   ```

## Architecture Overview

### News Fetching Flow

1. **Scheduler** triggers the `news:fetch` command daily at 6:00 AM UTC
2. **FetchNewsCommand** reads source configurations from `config/news.php`
3. **Jobs are dispatched** to the `news-fetch` queue for each configured source
4. **Laravel Horizon** processes the jobs using `FetchSingleSourceNewsJob`
5. **Articles are fetched** from each API and stored in the database
6. **Duplicate articles** are prevented using the `external_id` field

### Key Components

- **FetchNewsCommand**: Console command that dispatches fetch jobs
- **FetchSingleSourceNewsJob**: Queue job that fetches from a single source
- **NewsService**: Service class handling API requests and data processing
- **Article Model**: Eloquent model for storing article data
- **Laravel Horizon**: Queue dashboard and worker management

### Queue Configuration

The application uses Redis for queue management with Laravel Horizon providing:

- Real-time queue monitoring
- Failed job handling
- Worker process management
- Queue metrics and insights

Access Horizon at: <http://localhost:8080/horizon>

---

For more information, check the Laravel documentation for [Task Scheduling](https://laravel.com/docs/scheduling) and [Queues](https://laravel.com/docs/queues).