---
title: The definitive Guide to Webhooks in Laravel
excerpt: Master webhooks in Laravel with this definitive guide. Learn setup, security, event handling, and more to build powerful real-time integrations.
date: 2024-12-20
image: "/images/articles/webhooks-in-laravel.png"
minRead: 7
sponsor:
  name: Hookdeck
  url: https://juststeveking.link/hook
  logo: https://github.com/hookdeck.png
author: steve
tags: ["laravel", "webhooks", "api-integrations", "security"]
---

These days, real-time communication isn't just expected — it's essential. Our users are done with waiting for full-page refreshes to get the latest info. Enter webhooks, the unsung heroes of the tech world. They're not widely talked about, but that's about to change. I'm on a mission to create the ultimate guide to webhooks in Laravel, making it the go-to resource for integrating webhooks in your next Laravel app.


## What Are Webhooks?

Let's jump into the vibrant world of webhooks! I like to imagine webhooks as the  digital messengers of the internet, powering application to transmit real-time data to other applications, keeping them updated whenever specific events or actions occur. Whether it's the a `user registered` event or a `payment processed`, webhooks keep you application in sync. They're the pivotal component that ensures other applications are in perfect sync with significant events. Dive into more details with [this awesome blog post](https://hookdeck.com/webhooks/guides/introduction-to-webhook-problems?ref=juststeveking).


## How Webhooks Work

What kind of magic conjures up a webhook? It's just like a standard HTTP request but with a bit more pizzazz! Typically, it's an HTTP POST request because webhooks need to send data. But here's the real standout feature: what makes webhooks cool is the inclusion of either the `X-Signature` or `X-Hub-Signature`. This addition ensures that the payload hasn't been meddled with, keeping your data absolutely pristine and reliable!

Boost your application's security and guard your data like never before with HTTPS! Don't let crafty attackers capture the sensitive information you share with clients. It's time to upgrade and unleash the power of HTTPS!

When that webhook hits your application, it's showtime! You've got to verify exactly where it's coming from — only process webhooks from sources you recognize and trust. Generally, your webhook will arrive with a signed key. Consider it like a secret handshake. You can decode the `X-Signature` or `X-Hub-Signature`, and it should perfectly match the payload sent. If there's any inconsistency between the content and the decrypted header — stop immediately. That webhook's been tampered with, so don't proceed further. Let's keep things secure!


## Implementing Webhooks in Laravel

Let's dive into how we can effectively integrate webhooks into a Laravel application. Ready? Let's go! 🚀


### Step 1: Define a Route

```php
Route::post(
    'ingress/github',
    AcceptGitHubWebhooks::class,
)->name('ingress:github');
```

We've set up a route for `ingress/github`, which is going to be our powerhouse for managing all the webhooks coming in from GitHub. You can configure this in your GitHub repository settings under "Webhooks." When you're adding this webhook, you have the freedom to choose the content-type header and the signature that'll optimize your request signing. With this setup, we're ready to accept requests, supported by a robust controller to manage all the action. For an added layer of awesomeness, include some middleware around this route to enhance the verification of the source and payload.


### Step 2: Add Middleware for Verification

Let's fire up an Artisan command to create some middleware that will enhance our request verification by thoroughly checking the source and content.

```shell
php artisan make:middleware VerifyGitHubWebhook
```

After creating this, we can update our route definition.

```php
Route::post(
    'ingress/github',
    AcceptGitHubWebhooks::class,
)->name('ingress:github')->middleware([
    VerifyGitHubWebhook::class,
]);
```

Let's proceed to the middleware to explore verifying this incoming webhook.

```php
final class VerifyGitHubWebhook
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->isValidSource($request->ip())) {
            return new JsonResponse(
	            data: ['message' => 'Invalid source IP.'],
	            status: 403,
			);
        }

        $signature = $request->header('X-Hub-Signature-256');
        $secret = config('services.github.webhook_secret');

        if ( ! $signature ||  ! $this->isValidSignature(
	        $request->getContent(),
	        $signature,
	        $secret,
		)) {
            return new JsonResponse(
	            data: ['message' => 'Invalid signature.'],
	            status: 403,
			);
        }

        return $next($request);
    }

    private function isValidSignature(
	    string $payload,
	    string $signature,
	    string $secret,
	): bool {
        return hash_equals(
            'sha256=' . hash_hmac('sha256', $payload, $secret),
            $signature
        );
    }

    private function isValidSource(string $ip): bool
    {
        $validIps = cache()->remember(
	        key: 'github:webhook_ips',
	        ttl: 3600,
	        callback: fn () => Http::get(
		        url: 'https://api.github.com/meta',
			)->json('hooks', []),
        );

        return in_array($ip, $validIps, true);
    }
}
```

