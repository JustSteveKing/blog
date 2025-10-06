---
title: Multi-tenancy Without the Hassle
excerpt: Learn how to build a multi-tenant application in Laravel using Sprout, avoiding the common pitfalls of manual tenant management.
date: 2025-07-01
image: /prezet/img/ogimages/multi-tenancy-without-the-hassle.webp
minRead: 8
sponsor: [Sevalla, "https://juststeveking.link/sevalla", "https://github.com/sevalla-hosting.png"]
author: steve
tags: [laravel, multi-tenancy, saas, sprout]
---

I've been building SaaS applications for over a decade, and let me tell you—implementing multi-tenancy has always been one of those features that makes you question your life choices. You know the drill: you start with grand plans of clean architecture, then end up with a maze of `tenant_id` columns scattered throughout your database like breadcrumbs in a fairy tale gone wrong.

But recently, I discovered Laravel Sprout, and it's changed how I approach multi-tenancy entirely. Today, I want to walk you through building a multi-tenant application that doesn't make you want to throw your laptop out the window.

## Why Multi-tenancy Matters (And Why It's Usually a Pain)
Think of multi-tenancy like managing an apartment complex. Each tenant needs their own private space, but they all share the same plumbing, electricity, and WiFi infrastructure. In software terms, this means multiple customers sharing your application while keeping their data completely isolated.

The traditional approach? Sprinkle `tenant_id` columns everywhere and pray you never forget a `WHERE` clause. I've seen production bugs where customers could see each other's data because someone missed a single line of code. It's terrifying.

## Enter Laravel Sprout: A Different Approach
Sprout takes a fundamentally different approach. Instead of forcing you to manually manage tenant isolation at every database query, it handles this automatically through Laravel's service container and middleware. It's like having a really smart assistant who remembers which tenant context you're in and applies the appropriate filters without you having to think about it.

Let me show you exactly how this works.

## Setting Up Our Multi-Tenant Application
### The Foundation
First, let's create our Laravel project. I'm using Laravel 11 for this example:

```shell
laravel new multi-tenant-saas
cd multi-tenant-saas
composer dev
```

Now, here's where the magic begins. Install Sprout:

```shell
composer require sprout/sprout
php artisan vendor:publish --provider="Sprout\SproutServiceProvider"
```

This creates a `config/multitenancy.php` file that's going to be our control center.

### Defining Our Tenant Model
Every SaaS needs an organization model. Let's create one that implements Sprout's tenant interface:

```php
php artisan make:model Workspace -mf
```

Here's where Sprout's elegance really shines. Your tenant model just needs to implement two things:

```php
namespace App\Models;

class Workspace extends Model implements Tenant
{
    use IsTenant;

    protected $fillable = [
        'name',
        'subdomain',
        'email',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    /**
     * This tells Sprout how to identify this tenant
     */
    public function getTenantIdentifierName(): string
    {
        return 'subdomain';
    }

    /**
     * Define the tenant identifier value
     */
    public function getTenantIdentifier(): string
    {
        return $this->subdomain;
    }

    /**
     * Relationship to users belonging to this organisation
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Relationship to contacts belonging to this organisation
     */
    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }
}
```

The migration for our workspaces table:

```php
return new class extends Migration{
    public function up(): void
    {
        Schema::create('workspaces', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('subdomain')->unique();
            $table->string('email');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workspaces');
    }
};
```

### Configuring Tenant Identification
Now we need to tell Sprout about our tenant model. Update `config/multitenancy.php`:

```php
return [
    'providers' => [
        'tenants' => [
            'driver' => 'eloquent',
            'model' => \App\Models\Workspace::class,
        ],
    ],
    
    'resolvers' => [
        'subdomain' => [
            'driver' => 'subdomain',
            'domain' => env('TENANTED_DOMAIN', 'app.test'),
            'pattern' => '.*',
        ],
    ],
];
```

And in your `.env` file:

```dotenv
TENANTED_DOMAIN=multi-tenant-saas.test
```

This configuration tells Sprout to look for tenants using subdomains on `multi-tenant-saas.test`. So `acme.multi-tenant-saas.test` would resolve to the workspace with subdomain acme.

## Making Models Tenant-Aware
Here's where I used to spend hours debugging missing `WHERE` clauses. With Sprout, you just mark your models as tenant-related:

```php
namespace App\Models;

class Contact extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'workspace_id',
    ];

    /**
     * This attribute tells Sprout this model belongs to a tenant
     */
    #[TenantRelation]
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }
}
```

Now here's the beautiful part: when you query `Contact::all()` within a tenant context, Sprout automatically adds the appropriate `WHERE workspace_id = ?` clause. No more forgotten tenant filters!

## Setting Up Tenant-Aware Authentication
Let's modify the User model to be tenant-aware:

