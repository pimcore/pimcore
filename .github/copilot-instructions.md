## Project Overview

Pimcore is a PHP-based Data & Experience Management Platform built on Symfony. It combines PIM (Product Information Management), MDM (Master Data Management), CDP (Customer Data Platform), DAM (Digital Asset Management), DXP/CMS (Digital Experience Platform/Content Management System), and Digital Commerce functionalities.

## Final steps before finishing a Session
- Check composer.json and remove any repositories that are configured for the session
- Do this always before merging a PR
- composer.json should not contain any changes compared to the base branch


## Development Commands

### Testing
- **Run all tests**: `vendor/bin/codecept run`
- **Run specific test suite**:
    - Unit tests: `vendor/bin/codecept run Unit`
    - Service tests: `vendor/bin/codecept run Service`
    - Model tests: `vendor/bin/codecept run Model`
    - Cache tests: `vendor/bin/codecept run Cache`
    - Twig tests: `vendor/bin/codecept run Twig`
- **Run single test**: `vendor/bin/codecept run Unit:MyTestCest`

### Code Quality
- **PHPStan analysis**: `vendor/bin/phpstan analyse` (level 6)

## Architecture Overview

### Core Framework Structure
- **Symfony Integration**: Built on Symfony 6+ with full framework integration
- **Bundle Architecture**: Modular system with core bundles (CoreBundle, ApplicationLoggerBundle, SeoBundle, etc.)
- **Model-DAO Pattern**: Separation of domain models from database persistence
- **Event-Driven**: Comprehensive event system for extensibility

### Key Directories
- `bundles/` - Core Pimcore bundles with features organized modularly
- `lib/` - Core application code, services, and utilities
- `models/` - Domain models (Asset, Document, DataObject, User, etc.)
- `config/` - Configuration files and service definitions
- `templates/` - Twig templates
- `bin/` - Console application entry points
- `tests/` - Codeception test suites
- `doc/` - Comprehensive documentation

### Element Hierarchy
Three main element types form the core of Pimcore:
- **Assets**: Digital asset management (images, videos, documents)
- **Documents**: Content management (pages, snippets, emails)
- **DataObjects**: Structured data management (products, categories, customers)

All elements inherit from `AbstractElement` and support:
- Parent-child relationships in tree structure
- Permissions and access control
- Properties and dependencies
- Versioning and recycling

### Bundle Structure
Each bundle follows Symfony conventions:
```
bundles/ExampleBundle/
├── src/           # PHP classes and business logic
├── config/        # Bundle configuration
├── public/        # Web assets (JS, CSS)
├── translations/  # i18n files
└── templates/     # Twig templates
```

### Data Object System
- **Class Definitions**: Define data structure with graphical editor
- **Field Types**: Rich set of field types for different data needs
- **Inheritance**: Support for class and data inheritance
- **Field Collections**: Reusable field groups
- **Object Bricks**: Extension points for additional fields
- **Classification Store**: Dynamic attributes system

### Service Architecture
- **Dependency Injection**: Full Symfony DI container with auto-wiring
- **Service Collections**: Grouped functionality with tagged services
- **Factory Pattern**: Dynamic class instantiation and resolution
- **Event System**: Listeners and subscribers for business logic

### Console Application
Access via `bin/console`:
- Asset processing and thumbnail generation
- Cache management and warming
- Class definition operations
- Maintenance tasks and system cleanup
- Database migrations and updates

## Development Guidelines

### Code Standards
- Follow Symfony coding standards and PER Coding Style 3.0
- Use single quotes for strings unless interpolation needed
- Use `===` for comparisons, avoid `==`
- Make new classes `final` unless intended for extension
- Use constructor promotion for class properties
- Follow principle of minimal visibility (private by default)
- Use dependency injection and avoid static methods
- Add `@throws` annotations for methods that throw exceptions

### Testing Approach
- Use Codeception for all testing
- Write unit tests for new functionality
- Use mocks and stubs where applicable
- Tests are organized in suites: Unit, Service, Model, Cache, Twig
- Test configuration in `codeception.dist.yml`

### Repository Guidelines
- Do not modify `composer.json` or `composer.lock` in commits
- Do not modify or create `codeception.yml` in commits
- Use UTF-8 encoding and add newline at end of files
- Use interfaces for service classes
- Avoid abstract classes unless necessary
- Document exceptions with `@throws` annotations

### Architecture Patterns
- **MVC**: Model-View-Controller with Symfony integration
- **DAO**: Data Access Object pattern for database operations
- **Factory**: Object creation and type resolution
- **Observer**: Event-driven architecture for extensibility
- **Strategy**: Pluggable components via interfaces
- **Decorator**: Service decoration for extending functionality

### Extension Points
- Event listeners and subscribers
- Service tagging and compilation passes
- Custom field types and data adapters
- Workflow extensions and global actions
- Bundle-based plugin system

### Common Tasks
- Creating new bundle: Follow Symfony bundle structure in `bundles/`
- Adding new field type: Extend appropriate field type classes
- Custom console commands: Extend `AbstractCommand`
- Event handling: Implement event listeners/subscribers
- Database changes: Use Doctrine migrations with bundle filtering
- Cache management: Use tagged cache invalidation
- Permission system: Integrate with workspace-based permissions

### Debugging & Development
- Use Symfony's Web Profiler for debugging
- Enable debug mode in development environment
- Utilize comprehensive logging system
- Use Xdebug for step-through debugging
- Database queries logged in debug mode

This architecture provides a robust foundation for content management, digital asset management, and e-commerce applications while maintaining high code quality and extensibility standards.