Let's dive right into our middleware. First, we're snatching the source IP address from the request and matching it against some IP addresses we get from the GitHub API. But hey, why not make it more efficient by caching them? Just don't forget to refresh that cache every now and then. Next, grab the signature header and fetch the signing key from our app's config. Time to roll up our sleeves and verify that the hashed version of our request payload matches the given signature! Do this, and we've got solid proof that GitHub's got our back with the webhook data, and nobody else has been tampering with the data.


### Step 3: Dispatch a Job in the Controller

Let's dive into the controller code to unleash the potential of this webhook! Remember the battle cry: **Verify** - **Queue** - **Respond**! We've already verified the source, so what's next? It's time for our controller to dispatch a background job with the incoming payload.

```php
final class AcceptGitHubWebhooks
{
    public function __construct(private Dispatcher $bus) {}

    public function __invoke(Request $request): Response
    {
        defer(
	        callback: fn() => $this->bus->dispatch(
		        command: new HandleGitHubWebhook(
			        payload: $request->all(),
				),
			),
        );

        return new JsonResponse(
	        data: ['message' => 'Webhook accepted.'],
	        status: Response::HTTP_ACCEPTED,
		);
    }
}
```

Our controller's task is to dispatch the `HandleGitHubWebhook` job to the queue and promptly return a response, ensuring the sender is delighted to learn that the delivery was successful. At this stage, nothing should impede your webhook workflow: should you experience a sudden influx of webhooks, your queue is prepared to manage it — or you can deploy additional workers to handle your queued jobs. We have wrapped the dispatching here in a Laravel 11 helper, that will wait until the response has been sent before dispatching this job. What I'd call a micro-optimization but a pretty nifty one.


### Step 4: Process the Payload

Our controller is in excellent condition, but we are not stopping there. We're need to tackle the task of processing the incoming payload and setting things in motion. When a webhook is sent our way, it arrives as a JSON object filled with an `event` property. Next, we will align this event's name with our dynamic internal business logic. Let's dive into the `HandleGitHubWebhook` job and explore how we can make this happen.

```php
final class HandleGitHubWebhook implements ShouldQueue
{
    use Queueable;

    public function __construct(public array $payload) {}

    public function handle(GitHubService $service): void
    {
        $action = $this->payload['action'];

        match ($action) {
            'published' => $service->release(
	            payload: $this->payload,
			),
            'opened' => $service->opened(
	            payload: $this->payload,
			),
            default => Log::info(
	            message: "Unhandled webhook action: $action",
	            context: $this->payload,
			),
        };
    }
}
```

We're harnessing the power of the `ShouldQueue` interface, signaling Laravel to give this class the special treatment it deserves. Next, we inject the `Queueable` trait to enhance our queue behavior. And sure, if you ever feel like living on the edge, you can override the trait's methods, but honestly, after more than 8 years diving deep into Laravel, I've never needed to. The constructor accepts the payload we've dispatched from our controller. It's assigned as a public property because when this job is serialized onto the queue, it can't regenerate with private properties being unserialized. Finally, we have our handle method, the all-important function that springs into action to tackle this job on the queue. And guess what? You can leverage dependency injection on the handle method if there's anything specific you need to manage your business logic.

What I like to do next is write a service class that will contain all of the logic for handling the webhooks for each source. Let's work on a service under `app/Services/GitHubService`.

```php
final class GitHubService
{
    public function __construct(private DatabaseManager $database) {}

    public function release(array $payload): void
    {
        $this->database->transaction(function () use ($payload) {
            // Handle the Release Published logic here
        });
    }

    public function opened(array $payload): void
    {
        $this->database->transaction(function () use ($payload) {
            // Handle the PR opened logic here
        });
    }
}
```

Defining a specific method for each webhook action we aim to accept maintains everything orderly within the service class. We're now managing all desired GitHub webhooks, making it a breeze to expand as needed — without impacting our core business logic.

But there's a twist! How do we ensure we're catching all the webhooks we're supposed to? How do we make sure GitHub retries sending webhooks if the first attempt stumbles for some reason? We've got zero observability, unaware of what might be slipping through our fingers - or what our error ratio looks like. We're not alerted and remain in the dark about any failures - and that really amps up my developer spidey sense!


## Observability and Resilience

[Hookdeck](https://hookdeck.com/?ref=juststeveking) is your ultimate solution for managing webhooks with flair! Configure, monitor, and observe all your webhooks seamlessly in one powerful platform. No more juggling multiple sources - Hookdeck simplifies it in style. Need to reset the signature or retry a webhook? Do it effortlessly, with fantastic filters and super-smart routing built in. Say goodbye to messy logic and hello to determining which webhooks you want.

With Hookdeck, you no longer have to handle this in the background because it's your ultimate webhook queue! It sends and retries webhook deliveries, hitting multiple targets like your API and AWS S3. Hookdeck verifies each webhook's source and content for you, so all you need to do is sit back, accept that HTTP request, maybe grab a coffee, and let the magic happen!

<iframe class="aspect-video w-full rounded-lg" src="https://www.youtube.com/embed/L49OGRZ9mLw" title="YouTube video player" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>