```php
namespace App\Models;

class User extends Authenticatable
{
	use BelongsToTenant;
    use HasFactory;
    use Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'workspaceid',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    #[TenantRelation]
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }
}
```

## Routing: The Tenant-Aware Way
This is where Sprout really flexes its muscles. Instead of manually checking tenant context in every controller, you define tenant-specific routes:

```php
// Non-tenant routes (marketing site, etc.)
Route::get('/', function () {
    return view('welcome');
});

// Tenant-specific routes
Route::tenanted(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->middleware(['auth', 'verified'])
        ->name('dashboard');
    
    Route::middleware('auth')->group(function () {
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
        
        // Contact management routes
        Route::resource('contacts', ContactController::class);
    });
    
    require __DIR__.'/auth.php';
});
```

Now, any route defined within `Route::tenanted()` automatically operates within the correct tenant context.

## Building Tenant-Aware Controllers
Here's a controller that shows how simple tenant-aware operations become:

```php
namespace App\Http\Controllers;

class ContactController extends Controller
{
    /**
     * Display a listing of contacts for the current tenant
     */
    public function index(): View
    {
        // This automatically only returns contacts for the current tenant!
        $contacts = Contact::with('workspace')->paginate(15);
        
        return view('contacts.index', compact('contacts'));
    }

    /**
     * Store a newly created contact for the current tenant
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
        ]);

        // Sprout automatically sets the organisation_id!
        $contact = Contact::create($validated);

        return redirect()
            ->route('contacts.index')
            ->with('success', 'Contact created successfully!');
    }

    /**
     * Display the specified contact
     */
    public function show(Contact $contact): View
    {
        // Route model binding automatically scopes to current tenant
        return view('contacts.show', compact('contact'));
    }
}
```

The magic here is that `Contact::create($validated)` automatically includes the current tenant's `workspace_id`, and `Contact::paginate(15)` only returns contacts for the current tenant. No manual filtering required!

## Testing Your Multi-Tenant Setup
Let's create a seeder to test our implementation:

```php
namespace Database\Seeders;

class TenantSeeder extends Seeder
{
    public function run(): void
    {
        // Create some Workspaces
        $acme = Workspace::create([
            'name' => 'Acme Corporation',
            'subdomain' => 'acme',
            'email' => 'admin@acme.com',
            'active' => true,
        ]);

        $techcorp = Workspace::create([
            'name' => 'TechCorp Ltd',
            'subdomain' => 'techcorp',
            'email' => 'admin@techcorp.com',
            'active' => true,
        ]);

        // Create users for each workspace
        User::create([
            'name' => 'John Doe',
            'email' => 'john@acme.com',
            'password' => Hash::make('password'),
            'workspace_id' => $acme->id,
        ]);

        User::create([
            'name' => 'Jane Smith',
            'email' => 'jane@techcorp.com',
            'password' => Hash::make('password'),
            'workspace_id' => $techcorp->id,
        ]);

        // Create contacts for each workspace
        Contact::create([
            'name' => 'Alice Johnson',
            'email' => 'alice@client.com',
            'phone' => '+1234567890',
            'workspace_id' => $acme->id,
        ]);

        Contact::create([
            'name' => 'Bob Wilson',
            'email' => 'bob@customer.com',
            'phone' => '+0987654321',
            'workspace_id' => $techcorp->id,
        ]);
    }
}
```

Now you can test by visiting:
- `acme.multi-tenant-saas.test` (shows only Acme's data).
- `techcorp.multi-tenant-saas` (shows only TechCorp's data).
## What I Love About This Approach
After building several multi-tenant applications with Sprout, here's what keeps me coming back:
**No More Forgotten WHERE Clauses**: The automatic scoping means I can focus on business logic instead of remembering to filter by tenant_id everywhere.
**Flexible Identification**: Need to identify tenants by subdomain today and API key tomorrow? Just swap resolvers.
**Clean Controllers**: Look at that ContactController again—there's no tenant-specific code cluttering up the business logic.
**Laravel-Native**: It works with existing Laravel features like route model binding, Eloquent relationships, and middleware.

## The Gotchas (Because There Always Are Some)
No tool is perfect, and I've learned a few things the hard way:
- **Relationships Across Tenants**: If you need data that spans tenants, you'll need to be explicit about it.


## Looking Forward
Sprout is actively developed, and I'm excited about upcoming features like multi-database support and tenant-specific configuration. For now, the single-database approach has been rock-solid for my applications.

The real beauty of Sprout is that it gets out of your way. Instead of fighting with complex tenant isolation patterns, you can focus on building features your customers actually want. And isn't that why we became developers in the first place?

Give Sprout a try on your next multi-tenant project. Your future self will thank you when you're not debugging data leaks at 2 AM.

