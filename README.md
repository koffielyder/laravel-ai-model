# Laravel AI Model

Easily extend your Laravel Eloquent models with AI-driven functionality to generate new database entries, complete with smart handling of model fields and relations.

> **Built for Laravel 12.x**

---

## ✨ Features

- Add descriptions to your database fields to guide AI generation.
- Automatically build JSON schemas for your models and their relationships.
- Seamlessly integrate AI-generated data into your database.
- Extend existing entries intelligently based on previous context.
- Fully backend-driven — frontend implementation is left to the user.

---

## 📦 Installation

```bash
composer require koffielyder/laravel-ai-model
```

If you're publishing your package for local development:

```bash
composer require koffielyder/laravel-ai-model:@dev
```

---

## ⚙️ Configuration

Publish the package configuration file:

```bash
php artisan vendor:publish --provider="Koffielyder\\LaravelAiModel\\AiModelServiceProvider" --tag=config
```

This will create a `config/ai-model.php` file where you can set:

- Your OpenAI API key
- API endpoint
- Model version (e.g., `gpt-4`)

---

## 🧩 Usage

### 1. Extend Your Models

Use the `HasAi` trait on any model you want to generate with AI:

```php
use Koffielyder\LaravelAiModel\Traits\HasAi;
use Illuminate\Database\Eloquent\Model;

class Region extends Model
{
    use HasAi;

    protected $fillable = ['name', 'backstory'];

    protected $aiRelations = [
        'characters' => Character::class,
    ];
}
```

Optional: Add `aiRelations` property to define related models.

---

### 2. Add Descriptions to Migrations

Use the `description()` macro to describe your fields:

```php
Schema::create('regions', function (Blueprint $table) {
    $table->id();
    $table->string('name')->description('The name of the region');
    $table->text('backstory')->description('A detailed history of the region');
    $table->timestamps();
});
```

---

### 3. Generate Data with AI

Use the `AiGeneratorService`:

```php
use Koffielyder\LaravelAiModel\Services\AiGeneratorService;
use App\Models\Region;

$region = new Region();

$aiGenerator = app(AiGeneratorService::class);

$data = $aiGenerator->generate($region, 'Create a new region mainly inhabited by elves with a rich backstory.');
```

The `$data` array will contain the AI-generated structure ready for saving.

---

## 🔥 Future Roadmap

- AiSaverService to automatically persist generated data.
- Customizable validation rules before saving.
- Support for different LLM providers.
- More powerful relationship handling (nested saves).

---

## 🛡️ License

This package is open-sourced software licensed under the [MIT license](LICENSE).

---

# 🚀 Happy building your D&D worlds and beyond!
