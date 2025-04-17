# ✨ Axpecto — A Modern PHP Meta-Framework

[![GitHub](https://img.shields.io/github/license/rcrdortiz/Axpecto)](https://choosealicense.com/licenses/mit/)
[![codecov](https://codecov.io/gh/rcrdortiz/Axpecto/branch/main/graph/badge.svg)](https://codecov.io/gh/rcrdortiz/Axpecto)

Axpecto is a powerful meta-framework for modern PHP development. It blends **Aspect-Oriented Programming (AOP)**, **dependency injection (DI)**, and **enhanced collections**, into a highly composable and extensible architecture designed for AI-augmented development.

With Axpecto, you can:

- Build flexible, modular frameworks (e.g., WordPress dev kits, internal platforms).
- Inject behavior into code using clean, declarative annotations.
- Autowire dependencies, inject properties, and intercept method calls without boilerplate.
- Work with expressive Kotlin-style collections in a PHP-native way.

> ⚙️ **Axpecto is designed not just for developers, but for AI agents to extend, understand, and modify code easily, safely and cost efficiently.**

---

## ✨ Features at a Glance

- ✅ **Aspect-Oriented Programming (AOP)** via annotations and proxies.
- ✅ **Powerful DI container** with autowiring, circular reference detection, and injection via attributes.
- ✅ **Build-time annotations** that transform abstract classes and interfaces into runtime-ready implementations.
- ✅ **Method execution interception** for logging, caching, validation, etc.
- ✅ **Kotlin-inspired immutable/mutable collections** (`Klist`, `Kmap`) for functional-style workflows.
- ✅ **AI-friendly architecture**: easy to analyze, extend, and generate.

---

## 📦 Installation

Install via Composer:

```bash
composer require rcrdortiz/axpecto
```

---

## 🚀 Quickstart

### 🔁 Functional Collections

Use Axpecto's `Klist` and `Kmap` for expressive list handling:

```php
require_once 'src/functions.php';

$list = listOf(1, 2, 3, 4, 5);

$filtered = $list
    ->map(fn($i) => $i * 2)
    ->filter(fn($i) => $i > 5)
    ->toArray(); // [6, 8, 10]
```

Collections are **immutable by default**, but can be made mutable with `$list->toMutable()`.

#### Real-World Example: Extract and format user names

```php
class User {
    public function __construct(
        public readonly int $id,
        public readonly string $name
    ) {}
}

// Assume this is a list of User objects fetched from the user repository
$users = listOf(
    new User(1, 'Alice'),
    new User(2, 'Bob'),
    new User(3, 'Charlie')
);

$names = $users
    ->map(fn($u) => $u->name)
    ->join(', ');

// "Alice, Bob, Charlie"
```

#### Real-World Example: Group and filter by condition

```php
$orders = listOf(150, 20, 35, 80, 200);

$highValueOrders = $orders
    ->filter(fn($amount) => $amount >= 100);

// [150, 200]
```

#### Real-World Example: Using `maybe` to conditionally perform a context aware action

```php
$logs = listOf('info', 'debug', 'error');

// maybe() allows optional side effects or context-aware logic only when the list contains items
$logs->maybe(function(Klist $items) {
    $logger->log($items);
    $stats->bump('logs', $items->count());
    echo "done logging " . $items->count() . " items";
});

// done logging 3 items

// Now, let's say we have an empty list
$logs = emptyList();

// When there are no results, the callback is not executed
$logs->maybe(function(Klist $items) {
    echo "This doesn't get echoed";
});
```

#### Real-World Example: Checking conditions with `all` and `any`
```php
$orders = listOf(100, 250, 90, 300);

$allAbove50 = $orders->all(fn($amount) => $amount > 50); // true
$anyAbove500 = $orders->any(fn($amount) => $amount > 500); // false

if ($orders->any(fn($o) => $o > 100)) {
    echo "User gets free shipping and insurance!";
}
```

#### Comparison to native PHP:
```php
// Traditional PHP
array_filter(array_map(fn($x) => $x * 2, [1, 2, 3, 4]), fn($x) => $x > 5);

// With Axpecto
listOf(1, 2, 3, 4)
    ->map(fn($x) => $x * 2)
    ->filter(fn($x) => $x > 5);
```

---

### 🧠 Dependency Injection

Axpecto's DI container autowires your services, resolves dependencies via type hints, and supports injection via `#[Inject]`.

```php
$container = new Container();

$app = $container->get(MyApplication::class);
```

You can also register services manually:

```php
$container->bind(CacheInterface::class, MyCache::class);
$container->addClassInstance(Logger::class, new CustomLogger());
$container->addValue('api_key', 'abcdef123');
```

#### Constructor-Based Injection (Preferred)

The most common way to inject dependencies in Axpecto is through the constructor. The container resolves all parameters by type and automatically wires them:

```php
class ReportGenerator {
    public function __construct(
        private LoggerInterface $logger,
        private ReportRepository $repository
    ) {}

    public function generate() {
        $this->logger->info("Generated: " . count($this->repository->getAll()) . " reports");
    }
}

$reporter = $container->get(ReportGenerator::class);

// Generate reports
$reporter->generate();
```

#### Property Injection (when constructor-based injection is not ideal)

```php
$container->addValue('site_name', 'MyApp');
$container->bind(MailerInterface::class, SMTPMailer::class);

class WelcomeMailer {
    #[Inject] private string $site_name;
    #[Inject] private MailerInterface $mailer;

    public function send($to) {
        $this->mailer->send($to, "Welcome to {$this->site_name}!");
    }
}
```

#### Example: Reusing and re-binding implementations

```php
$container->bind(LoggerInterface::class, FileLogger::class);

// Replace with a mock during testing
$container->addClassInstance(LoggerInterface::class, new MockLogger());
```

---

### 📍 Aspect-Oriented Programming

You can define annotations that **intercept method calls**, allowing you to:

- Apply caching, logging, or validation logic.
- Modify arguments or results.
- Inject cross-cutting behavior without polluting your core logic.

```php
#[HtmlOutput(
  path: 'template.php',
  styles: ['main.css'],
  script_data_provider: fn($result) => ['data' => $result],
)]
public function renderDashboard() {
    return [ 'user' => 'Jane' ];
}
```

The method execution is intercepted by `HtmlOutputHandler`, which transforms the output into a templated HTML page.

---

### 🏗️ Build-Time Annotations (Dynamic Class Generation)

Declare abstract classes with metadata that **Axpecto will build into concrete implementations**:

```php
#[Repository(entityClass: Customer::class)]
abstract class CustomerRepository {
    public abstract function findByIdGreaterThanAndEmailOrEmailIsNull($id, $email);
}
```

Axpecto parses this and auto-generates logic to resolve database access without manual boilerplate.

#### 🧪 Example Build Annotation: `#[Service]`

```php
#[Service]
abstract class ReportService {
    public abstract function generate();
}
```

```php
class ServiceBuildHandler implements BuildHandler {
    public function intercept(Annotation $annotation, BuildContext $context): void {
        if (! $annotation instanceof Service || $annotation->getAnnotatedMethod() !== null) return;

        foreach ($context->getAbstractMethods() as $method) {
            $context->addMethod(
                $method->getName(),
                "public function {$method->getName()}()",
                "// Auto-generated service method implementation"
            );
        }
    }
}
```

---

### ⚡ Method Execution Interception

Intercept method calls to apply logic dynamically.

#### Example: `#[Cacheable]`

```php
#[Cacheable(ttl: 60)]
public function getLatestPosts() {
    return $this->query->fetch();
}
```

```php
class CacheableHandler implements MethodExecutionHandler {
    public function intercept(MethodExecutionContext $ctx): mixed {
        $key = md5($ctx->className . $ctx->methodName . serialize($ctx->arguments));
        $cache = new SimpleCache();

        if ($cache->has($key)) {
            return $cache->get($key);
        }

        $result = $ctx->proceed();
        $cache->set($key, $result, $ctx->getAnnotation()->ttl);

        return $result;
    }
}
```

#### Example: `#[Logged]`

```php
#[Logged]
public function processOrder($orderId) {
    // Core logic here
}
```

```php
class LoggedHandler implements MethodExecutionHandler {
    public function intercept(MethodExecutionContext $ctx): mixed {
        error_log("Entering {$ctx->className}::{$ctx->methodName}");
        $result = $ctx->proceed();
        error_log("Exiting {$ctx->className}::{$ctx->methodName}");
        return $result;
    }
}
```

---
# 🗃️ Using `#[Repository]` in Axpecto
### ⚠️ The repository feature is under construction and could change.

The `#[Repository]` annotation in Axpecto allows you to define data access logic declaratively by marking an interface or abstract class as a **repository**. When the container encounters this class, Axpecto will **build the implementation automatically**, parsing method names and generating queries based on conventions and entity metadata.

## 🔍 What `#[Repository]` Does

When you annotate a class with `#[Repository]`, Axpecto:

1. Reads the `entityClass` that this repository manages.
2. Parses abstract method names to extract conditions (e.g., `findByIdAndEmailIsNotNull`).
3. Generates runtime code that:
    - Builds a `Criteria` object.
    - Maps entity properties to database fields.
    - Calls the appropriate persistence logic.
4. Injects dependencies like the entity mapper and persistence strategy.

This behavior is defined by the `RepositoryBuildHandler`, which is automatically injected and associated with the annotation.
This defines a CustomerRepository where the methods will be built automatically. When you call:


---

## ✅ Basic Example

```php
#[Repository(entityClass: Customer::class)]
abstract class CustomerRepository {
    public abstract function findByEmail(string $email);
    public abstract function findByUsernameOrEmail(string $username, string $email);
}
// This defines a CustomerRepository where the methods will be built automatically. When you call:
$repo = $container->get(CustomerRepository::class);
$customer = $repo->findByEmail('someone@example.com');
```
Axpecto builds the logic to create a Criteria, apply the field conditions, and use the underlying storage to fetch and map the entity.

## 🧠 Behind the Scenes
The handler responsible is RepositoryBuildHandler, which:
- Uses ReflectionUtils to inspect the entity and repository class.
- Parses method names using RepositoryMethodNameParser.
- Resolves annotations like #[Entity], #[Mapping], #[Id], etc.
- Maps method parameters to conditions.
- Outputs real executable code injected at runtime via the BuildContext.
```php
// Example generated logic for a method like:
public abstract function findByIdGreaterThanAndEmailOrEmailIsNull($id, $email);

// ...will result in a criteria similar to:
$criteria->addCondition('id', $id, Operator::GREATER_THAN);
$criteria->addCondition('email', $email, Operator::EQUALS, LogicOperator::OR);
$criteria->addCondition('email', null, Operator::IS_NULL, LogicOperator::OR);
```

## 💡 Entity Example
```php
#[Entity(storage: MysqlPersistenceStrategy::class, table: 'customer')]
class Customer {
    public function __construct(
        #[Id(autoIncrement: true)]
        public ?int $id,
        #[UniqueNotNull]
        public string $username,
        #[UniqueNotNull]
        public string $email,
        #[Column(isNullable: true)]
        public string $password,
        #[Timestamp]
        public DateTime $createdAt,
        #[Timestamp(onUpdate: Column::CURRENT_TIMESTAMP)]
        public DateTime $updatedAt,
        #[UniqueNotNull]
        public string $displayName,
    ) {}
}
```
## 🧪 Tips and Best Practices
- ✅ Use clear, expressive method names — they’re parsed to build conditions.
- ✅ Combine with `#[Column]` annotations on entities to customize DB fields.
- ⚠️ Avoid overly complex method names with too many logical branches.
- ✅ Great for CRUD interfaces, admin panels, or quick scaffolding.

## 🛠 Advanced Ideas
You can extend this concept with:

- `#[Entity(storage: MyCustomStrategy::class)]` for custom persistence backends.
- Auto-generated GraphQL endpoints based on repository methods.
- Caching, logging, or access control annotations layered on top of repositories.

---

## 📊 Use Cases

Here are just a few ways you can use Axpecto:

### 🖥️ Build a WordPress Plugin Framework
- Use Axpecto to structure plugin architecture.
- Inject services like settings managers, loggers, or HTTP clients.
- Wrap admin views with custom output annotations like `#[HtmlOutput]`.

### ⚙️ Declarative Validation & Security
```php
#[Validate]
#[Authorize(role: 'admin')]
public function deleteUser($userId) {
    // Executed only after validation + security checks
}
```

### 🚀 AI-Generated Plugins
- Define plugin behavior with attributes.
- Let an AI generate or extend logic without modifying core classes.

### 💡 Scalable Domain Framework
- Define domain repositories via interfaces and `#[Repository]` annotations.
- Use `#[Entity]` and `#[Mapping]` to drive your ORM logic.

### 🌟 Developer Experience Enhancements
- Annotate logging, caching, rate-limiting.
- Inject metadata to methods without touching implementation.

---

## 🔗 Architecture & Philosophy

Axpecto is a meta-framework. It's:

- **Annotation-first**: Behavior is declarative, composable, and introspectable.
- **AI-extendable**: Built to be easily parsed, modified, and extended by AI.
- **Framework-agnostic**: Use in Laravel, Symfony, WordPress, or your own stack.
- **Composable**: Swap DI, AOP, or collections independently.

---

## 💪 Contributing

Contributions are welcome! Check out the [CONTRIBUTING.md](CONTRIBUTING.md) and explore `/examples` for demos.

---

## 📄 License

MIT License. See [LICENSE](LICENSE) for full terms.